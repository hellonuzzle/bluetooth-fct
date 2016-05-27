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

use Alzander\BluetoothFCT\DUT\BTDevice;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Connect implements XmlSerializable
{

    protected $timeout;

    public function __construct(BTDevice $device, $discover, $bond)
    {
        $this->device = $device;
        $this->shouldDiscover = $discover;
        $this->shouldBond = $bond;
    }

    public function xmlSerialize(Writer $writer)
    {
        if (!$this->device->connected) {
            $writer->write([
                'connect' => [
                    'attributes' => ['timeout' => $this->device->timeouts['connect']]
                ],
                new Refresh(),
                new Sleep(5000),
            ]);
            $this->device->connected = true;
        }

        if ($this->shouldDiscover)
            $writer->write([
                new DiscoverServices($this->device->id, $this->device->timeouts['discover'])
            ]);

    }
}