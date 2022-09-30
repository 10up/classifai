<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */
namespace UAParser\Util;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use UAParser\Exception\FileNotFoundException;
use function array_map;

class Converter
{
    /** @var string */
    private $destination;

    /** @var CodeGenerator */
    private $codeGenerator;

    /** @var Filesystem */
    private $fs;

    public function __construct(string $destination, CodeGenerator $codeGenerator = null, Filesystem $fs = null)
    {
        $this->destination = $destination;
        $this->codeGenerator = $codeGenerator ?: new CodeGenerator();
        $this->fs = $fs ?: new Filesystem();
    }

    /** @throws FileNotFoundException */
    public function convertFile(string $yamlFile, bool $backupBeforeOverride = true): void
    {
        if (!$this->fs->exists($yamlFile)) {
            throw FileNotFoundException::fileNotFound($yamlFile);
        }

        $this->doConvert(Yaml::parse(file_get_contents($yamlFile)), $backupBeforeOverride);
    }

    public function convertString(string $yamlString, bool $backupBeforeOverride = true): void
    {
        $this->doConvert(Yaml::parse($yamlString), $backupBeforeOverride);
    }

    protected function doConvert(array $regexes, bool $backupBeforeOverride = true): void
    {
        $regexes = $this->sanitizeRegexes($regexes);
        $code = $this->codeGenerator->generateArray($regexes);
        $code = "<?php\nreturn ".$code."\n";

        $regexesFile = $this->destination.'/regexes.php';
        if ($backupBeforeOverride && $this->fs->exists($regexesFile)) {

            $currentHash = hash('sha512', file_get_contents($regexesFile));
            $futureHash = hash('sha512', $code);

            if ($futureHash === $currentHash) {
                return;
            }

            $backupFile = $this->destination . '/regexes-' . $currentHash . '.php';
            $this->fs->copy($regexesFile, $backupFile);
        }

        $this->fs->dumpFile($regexesFile, $code);
    }

    private function sanitizeRegexes(array $regexes): array
    {
        foreach ($regexes as $groupName => $group) {
            $regexes[$groupName] = array_map([$this, 'sanitizeRegex'], $group);
        }

        return $regexes;
    }

    private function sanitizeRegex(array $regex): array
    {
        $regex['regex'] = '@' . str_replace('@', '\@', $regex['regex']) . '@';

        if (isset($regex['regex_flag'])) {
            $regex['regex'] .= $regex['regex_flag'];
        }

        unset($regex['regex_flag']);

        return $regex;
    }
}
