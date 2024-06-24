<?php

namespace RestClient;


class Request {
    
    /** @var Array LAST_RESPONSE_ALIAS contains alias for the last Response received */
    const LAST_RESPONSE_ALIAS = ['response', 'last_response'];

    private $build_indexed_queries = false;
    private $base_url;
    private $request_format;
    private $response_format;
    private $headers = [];
    private $curl_options = [
        CURLOPT_HEADER         => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYHOST => FALSE,
        CURLOPT_SSL_VERIFYPEER => FALSE,
        CURLOPT_CONNECTTIMEOUT => 10,
    ];
    private $response_history = [];
    private $callback_success;
    private $callback_error;
    private $callback_complete;
    
    public function __get($key){
        if( in_array($key, self::LAST_RESPONSE_ALIAS) ){
            return $this->getResponse();
        }
    }

    /**
     * Base URL to compose all endpoints sent in this object instance
     */
    public function baseUrl(string $url):self {
        if(!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL format');
        }
        
        $this->base_url = $url;

        return $this;
    }

    /**
     * http_build_query automatically adds an array index to repeated 
     * parameters which is not desirable on most systems. 
     * This hack reverts "key[0]=foo&key[1]=bar" to "key[]=foo&key[]=bar"
     * It will only be applied in cases where the request format is not defined
     */    
    public function buildIndexedQueries(bool $option):self {
        $this->build_indexed_queries = $option;

        return $this;
    }

    /**
     * Format header authorization as basic
     */
    public function basic(string $username, string $password):self {
        $authorization = base64_encode(sprintf("%s:%s", $username, $password));

        $this->header('Authorization', 'Basic '.$authorization);

        return $this;
    }

    /**
     * Format header authorization as bearer
     */
    public function bearer(string $token):self {
        $this->header('Authorization', 'Bearer '.$token);

        return $this;
    }

    /**
     * Defines the format of the request body that will be sent and processes the data along with the inclusion of the appropriate content-type. 
     * Custom formats handlers: json, xml e form
     */
    public function requestFormat(string $format):self {
        $this->request_format = $format;

        return $this;
    }

    /**
     * Defines the format of the response body that will be received. If it is not passed, it will be automatically detected. 
     * Currently supported formats: json, xml and html
     */
    public function responseFormat(string $format):self {
        $this->response_format = $format;

        return $this;
    }

    /**
     * Includes HTTP user-agent header
     */
    public function userAgent(string $user_agent = 'PHP RestClient 1.0.1'):self {
        $this->curl_options[CURLOPT_USERAGENT] = $user_agent;
        
        return $this;
    }

    /**
     * Includes SSL client certificate
     */
    public function sslCert(string $cert_filename, string $key_filename = null):self {
        $this->curl_options[CURLOPT_SSLCERT] = $cert_filename;  
        
        if($key_filename){
            $this->curl_options[CURLOPT_SSLKEY] = $key_filename;    
        }

        return $this;
    }

    /**
     * Includes username and password to use in authentication
     */
    public function userPwd(string $user, string $pwd):self {
        $this->curl_options[CURLOPT_USERPWD] = sprintf("%s:%s", $user, $pwd);

        return $this;
    }

    /**
     * Add headers for all requests
     */
    public function header(string $key, $value):self {
        $this->headers[$key] = $value;

        return $this;
    }    

    /**
     * Add options for cURL request
     * @link https://www.php.net/manual/pt_BR/curl.constants.php
     */
    public function curlOption($key, $value):self {
        $this->curl_options[$key] = $value;

        return $this;
    }

    /**
     * Callback that will be executed if the request is successful
     */
    public function onSuccess(callable $callback):self {
        $this->callback_success = $callback;

        return $this;
    }
    
    /**
     * Callback that will be executed if a request error is found
     */
    public function onError(callable $callback):self {
        $this->callback_error = $callback;

        return $this;
    }

    /**
     * Callback that will be executed at the end of the request
     */
    public function onComplete(callable $callback):self {
        $this->callback_complete = $callback;

        return $this;
    }

    /**
     * Returns the last Response received
     */
    public function getResponse():?Response {
        return empty($this->response_history) ? null : end($this->response_history);    
    }

    /**
     * Returns an Array of received responses
     */
    public function getResponseHistory():Array {                
        return $this->response_history;
    }    

    /**
     * Executes an HTTP request with GET method
     */
    public function get(string $url, Array|string $parameters = [], Array $headers = []):Response {
        return $this->execute($url, 'GET', $parameters, $headers);
    }
    
