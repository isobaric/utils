<?php

namespace Horseloft\Utils;

use Horseloft\Utils\Helper\VerifyHelper;

class RequestUtil extends VerifyHelper
{
    /**
     * permit:
     *  格式：字符串
     *  有效值：must/nullable/empty
     *     must 参数必须存在，并且值不能为Null
     *     nullable 参数必须存在，允许参数值为Null
     *     empty 参数可以不存在，并允许参数值不存在或null
     *
     * type:
     *  格式：字符串
     *  有效值：bool/int/numeric/string/array/list/date/file/email/json/url/ip
     *  作用：限定参数的格式
     *
     * in:
     *  格式：array
     *  适用的type：int/numeric/string/date/email/url/ip(使用严格模式校验)
     *  作用：限定参数值必须是in数组中的某一个
     *
     * min:
     *  格式: int/float
     *  适用于type: int
     *      作用：以数字大小的方式限定的最小值(min值是int类型时有效)
     *  适用于type: numeric
     *      作用：以数字大小的方式限定的最小值(min值是int/float时有效)
     *  适用于type: string/email/json/url/ip
     *      作用：以长度计算的方式限定参数的长度最小值(min值是int类型且大于0时有效)
     *  适用于type: array/list
     *      作用：以总数计算的方式限定元素的最小数量，list的计算深度为1(min值是int类型且大于0时有效)
     *  适用于type: date
     *      作用：以时间计算的方式限定参数值的最小日期(min是合法的Date日期时有效)
     *  适用于type: file
     *      作用：以文件大小计算的方式限定文件最小size(min值是int类型且大于0时有效)
     *
     * max:
     *  格式：int/float
     *  适用于type: int
     *      作用：以数字大小的方式限定的最大值(max值是int类型时有效)
     *  适用于type: numeric
     *      作用：以数字大小的方式限定的最大值(max值是int/float时有效)
     *  适用于type: string/email/json/url/ip
     *      作用：以长度计算的方式限定参数的长度最大值(max值是int类型且大于0时有效)
     *  适用于type: array/list
     *      作用：以总数计算的方式限定元素的最大数量，list的计算深度为1(max值是int类型且大于0时有效)
     *  适用于type: date
     *      作用：以时间计算的方式限定参数值的最大日期(max是合法的Date日期时有效)
     *  适用于type: file
     *      作用：以文件大小计算的方式限定文件最大size(max值是int类型且大于0时有效)
     *
     * length:
     *  格式：int
     *  适用于type: int/numeric/string/date/email/json/url/ip
     *      作用：以字符串长度计算的方式限定参数值的长度等于length(length值是int类型且大于0时有效)
     *  适用于type: array/list
     *      作用：以总数计算的方式限定元素数等于length，list的计算深度为1(length值是int类型且大于0时有效)
     *
     * preg:
     *  格式: string
     *  适用于type: int/numeric/string/date/email/url/ip
     *      email/url/ip 优先使用preg校验
     *  作用：限定参数格式符合该正则
     *
     * format:
     *  格式：string
     *  默认值：Y-m-d H:i:s
     *  适用于type: date
     *  作用：限定参数的时间格式
     *
     * mime:
     *  格式：array
     *  适用于type: file
     *  作用：限定上传文件的mime格式
     *
     * message:
     *  格式：string
     *  适用于全部type
     *  作用：当verify()方法返回值等于false时，getMessage()方法返回当前message
     */
    public function __construct()
    {

    }

    /**
     * @return string
     */
    final public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * 参数校验
     *
     * @param array $params
     * @param array $rules
     *
     * @return bool
     */
    final public function verify(array $params, array $rules): bool
    {
        // 校验规则或者参数为空 不校验
        if (empty($rules) || empty($params)) {
            return true;
        }
        $this->verifyParams = $params;

        // 校验
        foreach ($rules as $field => $rule) {
            // 当前校验规则
            $this->verifyRule = $rule;
            // 字段值要求校验
            if (!$this->verifyPermit($field)) {
                return false;
            }
            // 参数类型校验
            if (!$this->verifyType()) {
                continue;
            }
            // 参数值校验
            if (!$this->verifyParamsValue()) {
                return false;
            }
        }
        return true;
    }
}
