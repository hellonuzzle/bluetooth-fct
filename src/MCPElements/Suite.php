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

class Suite extends MCPElement
{
    public function getName()
    {
        return $this->params->name;
    }

    public function xmlSerialize(Writer $writer)
    {
        $writer->write([
            new Target(null, $this->target),
            'test' => [
                'attributes' => [
                    'target' => $this->target->id,
                    'id' => "auto_test"
                ],
                'value' => [
                    function () use ($writer) {
                        foreach ($this->subElements as $element)
                            $element->xmlSerialize($writer);
                    },
                    new Disconnect($this->target->id),
                ],
            ],
            new RunTest(['test' => "auto_test"])
        ]);

    }

    protected function parseSubElements()
    {
        $connect = new Connect(['shouldDiscover' => true], $this->target);
        array_push($this->subElements, $connect);

        if (isset($this->params->loop)) {
            $loops = $this->params->loop->count;
            $sleep = isset($this->params->loop->sleep) ? $this->params->loop->sleep : 0;
        } else {
            $loops = 1;
            $sleep = 0;
        }

        for ($i = 0; $i < $loops; $i++) {
            foreach ($this->params->tests as $test) {
                $testParts = explode(".", $test->command);
                $testName = "Alzander\\BluetoothFCT\\MCPElements\\" . implode("\\", $testParts);
                $test = new $testName($test, $this->target);
                array_push($this->subElements, $test);
            }
            if ($sleep > 0 && $i !== ($loops - 1) )
                array_push($this->subElements, new Sleep(['timeout'=> $sleep * 1000]));
        }
    }

}