<?php

namespace Isobaric\Utils;

class StringUtil
{
    /**
     * 验证字符串是否是邮箱
     * @param string|null $email
     * @return bool
     */
    public static function isEmail(?string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
