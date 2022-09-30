<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */
namespace UAParser\Exception;

final class ReaderException extends DomainException
{
    public static function userAgentParserError(string $line): self
    {
        return new self(sprintf('Cannot extract user agent string from line "%s"', $line));
    }

    public static function readerNotFound(string $line): self
    {
        return new self(sprintf('Cannot find reader that can handle "%s"', $line));
    }
}
