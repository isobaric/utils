<?php

namespace Isobaric\Utils;

use AMQPChannel;
use AMQPChannelException;
use AMQPConnection;
use AMQPConnectionException;
use AMQPEnvelopeException;
use AMQPExchange;
use AMQPExchangeException;
use AMQPQueue;
use AMQPQueueException;
use Isobaric\Utils\Handler\ConnectionHandler;


class AmqpUtil
{
    /**
     * 初始化连接的参数
     * @var array
     * @see \AMQPConnection::__construct()
     */
    protected array $credentials = [];

    /**
     * 消费者预取数量
     * @var int
     */
    protected int $prefetchCount = 20;

    /**
     * 心跳
     * @var int|mixed
     */
    protected int $heartbeat = 60;

    /**
     * connection
     * @var AMQPConnection|null
     */
    private ?AMQPConnection $connection = null;

    /**
     * channel
     * @var AMQPChannel|null
     */
    private ?AMQPChannel $channel = null;

    /**
     * exchange
     * @var AMQPExchange|null
     */
    private ?AMQPExchange $exchange = null;

    /**
     * queue
     * @var AMQPQueue|null
     */
    private ?AMQPQueue $queue = null;

    /**
     * 队列特性
     *  取值: AMQP_DURABLE / AMQP_PASSIVE / AMQP_EXCLUSIVE / AMQP_AUTODELETE
     * @var string|null
     */
    private ?string $queueFlag = null;

    /**
     * 队列名称
     * @var string|null
     */
    private ?string $queueName = null;

    /**
     * 交换机特性
     *  取值: AMQP_EX_TYPE_DIRECT / AMQP_EX_TYPE_FANOUT / AMQP_EX_TYPE_HEADERS / AMQP_EX_TYPE_TOPIC
     * @var string|null
     */
    private ?string $exchangeFlag = null;

    /**
     * 交换机类型
     *  取值: AMQP_EX_TYPE_DIRECT / AMQP_EX_TYPE_FANOUT / AMQP_EX_TYPE_TOPIC / AMQP_EX_TYPE_HEADERS
     * @var string|null
     */
    private ?string $exchangeType = null;

    /**
     * 交换机名称
     * @var string|null
     */
    private ?string $exchangeName = null;

    /**
     * 初始化参数
     * @param array|null $credentials   取值参考当前类的属性
     * @see AMQPConnection::__construct
     */
    public function __construct(null|array $credentials = null)
    {
        // 如果未初始化 则使用继承者的属性
        if (!is_null($credentials)) {
            $this->credentials = $credentials;
        }

        // 心跳时间
        if (array_key_exists('heartbeat', $this->credentials) && $this->credentials['heartbeat'] >= 0) {
            $this->heartbeat = $this->credentials['heartbeat'];
        } else {
            $this->credentials['heartbeat'] = $this->heartbeat;
        }
    }

    /**
     * 设置消费者的预取数量
     * @param int $count
     * @return $this
     */
    public function setPrefetchCount(int $count): static
    {
        $this->prefetchCount = $count;
        return $this;
    }

