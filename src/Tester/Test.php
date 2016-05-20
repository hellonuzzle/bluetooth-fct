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

namespace Alzander\BluetoothFCT\Tester;

use Alzander\BluetoothFCT\MCPElements\DiscoverServices;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;


use Alzander\BluetoothFCT\MCPElements\Connect;
use Webmozart\Console\Api\IO\IO;


abstract class Test implements XmlSerializable
{
    public $id;
    protected $testData;
    protected $devices;
    protected $io;
    protected $result;

    protected $validators;

    public function __construct(array $devices, $testData, IO $io, $index)
    {
        $this->testData = $testData;
        $this->devices = $devices;
        $this->io = $io;
        $this->id = $index;

        if (isset($this->testData->validation)) {
            if (is_array($this->testData->validation)) {
                foreach ($this->testData->validation as $validator) {
                    $this->addValidator($validator);
                }
            }
        }
    }

    abstract function runTest(Writer $writer);

    private function addValidator($validator)
    {
        $customValidatorClass = "FCT\\Validator\\" . $validator->type;

        if (class_exists($customValidatorClass))
            $this->validators[] = new $customValidatorClass($validator, $this->io);
        else {
            $definedValidatorClass = "Alzander\\BluetoothFCT\\Validator\\" . $validator->type;
            $this->validators[] = new $definedValidatorClass($validator, $this->io);
        }
    }

    public function getName()
    {
        return $this->testData->description;
    }

    public function getValidationResults()
    {
        $return = array();
        foreach ($this->validators as $validator) {
            $result = new \stdClass();
            $result->result = $validator->getResult();
            $result->output = $validator->getOutput();
            $result->pass = $validator->getPassFail();

            $return[] = $result;
        }
        return $return;
    }

    public function xmlSerialize(Writer $writer)
    {
        $writer->write([
            'test' => [
                'attributes' => [
                    'target' => $this->testData->target,
                    'id' => "test_" . $this->id
                ],
                'value' => [
                    new Connect($this->devices[$this->testData->target], true, false),
                    function ($writer) {
                        $this->runTest($writer);
                    }
                ],
            ]
        ]);

    }

    protected function parseTestFile()
    {
        if ($this->flysystem->has('suites/' . $this->testFile))
            $testData = $this->flysystem->read('suites/' . $this->testFile);
        else {
            $adapter = new Local(__DIR__ . '/standard');
            $defaultTestDir = new Filesystem($adapter);
            if ($defaultTestDir->has($this->testFile))
                $testData = $defaultTestDir->read($this->testFile);
        }

        try {
            $tests = json_decode($testData);
        } catch (\Exception $e) {
            throwException($e);
        }

        $this->tests = $tests;
    }

    public function runValidations($results)
    {

        $pattern = "AUTOTEST_TEST_" . $this->id . "_FILLER";
        $pattern = "/^.*" . $pattern . ".*\$/m";
        // search, and store all matching occurences in $matches
        if (preg_match_all($pattern, $results, $matches)) {
            // Pull the actual return value out of the string that looks like
            // - Value of the characteristic '14' is not equal to 'AUTOTEST_TEST_1_FILLER'
            $valStart = strpos($matches[0][0], '\'');
            $valEnd = strpos($matches[0][0], '\'', $valStart + 1);

            $value = substr($matches[0][0], $valStart + 1, $valEnd - $valStart - 1);

            $this->io->writeLine("Running validation on response: " . $value);

            foreach ($this->validators as $validator) {
                $validator->validate($value);
            }
        } else {
            $this->io->writeLine("<fail>ERROR! Results file did not have any values for test!</fail>");
        }
    }
}