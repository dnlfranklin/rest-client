<?php

namespace RestClient;

use RestClient\Extension\LogExtension;
use RestClient\Log\LogData;

class Request {
    // Alias for the last RestClientResponse received
    const LAST_RESPONSE_ALIAS = ['response', 'last_response'];

    private $options; // Custom options
    private $headers; // Static headers for all requests
    private $parameters; //Static parameters for all requests
    private $curl_options; //Custom curl options
    private $response_history; // History of responses received.
    private $logger; // record log of requests sent along with the responses received 
    
    public function __construct(Array $options=[], Array $headers = [], Array $parameters = [], Array $curl_options= []){
        $default_options = [
            'build_indexed_queries' => FALSE,
            'user_agent' => 'PHP RestClientRequest/1.0.0',
            'base_url' => NULL, // Base URL to compose all endpoints sent in this object instance
            'format' => NULL, // Defines the format of the request body that will be sent and processes the data along with the inclusion of the appropriate content-type. Currently supported formats: json, xml e form
            'username' => NULL, // username for basic authenticate
            'password' => NULL, // passoword for basic authenticate
            'token' => NULL, // JWT token for bearer authenticate
            'idempotencykey' => NULL, // Defines whether the request will send an idempotency key in the header. The attribute name must be passed and the GUID is automatically generated 
            'info' => NULL, // Additional information that is stored in the response and log to facilitate a grouped search or history of the request context
            'response_format' => NULL // Defines the format of the response body that will be received. If it is not passed, it will be automatically detected. Currently supported formats: json, xml and html
        ];

        $default_curl_options = [
            CURLOPT_HEADER => TRUE, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_SSL_VERIFYHOST => FALSE,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_CONNECTTIMEOUT => 10
        ];
        $this->options = array_merge($default_options, $options);
        $this->headers = $headers;
        $this->parameters = $parameters;
        $this->curl_options = $default_curl_options + $curl_options;
        $this->response_history = [];
    }

    public function __get($key){
        if( in_array($key, self::LAST_RESPONSE_ALIAS) ){
            return $this->getResponse();
        }
    }
    
    public function setOption(string $key, $value){
        $this->options[$key] = $value;
    }

    public function setHeader(string $key, $value){
        $this->headers[$key] = $value;
    }

    public function setParameter(string $key, $value){
        $this->parameters[$key] = $value;
    }

    public function setCurlOption($key, $value){
        $this->curl_options[$key] = $value;
    }
    
    public function setIdempotencyKeyName(string $name){
        $this->setOption('idempotencykey', $name);
    }

    public function setFormat(string $format){
        $this->setOption('format', $format);
    }
    
    public function setResponseFormat(string $format){
        $this->setOption('response_format', $format);
    }

    public function setCurlSSLCert($cert, $key = null){
        $this->curl_options[CURLOPT_SSLCERT] = $cert;  
        
        if( !empty($key) ){
            $this->curl_options[CURLOPT_SSLKEY] = $key;    
        }
    }
    
    public function enableLog(LogExtension $log){
        $this->logger = $log;        
    }

    //If $key = null returns the last RestClientResponse object received
    public function getResponse( int $key = null ){
        if( !is_null($key) ){
            if( array_key_exists($key, $this->response_history) ){
                return $this->response_history[$key];    
            }
            return null;
        }
        
        return empty($this->response_history) ? null : end($this->response_history);    
    }

    public function getResponseHistory(){                
        return $this->response_history;
    }

    // Request methods:
    public function get(string $url, Array|string $parameters=[], Array $headers=[]){
        return $this->execute($url, 'GET', $parameters, $headers);
    }
    
    public function post(string $url, Array|string $parameters=[], Array $headers=[]){
        return $this->execute($url, 'POST', $parameters, $headers);
    }
    
    public function put(string $url, Array|string $parameters=[], Array $headers=[]){
        return $this->execute($url, 'PUT', $parameters, $headers);
    }
    
    public function patch(string $url, Array|string $parameters=[], Array $headers=[]){
        return $this->execute($url, 'PATCH', $parameters, $headers);
    }
    
    public function delete(string $url, Array|string $parameters=[], Array $headers=[]){
        return $this->execute($url, 'DELETE', $parameters, $headers);
    }
    
    public function head(string $url, Array|string $parameters=[], Array $headers=[]){
        return $this->execute($url, 'HEAD', $parameters, $headers);
    }    
    
