<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2013 Dave Olsen, http://dmolsen.com
 * Copyright (c) 2013-2014 Lars Strojny, http://usrportage.de
 *
 * Released under the MIT license
 */

namespace UAParser;

use UAParser\Result\Device;

class DeviceParser extends AbstractParser
{
    use ParserFactoryMethods;

    /** Attempts to see if the user agent matches a device regex from regexes.php */
    public function parseDevice(string $userAgent): Device
    {
        $device = new Device();

        [$regex, $matches] = self::tryMatch($this->regexes['device_parsers'], $userAgent);

        if ($matches) {
            $device->family = self::multiReplace($regex, 'device_replacement', $matches[1], $matches) ?? $device->family;
            $device->brand = self::multiReplace($regex, 'brand_replacement', null, $matches);
            $deviceModelDefault = $matches[1] !== 'Other' ? $matches[1] : null;
            $device->model = self::multiReplace($regex, 'model_replacement', $deviceModelDefault, $matches);
        }

        return $device;
    }
}
