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

use Alzander\BluetoothFCT\DUT\BTCharacteristic;

class BTService{

    protected $characteristics;

    public function __construct($descriptor)
    {
        $this->characteristics = array();
        $this->parse($descriptor);
    }

    private function parse($descriptor)
    {
        $this->id = $descriptor->id;
        $this->name = $descriptor->name;
        $this->UUID = $descriptor->UUID;

        if (isset($descriptor->characteristics) && is_array($descriptor->characteristics)) {
            foreach ($descriptor->characteristics as $characteristic) {
                $this->characteristics[$characteristic->id] = new BTCharacteristic($characteristic);
            }
        }

    }

    public function getCharacteristic($name)
    {
        return $this->characteristics[$name];
    }


}