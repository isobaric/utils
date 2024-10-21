<?php

namespace Isobaric\Utils\Exceptions;

use Throwable;

class ElasticsearchException extends \RuntimeException
{
    /**
     * Construct the exception. Note: The message is NOT binary safe.
     * @link https://php.net/manual/en/exception.construct.php
     * @param string         $message  [optional] The Exception message to throw.
     * @param int            $code     [optional] The Exception code.
     * @param null|Throwable $previous [optional] The previous throwable used for the exception chaining.
     */
    public function __construct(string $message = '', int $code = ExceptionCode::ELASTICSEARCH_ERROR_CODE, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
