<?php

namespace Isobaric\Utils;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Isobaric\Utils\Exceptions\ConnectionException;

class ConnectionPoolUtil
{
    /**
     * 连接池
     * @var array
     */
    private static array $pool = [];

    /**
     * 连接存活时间
     * @var array
     */
    private static array $liveTime = [];

    /**
     * 连接超时时间 单位：秒
     * @var int
     */
    private static int $liveTimeout = 15;

    /**
     * ES连接
     * @param array $hosts
     * @return Client
     */
    public static function elasticsearch(array $hosts): Client
    {
        return self::getConnection(__FUNCTION__, $hosts);
    }

    /**
     * 连接AMQP http://www.rabbitmq.com/amqp-0-9-1-reference.html#connection.tune
     *
     * @param array $credentials
     *   $credentials = [
     *      'host'  => amqp.host；最多1024个字符；
     *      'port'  => amqp.port Port on the host.
     *      'vhost' => amqp.vhost；最多128个字符；
     *      'login' => amqp.login；最多128个字符；
     *      'password' => amqp.password；最多128个字符；
     *      'read_timeout'  => 读取的超时时间； >=0的秒 可以是分数；
     *      'write_timeout' => 写入的超时时间； >=0的秒 可以是分数；
     *      'connect_timeout' => 连接的超时时间； >=0的秒 可以是分数；
     *      'rpc_timeout' => RPC的超时时间； >=0的秒 可以是分数；
     *      'channel_max' => 指定服务器允许的最高通道号。0表示标准扩展限制；
     *      'frame_max'   => 服务器为连接建议的最大帧大小，包括帧头和结束字节。0表示标准扩展限制;
     *      'heartbeat'   => 0 则不设置心跳检测，大于0 则需要在回调中保持
     *      'cert'   => PEM cert 客户端证书的路径
     *      'key'    => PEM格式的客户端密钥路径
     *      'verify' => 启用或禁用对等验证。如果启用了对等验证，则服务器证书必须与服务器名称匹配。默认情况下启用对等验证
     *      'connection_name' => 用户确定的连接名称
     *   ]
     * @return \AMQPConnection
     */
    public static function amqp(array $credentials): \AMQPConnection
    {
        return self::getConnection(__FUNCTION__, $credentials);
    }

    /**
     * 连接池下标
     * @param string       $prefix
     * @param array|string $connection
     * @return string
     */
    private static function poolIndex(string $prefix, array|string $connection): string
    {
        if (is_array($connection)) {
            $connection = json_encode($connection);
        }
        return $prefix . '-' . md5($connection);
    }

    /**
     * 获取连接
     * @param string $index
     * @return object|null
     */
    private static function getPool(string $index): object|null
    {
        return self::$pool[$index] ?? null;
    }

    /**
     * 添加连接
     * @param string $index
     * @param object $connection
     * @return void
     */
    private static function setPool(string $index, object $connection): void
    {
        self::$pool[$index] = $connection;
    }

    /**
     * 获取连接存活时间
     * @param string $index
     * @return int|null
     */
    private static function getLiveTime(string $index): ?int
    {
        return self::$liveTime[$index] ?? null;
    }

    /**
     * 设置连接存活时间
     * @param string $index
     * @return void
     */
    private static function setLiveTime(string $index): void
    {
        self::$liveTime[$index] = time();
    }

    /**
     * 获取连接池的有效连接
     * @param string $index
     * @return \AMQPConnection|Client|null
     */
    private static function getConnectionFromPool(string $index): \AMQPConnection|Client|null
    {
        /**
         * @var \AMQPConnection|Client $connection
         */
        $connection = self::getPool($index);
        if (is_null($connection)) {
            return null;
        }

        // 超时时间内不验证连接是否有效
        $liveTime = self::getLiveTime($index);
        if (!is_null($liveTime) && $liveTime <= self::$liveTimeout) {
            return $connection;
        }

        // 验证连接是否有效
        if ($connection instanceof \AMQPConnection) {
            $isAlive = $connection->isConnected();
        } else if ($connection instanceof Client) {
            $isAlive = $connection->ping();
        } else {
            throw new ConnectionException('connection not support ' . get_class($connection));
        }

        // 连接无效
        if (!$isAlive) {
            return null;
        }

        return $connection;
    }

    /**
     * 获取远程连接
     * @param string $name
     * @param array  $config
     * @return \AMQPConnection|Client
     * @throws \AMQPConnectionException
     */
    private static function getConnectionFromRemote(string $name, array $config): \AMQPConnection|Client
    {
        switch ($name) {
            // ES
            case 'elasticsearch':
                return ClientBuilder::create()->setHosts($config)->build();
            // RabbitMQ
            case 'amqp':
                $connection = new \AMQPConnection($config);
                $connection->connect();
                return $connection;
            default:
                throw new ConnectionException('connection not support ' . $name);
         }
    }

    /**
     * 获取连接
     * @param string $name
     * @param array  $config
     * @return \AMQPConnection|Client
     * @throws \AMQPConnectionException
     */
    private static function getConnection(string $name, array $config): \AMQPConnection|Client
    {
        $index = self::poolIndex($name, $config);
        $connection = self::getConnectionFromPool($index);
        
        if (is_null($connection)) {
            // 获取连接
            $connection = self::getConnectionFromRemote($name, $config);

            // 加入连接池
            self::setPool($index, $connection);

            // 重置连接时间
            self::setLiveTime($index);
        }

        return $connection;
    }
}
