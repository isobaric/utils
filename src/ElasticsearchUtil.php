<?php

namespace Isobaric\Utils;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Isobaric\Utils\Exceptions\ElasticsearchException;

class ElasticsearchUtil
{
    // $hosts为字符串时，则以config()方法获取配置信息，配置信息应为List数组
    protected string|array $hosts;

    // 当前操作的ES的默认index
    protected string $index;

    /**
     * ES Client 对象
     * @var Client|null
     */
    private ?Client $client = null;

    /**
     * @var array
     */
    private array $params;

    /**
     * 超时时间 单位秒
     * @var int
     */
    private int $timeout = 20;

    /**
     * 超时时间 单位秒
     * @var int
     */
    private int $connectTimeout = 30;

    /**
     * 查询返回doc_id的key
     * @var string|null
     */
    private ?string $docIdStr = null;

    /**
     * ES的默认最大查询条目数
     * @var int
     */
    private int $maxSize = 10000;

    /**
     * @param array|string|null $hosts
     * @param string|null       $index
     */
    public function __construct(array|string|null $hosts = null, ?string $index = null)
    {
        // 如果未初始化$host $index 则使用继承着的$host $index
        if (!is_null($hosts)) {
            $this->hosts = $hosts;
        }
        if (!is_null($index)) {
            $this->index = $index;
        }
        $this->setDefaultParams();
    }

    /**
     * 获取ES客户端
     *
     * @return Client|null
     */
    public function client(): ?Client
    {
        return $this->clientBuilder();
    }

    /**
     * debug模式
     *
     * @return $this
     */
    public function debug(): ElasticsearchUtil
    {
        unset($this->params['client']['ignore']);
        return $this;
    }

    /**
     * 设置忽略项
     *
     * @param array $ignore
     *
     *  $ignore = [
     *    400, 404, 500
     *  ];
     *
     * @return $this
     */
    public function setClientIgnore(array $ignore): ElasticsearchUtil
    {
        $this->params['client']['ignore'] = $ignore;
        return $this;
    }

    // ===============  命令执行功能 - 开始  =========

    /**
     * 写入/更新一条记录
     *
     * @param string $id
     * @param array  $body
     *
     * @return bool
     */
    public function insert(string $id, array $body): bool
    {
        if (empty($body) || empty($id) || empty($this->index)) {
            return false;
        }
        $this->params['type'] = '_doc';
        $this->params['id'] = $id;
        $this->params['body'] = $body;

        $response = $this->client()->index($this->params);
        // 错误处理
        $this->responseErrorHandler($response);
        if ($response['result'] == 'created' || $response['result'] == 'updated') {
            return true;
        }
        return false;
    }

    /**
     * 写入/更新一条记录
     *
     * @param string $id
     * @param array  $body
     *
     * @return bool
     */
    public function update(string $id, array $body): bool
    {
        return $this->insert($id, $body);
    }

    /**
     * docId查询一条记录
     *  当前方法应该独立使用
     *
     * @param string $docId
     * @return array
     */
    public function find(string $docId): array
    {
        $this->params['id'] = $docId;
        $this->params['type'] = '_doc';
        // 兼容处理
        unset($this->params['body']);

        $response = $this->client()->getSource($this->params);
        $this->setDefaultParams();
        // 存在错误时可能是查询的数据不存在 返回空
        return isset($response['error']) && isset($response['status']) ? [] : $response;
    }

    /**
     * 获取一条数据
     * @param array $columns
     * @param ?string  $docId 不为Null时 以$docId为key返回doc_id的值
     * @return array
     */
    public function first(array $columns = [], ?string $docId = null): array
    {
        $this->params['body']['size'] = 1;

        // 设置返回字段
        if (!empty($columns)) {
            $this->select($columns);
        }
        $this->docIdStr = $docId;
        // 获取结果
        $response = $this->search($this->params);
        $this->setDefaultParams();
        // ES返回结果解析
        $data = $this->responseDecode($response);
        // 仅返回一条记录
        return empty($data) ? [] : $data[0];
    }

