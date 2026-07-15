<?php
namespace ClassifaiVendor\Aws\SSOOIDC;

use ClassifaiVendor\Aws\AwsClient;

/**
 * This client is used to interact with the **AWS SSO OIDC** service.
 * @method \ClassifaiVendor\Aws\Result createToken(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise createTokenAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result createTokenWithIAM(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise createTokenWithIAMAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result registerClient(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise registerClientAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result startDeviceAuthorization(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise startDeviceAuthorizationAsync(array $args = [])
 */
class SSOOIDCClient extends AwsClient {}
