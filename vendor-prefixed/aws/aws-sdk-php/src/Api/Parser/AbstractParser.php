<?php
namespace ClassifaiVendor\Aws\Api\Parser;

use ClassifaiVendor\Aws\Api\Service;
use ClassifaiVendor\Aws\Api\StructureShape;
use ClassifaiVendor\Aws\CommandInterface;
use ClassifaiVendor\Aws\ResultInterface;
use ClassifaiVendor\Psr\Http\Message\ResponseInterface;
use ClassifaiVendor\Psr\Http\Message\StreamInterface;

/**
 * @internal
 */
abstract class AbstractParser
{
    /** @var \ClassifaiVendor\Aws\Api\Service Representation of the service API*/
    protected $api;

    /** @var callable */
    protected $parser;

    /**
     * @param Service $api Service description.
     */
    public function __construct(Service $api)
    {
        $this->api = $api;
    }

    /**
     * @param CommandInterface  $command  Command that was executed.
     * @param ResponseInterface $response Response that was received.
     *
     * @return ResultInterface
     */
    abstract public function __invoke(
        CommandInterface $command,
        ResponseInterface $response
    );

    abstract public function parseMemberFromStream(
        StreamInterface $stream,
        StructureShape $member,
        $response
    );
}
