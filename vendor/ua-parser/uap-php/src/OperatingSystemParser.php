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

use UAParser\Result\OperatingSystem;

class OperatingSystemParser extends AbstractParser
{
    use ParserFactoryMethods;

    /** Attempts to see if the user agent matches an operating system regex from regexes.php */
    public function parseOperatingSystem(string $userAgent): OperatingSystem
    {
        $os = new OperatingSystem();

        [$regex, $matches] = self::tryMatch($this->regexes['os_parsers'], $userAgent);

        if ($matches) {
            $os->family = self::multiReplace($regex, 'os_replacement', $matches[1], $matches) ?? $os->family;
            $os->major = self::multiReplace($regex, 'os_v1_replacement', $matches[2], $matches);
            $os->minor = self::multiReplace($regex, 'os_v2_replacement', $matches[3], $matches);
            $os->patch = self::multiReplace($regex, 'os_v3_replacement', $matches[4], $matches);
            $os->patchMinor = self::multiReplace($regex, 'os_v4_replacement', $matches[5], $matches);
        }

        return $os;
    }
}
