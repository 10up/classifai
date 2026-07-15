<?php
namespace ClassifaiVendor\Aws\Arn\S3;

use ClassifaiVendor\Aws\Arn\ArnInterface;

/**
 * @internal
 */
interface BucketArnInterface extends ArnInterface
{
    public function getBucketName();
}
