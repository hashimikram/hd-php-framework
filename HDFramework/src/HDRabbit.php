<?php
namespace HDFramework\src;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPIOException;

/**
 * Wrapper class for RabbitMQ api
 *
 * Configurations dependencies:<br />
 * - config.*.php: RABBIT_HOST, RABBIT_PORT, RABBIT_USER, RABBIT_PASS<br />
 * <br />Release date: 09/11/2015
 *
 * @version 6.0
 * @author cornel
 * @package framework
 */
class HDRabbit
{

    private static $connection = null;

    private static $channel = null;

    private static $message = null;

    public static function getConnection()
    {
        if (is_null(self::$connection)) {
            $host = HDApplication::getConfiguration("RABBIT_HOST");
            $port = HDApplication::getConfiguration("RABBIT_PORT");
            $user = HDApplication::getConfiguration("RABBIT_USER");
            $password = HDApplication::getConfiguration("RABBIT_PASS");
            $vhost = '/';
            $insist = false;
            $login_method = 'AMQPLAIN';
            $login_response = null;
            $locale = 'en_US';
            $connection_timeout = 3.0;
            $read_write_timeout = 3.0;
            $context = null;
            $keepalive = false;
            $heartbeat = 30;
            try {

                self::$connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost, $insist, $login_method, $login_response, $locale, $connection_timeout, $read_write_timeout, $context, $keepalive, $heartbeat);
            } catch (AMQPIOException $e) {
                // donothing because we can't send logs or emails
                // all messages will be lost, uptrends will notify the malfunction
            }
        }
        return self::$connection;
    }

    public static function getMessage($body, $properties)
    {
        if (is_null(self::$message)) {
            self::$message = new AMQPMessage($body, $properties);
        } else {
            self::$message->setBody($body);
            foreach ($properties as $key => $propertie) {
                self::$message->set($key, $propertie);
            }
        }
        return self::$message;
    }

    public static function getChannel($connection)
    {
        if (is_null(self::$channel)) {
            if (! is_null($connection)) {
                self::$channel = $connection->channel();
            }
        }
        return self::$channel;
    }

    public static function basicPublish($msg, $exchange = '', $routing_key = '', $mandatory = false, $immediate = false, $ticket = null)
    {
        if (is_null(self::$channel)) {
            self::$channel = self::getChannel(self::getConnection());
        }
        if (! is_null(self::$channel)) {
            $msg = json_encode($msg);
            $properties = array();
            $properties['delivery_mode'] = 2;
            $publishMessage = self::getMessage($msg, $properties);
            self::$channel->basic_publish($publishMessage, '', $routing_key);
        }
    }
}
