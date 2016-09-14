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

namespace Alzander\BluetoothFCT\DUT;

//use Alzander\BluetoothFCT\DUT\BTService;

class BTDevice {

    public $id;
    public $name;
    public $services;
    public $address;
    public $rssi;

    public $connected = false;

    public $timeouts;

    public function __construct($descriptor, $address, $id, $rssi)
    {
        $this->services = array();
        $this->address = $address;
        $this->id = $id;
        $this->rssi = $rssi;

        $this->parse($descriptor);
    }

    private function parse($descriptor)
    {
        $this->name = $descriptor->name;
        //$this->CCID = $descriptor->CCCD_UUID;

        if (isset($descriptor->timeouts))
        {
            $this->timeouts['connect'] = $descriptor->timeouts->connect ?: 4000;
            $this->timeouts['discover'] = $descriptor->timeouts->discover_services ?: 10000;
            $this->timeouts['bond'] = $descriptor->timeouts->bond ?: 10000;
        }

        if (isset($descriptor->services) && is_array($descriptor->services)) {
            foreach ($descriptor->services as $service) {
                $this->services[$service->id] = new BTService($service);
            }
        }
    }

    public function getService($name)
    {
        return $this->services[$name];
    }
}