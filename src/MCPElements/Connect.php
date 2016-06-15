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

class Connect extends MCPElement
{
    protected $timeout;

    public function xmlSerialize(Writer $writer)
    {
        if (!$this->target->connected) {
            $writer->write([
                'connect' => [
                    'attributes' => ['timeout' => $this->target->timeouts['connect']]
                ],
                new Refresh(null),
                new Sleep(['timeout' => 5000]),
            ]);
            //$this->device->connected = true;
        }

        foreach ($this->subElements as $element)
            $writer->write([$element]);

    }

    protected function checkCriticalFailures($responseData)
    {
        $pattern = "Connect...FAIL";
        $pattern = "/^.*" . $pattern . ".*\$/m";

        if (preg_match_all($pattern, $responseData, $matches)) {
            throw new \Exception("Could not connect to device.", 3);
        }
    }

    protected function parseSubElements()
    {
        if ($this->params->shouldDiscover) {
            $discover = new DiscoverServices(null, $this->target);
            array_push($this->subElements, $discover);
        }
    }

}