    public function execute(string $url, string $method='GET', Array|string $parameters=[], Array $headers=[]) :Response {
        $ch = curl_init();
        $curlopt = $this->curl_options;
        $curlopt[CURLOPT_USERAGENT] = $this->options['user_agent'];
        
        if($this->options['username'] && $this->options['password']){
            $curlopt[CURLOPT_USERPWD] = sprintf("%s:%s", $this->options['username'], $this->options['password']);
        }

        $format = $this->options['format'] ? strtolower($this->options['format']) : '';

        switch($format){
            case 'json':
                if( is_array($parameters) ){
                    $parameters_string = empty($parameters) ? '' : json_encode($parameters);
                }
                else{
                    $parameters_string = (string) $parameters;
                }
                $headers['Content-Type'] = "application/json";
                break;
            
            case 'xml':
                if( is_array($parameters) ){
                    if(count($parameters) == 1){
                        $root = array_key_first($parameters);
                        $array_parameters = $parameters[$root];    
                    }else{
                        $root = 'root';
                        $array_parameters = $parameters;
                    }

                    $xml = new SimpleXMLElement("<{$root}/>");
                    array_walk_recursive($array_parameters, [$xml, 'addChild']);

                    $parameters_string = $xml->asXML();
                }
                else{
                    $parameters_string = (string) $parameters;
                }
                $headers['Content-Type'] = "application/xml";
                break;
            
            case 'form':
                if( is_array($parameters) ){
                    $parameters_string = http_build_query($parameters);
                }
                else{
                    $parameters_string = (string) $parameters;    
                }
                $headers['Content-Type'] = "application/x-www-form-urlencoded";
                break;

            default:
                // Allow passing parameters as a pre-encoded string (or something that
                // allows casting to a string). Parameters passed as strings will not be
                // merged with parameters specified in the default options.
                if(is_array($parameters)){
                    $parameters = array_merge($this->parameters, $parameters);
                    $parameters_string = http_build_query($parameters);                    
                    
                    // http_build_query automatically adds an array index to repeated
                    // parameters which is not desirable on most systems. This hack
                    // reverts "key[0]=foo&key[1]=bar" to "key[]=foo&key[]=bar"
                    if(!$this->options['build_indexed_queries']){
                        $parameters_string = preg_replace("/%5B[0-9]+%5D=/simU", "%5B%5D=", $parameters_string);
                    }
                }
                else{
                    $parameters_string = (string) $parameters;
                }
        }  
        
        if(strtoupper($method) == 'POST'){
            $curlopt[CURLOPT_POST] = TRUE;
            $curlopt[CURLOPT_POSTFIELDS] = $parameters_string;
        }
        elseif(strtoupper($method) != 'GET'){
            $curlopt[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
            $curlopt[CURLOPT_POSTFIELDS] = $parameters_string;
        }
        elseif($parameters_string){
            $url.= strpos($this->url, '?') ? '&' : '?';
            $url.= $parameters_string;
        }
        
        if($this->options['base_url']){
            if($url[0] != '/' && substr($this->options['base_url'], -1) != '/'){
                $url = '/' . $url;
            }
            $url = $this->options['base_url'] . $url;
        }        
        $curlopt[CURLOPT_URL] = $url;

        if( $this->options['idempotencykey'] ){
            $idempotencyvalue = strtoupper(implode('-', [
                bin2hex(random_bytes(4)),
                bin2hex(random_bytes(2)),
                bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)) . bin2hex(random_bytes(1)),
                bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)) . bin2hex(random_bytes(1)),
                bin2hex(random_bytes(6))
            ]));

            $headers[ $this->options['idempotencykey'] ] = $idempotencyvalue;
        }

        if(count($this->headers) || count($headers) || !empty($this->options['token'])){
            $curlopt[CURLOPT_HTTPHEADER] = [];

            $header_authorization = [];
            if( $this->options['token'] ){
                $header_authorization['authorization'] = 'Bearer '.$this->options['token'];
            }

            $headers = array_merge($header_authorization, $this->headers, $headers);
            foreach($headers as $key => $values){
                foreach(is_array($values)? $values : [$values] as $value){
                    $curlopt[CURLOPT_HTTPHEADER][] = sprintf("%s:%s", $key, $value);
                }
            }
        }
        
        curl_setopt_array($ch, $curlopt);
        
        $request = new RequestParams($url, strtoupper($method), $parameters, $headers, $curlopt);
        $request->idempotencykey = $idempotencyvalue ?? null;
        $request->info = $this->options['info'];                
        
        $start_time = microtime(true);
        $curl_response = curl_exec($ch);
        $end_time = microtime(true);
        
        $request->execution_time = ($end_time - $start_time);

        $response = new Response;
        $response->setCode(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        $response->setInfo((object) curl_getinfo($ch));
        $response->setError(curl_error($ch));
        $response->setFormat($this->options['response_format']);
        $response->setRequest($request);
        $response->parse_response($curl_response);
        
        curl_close($ch);

        empty($this->response_history) ? $this->response_history[1] = $response : $this->response_history[] = $response;

        if($this->logger){          
            $logdata = new LogData;
            $logdata->parse($response);  
            
            $this->logger->register($logdata);
        }

        return $response;
    }
    
    public static function run(
        string $url, 
        string $method = 'GET', 
        Array|string $parameters = [], 
        Array $headers = [], 
        Array $options = [], 
        Array $curl_options = [], 
        LogExtension $log = null)
    {
        $client = new self($options, [], [], $curl_options);        
        
        if($log){
            $client->enableLog($log);
        }
        
        return $client->execute($url, $method, $parameters, $headers);
    }
    
}


