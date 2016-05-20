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

use Webmozart\Console\UI\Component\Table;

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
    }

    public function runTests()
    {
        $this->io->writeLine("Running tests");
        $this->adb->checkAdbVersion();

        //$process = new Process('adb');
        //$process->run();
        $testSuite = $this->testFile;
        $this->adb->setTestSuite($testSuite);

        if (!$this->adb->devices())
        {
            $this->io->writeLine("\nERROR: Android devices not attached!\n\n\n");
            return 4;
        }

        foreach ($this->tests as $test) {
            $this->io->writeLine("\n<bu>Starting test " . $test->id . "</bu>");
            $testName = "test_" . $test->id;

            $this->adb->setTestName($testName);

            $this->adb->removeOldResults();
            $this->adb->uploadTestFile();
            $this->adb->startTestService();
            if (!$this->adb->fetchResultsFile())
            {
                $this->io->writeLine("\nCRITICAL ERROR: Results not returned. Something went wrong...\n\n");
                return 3;
            }

            // Fetch the results and have the test validate them.
            $results = $this->flysystem->read("results/test_" . $test->id . "_result.txt");

            $test->runValidation($results);

            sleep(3);
        }

        $this->printTestSummary();
        return 0;
    }

    protected function printTestSummary()
    {
        $table = new Table();
        $table->setHeaderRow(array("", "Name", "Result", "Output"));

        $passCnt = 0;
        $totalCnt = 0;

        foreach ($this->tests as $test)
        {
            $results =
                [
                    $test->id,
                    $test->getName(),
                    $test->getValidationResult(),
                    $test->getValidationOutput()
                ];
            $table->addRow($results);

            if ($test->getValidationPassFail())
                $passCnt++;

            $totalCnt++;
        }

        $this->io->writeLine("\n<b>Test Summary:</b>\n");
        $this->io->writeLine("Total Tests: " . $totalCnt);
        $this->io->writeLine("Tests Passed: " . $passCnt);
        $this->io->writeLine("Tests Failed: " . ($totalCnt - $passCnt));

        $table->render($this->io);
        if ($passCnt == $totalCnt)
            $this->io->writeLine("<pass>ALL TESTS PASSED!</pass>");
    }

}