<?php

namespace ClassifaiVendor\Aws\ClientSideMonitoring;

use ClassifaiVendor\Aws\CommandInterface;
use ClassifaiVendor\Aws\Exception\AwsException;
use ClassifaiVendor\Aws\ResultInterface;
use ClassifaiVendor\GuzzleHttp\Psr7\Request;
use ClassifaiVendor\Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
interface MonitoringMiddlewareInterface
{

    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param RequestInterface $request
     * @return array
     */
    public static function getRequestData(RequestInterface $request);


    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param ResultInterface|AwsException|\Exception $klass
     * @return array
     */
    public static function getResponseData($klass);

    public function __invoke(CommandInterface $cmd, RequestInterface $request);
}