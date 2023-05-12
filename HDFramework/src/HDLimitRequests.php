<?php
namespace HDFramework\src;

/**
 * Wrapper class for Leacky bucket algorithm api
 *
 * @version 6.0
 * @author cornel
 * @package framework
 */
use LeakyBucket\LeakyBucket;
use LeakyBucket\Storage\StorageInterface;

class HDLimitRequests
{

    private $database;

    private $collection;

    private $settings_database;

    private $settings_collection;

    private $app_name;

    public $settings;

    public $leakyBucket;

    /**
     * Class constructor.
     *
     * @param string $key
     *            The bucket key
     * @param StorageInterface $storage
     *            The storage provider that has to be used
     * @param array $options
     *            The settings to be set
     */
    public function __construct($key)
    {
        $this->collection = HDApplication::getConfiguration("LIMIT_REQUESTS_BY_IP_BUCKET_COLLECTION");
        $this->database = HDApplication::getConfiguration("LIMIT_REQUESTS_BY_IP_BUCKET_DATABASE");
        $this->settings_collection = HDApplication::getConfiguration("LIMIT_REQUESTS_BY_IP_BUCKET_SETTINGS_COLLECTION");
        $this->settings_database = HDApplication::getConfiguration("LIMIT_REQUESTS_BY_IP_BUCKET_SETTINGS_DATABASE");
        $this->app_name = HDApplication::getConfiguration("app_name");

        $this->settings = $this->getLimitRequestsSettings();
        $this->leakyBucket = new LeakyBucket("$key:$this->app_name", new HDLimitRequestsStorage($this->database, $this->collection), $this->settings['options']);

        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, '__construct', $key);
    }

    /**
     * Gets settings from database
     */
    private function getLimitRequestsSettings()
    {
        $query = array();
        $query['app_name'] = $this->app_name;
        return HDDatabase::getTable($this->settings_collection, $this->settings_database)->findOne($query);
    }

    public function isActive()
    {
        return $this->settings['isActive'];
    }

    public function getTtl()
    {
        return $this->settings['options']['capacity'] / $this->settings['options']['leak'] * 1.5;
    }
}