    /**
     * 获取全部数据 | 最多获取一万条数据
     * @param array $columns
     * @param ?string  $docId 不为Null时 以$docId为key返回doc_id的值
     * @return array
     */
    public function get(array $columns = [], ?string $docId = null): array
    {
        if (!isset($this->params['body']['size']) || $this->params['body']['size'] > $this->maxSize) {
            $this->params['body']['size'] = $this->maxSize;
        }
        // 设置返回字段
        if (!empty($columns)) {
            $this->select($columns);
        }
        $this->docIdStr = $docId;
        // 获取结果
        $response = $this->search($this->params);
        $this->setDefaultParams();
        // ES返回结果解析
        return $this->responseDecode($response);
    }

    /**
     * 获取分页数据 | 最多获取一万条数据
     * @param int   $page
     * @param int   $size
     * @param ?string  $docId 不为Null时 以$docId为key返回doc_id的值
     * @return array
     */
    public function forPage(int $page, int $size, ?string $docId = null): array
    {
        $form = 0;
        $number = 1;
        if ($size > 0) {
            $number = $size;
        }
        if ($page > 1) {
            $form = ($page - 1) * $number;
        }
        $this->params['body']['from'] = $form;
        $this->params['body']['size'] = $number;
        $this->docIdStr = $docId;
        // 获取结果
        $response = $this->search($this->params);
        $this->setDefaultParams();
        return [
            'total' => $response['hits']['total']['value'] ?? 0,
            'list' => $this->responseDecode($response)
        ];
    }

    /**
     * scroll查询全部记录
     *
     * 非脚本程序不推荐使用当前方法
     *
     * @param int $size 返回的记录条数 例 100
     * @param string $scroll 游标时间 例 10s
     * @param ?string  $docId 不为Null时 以$docId为key返回doc_id的值
     * @return array
     */
    public function all(int $size, string $scroll, ?string $docId = null): array
    {
        $this->docIdStr = $docId;
        $this->params['body']['size'] = $size;
        $this->params['scroll'] = $scroll;
        // 获取结果
        $response = $this->search($this->params);
        // ES返回结果解析
        $result[] = $this->responseDecode($response);

        // 使用游标查询全量数据
        $scrollId = $response['_scroll_id'];
        while (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
            $scrollId = $response['_scroll_id'];
            unset($response);
            // 使用游标查询
            $response = $this->client()->scroll([
                'scroll_id' => $scrollId,
                'scroll' => $scroll
            ]);
            // 错误处理
            $this->responseErrorHandler($response);
            // ES返回结果解析
            $result[] = $this->responseDecode($response);
        }
        // 移除游标
        $this->client()->clearScroll(['scroll_id' => $scrollId]);
        $this->setDefaultParams();
        if (empty($result)) {
            return [];
        }
        return array_reduce($result, 'array_merge', []);
    }

    /**
     * SDK的search方法
     *
     * @param array $params
     *
     * @return array
     */
    public function search(array $params): array
    {
        // 如果query为空 则删除query字段 避免异常报错
        if (isset($params['body']['query']) && empty($params['body']['query'])) {
            unset($params['body']['query']);
        }
        $response = $this->client()->search($params);
        // 错误处理
        $this->responseErrorHandler($response);
        return $response;
    }

    /**
     * 查询统计
     *
     * @return int
     */
    public function count(): int
    {
        $response = $this->client()->count($this->params);
        // 重置参数
        $this->setDefaultParams();
        $this->responseErrorHandler($response);
        // 仅返回统计数
        return $response['count'];
    }

    /**
     * group分组
     *  注意：当前方法返回值是原始的es查询结果
     *  注意：聚合结果是近似的，不一定完全正确；size值需要尽可能的大，才能覆盖全部查询
     * @param array $agg
     *   1. 以classId分组查询10条【默认查询10条记录，如需查询跟多则修改size值】
     *      $agg = [
     *          'response_field_name' => [
     *              'terms' => [
     *                  'field' => 'classId',
     *                  'size' => 10
     *               ]
     *          ]
     *      ];
     * @return array
     */
    public function aggregation(array $agg): array
    {
        if (!empty($agg)) {
            // 聚合查询时默认不获取列表，仅聚合数据结果，如需查询数据，使用size()方法
            $this->params['body']['size'] = 0;
            $this->params['body']['from'] = 0;
            $this->params['body']['aggregations'] = $agg;
        }
        $response = $this->search($this->params);
        $this->setDefaultParams();
        $this->responseErrorHandler($response);
        return $response;
    }

