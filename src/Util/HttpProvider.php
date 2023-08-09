<?php

namespace EvanPiAlert\Util;

use CurlHandle;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Класс для отправки вызовов по HTTP
 */
class HttpProvider {

    private string $cookie;
    public string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36';

    protected array $additionalHttpRequestHeaders = array();
    private string $http_header = '';


    /**
     * HttpProvider constructor.
     * @param array|string $cookie
     */
    public function __construct(array|string $cookie = '' ) {
        if ( is_array($cookie) ) {
            $string = '';
            foreach ($cookie as $name => $value ) {
                $string .= $name.'='.$value.'; ';
            }
            $cookie = $string;
        }
        $this->cookie = $cookie;
    }

    public function addHTTPRequestHeaders(string $header) : void {
        $this->additionalHttpRequestHeaders[] = $header;
    }

    protected function getHTTPRequestHeaders() : array {
        return array_merge(array(
            'Accept-Language: ru,en-us',
            "Cookie: ".$this->cookie,
            "Expect:",
        ), $this->additionalHttpRequestHeaders);
    }

    #[ArrayShape(['http_code' => "mixed", 'content_type' => "string", 'charset' => "string", 'http_header' => "string", 'http_body' => "string"])]
    public function requestPOST($url, $body, $content_type = 'application/json', $auth = false): array {
        $ch = $this->__initHTTPRequest($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $headers = $this->getHTTPRequestHeaders();
        $headers[] = "Content-Type: ".$content_type;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        return $this->__execHTTPRequest($ch, $auth);
    }

    #[ArrayShape(['http_code' => "mixed", 'content_type' => "string", 'charset' => "string", 'http_header' => "string", 'http_body' => "string"])]
    public function requestGET($url, $auth = false): array {
        $ch = $this->__initHTTPRequest($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHTTPRequestHeaders());
        return $this->__execHTTPRequest($ch, $auth);
    }


    /**
     * @param string $url
     * @param string $requestType DELETE, PUT
     * @param string $body
     * @param string $content_type
     * @param bool $auth
     * @return array
     */
    #[ArrayShape(['http_code' => "mixed", 'content_type' => "string", 'charset' => "string", 'http_header' => "string", 'http_body' => "string"])]
    public function requestCustom(string $url, string $requestType, string $body = '', string $content_type = 'application/json', bool $auth = false): array {
        $ch = $this->__initHTTPRequest($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHTTPRequestHeaders());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestType);
        if ( $body ) {
            $headers = $this->getHTTPRequestHeaders();
            $headers[] = "Content-Type: ".$content_type;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        return $this->__execHTTPRequest($ch, $auth);
    }

    /**
     * Получить cookie
     * @param string $needle
     * @return string
     */
    public function getHTTPResponseCookie(string $needle): string {
        $cookies = $this->getHTTPResponseCookies();
        return $cookies[$needle];
    }

    /**
     * Получить все cookie
     * @return array
     */
    public function getHTTPResponseCookies(): array {
        $cookiesRaw = $this->getHTTPResponseHeaders('set-cookie');
        $cookies = array();
        foreach ($cookiesRaw as $cookie) {
            list($cookie,) = explode(";", $cookie, 2);
            list($name, $value) = explode("=", $cookie, 2);
            $cookies[$name] = $value;
        }
        return $cookies;
    }

    /**
     * Вернуть все заголовки HTTP Response по названию
     * @param string $needle
     * @return string[]
     */
    public function getHTTPResponseHeaders(string $needle ): array {
        $result = array();
        $response = explode(PHP_EOL, $this->http_header);
        foreach ($response as $r) {
            // Match the header name up to ':', compare lower case
            if (stripos($r, $needle . ':') === 0) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                list($headerName, $headerValue) = explode(":", $r, 2);
                $result[] = trim($headerValue);
            }
        }
        return $result;
    }

    /**
     * Вернуть заголовок HTTP Response по названию. Если их несколько берет последний
     * @param string $needle
     * @return string|null
     */
    public function getHTTPResponseHeader(string $needle ): ?string {
        $result = $this->getHTTPResponseHeaders($needle);
        if ( $result ) {
            return array_pop($result);
        }
        return null;
    }

    private function __initHTTPRequest($url): CurlHandle|bool {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_REFERER, "https://google.com");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        //curl_setopt($ch,CURLOPT_AUTOREFERER,1);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 60);
        //curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        return $ch;
    }

    #[ArrayShape(['http_code' => "mixed", 'content_type' => "string", 'charset' => "string", 'http_header' => "string", 'http_body' => "string"])]
    private function __execHTTPRequest($ch, bool|string $auth): array {
        if ( $auth ) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth);
        }
        $http_answer = curl_exec($ch);
        list($this->http_header, $http_body) = explodeWithDefault("\r\n\r\n", $http_answer, 2);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        list($content_type, $charset) = explodeWithDefault(';', curl_getinfo($ch, CURLINFO_CONTENT_TYPE), 2, "");
        list(, $charset) = explodeWithDefault('=', $charset, 2);
        return array(
            'http_code'=>$http_code,
            'content_type'=>$content_type,
            'charset'=>$charset,
            'http_header'=>$this->http_header,
            'http_body'=>$http_body
        );
    }
}
