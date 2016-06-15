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

use Webmozart\Console\IO\ConsoleIO;

class Question extends Validator {

    protected function runValidationFor($val)
    {
        $this->io = new ConsoleIO();
        $this->io->writeLine($this->params->question);
        $this->io->setInteractive(true);

        $validResponse = false;
        while (!$validResponse)
        {
            $this->io->write(">> ");
            $readValue = rtrim($this->io->readLine());
            foreach ($this->params->options as $option)
            {
                $len = strlen($option->value);
                //$this->io->writeLine("Option length = " . $len);
                //$this->io->writeLine("Read Value = " . $readValue);
                $input = substr($readValue, -$len);
                //$this->io->writeLine("Comparing " . $option->value . " with input " . $input);
                if (strtolower($input) == strtolower($option->value)) {
                    $this->resultName = $option->result;
                    $this->pass = $option->pass;
                    $this->output = $option->name;
                    $validResponse = true;
                    break;
                }
            }
        }
        $this->io->setInteractive(false);
    }

}