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


class Validator {
    protected $params;
    protected $resultString;
    protected $io;

    protected $pass;
    protected $output;

    public function __construct($params)
    {
        $this->params = $params;
        $this->pass = false;
    }

    /**
     * Validates the return value with the given parameters
     *
     * @return bool
     */
    public function validate($val)
    {
        if (isset($this->params->element))
        {
            $data = str_split($val);
            $start = floor($this->params->element->offset / 4);
            $length = ceil($this->params->element->length / 4);
            $pieces = array_splice($data, $start, $length);
            $val = implode($pieces);
            echo $val;
        }

        if (isset($this->params->grep))
        {
            if (!preg_match($this->params->grep, $val))
            {
                $this->pass = false;
                $this->value = $val;
                $this->output = $val . " :: not proper format : " . $this->params->grep;
                return;
            }
        }
        $this->runValidationFor($val);
    }

    protected function runValidationFor($val)
    {

    }

    public function getName()
    {
        return isset($this->params->name) ? $this->params->name : null;
    }

    public function getResult()
    {
        return $this->pass ? "Pass" : "Fail";
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function getPassFail()
    {
        return $this->pass;
    }
}