<?php

namespace Isobaric\Utils;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class ConnectionPoolUtil
{
    /**
     * 连接池
     * @var array
     */
    private static array $pool = [];

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
     *      'cacert' => PEM CA cert 客户端证书的路径
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
     * 获取连接池的有效连接
     * @param string $index
     * @return \AMQPConnection|Client|null
     */
    private static function getConnectionFromPool(string $index): \AMQPConnection|Client|null
    {
        if (!array_key_exists($index, self::$pool)) {
            return null;
        }

        /**
         * @var \AMQPConnection|Client $connection
         */
        $connection = self::$pool[$index];

        if ($connection instanceof \AMQPConnection) {
            $isAlive = $connection->isConnected();
        } else if ($connection instanceof Client) {
            $isAlive = $connection->ping();
        } else {
            throw new \RuntimeException('support');
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
     */
    private static function getConnectionFromRemote(string $name, array $config): \AMQPConnection|Client
    {
        return match ($name) {
            // ES
            'elasticsearch' => ClientBuilder::create()->setHosts($config)->build(),
            // RabbitMQ
            'amqp' => new \AMQPConnection($config),
            // 不支持的连接
            default => throw new \RuntimeException('support'),
        };
    }

    /**
     * 获取连接
     * @param string $name
     * @param array  $config
     * @return \AMQPConnection|Client
     */
    private static function getConnection(string $name, array $config): \AMQPConnection|Client
    {
        $index = self::poolIndex($name, $config);

        $connection = self::getConnectionFromPool($index);
        
        if (is_null($connection)) {
            return self::getConnectionFromRemote($name, $config);
        }

        return $connection;
    }
}
