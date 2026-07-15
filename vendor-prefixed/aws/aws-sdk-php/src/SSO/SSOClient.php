<?php
namespace ClassifaiVendor\Aws\SSO;

use ClassifaiVendor\Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Single Sign-On** service.
 * @method \ClassifaiVendor\Aws\Result getRoleCredentials(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise getRoleCredentialsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listAccountRoles(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listAccountRolesAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result listAccounts(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise listAccountsAsync(array $args = [])
 * @method \ClassifaiVendor\Aws\Result logout(array $args = [])
 * @method \ClassifaiVendor\GuzzleHttp\Promise\Promise logoutAsync(array $args = [])
 */
class SSOClient extends AwsClient {}
