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

namespace Alzander\BluetoothFCT\MCPElements;

use Sabre\Xml\XmlSerializable;

abstract class MCPElement implements XmlSerializable
{
    public $validators = array();
    public $subElements = array();

    protected $params;
    protected $target;
    protected $io;
    protected $result;

    public function __construct($params, $target = null)
    {
        $this->params = (object) $params;
        $this->target = $target;

        $this->parseSubElements();
        if (isset($this->params->validation)) {
            if (is_array($this->params->validation)) {
                foreach ($this->params->validation as $validator) {
                    $this->addValidator($validator);
                }
            } else
                $this->addValidator($this->params->validation);
        }
        $this->testId = uniqid();
    }

    protected function parseSubElements()
    {

    }

    private function addValidator($validator)
    {
        $customValidatorClass = "FCT\\Validator\\" . $validator->type;

        if (class_exists($customValidatorClass))
            $this->validators[] = new $customValidatorClass($validator);
        else {
            $definedValidatorClass = "Alzander\\BluetoothFCT\\Validator\\" . $validator->type;
            $this->validators[] = new $definedValidatorClass($validator);
        }
    }

    public function getName()
    {
        return isset($this->params->name) ? $this->params->name : "";
    }

    public function getValidationResults()
    {
        $allValidators = $this->validators;
        foreach ($this->subElements as $element) {
            $subValidators = $element->getValidationResults();
            if (!empty($subValidators))
                array_push($allValidators, $subValidators[0]);
        }

        return $allValidators;
    }

    public function getTestFillerValue()
    {
        return "AUTOTEST_TEST_" . $this->testId . "_FILLER";
    }

    public function runValidations($responseData)
    {
        $this->checkCriticalFailures($responseData);

        $this->runValidation($responseData);
        foreach ($this->subElements as $element) {
            $element->runValidations($responseData);
        }
    }

    protected function checkCriticalFailures($responseData)
    {

    }

    private function runValidation($responseData)
    {
        $pattern = $this->getTestFillerValue();
        $value = null;

        if ($pattern !== null) {
            $pattern = "/^.*" . $pattern . ".*\$/m";
            // search, and store all matching occurences in $matches

            if (preg_match_all($pattern, $responseData, $matches)) {
                // Pull the actual return value out of the string that looks like
                // - Value of the characteristic '14' is not equal to 'AUTOTEST_TEST_1_FILLER'
                $valStart = strpos($matches[0][0], '\'');
                $valEnd = strpos($matches[0][0], '\'', $valStart + 1);

                $value = substr($matches[0][0], $valStart + 1, $valEnd - $valStart - 1);

//                $this->io->writeLine("Running validation on response: " . $value);

            } else {
//                $this->io->writeLine("<fail>Results file did not have any values for test.</fail>");
            }
        }

        if (count($this->validators) > 0)
            echo "Running validation for : " . $this->getName() . "\n";

        foreach ($this->validators as $validator) {
            $validator->validate($value);
            echo "\n";
        }
    }
}