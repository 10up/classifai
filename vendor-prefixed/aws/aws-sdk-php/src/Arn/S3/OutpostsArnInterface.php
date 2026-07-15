<?php
namespace ClassifaiVendor\Aws\Arn\S3;

use ClassifaiVendor\Aws\Arn\ArnInterface;

/**
 * @internal
 */
interface OutpostsArnInterface extends ArnInterface
{
    public function getOutpostId();
}
