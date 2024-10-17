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
     * ES连接
     * @param array $hosts
     * @return Client
     */
    public static function elasticsearch(array $hosts): Client
    {
        $client = null;
        $index = self::poolIndex(__FUNCTION__, $hosts);

        if (array_key_exists($index, self::$pool)) {
            /**
             * @var Client $client
             */
            $client = self::$pool[$index];

            if (!$client->ping()) {
                $client = null;
            }
        }

        if (is_null($client)) {
            $client = ClientBuilder::create()->setHosts($hosts)->build();
            self::$pool[$index] = $client;
        }

        return $client;
    }
}
