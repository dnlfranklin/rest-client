<?php

namespace RestClient\Log;

use RestClient\Response;

class LogData{

    public $request_id;
    public $request_from_server;
    public $request_from_address;
    public $request_url;
    public $request_method;
    public $request_idempotencykey;
    public $request_headers;
    public $request_params;
    public $request_curl_options;
    public $request_execution_time;
    public $request_additional_info;
    public $response_code;
    public $response_error;
    public $response_format;
    public $response_headers;
    public $response_body;
    public $request_datetime;

    public function parse(Response $response){
        $request = $response->request;

        $this->request_id              = $request->id;
        $this->request_from_server     = $request->from_server;
        $this->request_from_address    = $request->from_address;
        $this->request_url             = $request->url;
        $this->request_method          = $request->method;
        $this->request_idempotencykey  = $request->idempotencykey;
        $this->request_headers         = $request->headers;
        $this->request_params          = $request->parameters;
        $this->request_curl_options    = $request->curl_options;
        $this->request_datetime        = $request->creation_at;
        $this->request_execution_time  = $request->execution_time;
        $this->request_additional_info = $request->info;
        $this->response_code           = $response->code;
        $this->response_error          = $response->error;
        $this->response_format         = $response->format;
        $this->response_headers        = $response->headers;
        $this->response_body           = $response->body;
    }

}