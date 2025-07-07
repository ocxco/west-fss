<?php

namespace Chasel\WestFss;

class FSSSign
{
    public static function signRestApi(array $params): string
    {
        $signStr = sprintf("%s&%s&%s",
            $params['Method'],
            $params['URI'],
            $params['Date']
        );
        if (!empty($params['Content-MD5'])) {
            $signStr .= "&{$params['Content-MD5']}";
        }
        $sign = base64_encode(hash_hmac('sha1', $signStr, $params['Password'], true));
        return sprintf("%s %s:%s", 'WESTYUN', $params['Operator'], $sign);
    }
}