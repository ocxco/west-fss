<?php

namespace Chasel\WestFss\Exceptions;

class FSSRequestException extends FSSClientException
{
    /**
     * @var int HTTP 状态码
     */
    private $statusCode;

    /**
     * FSSRequestException 构造函数
     *
     * @param int $statusCode HTTP 状态码
     * @param string $message 错误信息
     */
    public function __construct(int $statusCode, string $message)
    {
        $this->statusCode = $statusCode;
        parent::__construct(sprintf("Request failed with status %d: %s", $statusCode, $message));
    }

    /**
     * 获取 HTTP 状态码
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
