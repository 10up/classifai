<?php
namespace ClassifaiVendor\Aws\Retry\Exception;

use ClassifaiVendor\Aws\HasMonitoringEventsTrait;
use ClassifaiVendor\Aws\MonitoringEventsInterface;

/**
 * Represents an error interacting with retry configuration
 */
class ConfigurationException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
