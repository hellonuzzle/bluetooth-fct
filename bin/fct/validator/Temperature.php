<?php

namespace FCT\Validator;

use Alzander\BluetoothFCT\Validator\Range;

class Temperature extends Range
{

    protected function runValidationFor($val)
    {
        $this->io->writeLine("Raw Input: " . $val);
//        $this->io->writeLine("Hex Input: " . dechex($val));

        $data = str_split($val);
        $temp = implode(array_splice($data, 0, 2));

        $this->io->writeLine("Hex temp: " . $temp);

        $temp = hexdec($temp);
        $temp = $temp - 40; // Now we're in Celsius
        $this->io->writeLine("Processed temp: " . $temp . "C / " . (($temp * 1.8) + 32) . "F");

        $min = $this->params->min;
        $max = $this->params->max;

        if ($temp >= $min && $temp <= $max) {
            $this->output = "Temperature: " . $temp . "C passes. Within range of " . $min . "C to " . $max . "C";
            $this->resultName = "Pass";
            $this->pass = true;
        } else {
            $this->output = "Temperature: " . $temp . "C is not within " . $min . "C to " . $max . "C";
            $this->resultName = "Fail";
            $this->pass = false;
        }

        // Get GPS

        $this->processLocation($data);

    }


    private function processLocation($pa)
    {
        $this->io->writeLine("Processing Location: " . implode($pa));
        $origPacket = array_splice($pa, 0, 12);
        $origPacket = array_reverse($origPacket);

        $packet = array();
        for ($j = 0; $j < count($origPacket); $j = $j + 2) {
            $packet[$j] = $origPacket[$j + 1];
            $packet[$j + 1] = $origPacket[$j];
        }

        $lat = implode(array_splice($packet, -5));
        $lat = hexdec($lat);
        $lat = $lat / 10000;
        $latDegrees = floor($lat);
        $latDDMMMM = $lat - $latDegrees;
        $latDDMMMM = $latDDMMMM * 10000;
        $latDDMMMM = $latDDMMMM * 60;
        $latMinutes = $latDDMMMM / 10000;


        $packet = hexdec(implode($packet));
        $latNS = $packet & 0x1;
        if (!$latNS)
            $latDegrees = $latDegrees * -1;

        $packet = $packet >> 1;
        $lng = $packet & 0x1FFFFF;

        $lng = $lng / 10000;
        $lngDegrees = floor($lng);
        $lngDDMMMM = $lng - $lngDegrees;
        $lngDDMMMM = $lngDDMMMM * 10000;
        $lngDDMMMM = $lngDDMMMM * 60;
        $lngMinutes = $lngDDMMMM / 10000;
        // Remove $latDir

        $packet = $packet >> 21;
        $lngEW = $packet & 0x1;

        if (!$lngEW)
            $lngDegrees = $lngDegrees * -1;

        $packet = $packet >> 1;
        $relTime = $packet;

        $finalLong = $lngDegrees - ($lngMinutes / 60);
        $finalLat = $latDegrees + ($latMinutes / 60);
        $this->io->writeLine("Longitude: " . $finalLong);
        $this->io->writeLine("Latitude: " . $finalLat);

        $this->output .= "\nGPS: " . $finalLat . ",  " . $finalLong;
    }
}