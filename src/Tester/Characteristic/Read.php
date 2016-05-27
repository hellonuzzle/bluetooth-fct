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

namespace Alzander\BluetoothFCT\Tester\Characteristic;

use Alzander\BluetoothFCT\Tester\Test;
use Sabre\Xml\Writer;

class Read extends Test
{
    public function getTestFillerValue()
    {
        return "AUTOTEST_TEST_" . $this->id . "_FILLER";
    }

    public function runTest(Writer $writer)
    {
        $writer->write([
            "read" => [
                "attributes" => [
                    "description" => "Read temp sensor",
                    "service-uuid" => $this->devices[$this->testData->target]->getService($this->testData->service)->UUID,
                    "characteristic-uuid" =>
                        $this->devices[$this->testData->target]->getService($this->testData->service)->getCharacteristic($this->testData->characteristic)->UUID
                ],
                "value" => [
                    "assert-value" => [
                        "attributes" => [
                            "description" => "AUTOMATED_TEST_" . $this->id . ": ",
                            "expected" => "SUCCESS_WARNING_ON_FAIL",
                            "value" => $this->getTestFillerValue()
                        ]
                    ]
                ]
            ]
        ]);
    }
}