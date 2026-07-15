<?php
namespace ClassifaiVendor\Aws\Exception;

use ClassifaiVendor\Aws\HasMonitoringEventsTrait;
use ClassifaiVendor\Aws\MonitoringEventsInterface;

class UnresolvedEndpointException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
