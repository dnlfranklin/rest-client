<?php
/**
* @method mixed get_url()
*
* @method mixed get_content_type()
*
* @method mixed get_http_code()
*
* @method mixed get_header_size()
*
* @method mixed get_request_size()
*
* @method mixed get_filetime()
*
* @method mixed get_ssl_verify_result()
*
* @method mixed get_redirect_count()
*
* @method mixed get_total_time()
*
* @method mixed get_namelookup_time()
*
* @method mixed get_connect_time()
*
* @method mixed get_pretransfer_time()
*
* @method mixed get_size_upload()
*
* @method mixed get_size_download()
*
* @method mixed get_speed_download()
*
* @method mixed get_speed_upload()
*
* @method mixed get_download_content_length()
*
* @method mixed get_upload_content_length()
*
* @method mixed get_starttransfer_time()
*
* @method mixed get_redirect_time()
*
* @method mixed get_redirect_url()
*
* @method mixed get_primary_ip()
*
* @method mixed get_certinfo()
*
* @method mixed get_primary_port()
*
* @method mixed get_local_ip()
*
* @method mixed get_local_port()
*
* @method mixed get_http_version()
*
* @method mixed get_protocol()
*
* @method mixed get_ssl_verifyresult()
*
* @method mixed get_scheme()
*
* @method mixed get_appconnect_time_us()
*
* @method mixed get_connect_time_us()
*
* @method mixed get_namelookup_time_us()
*
* @method mixed get_pretransfer_time_us()
*
* @method mixed get_redirect_time_us()
*
* @method mixed get_starttransfer_time_us()
*
* @method mixed get_total_time_us()
*
* @method mixed get_request_method()
*
* @method mixed get_request_header()
*
* @method mixed get_request_parameters()
*     
* @method mixed get_errno()
*
* @method mixed get_errmessage()
*
* @method mixed get_content()
*
* @method mixed get_headers()
*
* @method mixed get_body()
*
* @method mixed get_data()
*/

namespace RestClient;


class Response {    
    
    const DECODE_ALIAS = [
        'response', 
        'data',
        'decode', 
        'decode_body', 
        'decode_response', 
        'decode_data'
    ];

    private $status_line;
    private $headers;
    private $body;

    public function __construct(
        private \RestClient\cURL\Handler $ch, 
        private ?string $format = null
    ){
        $this->parse_response($ch->content);
    }

    public function __call($method, $arguments){
        if(str_starts_with($method, 'get_')){
            $var = substr($method, 4);
            
            if(!empty($var)){
                return $this->{$var};
            }
        }    
    }

    public function __get(string $name){
        if(in_array($name, self::DECODE_ALIAS)){
            return $this->decode();
        }
        
        if(property_exists($this, $name)){
            return $this->{$name};
        }

        return $this->ch->{$name};    
    }

    public function getHeaderLine(string $name){
        $header = strtolower($name);

        if(array_key_exists($header, $this->headers)){
            return $this->headers[$header];
        }
    }

    private function parse_response(string $response){
        $headers = [];
        $response_status_lines = [];
        $status_line = null;

        $line = strtok($response, "\n");        
        do {
            if(strlen(trim($line)) == 0){
                // Since we tokenize on \n, use the remaining \r to detect empty lines.
                if(count($headers) > 0) break; // Must be the newline after headers, move on to response body
            }
            elseif(strpos($line, 'HTTP') === 0){
                // One or more HTTP status lines
                $response_status_lines[] = trim($line);

                if (preg_match('/HTTP\/[\d.]+\s(\d+)\s(.+)/', $line, $matches)) {
                    $status_line = $matches[1] . ' ' . trim($matches[2]);
                }
            }
            else { 
                // Has to be a header
                list($key, $value) = explode(':', $line, 2);
                $key = trim(strtolower($key));
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
        
        $this->headers = $headers;
        $this->body = strtok("");
        $this->status_line = $status_line;

        // Extract format from response content-type header. 
        if( empty($this->format) && array_key_exists('content-type', $this->headers)) {
            if( preg_match("/(\w+)\/(\w+)(;[.+])?/", $this->headers['content-type'], $matches) ){
                $this->format = $matches[2];
            }                
        } 
    }

    public function decode(Callable $callable = null){
        if(!empty($callable)){
            return call_user_func($callable, $this->body);
        }
        
        switch(strtolower($this->format)) {
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