<?php
namespace ClassifaiVendor\Aws\S3;

use ClassifaiVendor\Aws\CommandInterface;
use ClassifaiVendor\Aws\ResultInterface;
use ClassifaiVendor\Psr\Http\Message\RequestInterface;

/**
 * Logs a warning when the `expires` header
 * fails to be parsed.
 *
 * @internal
 */
class ExpiresParsingMiddleware
{
    /** @var callable  */
    private $nextHandler;

    /**
     * Create a middleware wrapper function.
     *
     * @return callable
     */
    public static function wrap()
    {
        return function (callable $handler) {
            return new self($handler);
        };
    }

    /**
     * @param callable $nextHandler Next handler to invoke.
     */
    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }

    public function __invoke(CommandInterface $command, ?RequestInterface $request = null)
    {
        $next = $this->nextHandler;
        return $next($command, $request)->then(
            function (ResultInterface $result) {
                if (empty($result['Expires']) && !empty($result['ExpiresString'])) {
                    trigger_error(
                        "Failed to parse the `expires` header as a timestamp due to "
                        . " an invalid timestamp format.\nPlease refer to `ExpiresString` "
                        . "for the unparsed string format of this header.\n"
                        , E_USER_WARNING
                    );
                }
                return $result;
            }
        );
    }
}
