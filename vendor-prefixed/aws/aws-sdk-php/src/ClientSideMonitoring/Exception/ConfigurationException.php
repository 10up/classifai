<?php
namespace ClassifaiVendor\Aws\ClientSideMonitoring\Exception;

use ClassifaiVendor\Aws\HasMonitoringEventsTrait;
use ClassifaiVendor\Aws\MonitoringEventsInterface;


/**
 * Represents an error interacting with configuration for client-side monitoring.
 */
class ConfigurationException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
