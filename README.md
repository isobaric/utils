<h4 align="center">Util Library For PHP</h4>

# 注意事项
1. 当前Util的最低PHP版本为8.1

# 版本说明

## v0.2.0
- 优化 ElasticsearchUtil
- 新增 ConnectionPoolUtil，并应用于 ElasticsearchUtil
- 新增 ProcessUtl
- DateUtil 新增格式化秒的方法
- 新增 HttpUtil
- 新增 GitUtil
- 移除 CurlUtil

## v0.1.0
1. 增加CURL工具：CurlUtil
2. 增加接口请求参数校验工具：RequestUtil
3. 增加数组工具：ArrayUtil
4. 增加数字及数字字符串工具：NumericUtil
5. 增加时间及日期工具：DateUtl
6. 增加Elasticsearch操作工具：ElasticsearchUtil
7. 增加单元测试模块：phpunit/phpunit
8. CurlUtil 默认设置 CURLOPT_RETURNTRANSFER = true 将curl_exec()获取的信息以字符串返回，而不是直接输出