    /**
     * distinct
     *  仅对keyword类型和数字类型字段有效
     * @param array $collapse
     * $collapse = [
     *  'field' => 'userName',
     *  'inner_hits' => [
     *        //inner_hits的折叠字段名
     *       'name' => 'collapse_name',
     *       // 默认为false,如果存在一些数据没有折叠字段的会报错,设置为true可以避免类似的报错
     *       'ignore_unmapped' => true,
     *       // 如相同字段仅返回头条,所以两个参数均设置为0,如果有需求折叠列表的可以通过这里控制
     *       'from' => 0,
     *       'size' => 0,
     *       // 折叠列表的排序
     *       'sort' => [
     *            [
     *            'syllabusId' => ['order' => 'desc'], // 例：syllabusId倒叙
     *           ]
     *        ]
     *   ]
     * ];
     * @return array
     */
    public function collapse(array $collapse): array
    {
        if (!empty($collapse)) {
            $this->params['body']['collapse'] = $collapse;
        }
        // 获取结果
        $response = $this->search($this->params);
        $this->setDefaultParams();
        $this->responseErrorHandler($response);
        return $response;
    }

    /**
     * 返回受影响的行数
     * 失败时返回false
     *  注意：如果mapping中的字段不存在于$update中，则新建字段，并默认为当前值的类型
     * @param array $update
     * @param bool  $isUseBuilder
     * @return false|int
     */
    public function updateByQuery(array $update, bool $isUseBuilder = true): bool|int
    {
        if (empty($this->params['body']['query'])) {
            return false;
        }
        if ($isUseBuilder) {
            $update = $this->updateScriptBuilder($update);
        }
        $this->params['body']['script'] = $update;
        $response = $this->client()->updateByQuery($this->params);
        $this->setDefaultParams();
        $this->responseErrorHandler($response);
        /**
         * $response：数组格式
         * [
         *   'total' => 2, // 符合条件的总记录数,
         *   'updated' => 2, // 更新的条数
         *   'deleted' => 0, // 删除的条数
         * ]
         *
         * 没有异常信息 则认为更新成功
         */
        return $response['updated'];
    }

    /**
     * 根据docId更新|无需使用filter、must等方法
     *  注意：如果mapping中的字段不存在于$update中，则新建字段，并默认为当前值的类型
     * @param string $docId
     * @param array  $update
     * @param bool   $isDoc body的格式 仅支持doc和script
     * @return bool
     */
    public function updateByDocId(string $docId, array $update, bool $isDoc = true): bool
    {
        if (empty($docId) || empty($update)) {
            return false;
        }
        unset($this->params['body']['query']);

        if ($isDoc) {
            $this->params['body']['doc'] = $update;
        } else {
            $this->params['body']['script'] = $update;
        }
        $this->params['type'] = '_doc';
        $this->params['id'] = $docId;
        $response = $this->client()->update($this->params);
        $this->setDefaultParams();
        $this->responseErrorHandler($response);
        /*
         * 返回值result 等于 updated 表示更新成功；等于 noop 表示未更新
         * docId不存在或者其他情况 抛出异常
         */
        return isset($response['result']);
    }

    /**
     * @param string $docId
     * @return bool
     */
    public function deleteByDocId(string $docId): bool
    {
        if (empty($docId)) {
            return false;
        }
        unset($this->params['body']);
        $this->params['type'] = '_doc';
        $this->params['id'] = $docId;

        $response = $this->client()->delete($this->params);
        $this->setDefaultParams();
        $this->responseErrorHandler($response);
        /*
         * 返回值result 等于 deleted 表示删除成功；等于 not_found 表示未发现需要删除的数据
         * docId不存在或者其他情况 抛出异常
         */
        return isset($response['result']);
    }

