<?php

namespace ClassifaiVendor\Psr\Http\Client;

use ClassifaiVendor\Psr\Http\Message\RequestInterface;
use ClassifaiVendor\Psr\Http\Message\ResponseInterface;

interface ClientInterface
{
    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \ClassifaiVendor\Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
