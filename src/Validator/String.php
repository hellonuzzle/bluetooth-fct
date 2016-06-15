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

namespace Alzander\BluetoothFCT\Validator;


class String extends Validator
{

    protected function runValidationFor($val)
    {

        $string = '';
        for ($i = 0; $i < strlen($val) - 1; $i += 2) {
            $string .= chr(hexdec($val[$i] . $val[$i + 1]));
        }
        echo "Value: " . $string . "\n";

        if (isset($this->params->expected) && $this->params->expected != $string) {
            $this->resultName = "Fail";
            $this->pass = false;
            $this->output = $string . " :: not equal to : " . $this->params->expected;
        } else {
            $this->resultName = "Pass";
            $this->pass = true;
            $this->output = $string;
        }
    }
}