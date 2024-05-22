<?php

namespace RestClient;

class Response {
    const BODY_ALIAS = ['response', 'data'];
    const BODY_DECODE_ALIAS = [
        'decode', 
        'decode_body', 
        'decode_response', 
        'decode_data'
    ];

    private $request;
    private $code;
    private $info;
    private $error;
    private $format;
    private $headers;
    private $body;

    public function __get($key){
        if( in_array($key, self::BODY_ALIAS) ){
            return $this->body;
        }

        if( in_array($key, self::BODY_DECODE_ALIAS) ){
            return $this->decode();
        }
        
        return $this->{$key};    
    }

    public function setCode($code){
        $this->code = $code;
    }

    public function setInfo($info){
        $this->info = $info;
    }

    public function setError($error){
        $this->error = $error;
    }

    public function setFormat($format){
        $this->format = $format;
    }

    public function setRequest(RequestParams $request){
        $this->request = $request;
    }

    public function parse_response($response){
        $headers = [];
        $response_status_lines = [];

        $line = strtok($response, "\n");        
        do {
            if(strlen(trim($line)) == 0){
                // Since we tokenize on \n, use the remaining \r to detect empty lines.
                if(count($headers) > 0) break; // Must be the newline after headers, move on to response body
            }
            elseif(strpos($line, 'HTTP') === 0){
                // One or more HTTP status lines
                $response_status_lines[] = trim($line);
            }
            else { 
                // Has to be a header
                list($key, $value) = explode(':', $line, 2);
                $key = trim(strtolower(str_replace('-', '_', $key)));
                $value = trim($value);
                
                if(empty($headers[$key])){
                    $headers[$key] = $value;
                }
                elseif(is_array($headers[$key])){
                    $headers[$key][] = $value;
                }
                else{
                    $headers[$key] = [$headers[$key], $value];
                }                
            }
        } 
        while($line = strtok("\n"));
        
        $this->headers = (object) $headers;
        $this->body = strtok("");

        // Extract format from response content-type header. 
        if( empty($this->format) && !empty($this->headers->content_type)) {
            if( preg_match("/(\w+)\/(\w+)(;[.+])?/", $this->headers->content_type, $matches) ){
                $this->setFormat($matches[2]);
            }                
        } 
    }

    public function decode( Callable $callable = null ){
        if(!empty($callable)){
            return call_user_func($callable, $this->body);
        }
        
        switch($this->format) {
            case 'json':
                return json_decode($this->body, true);
            case 'xml':
                $xml = simplexml_load_string($this->body);
                return json_decode(json_encode((array) $xml));
            default:
                return $this->body;                
        }
    }

}