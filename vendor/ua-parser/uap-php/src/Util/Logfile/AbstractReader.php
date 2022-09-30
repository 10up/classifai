<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */

namespace UAParser\Util\Logfile;

use UAParser\Exception\ReaderException;

abstract class AbstractReader implements ReaderInterface
{
    /** @var ReaderInterface[] */
    private static $readers = [];

    public static function factory(string $line): ReaderInterface
    {
        foreach (static::getReaders() as $reader) {
            if ($reader->test($line)) {
                return $reader;
            }
        }

        throw ReaderException::readerNotFound($line);
    }

    private static function getReaders(): array
    {
        if (static::$readers) {
            return static::$readers;
        }

        static::$readers[] = new ApacheCommonLogFormatReader();

        return static::$readers;
    }

    public function test(string $line): bool
    {
        $matches = $this->match($line);

        return isset($matches['userAgentString']);
    }

    public function read(string $line): string
    {
        $matches = $this->match($line);

        if (!isset($matches['userAgentString'])) {
            throw ReaderException::userAgentParserError($line);
        }

        return $matches['userAgentString'];
    }

    protected function match(string $line): array
    {
        if (preg_match($this->getRegex(), $line, $matches)) {
            return $matches;
        }

        return [];
    }

    abstract protected function getRegex();
}
