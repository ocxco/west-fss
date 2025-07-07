<?php

namespace Chasel\WestFss;

use Chasel\WestFss\Exceptions\FSSRequestException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;

class FSSClient
{
    /**
     * @var string 访问密钥 ID
     */
    private $accessKey;

    /**
     * @var string 访问密钥 Secret
     */
    private $secretKey;

    /**
     * @var string FSS 服务端点
     */
    private $endpoint;

    /**
     * @var HttpClient Guzzle HTTP 客户端实例
     */
    private $httpClient;

    /**
     * Client 构造函数
     *
     * @param string $accessKey 访问密钥 ID
     * @param string $secretKey 访问密钥 Secret
     * @param string $endpoint FSS 服务端点
     */
    public function __construct(string $accessKey, string $secretKey, string $endpoint)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->endpoint = $endpoint;
        $this->httpClient = new HttpClient([
            'base_uri' => $this->endpoint,
            'timeout'  => 30.0,
        ]);
    }
    
    /**
     * 设置HTTP客户端代理
     *
     * @param string|array $proxy 代理地址，可以是字符串或数组形式
     * 字符串格式: "http://proxy.example.com:8080"
     * 数组格式: [
     *     'http'  => 'http://proxy.example.com:8080',
     *     'https' => 'https://proxy.example.com:8080',
     *     'no'    => ['.example.com', 'localhost'] // 不需要代理的域名
     * ]
     */
    public function setProxy($proxy)
    {
        $config = $this->httpClient->getConfig();
        $config['proxy'] = $proxy;
        $this->httpClient = new HttpClient($config);
        return $this;
    }

    /**
     * 发起 HTTP 请求到 FSS 服务
     *
     * @param string $method 请求方法（GET、POST、PUT、DELETE 等）
     * @param string $path 请求路径
     * @param array $options 请求选项
     * @return array 响应数据
     * @throws FSSRequestException|\GuzzleHttp\Exception\GuzzleException
     */
    private function makeRequest(string $method, string $path, array $options = [])
    {
        $date = date('Y-m-d H:i:s');
        try {
            $signHead = [
                'Operator' => $this->accessKey,
                'Method' => $method,
                'URI' => $path,
                'Date' => $date,
                'Password' => base64_encode($this->secretKey),
//                'Content-MD5' => '',
            ];
            // 添加签名头部
            $signHead['Authorization'] = FSSSign::signRestApi($signHead);
            $options['headers'] = array_merge($signHead, $options['headers'] ?? []);
            $response = $this->httpClient->request($method, $path, $options);
            $body = $response->getBody()->getContents();
            return json_decode($body, true);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $body = $e->getResponse()->getBody()->getContents();
                throw new FSSRequestException($statusCode, $body);
            }
            throw new FSSRequestException(0, $e->getMessage());
        }
    }

    /**
     * 示例方法：获取存储桶列表
     * 根据文档 https://www.west.cn/paas/doc/fss/restapi.html 替换实际请求逻辑
     *
     * @return array 存储桶列表数据
     * @throws FSSRequestException
     */
    public function getBucketList()
    {
        $method = 'GET';
        $path = '/your-bucket-list-path'; // 替换为实际的获取存储桶列表的 API 路径
        return $this->makeRequest($method, $path);
    }

    /**
     * 上传文件到FSS服务
     *
     * @param string $bucketName 存储桶名称
     * @param string $targetPath 目标路径
     * @param string $localFile
     * @return array 响应数据
     * @throws FSSRequestException
     */
    public function uploadFile(string $bucketName, string $targetPath, string $localFile)
    {
        $method = 'PUT';
        $targetPath = urlencode("/$targetPath");
        $path = "/$bucketName$targetPath";

        
        if (!file_exists($localFile)) {
            throw new FSSRequestException(0, "文件不存在: {$localFile}");
        }
        
        $options = [
            'body' => fopen($localFile, 'r'),
            'headers' => [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => filesize($localFile),
                'x-west-automkdir' => "true",
            ]
        ];
        
        return $this->makeRequest($method, $path, $options);
    }
}