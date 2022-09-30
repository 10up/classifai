<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */
namespace UAParser\Exception;

final class FetcherException extends DomainException
{
    public static function httpError(string $resource, string $error): self
    {
        return new self(
            sprintf('Could not fetch HTTP resource "%s": %s', $resource, $error)
        );
    }
}
