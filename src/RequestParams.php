<?php

namespace RestClient;

class RequestParams{

    public $id;
    public $from_server;
    public $from_address;
    public $url;
    public $method;
    public $headers;
    public $parameters;
    public $curl_options;
    public $creation_at;
    public $execution_time;
    public $idempotencykey;
    public $info;

    public function __construct(
        string $url, 
        string $method, 
        Array $parameters = [], 
        Array $headers = [], 
        Array $curl_options = []
    ){
        $this->id = strtoupper(implode('-', [
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)) . bin2hex(random_bytes(1)),
            bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)) . bin2hex(random_bytes(1)),
            bin2hex(random_bytes(6))
        ]));
        $this->from_server = $_SERVER['SERVER_NAME'] ?? '';
        $this->from_address = $_SERVER['SERVER_ADDR'] ?? '';
        $this->url = $url;
        $this->method = $method;
        $this->headers = $headers;
        $this->parameters = $parameters;
        $this->curl_options = $curl_options;
        $this->creation_at = date('Y-m-d H:i:s');
    }

}