    /**
     * @param array $response
     * @return void
     */
    private function responseErrorHandler(array $response): void
    {
        if (isset($response['error'])) {
            if (!empty($response['error']['root_cause'][0]['reason'])) {
                throw new ElasticsearchException(
                    $response['error']['root_cause'][0]['reason']
                );
            }
            throw new ElasticsearchException($response['error']['reason']);
        }
    }

    /**
     * 查询结果集整理
     *
     * @param array $response
     * @return array
     */
    private function responseDecode(array $response): array
    {
        $response = $response['hits']['hits'] ?? [];
        return array_map(function ($item) {
            if (!is_null($this->docIdStr)) {
                $item['_source'][$this->docIdStr] = $item['_id'];
            }
            return $item['_source'];
        }, $response);
    }

    // ================ 命令执行功能 - 结束  ============

    // ================  命令条件 - 开始 end ============

    /**
     * 布尔过滤器-filter条件
     *  所有分句都必须匹配 与AND相同 与must()方法相同
     * @param array $filter
     *    $filter允许两种格式：
     *    1. 有序索引数组：
     *      $filter = [
     *          ['match' => ['classId' => 239403]],
     *          ['match' => ['type' => 1]]
     *      ];
     *    2. 关联数组：
     *      $filter = ['match' => ['syllabusId' => 8912666250]]
     * @return $this
     */
    public function filter(array $filter): ElasticsearchUtil
    {
        if (!empty($filter)) {
            $this->boolQueryBuilder('filter', $filter);
        }
        return $this;
    }

    /**
     * 布尔过滤器-must条件
     *  所有分句都必须匹配 与AND相同 与filter()方法相同
     *  推荐使用filter()方法
     * @param array $must
     *    1. 有序索引数组：
     *      $must = [
     *          ['match' => ['classId' => 239403]],
     *          ['match' => ['userName' => 'app_ztk1913981565']]
     *      ];
     *    2. 关联数组：
     *    $must = ['match' => ['syllabusId' => 8912666250]]
     * @return $this
     */
    public function must(array $must): ElasticsearchUtil
    {
        if (!empty($must)) {
            $this->boolQueryBuilder('must', $must);
        }
        return $this;
    }

    /**
     * 布尔过滤器-must-not条件
     *  所有分句都必须不匹配，与 NOT 相同
     * @param array $not
     *    1. 有序索引数组：
     *      $not = [
     *          ['match' => ['classId' => 239403]],
     *          ['match' => ['userName' => 'app_ztk1913981565']]
     *      ];
     *    2. 关联数组：
     *      $not = ['match' => ['syllabusId' => 8912666250]]
     * @return $this
     */
    public function mustNot(array $not): ElasticsearchUtil
    {
        if (!empty($not)) {
            $this->boolQueryBuilder('must_not', $not);
        }
        return $this;
    }

    /**
     * 布尔过滤器-should条件
     *  至少有一个分句匹配，与 OR 相同
     *  可能需要配合 shouldMatch() 一起使用
     * @param array $should
     *    1. 有序索引数组：
     *      $should = [
     *          ['match' => ['classId' => 239403]],
     *          ['match' => ['userName' => 'app_ztk1913981565']]
     *      ];
     *    2. 关联数组：
     *      $should = ['match' => ['syllabusId' => 8912666250]]
     * @return $this
     */
    public function should(array $should): ElasticsearchUtil
    {
        if (!empty($should)) {
            $this->boolQueryBuilder('should', $should);
        }
        return $this;
    }

    /**
     * 匹配的should查询条件中至少$miniNum个条件
     *  should和must结合使用时
     *    minimum_should_match = 0 时，查询条件为(must); (should)不生效
     *    minimum_should_match = 1 时，查询条件为(must) and (should_1 or should_2 or should_3)
     *    ...
     * @param int $miniNum
     * @return $this
     */
    public function shouldMatch(int $miniNum = 0): ElasticsearchUtil
    {
        if (!isset($this->params['body']['query']['bool'])) {
            $this->params['body']['query']['bool'] = [];
        }
        $this->params['body']['query']['bool']['minimum_should_match'] = $miniNum;
        return $this;
    }

