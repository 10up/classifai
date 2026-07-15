<?php
namespace ClassifaiVendor\Aws\Exception;

use ClassifaiVendor\Aws\HasMonitoringEventsTrait;
use ClassifaiVendor\Aws\MonitoringEventsInterface;

class InvalidRegionException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
