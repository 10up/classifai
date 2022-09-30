<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */
namespace UAParser\Exception;

use Exception;

final class FileNotFoundException extends Exception
{
    public static function fileNotFound(string $file): self
    {
        return new self(sprintf('File "%s" does not exist', $file));
    }

    public static function customRegexFileNotFound(string $file): self
    {
        return new self(
            sprintf(
                'ua-parser cannot find the custom regexes file you supplied ("%s"). Please make sure you have the correct path.',
                $file
            )
        );
    }

    public static function defaultFileNotFound(string $file): self
    {
        return new self(
            sprintf(
                'Please download the "%s" file before using ua-parser by running "php bin/uaparser ua-parser:update"',
                $file
            )
        );
    }
}