    /**
     * 查询条件
     * @param array $query
     *  $query = [
     *   'bool' => [
     *       'must' => [
     *           ['match' => ['username' => 'Tom']],        // username = 'Tom'
     *           ['terms' => ['classId' => [1, 2, 3]]]],    // classId in (1, 2, 3)
     *           ['range' => ['type' => ['gt' => 1]]],      // type > 1
     *       ]
     *    ]
     *  ];
     * @return $this
     */
    public function query(array $query): ElasticsearchUtil
    {
        if (!empty($query)) {
            $this->params['body']['query'] = $query;
        }
        return $this;
    }

    /**
     * 排序
     *
     * $sort 是二维数组
     *
     * @param array $sort
     *  $sort = [
     *      ['updated_at' => 'asc']
     *   ];
     * @return $this
     */
    public function sort(array $sort): ElasticsearchUtil
    {
        if (!empty($sort)) {
            $this->params['body']['sort'] = $sort;
        }
        return $this;
    }

    /**
     * 从指定的位置查询指定条数的记录
     *
     * @param int $from
     * @param int $size
     *
     * @return $this
     */
    public function limit(int $from, int $size): ElasticsearchUtil
    {
        if ($from >= 0) {
            $this->params['body']['from'] = $from;
        }
        if ($size >= 0) {
            $this->params['body']['size'] = $size;
        }
        return $this;
    }

    /**
     * 从指定的位置开始查询
     *
     * @param int $from
     * @return $this
     */
    public function from(int $from): ElasticsearchUtil
    {
        if ($from >= 0) {
            $this->params['body']['from'] = $from;
        }
        return $this;
    }

    /**
     * 查询指定的条数
     *
     * @param int $size
     * @return $this
     */
    public function size(int $size): ElasticsearchUtil
    {
        if ($size >= 0) {
            $this->params['body']['size'] = $size;
        }
        return $this;
    }

    /**
     * 查询的字段
     *
     * @param array $columns
     * @return $this
     */
    public function select(array $columns): ElasticsearchUtil
    {
        if (!empty($columns)) {
            $this->params['_source'] = $columns;
        }
        return $this;
    }

    /**
     * bool查询条件组装
     *
     * @param string $field
     * @param array  $params
     *
     * @return void
     */
    private function boolQueryBuilder(string $field, array $params): void
    {
        if (empty($this->params['body']['query']['bool'][$field])) {
            $this->params['body']['query']['bool'][$field] = [];
        }
        // 则默认为list是三维数组
        if (array_is_list($params)) {
            $this->params['body']['query']['bool'][$field] = array_merge(
                $this->params['body']['query']['bool'][$field], $params
            );
        } else {
            $this->params['body']['query']['bool'][$field][] = $params;
        }
    }

    /**
     * 创建ES客户端
     *
     * @return Client|null
     */
    private function clientBuilder(): ?Client
    {
        if (empty($this->hosts)) {
            throw new ElasticsearchException('missing hosts...');
        }
        if (!empty($this->index)) {
            $this->params['index'] = $this->index;
        }
        if (is_null($this->client) || $this->client->ping() == false) {
            if (function_exists('\config') && is_string($this->hosts)) {
                $hosts = \config($this->hosts);
            } else {
                $hosts = $this->hosts;
            }
            return ClientBuilder::create()->setHosts($hosts)->build();
        }
        return $this->client;
    }

    /**
     * 构建更新脚本
     *
     * @param array $update
     *
     * @return array
     */
    private function updateScriptBuilder(array $update): array
    {
        $inline = '';
        foreach ($update as $key => $value) {
            if (!is_string($key)) {
                throw new ElasticsearchException('field ' . $key . ' is must string');
            }
            $value = is_string($value) ? "'$value'" : $value;
            $inline .= "ctx._source.$key=$value;";
        }
        return [
            'inline' => $inline,
            'lang' => 'painless'
        ];
    }

    /**
     * 设置参数
     *
     * @return void
     */
    private function setDefaultParams(): void
    {
        $this->params = [
            'index' => $this->index,
            'body' => [
                'query' => [],
            ],
            'client' => [
                'ignore' => [400, 404], // 忽略错误
                'timeout' => $this->timeout,
                'connect_timeout' => $this->connectTimeout
            ]
        ];
    }
}