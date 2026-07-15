<?php

namespace ClassifaiVendor\Aws\Endpoint\UseFipsEndpoint;

use ClassifaiVendor\Aws;
use ClassifaiVendor\Aws\Endpoint\UseFipsEndpoint\Exception\ConfigurationException;
class Configuration implements ConfigurationInterface
{
    private $useFipsEndpoint;
    public function __construct($useFipsEndpoint)
    {
        $this->useFipsEndpoint = \ClassifaiVendor\Aws\boolean_value($useFipsEndpoint);
        if (is_null($this->useFipsEndpoint)) {
            throw new ConfigurationException("'use_fips_endpoint' config option" . " must be a boolean value.");
        }
    }
    /**
     * {@inheritdoc}
     */
    public function isUseFipsEndpoint()
    {
        return $this->useFipsEndpoint;
    }
    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return ['use_fips_endpoint' => $this->isUseFipsEndpoint()];
    }
}