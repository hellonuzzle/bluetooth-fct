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

class Dfu extends MCPElement
{
    public function getName()
    {
        return $this->params->name;
    }

    public function xmlSerialize(Writer $writer)
    {
        $writer->write([
                'dfu' => [
                    'attributes' => [
                        'description' => "DFU Download",
                        'file' => '/AlzanderBT/Firmware/' . $this->params->package,
                        //'file' => '/Nordic Semiconductor/Board/pca10028/ble_app_hrm_dfu_s130_v2_0_0_sdk_11_0_all_in_one.zip',
                        'target' => $this->target->id
                    ]
                ]
            ]
        );
    }

    protected function checkCriticalFailures($responseData)
    {
        $pattern = " DFU Download...FAIL";
        $pattern = "/^.*" . $pattern . ".*\$/m";

        if (preg_match_all($pattern, $responseData, $matches)) {
            throw new \Exception("DFU Download Failed", 3);
        }
    }


}