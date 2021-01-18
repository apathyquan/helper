<?php
/*
 * @Descripttion: 请求封装
 * @version: 
 * @Author: Quan
 * @Date: 2020-12-30 17:57:30
 * @LastEditors: Quan
 * @LastEditTime: 2021-01-18 11:28:38
 */

declare(strict_types=1);

namespace Quan\Helper\Http;


use Quan\Helper\Constants\HttpCode;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Context;
use Hyperf\Utils\Contracts\Arrayable;
use Hyperf\Utils\Contracts\Jsonable;
use Psr\Http\Message\ResponseInterface;


trait ResponseTrait
{

    protected $headers = [];
    protected $httpCode = HttpCode::HTTP_OK;

    private function message(string $message): object
    {
        return $this->response([
            'code' => $this->httpCode,
            'message' => $message
        ]);
    }

    public function fail(string $message = 'failed', int $code = HttpCode::HTTP_BAD_REQUEST, array $headers = []): object
    {

        return $this->setHttpCode($code)->addHttpHeaders($headers)->message($message);
    }



    public function success(string $message = 'ok', int  $code = HttpCode::HTTP_OK, array $headers = []): object
    {
        return $this->setHttpCode($code)->addHttpHeaders($headers)->message($message);
    }
    /**
     * @description: 
     * @Author: Quan
     * @param  array|object $data
     * @param string $message
     * @param  int $code
     * @return object ResponseInterface
     */
    public function data($data, string $message = 'ok', int $code = HttpCode::HTTP_OK, array $headers = []): object
    {
        $result = [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];
        return $this->setHttpCode($code)->addHttpHeaders($headers)->response($result);
    }
    /**
     * @description: 状态码设置
     * @Author: Quan
     * @param {*} int
     * @return {*}
     */
    private function setHttpCode(int $code): self
    {
        $this->httpCode = $code;
        return $this;
    }

    /**
     * 批量设置头部返回
     * @param array $headers    header数组：[key1 => value1, key2 => value2]
     * @return $this
     */
    private function addHttpHeaders(array $headers = []): self
    {
        $this->headers += $headers;
        return $this;
    }
    /**
     * @param null|array|Arrayable|Jsonable|string $response
     * @return ResponseInterface
     */
    private function response($responseData): ResponseInterface
    {
        $response = $this->setResponse();
        if (is_string($responseData)) {
            return  $response->withAddedHeader('content-type', 'text/plain')->withBody(new SwooleStream($responseData));
        }
        if (is_array($responseData) || $responseData instanceof Arrayable) {
            return $response->withAddedHeader('content-type', 'application/json')
                ->withBody(new SwooleStream(Json::encode($responseData)));
        }
        if ($responseData instanceof Jsonable) {
            return  $response->withAddedHeader('content-type', 'application/json')
                ->withBody(new SwooleStream((string)$responseData));
        }
        return  $response->withAddedHeader('content-type', 'text/plain')->withBody(new SwooleStream((string)$responseData));
    }

    /**
     * @description: 从协程上下文中取出响应对象并设置属性
     * @Author: Quan
     * @return mixed|ResponseInterface|null
     */
    protected function setResponse(): ResponseInterface
    {
        $response = Context::get(ResponseInterface::class);
        foreach ($this->headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        return $response->withStatus($this->httpCode);
    }
}
