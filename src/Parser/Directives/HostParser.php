<?php
namespace vipnytt\RobotsTxtParser\Parser\Directives;

use vipnytt\RobotsTxtParser\Client\Directives\HostClient;

/**
 * Class HostParser
 *
 * @package vipnytt\RobotsTxtParser\Parser\Directives
 */
class HostParser extends HostParserCore
{
    /**
     * HostParser constructor.
     *
     * @param string $base
     * @param string $effective
     */
    public function __construct($base, $effective)
    {
        parent::__construct($base, $effective);
    }

    /**
     * Client
     *
     * @return HostClient
     */
    public function client()
    {
        return new HostClient($this->base, $this->effective, $this->host);
    }

    /**
     * Render
     *
     * @return string[]
     */
    public function render()
    {
        return isset($this->host[0]) ? [
            self::DIRECTIVE_HOST . ':' . $this->host[0]
        ] : [];
    }
}