    /**
     * @return void
     * @throws AMQPConnectionException
     */
    public function keepalive(): void
    {
        //$queue->getConnection()->connect();
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

//    /**
//     * 获取连接
//     * @return AMQPConnection|null
//     */
//    public function getConnection(): ?AMQPConnection
//    {
//        return $this->connection;
//    }
//
//    /**
//     * 获取channel
//     * @return AMQPChannel|null
//     */
//    public function getChannel(): ?AMQPChannel
//    {
//        return $this->channel;
//    }
//
//    /**
//     * 获取exchange
//     * @return AMQPExchange|null
//     */
//    public function getExchange(): ?AMQPExchange
//    {
//        return $this->exchange;
//    }

    /**
     * 设置channel
     * @return void
     * @throws AMQPConnectionException
     */
    private function initChannel(): void
    {
        if (!is_null($this->connection)) {
            return;
        }

        // 建立连接
        $this->connection = ConnectionHandler::amqp($this->credentials);

        // 初始化channel对象
        $this->channel = new AMQPChannel($this->connection);
    }

    /**
     * 设置exchange
     * @return void
     * @throws AMQPConnectionException
     * @throws AMQPExchangeException
     */
    private function initExchange(): void
    {
        // 初始化channel对象
        $this->initChannel();

        // 初始化exchange对象
        $this->exchange = new AMQPExchange($this->channel);

        // 设置名称
        $this->exchange->setName($this->exchangeName);
    }

    /**
     * @return void
     * @throws AMQPConnectionException
     * @throws AMQPQueueException
     */
    private function initQueue(): void
    {
        // 初始化channel对象
        $this->initChannel();

        // 初始化queue对象
        $this->queue = new AMQPQueue($this->channel);

        /**
         * Exchange为direct时 存在以下情况：
         * 1. 当全部消费者不设置队列名称 仅设置相同的路由名称时，
         *    情况1：生产者设置或不设置队列名称 仅设置路由名称时，则消息将同时发送给这些消费者
         *    情况2：如果生产者不设置队列名称和路由名称，则消息发送到exchange后被丢弃
         *
         * 2. 当全部消费者不设置队列名称 且不设置路由名称时，
         *    生产者也不设置队列名称和路由名称时，则消息将同时发送给这些消费者（使用了默认交换机）
         *
         * 3. 当一个消费者设置了队列名称 另一个消费者未设置队列名称 且两消费者都设置了相同路由名
         *    情况1：生产者设置队列名称和路由名称，消息将同时发送给这两个消费者
         *    情况2：生产者设置队列名称 不设置路由名称，则消息发送到exchange后被丢弃
         *
         * 4. 当一个消费者设置了队列名称且设置了路由名称 另一个消费者设置了相同的队列名 但未设置路由名
         *    情况1：生产者设置队列名称和路由名称，消息将轮流发送给这些消费者
         *    情况2：生产者设置队列名称但不是设置路由名称，消息将轮流发送给这些消费者
         *    情况3：生产者不设置队列名称，仅设置路由名称时，消息将轮流发送给这些消费者
         *
         * 结论：
         *  1 同名队列的消费者 轮流接收同名生产者的消息
         *  2 同名路由的消费者 同时接收生产者消息
         *  3 同名队列+同名路由消费者 轮流接收同名生产者的消息
         */
    }

    /**
     * 设置exchange
     * @param string      $exchangeName 交换机名称
     * @param string|null $exchangeType 交换机类型；当前参数仅用于消费者；
     *  exchangeType取值：
     *      AMQP_EX_TYPE_DIRECT     直连
     *      AMQP_EX_TYPE_FANOUT     扇形
     *      AMQP_EX_TYPE_HEADERS    头部
     *      AMQP_EX_TYPE_TOPIC      主题
     * @param string|null $flag 交换机flag；当前参数仅用于消费者；
     *   flag取值：
     *       AMQP_DURABLE 持久的交换和队列将在代理重启后幸存下来，并包含其所有数据。
     *       AMQP_PASSIVE 被动交换和队列不会被重新声明，但如果交换或队列不存在，代理将抛出错误
     *       AMQP_EXCLUSIVE 仅对队列有效，此标志表示只有一个客户端可以从该队列中侦听和消费。
     *       AMQP_AUTODELETE 自动删除
     *       对于交换，自动删除标志表示一旦没有更多队列绑定到交换，该交换将被删除。如果没有队列绑定到该交换，则该交换将永远不会被删除。
     *       注意：客户端断开连接时，独占队列将始终自动删除。
     * @return $this
     */
    public function setExchange(string $exchangeName, ?string $exchangeType = null, ?string $flag = \AMQP_DURABLE): static
    {
        $this->exchangeName = $exchangeName;
        $this->exchangeType = $exchangeType;
        $this->exchangeFlag = $flag;
        return $this;
    }

    /**
     * 设置queue
     * @param string   $queueName 队列名称
     * @param int|null $flag 队列模式；当前参数仅用于消费者；
     *  flag取值：
     *      AMQP_DURABLE 持久的交换和队列将在代理重启后幸存下来，并包含其所有数据。
     *      AMQP_PASSIVE 被动交换和队列不会被重新声明，但如果交换或队列不存在，代理将抛出错误
     *      AMQP_EXCLUSIVE 仅对队列有效，此标志表示只有一个客户端可以从该队列中侦听和消费。
     *      AMQP_AUTODELETE 自动删除
     *      对于队列，自动删除标志表示一旦没有更多的消费者订阅该队列，该队列将被删除。如果没有订阅处于活动状态，则该队列将永远不会被删除。
     *      注意：客户端断开连接时，独占队列将始终自动删除。
     * @return $this
     */
    public function setQueue(string $queueName, null|int $flag = \AMQP_DURABLE): static
    {
        $this->queueName = $queueName;
        $this->queueFlag = $flag;
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
     */
    public function publish(string|array $message, ?string $routingKey = null, ?int $flags = null, array $headers = []): void
    {
        if (!is_string($message)) {
            $message = serialize($message);
        }

        if (is_null($this->exchangeName)) {
            throw new AMQPExchangeException('Exchange must be initialized with function setExchange()');
        }

        // 设置交换机
        $this->initExchange();

        // 消息发送
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
    public function consume(callable $callback, ?string $routingKey = null, ?int $flags = null, ?string $consumerTag = null): void
    {
        if (is_null($this->exchangeName)) {
            throw new AMQPExchangeException('Exchange must be initialized with function setExchange()');
        }

        if (is_null($this->exchangeType)) {
            throw new AMQPExchangeException('before consume must be set exchange type with function setExchange()');
        }

        if (is_null($this->queueName)) {
            throw new AMQPQueueException('Queue must be initialized with function setQueue()');
        }

        // 声明交换机
        $this->initExchange();
        $this->exchange->setType($this->exchangeType);
        $this->exchange->setFlags($this->exchangeFlag);
        $this->exchange->declareExchange();

        // 声明队列
        $this->initQueue();
        $this->queue->setName($this->queueName);
        $this->queue->setFlags($this->queueFlag);
        $this->queue->declareQueue();

        // 设置预取消息数量
        $this->channel->setPrefetchCount($this->prefetchCount);

        // 绑定路由Key到交换机
        $this->queue->bind($this->exchangeName, $routingKey);

        // 消费数据
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
