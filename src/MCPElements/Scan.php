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
                            'timeout' => 20000
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

            }
            else {
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
        foreach ($devices as $device)
        {
            $devName = "";
            for ($i = 18; $i < 18+12; $i += 2) {
                $devName .= chr(hexdec($device->advertising[$i] . $device->advertising[$i + 1]));
            }
            $io->writeLine($device->address . " " . $devName . " " . $device->rssi . "dBm");

            if (isset($this->params->scan->device_name)) {
                if ($devName == $this->params->scan->device_name) {
                    // Found a possible device. Is this the best match?
                    if (empty($foundDevice) || $device->rssi > $foundDevice->rssi) {
                        $foundDevice = $device;
                        $foundDevice->id = $this->params->id;
                        $foundDevice->name = $devName;
                        $foundDevice->rssi = $device->rssi;
                    }
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