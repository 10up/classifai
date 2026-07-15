<?php
namespace ClassifaiVendor\Aws\S3;

use ClassifaiVendor\Aws\CacheInterface;
use ClassifaiVendor\Aws\CommandInterface;
use ClassifaiVendor\Aws\LruArrayCache;
use ClassifaiVendor\Aws\MultiRegionClient as BaseClient;
use ClassifaiVendor\Aws\Exception\AwsException;
use ClassifaiVendor\Aws\S3\Exception\PermanentRedirectException;
use ClassifaiVendor\GuzzleHttp\Promise;

/**
 * **Amazon Simple Storage Service** multi-region client.
 *
 * @method \ClassifaiVendor\Aws\Result abortMultipartUpload(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise abortMultipartUploadAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result completeMultipartUpload(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise completeMultipartUploadAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result copyObject(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise copyObjectAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result createBucket(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise createBucketAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result createBucketMetadataTableConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise createBucketMetadataTableConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result createMultipartUpload(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise createMultipartUploadAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result createSession(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise createSessionAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucket(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketAnalyticsConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketAnalyticsConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketCors(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketCorsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketEncryption(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketEncryptionAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketIntelligentTieringConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketIntelligentTieringConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketInventoryConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketInventoryConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketLifecycle(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketLifecycleAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketMetadataTableConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketMetadataTableConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketMetricsConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketMetricsConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketOwnershipControls(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketOwnershipControlsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketPolicy(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketPolicyAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketReplication(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketReplicationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketTagging(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketTaggingAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteBucketWebsite(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteBucketWebsiteAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteObject(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteObjectAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteObjectTagging(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteObjectTaggingAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deleteObjects(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deleteObjectsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result deletePublicAccessBlock(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise deletePublicAccessBlockAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketAccelerateConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketAccelerateConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketAcl(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketAclAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketAnalyticsConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketAnalyticsConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketCors(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketCorsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketEncryption(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketEncryptionAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketIntelligentTieringConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketIntelligentTieringConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketInventoryConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketInventoryConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketLifecycle(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketLifecycleAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketLifecycleConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketLifecycleConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketLocation(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketLocationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketLogging(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketLoggingAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketMetadataTableConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketMetadataTableConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketMetricsConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketMetricsConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketNotification(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketNotificationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketNotificationConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketNotificationConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketOwnershipControls(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketOwnershipControlsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketPolicy(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketPolicyAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketPolicyStatus(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketPolicyStatusAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketReplication(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketReplicationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketRequestPayment(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketRequestPaymentAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketTagging(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketTaggingAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketVersioning(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketVersioningAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getBucketWebsite(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getBucketWebsiteAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getObject(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getObjectAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getObjectAcl(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getObjectAclAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getObjectAttributes(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getObjectAttributesAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getObjectLegalHold(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getObjectLegalHoldAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getObjectLockConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getObjectLockConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getObjectRetention(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getObjectRetentionAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getObjectTagging(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getObjectTaggingAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getObjectTorrent(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getObjectTorrentAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result getPublicAccessBlock(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getPublicAccessBlockAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result headBucket(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise headBucketAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result headObject(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise headObjectAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listBucketAnalyticsConfigurations(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listBucketAnalyticsConfigurationsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listBucketIntelligentTieringConfigurations(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listBucketIntelligentTieringConfigurationsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listBucketInventoryConfigurations(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listBucketInventoryConfigurationsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listBucketMetricsConfigurations(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listBucketMetricsConfigurationsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listBuckets(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listBucketsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listDirectoryBuckets(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listDirectoryBucketsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listMultipartUploads(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listMultipartUploadsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listObjectVersions(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listObjectVersionsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listObjects(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listObjectsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listObjectsV2(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listObjectsV2Async(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listParts(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listPartsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketAccelerateConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketAccelerateConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketAcl(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketAclAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketAnalyticsConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketAnalyticsConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketCors(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketCorsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketEncryption(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketEncryptionAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketIntelligentTieringConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketIntelligentTieringConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketInventoryConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketInventoryConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketLifecycle(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketLifecycleAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketLifecycleConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketLifecycleConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketLogging(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketLoggingAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketMetricsConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketMetricsConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketNotification(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketNotificationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketNotificationConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketNotificationConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketOwnershipControls(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketOwnershipControlsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketPolicy(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketPolicyAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketReplication(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketReplicationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketRequestPayment(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketRequestPaymentAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketTagging(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketTaggingAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketVersioning(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketVersioningAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putBucketWebsite(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putBucketWebsiteAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putObject(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putObjectAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putObjectAcl(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putObjectAclAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putObjectLegalHold(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putObjectLegalHoldAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putObjectLockConfiguration(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putObjectLockConfigurationAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putObjectRetention(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putObjectRetentionAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putObjectTagging(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putObjectTaggingAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result putPublicAccessBlock(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise putPublicAccessBlockAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result restoreObject(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise restoreObjectAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result selectObjectContent(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise selectObjectContentAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result uploadPart(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise uploadPartAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result uploadPartCopy(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise uploadPartCopyAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result writeGetObjectResponse(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise writeGetObjectResponseAsync(array $args = [])
 */
