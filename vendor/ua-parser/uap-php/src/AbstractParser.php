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

use UAParser\Exception\FileNotFoundException;

abstract class AbstractParser
{
    /** @var array */
    protected $regexes = [];

    public function __construct(array $regexes)
    {
        $this->regexes = $regexes;
    }

    protected static function tryMatch(array $regexes, string $userAgent): array
    {
        foreach ($regexes as $regex) {
            if (preg_match($regex['regex'], $userAgent, $matches)) {

                $defaults = [
                    1 => 'Other',
                    2 => null,
                    3 => null,
                    4 => null,
                    5 => null,
                ];

                return [$regex, $matches + $defaults];
            }
        }

        return [null, null];
    }

    protected static function multiReplace(array $regex, string $key, ?string $default, array $matches): ?string
    {
        if (!isset($regex[$key])) {
            return self::emptyStringToNull($default);
        }

        $replacement = preg_replace_callback(
            '|\$(?P<key>\d)|',
            static function ($m) use ($matches) {
                return $matches[$m['key']] ?? '';
            },
            $regex[$key]
        );

        return self::emptyStringToNull($replacement);
    }

    private static function emptyStringToNull(?string $string): ?string
    {
        $string = trim($string ?? '');

        return $string === '' ? null : $string;
    }
}
