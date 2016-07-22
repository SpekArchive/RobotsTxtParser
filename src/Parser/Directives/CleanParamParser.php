<?php
namespace vipnytt\RobotsTxtParser\Parser\Directives;

use vipnytt\RobotsTxtParser\Client\Directives\CleanParamClient;

/**
 * Class CleanParamParser
 *
 * @package vipnytt\RobotsTxtParser\Parser\Directives
 */
class CleanParamParser extends CleanParamParserCore
{
    /**
     * CleanParamParser constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Client
     *
     * @return CleanParamClient
     */
    public function client()
    {
        return new CleanParamClient($this->cleanParam);
    }
}
