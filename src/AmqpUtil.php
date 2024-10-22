<?php

namespace Isobaric\Utils;

use AMQPConnection;
use AMQPChannel;
use AMQPExchange;
use AMQPQueue;
use AMQPQueueException;
use AMQPChannelException;
use AMQPExchangeException;
use AMQPEnvelopeException;
use AMQPConnectionException;


class AmqpUtil
{
    /**
     * 初始化连接的参数
     * @var array
     * @see \AMQPConnection::__construct()
     */
    protected array $credentials;

    /**
     * @var AMQPConnection|null
     */
    protected ?AMQPConnection $connection = null;

    /**
     * @var AMQPChannel|null
     */
    protected ?AMQPChannel $channel = null;

    /**
     * @var AMQPExchange|null
     */
    protected ?AMQPExchange $exchange = null;

    /**
     * @var AMQPQueue|null
     */
    protected ?AMQPQueue $queue = null;

    /**
     * @var string
     */
    private string $exchangeName;

    /**
     * @param array|null $credentials
     * @see AMQPConnection::__construct
     * @throws AMQPConnectionException
     */
    public function __construct(null|array $credentials = null)
    {
        // 如果未初始化 则使用继承者的属性
        if (!is_null($credentials)) {
            $this->credentials = $credentials;
        }

        // 建立连接
        $this->connection = ConnectionPoolUtil::amqp($credentials);

        // 初始化channel对象
        $this->channel = new AMQPChannel($this->connection);
    }

    /**
     * 关闭连接
     * @return void
     * @throws AMQPConnectionException
     */
    public function disconnect(): void
    {
        $this->connection->disconnect();
    }

    /**
     * 获取连接
     * @return AMQPConnection
     */
    public function getConnection(): AMQPConnection
    {
        return $this->connection;
    }

    /**
     * 获取channel
     * @return AMQPChannel
     */
    public function getChannel(): AMQPChannel
    {
        return $this->channel;
    }

    /**
     * 获取exchange
     * @return AMQPExchange
     */
    public function getExchange(): AMQPExchange
    {
        return $this->exchange;
    }

    /**
     * 设置exchange
     * @param string   $exchangeName 名称
     * @param string   $exchangeType 类型 | AMQP_EX_TYPE_DIRECT/AMQP_EX_TYPE_FANOUT/AMQP_EX_TYPE_HEADERS/AMQP_EX_TYPE_TOPIC
     * @param int|null $flag         标识 | AMQP_DURABLE / AMQP_PASSIVE / AMQP_EXCLUSIVE / AMQP_AUTODELETE
     * @return $this
     * @throws AMQPConnectionException
     * @throws AMQPExchangeException
     */
    public function setExchange(string $exchangeName, string $exchangeType, int|null $flag = AMQP_DURABLE): static
    {
        // 初始化exchange对象
        $this->exchange = new AMQPExchange($this->channel);

        // 设置类型
        $this->exchange->setType($exchangeType);
        // 设置名称
        $this->exchange->setName($exchangeName);
        // 设置标识
        $this->exchange->setFlags($flag);

        $this->exchangeName = $exchangeName;

        return $this;
    }

    /**
     * 设置queue
     * @param string   $queueName 名称
     * @param int|null $flag 标识 | AMQP_DURABLE / AMQP_PASSIVE / AMQP_EXCLUSIVE / AMQP_AUTODELETE
     * @return $this
     * @throws AMQPConnectionException
     * @throws AMQPQueueException
     */
    public function setQueue(string $queueName, null|int $flag = AMQP_DURABLE): static
    {
        // 初始化queue对象
        $this->queue = new AMQPQueue($this->channel);

        // 设置flag
        /**
         * AMQP_DURABLE 持久的交换和队列将在代理重启后幸存下来，并包含其所有数据。
         * AMQP_PASSIVE 被动交换和队列不会被重新声明，但如果交换或队列不存在，代理将抛出错误
         * AMQP_EXCLUSIVE 仅对队列有效，此标志表示只有一个客户端可以从该队列中侦听和消费。
         * AMQP_AUTODELETE 自动删除
         *  对于交换，自动删除标志表示一旦没有更多队列绑定到交换，该交换将被删除。如果没有队列绑定到该交换，则该交换将永远不会被删除。
         *  对于队列，自动删除标志表示一旦没有更多的侦听器订阅该队列，该队列将被删除。如果没有订阅处于活动状态，则该队列将永远不会被删除。
         *  注意：客户端断开连接时，独占队列将始终自动删除。
         */
        $this->queue->setFlags($flag);
        // 设置名称
        $this->queue->setName($queueName);

        return $this;
    }

    /**
     * 发布direct消息
     * @param string|array $message 如果不是字符串，则序列化
     * @param string|null  $routingKey
     * @param int|null     $flags
     * @param array        $headers
     * @return void
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     * @throws AMQPExchangeException
     * @throws AMQPQueueException
     */
    public function publish(
        string|array $message,
        string|null $routingKey = null,
        int|null $flags = null,
        array $headers = []
    ): void
    {
        if (!is_string($message)) {
            $message = serialize($message);
        }

        if (is_null($this->queue)) {
            throw new AMQPQueueException('before publish the queue must be initialized with function setQueue()');
        }

        if (is_null($this->exchange)) {
            throw new AMQPExchangeException('before publish the exchange must be initialized with function setExchange()');
        }
        $this->exchange->publish($message, $routingKey, $flags, $headers);
    }

    /**
     * 消费direct消息
     * @param callable    $callback 消费消息的回调方法 | 方法接收两个参数: AMQPEnvelope $message, AMQPQueue $queue
     * @param string|null $routingKey 路由
     * @param int|null    $flags 标识 | AMQP_AUTOACK / AMQP_JUST_CONSUME
     * @param string|null $consumerTag  消费者标签
     * @return void
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     * @throws AMQPEnvelopeException
     * @throws AMQPExchangeException
     * @throws AMQPQueueException
     */
    public function consume(
        callable $callback,
        null|string $routingKey = null,
        null|int $flags = null,
        null|string $consumerTag = null
    ): void
    {
        // 声明交换机
        $this->exchange->declareExchange();

        // 声明队列
        $this->queue->declareQueue();

        // 直连模式需要绑定交换机和路由
        $this->queue->bind($this->exchangeName, $routingKey);

        // 消费消息
        $this->queue->consume($callback, $flags, $consumerTag);
    }

    // / AMQP_EX_TYPE_FANOUT / AMQP_EX_TYPE_HEADERS / AMQP_EX_TYPE_TOPIC
    public static function fanoutPublish(string $message)
    {

    }

    public static function topicPublish(string $message)
    {

    }

    public static function headersPublish()
    {

    }
}
