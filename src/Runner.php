<?php
/*
 * This file is part of the hellonuzzle/bluetooth-fct package.
 *
 * @author: Alex Andreae
 * @license: GPL v3
 * @company: HelloNuzzle, Inc
 * @website: http://hellonuzzle.com
 *
 * (c) Alex Andreae <alzander@gmail.com> | <alex@hellonuzzle.com
 *
 * Bluetooth-fct is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Foobar is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with bluetooth-fct.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Alzander\BluetoothFCT;

use Alzander\BluetoothFCT\MCPElements\RunTest;
use League\Flysystem\Filesystem;
use Webmozart\Console\Api\IO\IO;

use Sabre\Xml\Service;
use Alzander\BluetoothFCT\DUT\BTDevice;

use Sabre\Xml;
use Alzander\BluetoothFCT\MCPElements\Target;

use Alzander\BluetoothFCT\Adb;

class Runner
{
    protected $testFile;
    protected $generateXMLOnly;
    protected $flysystem;
    protected $io;
    protected $testObject;

    protected $tests = array();

    public function __construct($testFile, IO $io, Filesystem $flysystem)
    {
        $this->testFile = $testFile;
        $this->flysystem = $flysystem;
        $this->io = $io;

        $this->adb = new Adb($io);
    }

    public function generateXMLs()
    {
        $this->io->writeLine("Generating XML test scripts from " . $this->testFile);

        $this->flysystem->deleteDir('/xmls/' . $this->testFile);

        $this->processTestFile();
    }

    private function processTestFile()
    {
        $testData = $this->flysystem->read('suites/' . $this->testFile . '.json');
        try {
            $this->testObject = json_decode($testData);
        } catch (\Exception $e) {
            $this->io->writeLine($this->testFile . " is not a properly formatted JSON file");
        }

        $this->generateTests();
    }

    private function generateTests()
    {
        $xml = new Service();

        $devices = array();
        // Setup the targets first

        foreach ($this->testObject->targets as $target) {
            if ($this->flysystem->has('/devices/' . $target->device . '.json'))
                $descriptor = $this->flysystem->read('/devices/' . $target->device . '.json');
            else
                return false;

            $descriptor = json_decode($descriptor);

            $device = new BTDevice($descriptor, $target->address, $target->id);
            $devices[$target->id] = $device;
        }

        // Then, setup each test to be run
        $index = 1;
        foreach ($this->testObject->tests as $testData) {
            $testParts = explode(".", $testData->command);
            $testName = "Alzander\\BluetoothFCT\\Tester\\" . implode("\\", $testParts);
            $test = new $testName($devices, $testData, $this->io, $index);
            array_push($this->tests, $test);
            $index++;
        }

        foreach ($this->tests as $test)
        {
            // Each test needs to reconnect. Later, we'll try to chain tests together that can run without stopping
            foreach ($devices as $device)
                $device->connected = false;

            $data = $xml->write('test-suite', [
                function ($writer) use ($devices) {
                    foreach ($devices as $device) {
                        $target = new Target($device);
                        $writer->write($target);
                    }
                },
                $test,
                new RunTest("test_" . $test->id)
            ]);

            $this->flysystem->write('/xmls/' . $this->testFile . '/test_' . $test->id . '.xml', $data);
        }
/*
        $data = $xml->write('test-suite', [
            function ($writer) use ($devices) {
                foreach ($devices as $device) {
                    $target = new Target($device);
                    $writer->write($target);
                }
            },
            function ($writer) {
                foreach ($this->tests as $test) {
                    $writer->write($test);
                }
            },

            function ($writer) {
                foreach ($this->tests as $test)
                    $writer->write(
                        new RunTest("test_" . $test->id)
                    );
            }
        ]);

        $this->flysystem->write('/xmls/' . $this->testFile . '/test_1.xml', $data);
*/

    }

    public function runTests()
    {
        $this->io->writeLine("Running tests");
        $this->adb->checkAdbVersion();

        //$process = new Process('adb');
        //$process->run();
        $testSuite = "read_temp";
        $this->adb->setTestSuite($testSuite);

        $this->adb->devices();

        foreach ($this->tests as $test) {
            $this->io->writeLine("<bu>Starting test " . $test->id . "</bu>");
            $testName = "test_" . $test->id;

            $this->adb->setTestName($testName);

            $this->adb->removeOldResults();
            $this->adb->uploadTestFile();
            $this->adb->startTestService();
            $this->adb->fetchResultsFile();

            $this->processResultFile($test);
        }
    }

    protected function processResultFile($test)
    {
        $results = $this->flysystem->read("results/test_" . $test->id . "_result.txt");

        $pattern = "AUTOTEST_TEST_" . $test->id . "_FILLER";
        $pattern = "/^.*" . $pattern . ".*\$/m";
        // search, and store all matching occurences in $matches
        if(preg_match_all($pattern, $results, $matches)){
            // Pull the actual return value out of the string that looks like
            // - Value of the characteristic '14' is not equal to 'AUTOTEST_TEST_1_FILLER'
            $valStart = strpos($matches[0][0], '\'');
            $valEnd = strpos($matches[0][0], '\'', $valStart + 1);

            $value = substr($matches[0][0], $valStart+1, $valEnd - $valStart - 1);

            $customValidatorClass = "FCT\\Validator\\" . $test->testData->validation->type;

            $definedValidatorClass = "Alzander\\BluetoothFCT\\Validator\\" . $test->testData->validation->type;

            if (class_exists($customValidatorClass))
                $validator = new $customValidatorClass($test->testData, $this->io);
            else
                $validator = new $definedValidatorClass($test->testData, $this->io);
            $validator->validate($value);
        }
        else{
            echo "No matches found";
        }

    }
}