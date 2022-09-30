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

class Device extends AbstractSoftware
{
    /** @var string|null */
    public $brand;

    /** @var string|null */
    public $model;
}
