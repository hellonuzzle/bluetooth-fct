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

namespace Alzander\BluetoothFCT\MCPElements\Characteristic;

use Alzander\BluetoothFCT\MCPElements\MCPElement;
use Sabre\Xml\Writer;

class Write extends MCPElement
{
    public function xmlSerialize(Writer $writer)
    {
        //  <write description="Write a name" type="WRITE_REQUEST" service-uuid="00001800-0000-1000-8000-00805f9b34fb" characteristic-uuid="00002A00-0000-1000-8000-00805f9b34fb" value-string="New name" />
        $attributes = [
            "description" => $this->params->name,
            "service-uuid" => $this->target->getService($this->params->service)->UUID,
            "characteristic-uuid" =>
                $this->target->getService($this->params->service)->getCharacteristic($this->params->characteristic)->UUID,
        ];
        if (isset($this->params->bytes) )
            $attributes['value'] = $this->params->bytes;
        else if (isset($this->params->text))
            $attributes['value-string'] = $this->params->text;


        $writer->write([
            "write" => [
                "attributes" => $attributes
            ]
        ]);
    }
}