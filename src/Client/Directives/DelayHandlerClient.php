<?php
namespace vipnytt\RobotsTxtParser\Client\Directives;

use PDO;
use vipnytt\RobotsTxtParser\Exceptions\SQLException;
use vipnytt\RobotsTxtParser\SQL\SQLInterface;
use vipnytt\RobotsTxtParser\SQL\TableConstructor;
use vipnytt\UserAgentParser;

/**
 * Class DelayHandlerClient
 *
 * @package vipnytt\RobotsTxtParser\Client\Directives
 */
class DelayHandlerClient implements SQLInterface
{
    /**
     * Supported database drivers
     */
    const SUPPORTED_DRIVERS = [
        self::DRIVER_MYSQL,
    ];

    /**
     * Database connection
     * @var PDO
     */
    private $pdo;

    /**
     * PDO driver
     * @var string
     */
    private $driver;

    /**
     * Base UriClient
     * @var string
     */
    private $base;

    /**
     * User-agent
     * @var string
     */
    private $userAgent;

    /**
     * Delay
     * @var float|int
     */
    private $delay;

    /**
     * DelayClient constructor.
     *
     * @param PDO $pdo
     * @param string $baseUri
     * @param string $userAgent
     * @param float|int $delay
     * @throws SQLException
     */
    public function __construct(PDO $pdo, $baseUri, $userAgent, $delay)
    {
        $this->pdo = $this->pdoInitialize($pdo);
        $this->base = $baseUri;
        $uaStringParser = new UserAgentParser($userAgent);
        $this->userAgent = $uaStringParser->stripVersion();
        $this->delay = round($delay, 6, PHP_ROUND_HALF_UP);
    }

    /**
     * Initialize PDO connection
     *
     * @param PDO $pdo
     * @return PDO
     * @throws SQLException
     */
    private function pdoInitialize(PDO $pdo)
    {
        if ($pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_SILENT) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        }
        $pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $pdo->exec('SET NAMES ' . self::SQL_ENCODING);
        $this->driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (!in_array($this->driver, self::SUPPORTED_DRIVERS)) {
            throw new SQLException('Unsupported database. ' . self::README_SQL_DELAY);
        }
        $tableConstructor = new TableConstructor($pdo, self::TABLE_DELAY);
        if ($tableConstructor->exists() === false) {
            $tableConstructor->create(file_get_contents(__DIR__ . '/../../SQL/delay.sql'), self::README_SQL_DELAY);
        }
        return $pdo;
    }

    /**
     * Queue
     *
     * @return float|int
     */
    public function getQueue()
    {
        if ($this->delay == 0) {
            return 0;
        }
        $query = $this->pdo->prepare(<<<SQL
SELECT GREATEST(0, (microTime / 1000000) - UNIX_TIMESTAMP(CURTIME(6))) AS sec
FROM robotstxt__delay0
WHERE base = :base AND userAgent = :userAgent;
SQL
        );
        $query->bindParam(':base', $this->base, PDO::PARAM_STR);
        $query->bindParam(':userAgent', $this->userAgent, PDO::PARAM_STR);
        $query->execute();
        if ($query->rowCount() > 0) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return $row['sec'];
        }
        return 0;
    }

    /**
     * Reset queue
     *
     * @return bool
     */
    public function reset()
    {
        $query = $this->pdo->prepare(<<<SQL
DELETE FROM robotstxt__delay0
WHERE base = :base AND userAgent = :useragent;
SQL
        );
        $query->bindParam(':base', $this->base, PDO::PARAM_INT);
        $query->bindParam(':useragent', $this->userAgent, PDO::PARAM_INT);
        return $query->execute();
    }

    /**
     * Sleep
     *
     * @return float|int
     */
    public function sleep()
    {
        $start = microtime(true);
        $until = $this->getTimeSleepUntil();
        if (microtime(true) > $until) {
            return 0;
        }
        try {
            time_sleep_until($until);
        } catch (\Exception $warning) {
            // Timestamp already in the past
        }
        return microtime(true) - $start;
    }

    /**
     * Timestamp with milliseconds
     *
     * @return float|int
     * @throws SQLException
     */
    public function getTimeSleepUntil()
    {
        if ($this->delay == 0) {
            return 0;
        }
        $query = $this->pdo->prepare(<<<SQL
SELECT
  microTime,
  UNIX_TIMESTAMP()
FROM robotstxt__delay0
WHERE base = :base AND userAgent = :userAgent;
SQL
        );
        $query->bindParam(':base', $this->base, PDO::PARAM_STR);
        $query->bindParam(':userAgent', $this->userAgent, PDO::PARAM_STR);
        $query->execute();
        $this->increment();
        if ($query->rowCount() > 0) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            if (abs(time() - $row['UNIX_TIMESTAMP()']) > 10) {
                throw new SQLException('`PHP server` and `SQL server` timestamps are out of sync. Please fix!');
            }
            return $row['microTime'] / 1000000;
        }
        return 0;
    }

    /**
     * Set new delayUntil timestamp
     *
     * @return bool
     */
    private function increment()
    {
        $query = $this->pdo->prepare(<<<SQL
INSERT INTO robotstxt__delay0 (base, userAgent, microTime, lastDelay)
VALUES (:base, :userAgent, (UNIX_TIMESTAMP(CURTIME(6)) + :delay) * 1000000, ROUND(:delay))
ON DUPLICATE KEY UPDATE
  microTime = GREATEST((UNIX_TIMESTAMP(CURTIME(6)) + :delay) * 1000000, microTime + (:delay * 1000000)),
  lastDelay = ROUND(:delay);
SQL
        );
        $query->bindParam(':base', $this->base, PDO::PARAM_STR);
        $query->bindParam(':userAgent', $this->userAgent, PDO::PARAM_STR);
        $query->bindParam(':delay', $this->delay, is_int($this->delay) ? PDO::PARAM_INT : PDO::PARAM_STR);
        return $query->execute();
    }
}