    /**
     * Executes an HTTP request with POST method
     */
    public function post(string $url, Array|string $parameters = [], Array $headers = []):Response {
        return $this->execute($url, 'POST', $parameters, $headers);
    }
    
    /**
     * Executes an HTTP request with PUT method
     */
    public function put(string $url, Array|string $parameters = [], Array $headers = []):Response {
        return $this->execute($url, 'PUT', $parameters, $headers);
    }
    
    /**
     * Executes an HTTP request with PATCH method
     */
    public function patch(string $url, Array|string $parameters = [], Array $headers = []):Response {
        return $this->execute($url, 'PATCH', $parameters, $headers);
    }
    
    /**
     * Executes an HTTP request with DELETE method
     */
    public function delete(string $url, Array|string $parameters = [], Array $headers = []):Response {
        return $this->execute($url, 'DELETE', $parameters, $headers);
    }
    
    /**
     * Executes an HTTP request with HEAD method
     */
    public function head(string $url, Array|string $parameters = [], Array $headers = []):Response {
        return $this->execute($url, 'HEAD', $parameters, $headers);
    }    
    
    /**
     * Executes an HTTP request
     */
    public function execute(string $url, string $method='GET', Array|string $parameters = [], Array $headers = []) :Response {
        $curlopt = $this->curl_options;

        switch($this->request_format){
            case 'json':
                if(is_array($parameters)){
                    $parameters_string = empty($parameters) ? '' : json_encode($parameters);
                }
                else{
                    $parameters_string = (string) $parameters;
                }

                $headers['Content-Type'] = "application/json";
                break;            
            case 'xml':
                if(is_array($parameters)){
                    if(count($parameters) == 1){
                        $root = array_key_first($parameters);
                        $array_parameters = $parameters[$root];    
                    }else{
                        $root = 'root';
                        $array_parameters = $parameters;
                    }

                    $xml = new \SimpleXMLElement("<{$root}/>");
                    array_walk_recursive($array_parameters, [$xml, 'addChild']);

                    $parameters_string = $xml->asXML();
                }
                else{
                    $parameters_string = (string) $parameters;
                }

                $headers['Content-Type'] = "application/xml";
                break;
            
            case 'form':
                if(is_array($parameters)){
                    $parameters_string = http_build_query($parameters);
                }
                else{
                    $parameters_string = (string) $parameters;    
                }

                $headers['Content-Type'] = "application/x-www-form-urlencoded";
                break;

            default:
                if(is_array($parameters)){
                    $parameters_string = http_build_query($parameters);

                    if(!$this->build_indexed_queries){
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
            $url.= strpos($url, '?') !== false ? '&' : '?';
            $url.= $parameters_string;
        }

        if($this->base_url){
            $concat_url = trim($this->base_url, '/');
            $concat_url.= '/'.ltrim($url, '/');

            $url = $concat_url;
        }

        $curlopt[CURLOPT_URL] = $url;        

        $headers = array_merge($this->headers, $headers);

        if(!empty($headers)){
            $curlopt[CURLOPT_HTTPHEADER] = [];            
            
            foreach($headers as $key => $values){
                foreach(is_array($values) ? $values : [$values] as $value){
                    $curlopt[CURLOPT_HTTPHEADER][] = sprintf("%s:%s", $key, $value);
                }
            }
        }

        $response = new Response(new \RestClient\cURL\Handler($curlopt), $this->response_format);

        if($response->get_errno()){
            if($this->callback_error){
                call_user_func($this->callback_error, $response);        
            }
        }
        else{
            if($this->callback_success){
                call_user_func($this->callback_success, $response);        
            }
        }        

        if($this->callback_complete){
            call_user_func($this->callback_complete, $response);        
        }

        $this->response_history[] = $response;
        
        return $response;
    }
    
    public static function run(
        string $url, 
        string $method = 'GET', 
        Array|string $parameters = [], 
        Array $headers = [],
        Array $curl_options = [],
        string $request_format = null,
        string $response_format = null,
        bool $build_indexed_queries = false
    ):Response {
        $client = new self;
        $client->buildIndexedQueries($build_indexed_queries);

        if($request_format){
            $client->requestFormat($request_format);
        }

        if($response_format){
            $client->responseFormat($response_format);
        }

        foreach($curl_options as $curl_option_key => $curl_option_value){
            $client->curlOption($curl_option_key, $curl_option_value);
        }
        
        return $client->execute($url, $method, $parameters, $headers);
    }
    
}


