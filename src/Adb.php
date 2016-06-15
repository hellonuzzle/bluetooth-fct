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


use Webmozart\Console\Api\IO\IO;

class Adb
{
    private $testSuite;
    private $testName;

    public function __construct(IO $io)
    {
        $this->io = $io;
    }

    public function checkAdbVersion()
    {
        return true;
    }

    public function setTestSuite($name)
    {
        $this->testSuite = $name;
    }

    public function setTestName($name)
    {
        $this->testName = $name;
    }

    public function devices()
    {
        $output = shell_exec('adb devices');

        $deviceCnt = substr_count($output, "device");
        $deviceCnt--; // Remove the first line of "List of devices attached
        $this->io->writeLine($output);
        $this->io->writeLine("");
        if ($deviceCnt == 1)
            return true;
        else
            return false;
    }

    public function removeOldResults()
    {
//        $this->io->writeLine("Removing old results file...");
        $output = shell_exec('adb shell rm "/sdcard/AlzanderBT/Test/' . $this->testName . '_result.txt" > nul 2>&1');
    }

    public function uploadTestFile()
    {
        $uploadFile = './fct/xmls/' . $this->testSuite . '/' . $this->testName . '.xml';
        $uploadLocation = '/sdcard/AlzanderBT/Test/' . $this->testName . '.xml';
//        $this->io->writeLine("Uploading new test file...");
//        $this->io->writeLine($uploadFile . ' to ' . $uploadLocation);
        $output = shell_exec('adb push ' . $uploadFile . ' "' . $uploadLocation . '" > nul 2>&1');
    }

    public function startTestService()
    {
//        $this->io->writeLine("Starting test service...");
        $output = shell_exec('adb shell am startservice -a no.nordicsemi.android.action.START_TEST ' .
            '-e no.nordicsemi.android.test.extra.EXTRA_FILE_PATH "/sdcard/AlzanderBT/Test/' . $this->testName . '.xml" > nul 2>&1');
    }

    public function fetchResultsFile()
    {
        $this->io->writeLine("Waiting for test results...");
        $keepGoing = true;
        $i = 0;
        while ($keepGoing) {
            $resultFile = $this->testName . '_result.txt';
            exec('adb pull "/sdcard/AlzanderBT/Test/' . $resultFile . '" "fct/results/' . $resultFile . '" > nul 2>&1', $output, $returnVal);
            if ($returnVal == 1) {
                sleep(1);
                $i++;
                if ($i > 30) // We've waited too long, something is wrong
                {
                    return false;
                }
            }
            else
                return true;
        }
    }
}