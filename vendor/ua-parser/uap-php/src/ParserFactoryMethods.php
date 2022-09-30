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
use function dirname;
use const DIRECTORY_SEPARATOR;

trait ParserFactoryMethods
{
    /** @var string */
    public static $defaultFile;

    /**
     * Create parser instance
     *
     * Either pass a custom regexes.php file or leave the argument empty and use the default file.
     * @throws FileNotFoundException
     */
    public static function create(?string $file = null): self
    {
        return $file ? self::createCustom($file) : self::createDefault();
    }

    /** @throws FileNotFoundException */
    protected static function createDefault(): self
    {
        return self::createInstance(
            self::getDefaultFile(),
            [FileNotFoundException::class, 'defaultFileNotFound']
        );
    }

    /** @throws FileNotFoundException */
    protected static function createCustom(string $file): self
    {
        return self::createInstance(
            $file,
            [FileNotFoundException::class, 'customRegexFileNotFound']
        );
    }

    private static function createInstance(string $file, callable $exceptionFactory): self
    {
        if (!file_exists($file)) {
            throw $exceptionFactory($file);
        }

        static $map = [];
        if (!isset($map[$file])) {
            $map[$file] = include $file;
        }

        return new self($map[$file]);
    }

    protected static function getDefaultFile(): string
    {
        return self::$defaultFile ?: dirname(__DIR__).'/resources'.DIRECTORY_SEPARATOR.'regexes.php';
    }
}
