<?php
namespace HDFramework\src;

use MongoClient;
use MongoCursorException;
use PDO;
use PDOException;

/**
 * HDDatabase class to manage database connections
 *
 * Dependencies: HDApplication<br />
 * Configurations dependencies:<br />
 * config.*.php: DBMODE<br />
 * - for mongo db: MONGO_DB, MONGO_IP, MONGO_PORT, MONGO_REPLICA_SET, MONGO_USER, MONGO_PASSWORD<br />
 * - for mysql db: MYSQL_IP, MYSQL_DB, MYSQL_PORT, MYSQL_USER, MYSQL_PASSWORD<br />
 * - for oracle db: ORACLE_IP, ORACLE_PORT, ORACLE_DB, ORACLE_USER, ORACLE_PASSWORD<br />
 * <br />Release date: 01/10/2015
 *
 * @version 7.0
 * @author Alin
 * @package framework
 */
class HDDatabase
{

    private static $database = null;

    private static $collections = array();

    /**
     *
     * Retrieve a MongoCollection object by name from existing connection
     *
     * @param String $collectionName
     */
    public static function getTable($collectionName, $dbName = "")
    {
        // retrieve application db type
        $dbType = HDApplication::getConfiguration('DBMODE');

        // default framework db type will be mongo db
        if (empty($dbType)) {
            $dbType = "mongo";
        }

        // get configuration database if none is defined
        if (empty($dbName)) {
            $dbName = HDApplication::getConfiguration("MONGO_DB");
        }

        // exit if this method is called for a different database system
        if ($dbType != "mongo") {
            die("getTable method is available only for MongoDB database system");
            exit();
        }

        // get ip configuration for connect
        $mongo_ips = HDApplication::getConfiguration("MONGO_IP");

        // flag replica set
        if (is_array($mongo_ips))
            $isReplicaSet = true;
        else
            $isReplicaSet = false;

        // check if database object exists
        if ($isReplicaSet && (self::$database == null)) {
            // add port ':port' at the end of all elements in array
            foreach ($mongo_ips as &$mongo_ip)
                $mongo_ip = $mongo_ip . ":" . HDApplication::getConfiguration("MONGO_PORT");

            // make string of ips separated by comma
            $connection_string = implode(",", $mongo_ips);

            // connect to db
            try {
                self::$database = new MongoClient("mongodb://" . $connection_string . "/?replicaSet=" . HDApplication::getConfiguration("MONGO_REPLICA_SET"), array(
                    "username" => HDApplication::getConfiguration("MONGO_USER"),
                    "password" => HDApplication::getConfiguration("MONGO_PASSWORD"),
                    "connectTimeoutMS" => 500
                ));
            } catch (MongoCursorException $e) {
                echo $e->getMessage() . '<br><br>';
                echo $e->getCode() . '<br><br>';
                exit();
            }
        } elseif (! $isReplicaSet && ! isset(self::$database[$dbName])) {
            self::$database[$dbName] = new MongoClient("mongodb://" . HDApplication::getConfiguration("MONGO_IP") . ":" . HDApplication::getConfiguration("MONGO_PORT"), array(
                "username" => HDApplication::getConfiguration("MONGO_USER"),
                "password" => HDApplication::getConfiguration("MONGO_PASSWORD"),
                "db" => $dbName,
                "connectTimeoutMS" => 500
            ));
        }

        if (! array_key_exists($dbName, self::$collections)) {
            // get collections list
            if ($isReplicaSet)
                $tmpCollections = self::$database->__get($dbName)->listCollections();
            else
                $tmpCollections = self::$database[$dbName]->__get($dbName)->listCollections();

            // build simple collection array names
            foreach ($tmpCollections as $collection) {
                self::$collections[$dbName][] = str_replace($dbName . ".", "", "" . $collection);
            }
        }

        // test if required collection exists in user defined tables
        if (in_array($collectionName, self::$collections[$dbName])) {
            // return collection object
            if ($isReplicaSet)
                return self::$database->selectCollection($dbName, $collectionName);
            else
                return self::$database[$dbName]->selectCollection($dbName, $collectionName);
        } else {
            // throw exception if unknown table is requested
            // create email body
            $body = "<pre><br />" . print_r(debug_backtrace(), true) . "<br /></pre>";
            HDMail::sendEmail('no-reply@moneymail.me', // fromEmail
            'me again', // fromName
            'cornel@moneymail.me', // toEmail
            "Cornel Zgardan", // toName
            "", // ccEmail
            "", // bccEmail
            'Eroare in baza de date', // emailSubject
            $body, // emailHTMLContent
            "", // emailTxtContent
            "" // emailAtachements
            );
            unset($body);

            die("Table " . $collectionName . " does not exists!");
            exit();
        }
    }

    /**
     * Retrieve a mysql/oracle connection
     */
    public static function getConnection()
    {
        // retrieve application db type
        $dbType = HDApplication::getConfiguration('DBMODE');

        // exit if this method is called for a different database system
        if (($dbType != "mysql") && ($dbType != "oracle")) {
            die("getConnection method is available only for mysql/oracle database system");
            exit();
        }

        if (self::$database === null) {
            self::initMysqlConnection($dbType);
        } else {
            set_error_handler(array(
                __CLASS__,
                'error_handler'
            ), E_WARNING);
            self::$database->prepare("SELECT 1")->execute(array());
            restore_error_handler();
        }
        return self::$database;
    }

    protected static function initMysqlConnection($dbType)
    {
        $options = array(
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
            PDO::ATTR_CASE => PDO::CASE_NATURAL
        );
        try {
            if ($dbType == "mysql") {
                self::$database = new HDPDO(
                    // connection string
                    'mysql:host=' . HDApplication::getConfiguration('MYSQL_IP') . ';dbname=' . HDApplication::getConfiguration('MYSQL_DB') . ';port=' . HDApplication::getConfiguration('MYSQL_PORT') . ';charset=utf8',
                    // user
                    HDApplication::getConfiguration('MYSQL_USER'),
                    // password
                    HDApplication::getConfiguration('MYSQL_PASSWORD'),
                    // options
                    $options);
            } elseif ($dbType == "oracle") {
                self::$database = new HDPDO(
                    // connection string
                    'oci:dbname=(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=' . HDApplication::getConfiguration('ORACLE_IP') . ')(PORT=' . HDApplication::getConfiguration('ORACLE_PORT') . ')))(CONNECT_DATA=(SERVICE_NAME=' . HDApplication::getConfiguration('ORACLE_DB') . ')))',
                    // user
                    HDApplication::getConfiguration('ORACLE_USER'),
                    // password
                    HDApplication::getConfiguration('ORACLE_PASSWORD'),
                    // options
                    $options);
            }
        } catch (PDOException $e) {
            die("Error connecting to database: " . $e->getMessage());
            exit();
        }
    }

    public static function error_handler($errno, $errstr, $errfile, $errline)
    {
        if (! (error_reporting() & $errno)) {
            // This error code is not included in error_reporting
            return;
        }

        switch ($errno) {
            case E_WARNING:
                self::$database = null;
                self::initMysqlConnection(HDApplication::getConfiguration('DBMODE'));
                break;
            default:
                die("Unknown error type: [$errno] $errstr");
                break;
        }

        /* Don't execute PHP internal error handler */
        return true;
    }
}
