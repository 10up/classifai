<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2013 Dave Olsen, http://dmolsen.com
 * Copyright (c) 2013-2014 Lars Strojny, http://usrportage.de
 *
 * Released under the MIT license
 */
namespace UAParser\Result;

abstract class AbstractVersionedSoftware extends AbstractSoftware
{
    abstract public function toVersion(): string;

    public function toString(): string
    {
        return implode(' ', array_filter([$this->family, $this->toVersion()]));
    }

    protected function formatVersion(?string ...$args): string
    {
        return implode('.', array_filter($args, 'is_numeric'));
    }
}
