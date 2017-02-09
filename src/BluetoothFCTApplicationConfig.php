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
namespace Alzander\BluetoothFCT;

use Webmozart\Console\Config\DefaultApplicationConfig;
use Alzander\BluetoothFCT\Commands\RunCommandHandler;
use Alzander\BluetoothFCT\Commands\DfuCommandHandler;
use Alzander\BluetoothFCT\Commands\GenerateXMLCommandHandler;
use Webmozart\Console\Api\Args\Format\Argument;
use Webmozart\Console\Api\Args\Format\Option;
use Webmozart\Console\Api\Formatter\Style;

use League\Flysystem\Filesystem;

class BluetoothFCTApplicationConfig extends DefaultApplicationConfig
{
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('bluetooth-fct')
            ->setVersion('0.1')
        ;

        $this->addStyle(Style::tag('pass')->fgGreen());
        $this->addStyle(Style::tag('fail')->fgRed());
        $this->addStyle(Style::tag('warning')->fgYellow());

        $this->beginCommand("run")
            ->setDescription("Runs the test file specified. Generates the XML files as well.")
            ->setHandler(new RunCommandHandler($this->filesystem) )
            ->addArgument('test', Argument::REQUIRED, 'The test file to load from the fct/suites directory. Omit .json')
            ->addOption('log', null, Option::OPTIONAL_VALUE, 'Filename to log data to. ', null, 'log')
            ->addOption('android', null, Option::OPTIONAL_VALUE, 'Android serial number to target. ', null, 'android')
        ->end();

        $this->beginCommand("generateXML")
            ->setDescription("Generates the XML test file for MCP from the input JSON file")
            ->setHandler(new GenerateXMLCommandHandler($this->filesystem) )
            ->addArgument('test', Argument::REQUIRED, 'The test file to load from the fct/suites directory. Omit .json')
        ->end();

        $this->beginCommand("dfu")
            ->setDescription("Run the specified test file to do an over-the-air update of a board.")
            ->setHandler(new DfuCommandHandler($this->filesystem) )
            ->addArgument('address', Argument::REQUIRED, 'The BT address of the device to program.')
            ->addArgument('file', Argument::REQUIRED, 'The zip file from the firmware directory to program. Ex. test-dfu.zip')
            ->end();
    }
}
