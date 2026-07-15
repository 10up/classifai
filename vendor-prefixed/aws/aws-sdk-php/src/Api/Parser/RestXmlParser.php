<?php
namespace ClassifaiVendor\Aws\Api\Parser;

use ClassifaiVendor\Aws\Api\StructureShape;
use ClassifaiVendor\Aws\Api\Service;
use ClassifaiVendor\Psr\Http\Message\ResponseInterface;
use ClassifaiVendor\Psr\Http\Message\StreamInterface;

/**
 * @internal Implements REST-XML parsing (e.g., S3, CloudFront, etc...)
 */
class RestXmlParser extends AbstractRestParser
{
    use PayloadParserTrait;

    /**
     * @param Service   $api    Service description
     * @param XmlParser $parser XML body parser
     */
    public function __construct(Service $api, ?XmlParser $parser = null)
    {
        parent::__construct($api);
        $this->parser = $parser ?: new XmlParser();
    }

    protected function payload(
        ResponseInterface $response,
        StructureShape $member,
        array &$result
    ) {
        $result += $this->parseMemberFromStream($response->getBody(), $member, $response);
    }

    public function parseMemberFromStream(
        StreamInterface $stream,
        StructureShape $member,
        $response
    ) {
        $xml = $this->parseXml($stream, $response);
        return $this->parser->parse($member, $xml);
    }
}
