<?php
namespace vipnytt\RobotsTxtParser\Client\Directives;

/**
 * Class DelayClient
 *
 * @see https://github.com/VIPnytt/RobotsTxtParser/blob/master/docs/methods/DelayClient.md for documentation
 * @package vipnytt\RobotsTxtParser\Client\Directives
 */
class DelayClient extends DelayCore implements ClientInterface
{
    /**
     * Value
     * @var float|int
     */
    private $value;

    /**
     * Export value
     * @var float|int
     */
    private $exportValue;

    /**
     * DelayClient constructor.
     *
     * @param string $baseUri
     * @param string $userAgent
     * @param float|int $value
     * @param float|int $fallbackValue
     */
    public function __construct($baseUri, $userAgent, $value, $fallbackValue = 0)
    {
        parent::__construct($baseUri, $userAgent);
        $this->exportValue = $value;
        $this->value = $value > 0 ? $value : $fallbackValue;
    }

    /**
     * Get value
     *
     * @return float|int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Export
     *
     * @return float|int
     */
    public function export()
    {
        return $this->exportValue;
    }
}
