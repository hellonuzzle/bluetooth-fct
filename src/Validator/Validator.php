<?php
/*
 * This file is part of the hellonuzzle/bluetooth-fct package.
 *
 * @author: Alex Andreae
 * @license: GPL v3
 * @company: HelloNuzzle, Inc
 * @website: http://hellonuzzle.com
 *
 * (c) Alex Andreae <alzander@gmail.com> | <alex@hellonuzzle.com
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


use Webmozart\Console\Api\IO\IO;

abstract class Validator {
    protected $params;
    protected $resultString;
    protected $io;

    protected $pass;
    protected $resultName;
    protected $output;

    public function __construct($test, IO $io)
    {
        $this->test = $test;
        $this->io = $io;
        $this->pass = false;
    }

    /**
     * Validates the return value with the given parameters
     *
     * @return bool
     */
    public function validate($val)
    {
        $this->runValidationFor($val);

        if ($this->pass)
        {
            $this->io->writeLine("<pass>" . $this->resultName . "</pass> - " . $this->test->description . ". Value = " . $val);
        }
        else
        {
            $this->io->writeLine("<fail>" . $this->resultName . "</fail> - " . $this->test->description . " " . $this->output);
        }

        // First, par
    }

    abstract protected function runValidationFor($val);

    public function getResult()
    {
        return $this->resultName;
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