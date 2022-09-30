<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */
namespace UAParser\Util;

use function array_keys;
use function array_map;
use function array_reduce;
use function array_values;
use function is_scalar;
use function is_string;
use function str_repeat;
use function var_export;

class CodeGenerator
{
    public function generateArray(array $array): string
    {
        $createReducer = static function (bool $multi = true, int $indentation = 1) use (&$createReducer) {
            return static function(string $source, array $element) use ($indentation, $multi, $createReducer) {
                [$key, $value] = $element;

                if (is_scalar($value)) {
                    if ($multi) {
                        $source .= self::indent($indentation);
                    }
                    $source .= self::generateKey($key) .  var_export($value, true);
                    if ($multi) {
                        $source .= ",\n";
                    }

                    return $source;
                }

                if ($multi) {
                    $source .= self::indent($indentation);
                }
                $source .= self::generateKey($key) . "[";
                $nextMulti = count($value) > 1;
                if ($nextMulti) {
                    $source .= "\n";
                }
                $source .= array_reduce(self::toPairs($value), $createReducer($nextMulti, $indentation + 1), '');
                if ($nextMulti) {
                    $source .= self::indent($indentation);
                }
                $source .= "]";
                if ($multi) {
                    $source .= ",\n";
                }

                return $source;
            };
        };

        $multi = count($array) > 1;
        return array_reduce(self::toPairs($array), $createReducer($multi, 1), "[" . ($multi ? "\n" : '')) . '];';
    }

    private static function generateKey($key): string
    {
        return is_string($key) ? var_export($key, true) . ' => ' : '';
    }

    private static function indent(int $indentation): string
    {
        return str_repeat(' ', $indentation * 4);
    }

    private static function toPairs(array $map): array
    {
        return array_map(
            static function ($key, $value): array {
                return [$key, $value];
            },
            array_keys($map),
            array_values($map)
        );
    }
}
