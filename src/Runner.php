<?php
/*
 * This file is part of the hellonuzzle/bluetooth-fct package.
 *
 * @author: Alex Andreae
 * @license: GPL v3
 * @company: HelloNuzzle, Inc
 * @website: http://hellonuzzle.com
 *
 * (c) Alex Andreae <alzander@gmail.com> | <alex@hellonuzzle.com>
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

use Carbon\Carbon;
use Alzander\BluetoothFCT\Adb;
use Alzander\BluetoothFCT\MCPElements\Suite;
use Alzander\BluetoothFCT\MCPElements\Dfu;
use Alzander\BluetoothFCT\MCPElements\Scan;
use Alzander\BluetoothFCT\MCPElements\Target;

class Runner
{
    protected $testFile;
    protected $generateXMLOnly;
    protected $flysystem;
    protected $io;
    protected $testObject;

    protected $suites = array();
    protected $logFile;
    protected $logParameter;
    protected $androidSN;

    private $logHeaderWritten = false;

    public function __construct($testFile, IO $io, Filesystem $flysystem, $logFile = null, $androidSN = null)
    {
        $this->testFile = $testFile;
        $this->flysystem = $flysystem;
        $this->logParameter = $logFile;
        $this->androidSN = $androidSN;
        $this->io = $io;

        date_default_timezone_set('UTC');
        $this->adb = new Adb($io, $this->androidSN);

        $this->adb->checkAdbVersion();
        if (!$this->adb->devices()) {
            $this->io->writeLine("\nERROR: No Android phone connected!\n\n\n");
            exit;
        }
    }

    /**
     * CLI option to create the XML files.
     */
    public function generateXMLs()
    {
        $this->io->writeLine("Generating XML test scripts from " . $this->testFile);

        $this->flysystem->deleteDir('/xmls/' . $this->testFile);

        $testData = $this->flysystem->read('suites/' . $this->testFile . '.json');
        try {
            $this->testObject = json_decode($testData);
        } catch (\Exception $e) {
            $this->io->writeLine($this->testFile . " is not a properly formatted JSON file");
        }

        // Test file loaded, let's start creating the XMLs to run
        $xml = new Service();

        // Setup the targets first
        $devices = $this->setupDevices();

        // Then, setup each test to be run
        $suiteId = 1;
        foreach ($this->testObject->suites as $suite) {
            if (isset($suite->type) && $suite->type == "dfu")
            {
                $testSuite = new Suite($suite, $devices[$suite->target]);
                $this->suites[$suiteId] = $testSuite;

                $descriptor = new \stdClass();
                $descriptor->name = "OTA DUT";
//                $device = new BTDevice($descriptor, $btAddress, "DFU", null);

                $device = new BTDevice($descriptor, $devices[$suite->target]->address, "DFU", null);
                $params = new \stdClass();
                $params->package = $suite->package;

                $data = $xml->write('test-suite',
                    [
                        new Target(null, $device),
                        'test' => [
                            'attributes' => [
                                'id' => "dfu"
                            ],
                            'value' => [
                                new Dfu($params, $device),
                            ],
                        ],
                        new RunTest(['test' => "dfu"])
                    ]
                );

                $this->adb->uploadFirmware($params->package);
            }
            else {
                $device = isset($suite->target) ? $devices[$suite->target] : null;
                $testSuite = new Suite($suite, $device);
                $this->suites[$suiteId] = $testSuite;

                $data = $xml->write('test-suite', $testSuite);

            }
            $this->flysystem->write('/xmls/' . $this->testFile . '/suite_' . $suiteId . '.xml', $data);
            $suiteId++;
        }
    }

    /**
     * CLI option to create the XML files.
     */
    public function generateDfuXML($btAddress, $file)
    {
        $this->io->writeLine("Generating DFU XML test scripts for device at " . $btAddress . " to update to " . $file);

        $this->flysystem->deleteDir('/xmls/dfu');

/*        $testData = $this->flysystem->read('suites/' . $this->testFile . '.json');
        try {
            $this->testObject = json_decode($testData);
        } catch (\Exception $e) {
            $this->io->writeLine($this->testFile . " is not a properly formatted JSON file");
        }
*/
        // Test file loaded, let's start creating the XMLs to run
        $xml = new Service();

        // Setup the targets first
//        $devices = $this->setupDevices();
        $descriptor = new \stdClass();
        $descriptor->name = "OTA DUT";
        $device = new BTDevice($descriptor, $btAddress, "DFU", null);
        $params = new \stdClass();
        $params->package = $file;

        $data = $xml->write('test-suite',
            [
                new Target(null, $device),
                'test' => [
                    'attributes' => [
                        'id' => "dfutest"
                    ],
                    'value' => [
                        new Dfu($params, $device),
                    ],
                ],
                new RunTest(['test' => "dfutest"])
            ]
        );
        $this->flysystem->write('/xmls/dfu/dfu.xml', $data);
    }

    /* Setup the devices specified in the test file.
     * Could be set with a specific address or require a scan and some logic to find the best device.
     */
    private function setupDevices()
    {
        $devices = array();
        foreach ($this->testObject->targets as $target) {
            if ($this->flysystem->has('/devices/' . $target->device . '.json'))
                $descriptor = $this->flysystem->read('/devices/' . $target->device . '.json');
            else
                return false;

            // Check if we need to find a device to test instead of having a specific address
            if (isset($target->scan) && !isset($target->address)) {
                $xml = new Service();
                $scan = new Scan($target);
                $data = $xml->write('test-suite', $scan);

                $this->flysystem->write('/xmls/' . $this->testFile . '/suite_scanner.xml', $data);

                $testSuite = $this->testFile;
                $this->adb->setTestSuite($testSuite);

                $this->io->writeLine(" Scanning for DUT...");
                $results = $this->runSuite('suite_scanner');

                $target = $scan->findDevice($results);
                if (empty($target)) {
                    $this->io->writeLine("\nERROR: No DUTs found in range!\n\n\n");
                    exit;
                }
            }
            $descriptor = json_decode($descriptor);

            $rssi = isset($target->rssi) ? $target->rssi : "Unknown";
            $device = new BTDevice($descriptor, $target->address, $target->id, $rssi);
            $devices[$target->id] = $device;
        }
        return $devices;
    }

    /**
     * Setup the logFile for writing results. Use the passed in log parameters, if set.
     * If not, check for a log parameter in the test file to use.
     */
    private function setupLogFile()
    {
        $this->flysystem->createDir('results/logs');

        $append = false;
        if (!$this->logParameter && isset($this->testObject->log)) {
            $append = isset($this->testObject->log->append) ? $this->testObject->log->append : $append;
            $this->logParameter = $this->testObject->log->file;
        }

        if (!$this->logParameter)
            return;

        if (!$this->flysystem->has('results/logs/' . $this->logParameter . '.csv')) {
            $this->logFile = './fct/results/logs/' . $this->logParameter . '.csv';
            return;
        } else if ($append) {
            $this->logFile = './fct/results/logs/' . $this->logParameter . '.csv';
            $this->logHeaderWritten = true;
            return;
        }

        // Figure out if we should increment the log
        $found = false;
        $index = 1;
        while (!$found) {
            if (!$this->flysystem->has('results/logs/' . $this->logParameter . '-' . $index . '.csv')) {
                $this->logFile = './fct/results/logs/' . $this->logParameter . '-' . $index . '.csv';
                $found = true;
            }
            $index++;
        }
    }

    public function runTests()
    {
        $this->setupLogFile();

        //$this->io->writeLine("Running tests");

        $testSuite = $this->testFile;
        $this->adb->setTestSuite($testSuite);

        if (isset($this->testObject->loop)) {
            $loops = $this->testObject->loop->count;
            $exitOnFail = isset($this->testObject->loop->stopOnFail) ? $this->testObject->loop->stopOnFail : false;
            $looping = true;
            $sleep = isset($this->testObject->loop->sleep) ? $this->testObject->loop->sleep : 0;
        } else {
            $loops = 1;
            $exitOnFail = false;
            $looping = false;
            $sleep = 0;
        }

        $failCnt = 0;
        $startTime = new Carbon;

        for ($i = 1; $i <= $loops; $i++) {
            $connectivityError = false;
            if ($looping) {
                $failText = $failCnt > 0 ? " (" . $failCnt . " failures)" : "            ";
                $this->io->writeLine("\n<bu>       Loop " . $i . " / " . $loops . $failText . "</bu>");
            }

            foreach ($this->suites as $suiteId => $suite) {
                $this->io->writeLine("<bu>Starting Test Suite " . $suiteId . ": " . $suite->getName() . "</bu>");
                $results = $this->runSuite('suite_' . $suiteId);

                ob_start();
                try {
                    $suite->runValidations($results);
                } catch (\Exception $e) {
                    $failCnt++;

                    $output = ob_get_clean();
                    $this->io->write($output);
                    $this->io->write("<fail>CONNECTIVITY FAILURE: </fail>" . $e->getMessage() . "\n");

                    $values = array(new Carbon, $this->suites[1]->target->address, $e->getMessage());

                    $data = implode('","', $values);
                    $csv = '"' . $data . '"' . "\n";
                    $this->writeToLog($csv);

                    if ($exitOnFail) {
                        $this->io->writeLine("Testing stopped.\n\n");
                        return $e->getCode();
                    } else {
                        $connectivityError = true;
                        break;
                    }
                }
                $output = ob_get_clean();

                if (isset($this->testObject->verbose) && $this->testObject->verbose)
                    $this->io->write($output);
                else
                    $this->io->writeLine("\n");
            }

            if (!$connectivityError) {
                $allPass = $this->printTestSummary();
                if (!$allPass) {
                    $failCnt++;

                    if ($exitOnFail)
                        break;
                }
            }
            $this->io->writeLine("  Total Test Time: " . $startTime->diff(new Carbon)->format("%Hh%im:%ss"));

            if (!$connectivityError && $sleep > 0) { // Connectivity problems should just be immediately retried
                $this->io->writeLine("  ... Sleeping for " . $sleep . " seconds ...");
                sleep($sleep);
            }

        }
        return 0;
    }


    public function runDfuSuite($dfuFile)
    {
        $this->adb->setTestSuite('dfu');

        $this->adb->setTestName('dfu');

        $this->adb->removeOldResults();
        $this->adb->uploadTestFile();

        $this->adb->uploadFirmware($dfuFile);

        $this->adb->startTestService();
        if (!$this->adb->fetchResultsFile()) {
            $this->io->writeLine("\n<fail>CRITICAL ERROR: </fail>Results not returned. Something went wrong...\n\n");
            return 3;
        }

        $this->io->writeLine("");

        // Fetch the results and have the test validate them.
        $results = $this->flysystem->read("results/dfu_result.txt");

        return $results;
    }

    private function runSuite($suiteName)
    {
        $this->adb->setTestName($suiteName);

        $this->adb->removeOldResults();
        $this->adb->uploadTestFile();

        $this->adb->startTestService();
        if (!$this->adb->fetchResultsFile()) {
            $this->io->writeLine("\n<fail>CRITICAL ERROR: </fail>Results not returned. Something went wrong...\n\n");
            return 3;
        }

        $this->io->writeLine("");

        // Fetch the results and have the test validate them.
        $results = $this->flysystem->read("results/" . $suiteName . "_result.txt");

        return $results;
    }

    protected function printTestSummary()
    {
        $table = new Table();
        $table->setHeaderRow(array("Test ID", "Result", "Output"));

        $passCnt = 0;
        $totalCnt = 0;

        $suiteId = 1;

        $header = array('Date', "BT Address", "BT RSSI");
        $values = array(new Carbon, $this->suites[1]->target->address, $this->suites[1]->target->rssi); //$this->testObject->targets[0]->address);

        foreach ($this->suites as $suite) {
            $elementId = 1;
            foreach ($suite->subElements as $element) {
                $testId = 1;
                $hasValidators = false;
                foreach ($element->validators as $validator) {

                    $hasValidators = true;

                    $tag = $validator->getPassFail() ? "pass" : "fail";
                    $testName = $element->getName();
                    if ($validator->getName())
                        $testName .= ": " . $validator->getName();

                    $index = $suiteId . "." . $elementId;
                    if (count($element->validators) > 1)
                        $index .= "." . $testId;

                    $results =
                        [
                            "<" . $tag . ">" . $index . " - " . $testName . "</" . $tag . ">",
                            "<" . $tag . ">" . $validator->getResult() . "</" . $tag . ">",
                            $validator->getOutput(),
                        ];
                    $table->addRow($results);

                    if ($validator->getPassFail())
                        $passCnt++;

                    if ($this->logFile) {
                        array_push($header, $testName);
                        array_push($values, $validator->value);
                    }

                    $totalCnt++;

                    $testId++;
                }
                if ($hasValidators)
                    $elementId++;
            }
            $suiteId++;
        }

        // Generate CSV log data
        $csv = "";
        if (!$this->logHeaderWritten) {
            $data = implode('","', $header);
            $csv = '"' . $data . '"' . "\n";
//                $this->flysystem->put($this->logFile, $csv);
            $this->logHeaderWritten = true;
        }

        $data = implode('","', $values);
        $csv .= '"' . $data . '"' . "\n";
        $this->writeToLog($csv);
        // End log file creation

        $this->io->writeLine("<b>Test Summary:</b>\n");
        $this->io->writeLine("Total Tests: " . $totalCnt);
        $this->io->writeLine("Tests Passed: " . $passCnt);
        $this->io->writeLine("Tests Failed: " . ($totalCnt - $passCnt));

        $table->render($this->io);
        if ($passCnt == $totalCnt) {
            $this->io->writeLine("<pass>ALL TESTS PASSED!</pass>");
            return true;
        }

        return false;
    }

    private function writeToLog($data)
    {
        if ($this->logFile) {
            $fh = fopen($this->logFile, 'a');
            fwrite($fh, $data);
            fclose($fh);
        }
    }

}