class S3MultiRegionClient extends BaseClient implements S3ClientInterface
{
    use S3ClientTrait;

    /** @var CacheInterface */
    private $cache;

    public static function getArguments()
    {
        $args = parent::getArguments();
        $regionDef = $args['region'] + ['default' => function (array &$args) {
            $availableRegions = array_keys($args['partition']['regions']);
            return end($availableRegions);
        }];
        unset($args['region']);

        return $args + [
            'bucket_region_cache' => [
                'type' => 'config',
                'valid' => [CacheInterface::class],
                'doc' => 'Cache of regions in which given buckets are located.',
                'default' => function () { return new LruArrayCache; },
            ],
            'region' => $regionDef,
        ];
    }

    public function __construct(array $args)
    {
        parent::__construct($args);
        $this->cache = $this->getConfig('bucket_region_cache');

        $this->getHandlerList()->prependInit(
            $this->determineRegionMiddleware(),
            'determine_region'
        );
    }

    private function determineRegionMiddleware()
    {
        return function (callable $handler) {
            return function (CommandInterface $command) use ($handler) {
                $cacheKey = $this->getCacheKey($command['Bucket']);
                if (
                    empty($command['@region']) &&
                    $region = $this->cache->get($cacheKey)
                ) {
                    $command['@region'] = $region;
                }

                return Promise\Coroutine::of(function () use (
                    $handler,
                    $command,
                    $cacheKey
                ) {
                    try {
                        yield $handler($command);
                    } catch (PermanentRedirectException $e) {
                        if (empty($command['Bucket'])) {
                            throw $e;
                        }
                        $result = $e->getResult();
                        $region = null;
                        if (isset($result['@metadata']['headers']['x-amz-bucket-region'])) {
                            $region = $result['@metadata']['headers']['x-amz-bucket-region'];
                            $this->cache->set($cacheKey, $region);
                        } else {
                            $region = (yield $this->determineBucketRegionAsync(
                                $command['Bucket']
                            ));
                        }

                        $command['@region'] = $region;
                        yield $handler($command);
                    } catch (AwsException $e) {
                        if ($e->getAwsErrorCode() === 'AuthorizationHeaderMalformed') {
                            $region = $this->determineBucketRegionFromExceptionBody(
                                $e->getResponse()
                            );
                            if (!empty($region)) {
                                $this->cache->set($cacheKey, $region);

                                $command['@region'] = $region;
                                yield $handler($command);
                            } else {
                                throw $e;
                            }
                        } else {
                            throw $e;
                        }
                    }
                });
            };
        };
    }

    public function createPresignedRequest(CommandInterface $command, $expires, array $options = [])
    {
        if (empty($command['Bucket'])) {
            throw new \InvalidArgumentException('The S3\\MultiRegionClient'
                . ' cannot create presigned requests for commands without a'
                . ' specified bucket.');
        }

        /** @var S3ClientInterface $client */
        $client = $this->getClientFromPool(
            $this->determineBucketRegion($command['Bucket'])
        );
        return $client->createPresignedRequest(
            $client->getCommand($command->getName(), $command->toArray()),
            $expires,
            $options
        );
    }

    public function getObjectUrl($bucket, $key)
    {
        /** @var S3Client $regionalClient */
        $regionalClient = $this->getClientFromPool(
            $this->determineBucketRegion($bucket)
        );

        return $regionalClient->getObjectUrl($bucket, $key);
    }

    public function determineBucketRegionAsync($bucketName)
    {
        $cacheKey = $this->getCacheKey($bucketName);
        if ($cached = $this->cache->get($cacheKey)) {
            return Promise\Create::promiseFor($cached);
        }

        /** @var S3ClientInterface $regionalClient */
        $regionalClient = $this->getClientFromPool();
        return $regionalClient->determineBucketRegionAsync($bucketName)
            ->then(
                function ($region) use ($cacheKey) {
                    $this->cache->set($cacheKey, $region);

                    return $region;
                }
            );
    }

    private function getCacheKey($bucketName)
    {
        return "aws:s3:{$bucketName}:location";
    }
}
