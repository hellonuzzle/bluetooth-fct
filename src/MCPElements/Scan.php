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

use Sabre\Xml\Writer;
use Webmozart\Console\IO\ConsoleIO;

class Scan extends MCPElement
{

    public function xmlSerialize(Writer $writer)
    {
        // Create a dummy target. Necessary or nRF Connect will throw an error
        $target = new \stdClass();
        $target->id = "blah";
        $target->name = "blah";
        $target->address = "AA:BB:CC:DD:EE:FF";

        $timeout = isset ($this->params->scan->timeout) ? $this->params->scan->timeout : 20000;
        //        <scan description="List nearby devices" rssi="-60" timeout="10000"/>
        $writer->write([
            new Target(null, $target),
            'test' => [
                'attributes' => [
                    'id' => "bt_scanner",
                    'description' => "Auto scan for devices"
                ],
                'value' => [
                    'scan' => [
                        'attributes' => [
                            'description' => 'BT Scanner START',
                            'rssi' => -100,
                            'timeout' => $timeout,
                        ]
                    ]
                ],
            ],
            new RunTest(['test' => "bt_scanner"])
        ]);

    }

    public function findDevice($results)
    {
        $io = new ConsoleIO();

        $lines = explode("\n", $results);
        $started = false;

        $devices = array();
        foreach ($lines as $line) {
            if (!$started) {
                if (strpos($line, "BT Scanner START"))
                    $started = true;

                continue;
            }

            if (strpos($line, "OK")) {
                break;
            }

            $parts = explode(" ", $line);

            if (isset($devices[$parts[1]])) {
                $device = $devices[$parts[1]];
                $device->rssi = max($parts[2], $device->rssi);

            } else {
                $device = new \stdClass();
                $device->address = $parts[1];
                $device->rssi = $parts[2];
            }


            // Always use the last advertising packet found
            $device->advertising = $parts[3];

            $devices[$device->address] = $device;
        }


        // Filter devices to find best match
        $foundDevice = null;
        $io->writeLine("<b>Bluetooth devices found:</b>");
        $fullNameFound = false;
        foreach ($devices as $device) {
            $advNameFound = false;
            $index = 0;

            while (!$advNameFound && (strlen($device->advertising) > $index)) {
                $length = hexdec($device->advertising[$index] . $device->advertising[$index + 1]);
                $type = hexdec($device->advertising[$index + 2] . $device->advertising[$index + 3]);
                if ($type == 8 || $type == 9) {
                    $advNameFound = true;

                    $addressStart = $index + 4;
                    $devName = hex2bin(substr($device->advertising, $addressStart, ($length - 1) * 2));
                    if (strlen($devName) != (($length - 1))) // Name was truncated..?
                    {
                        $devLength = strlen($devName);
                        $truncated = true;
                    } else {
                        $devLength = ($length - 1);
                        $truncated = false;
                    }

                    $io->writeLine($device->address . " " . $devName . " " . $device->rssi . "dBm");

                    if (isset($this->params->scan->device_name)) {
                        if ($devName == substr($this->params->scan->device_name, 0, $devLength)) {
                            if ($truncated && $fullNameFound)
                                break; // Don't replace a full name with a truncated name

                            // Found a possible device. Is this the best match?
                            if (!$truncated) // Only use truncated names if necessary
                                $fullNameFound = true;

                            if (empty($foundDevice) || // No device found, set this one
                                (!$truncated && $foundDevice->truncatedName) || // Used to be truncated, but a non-truncated found
                                $device->rssi > $foundDevice->rssi) { // This device has a better strength
                                $foundDevice = $device;
                                $foundDevice->id = $this->params->id;
                                $foundDevice->name = $devName;
                                $foundDevice->rssi = $device->rssi;
                                $foundDevice->truncatedName = $truncated;
                            }
                        }
                    }
                } else {
                    $index += ($length * 2) + 2;
                }
            }
        }

        $io->writeLine("\n");
        if ($foundDevice) {
            $io->writeLine("<b>DUT selected:</b>");
            $io->writeLine($foundDevice->address . " " . $foundDevice->name . " " . $foundDevice->rssi . "dBm");
        }
        $io->writeLine("\n");

        return $foundDevice;
    }
}