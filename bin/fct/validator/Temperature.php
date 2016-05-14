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
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FCT\Validator;

use Alzander\BluetoothFCT\Validator\Range;

class Temperature extends Range {

    protected function runValidationFor($val)
    {
//        $this->io->writeLine("Raw temp: " . $val);
        $val = hexdec($val);
        $val = $val - 40; // Now we're in Celsius
        $this->io->writeLine("Processed temp: " . $val . "C / " . (($val * 1.8) + 32) . "F");

        $min = $this->test->validation->min;
        $max = $this->test->validation->max;

        if ($val >= $min && $val <= $max)
            return true;
        else {
            $this->failureDescription = $val . "C is not within " . $min . "C to " . $max . "C";
            return false;
        }
    }

}