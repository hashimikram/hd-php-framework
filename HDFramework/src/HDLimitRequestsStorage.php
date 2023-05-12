<?php
namespace HDFramework\src;

use LeakyBucket\Storage\StorageInterface;

class HDLimitRequestsStorage implements StorageInterface
{

    private $database;

    private $collection;

    private $dbBucket;

    public function __construct($database, $collection)
    {
        $this->database = $database;
        $this->collection = $collection;
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'database', $database);
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'collection', $collection);
    }

    public function fetch($key)
    {
        $query = array();
        $query['key'] = $key;
        $this->dbBucket = HDDatabase::getTable($this->collection, $this->database)->findOne($query);
        return $this->dbBucket['value'];
    }

    public function exists($key)
    {
        $query = array();
        $query['key'] = $key;
        return HDDatabase::getTable($this->collection, $this->database)->count($query);
    }

    public function purge($key)
    {
        $query = array();
        $query['key'] = $key;
        return HDDatabase::getTable($this->collection, $this->database)->remove($query);
    }

    public function store($key, $value, $ttl = 0)
    {
        $query = array();
        $query['key'] = $key;

        $new_object = array();
        $new_object['$set']['value.time'] = $value['time'];
        $new_object['$set']['ttl'] = $ttl;
        $new_object['$inc']['value.drops'] = $value['drops'] - $this->dbBucket['value']['drops'];

        $options = array();
        $options['upsert'] = true;
        return HDDatabase::getTable($this->collection, $this->database)->update($query, $new_object, $options);
    }
}