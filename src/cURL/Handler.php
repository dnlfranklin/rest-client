<?php

namespace RestClient\cURL;


class Handler{

    private $url;
    private $content_type;
    private $http_code;
    private $header_size;
    private $request_size;
    private $filetime;
    private $ssl_verify_result;
    private $redirect_count;
    private $total_time;
    private $namelookup_time;
    private $connect_time;
    private $pretransfer_time;
    private $size_upload;
    private $size_download;
    private $speed_download;
    private $speed_upload;
    private $download_content_length;
    private $upload_content_length;
    private $starttransfer_time;
    private $redirect_time;
    private $redirect_url;
    private $primary_ip;
    private $certinfo;
    private $primary_port;
    private $local_ip;
    private $local_port;
    private $http_version;
    private $protocol;
    private $ssl_verifyresult;
    private $scheme;
    private $appconnect_time_us;
    private $connect_time_us;
    private $namelookup_time_us;
    private $pretransfer_time_us;
    private $redirect_time_us;
    private $starttransfer_time_us;
    private $total_time_us;
    private $request_method;
    private $request_header;
    private $request_parameters;     
    private $errno;
    private $errmessage;
    private $content;

    public function __construct(private Array $options){
        $ch = curl_init();
        curl_setopt_array($ch, $this->options);
        $this->content    = curl_exec($ch);
        $this->errno      = curl_errno($ch);
        $this->errmessage = curl_error($ch);
        curl_close($ch);

        foreach(curl_getinfo($ch) as $key => $value){
            if(property_exists($this, $key)){
                $this->{$key} = $value;
            }    
        }

        $this->request_header = $this->options[CURLOPT_HTTPHEADER] ?? [];
        $this->request_method = empty($this->options[CURLOPT_POST]) ? $this->options[CURLOPT_CUSTOMREQUEST] ?? 'GET' : 'POST';

        $query_parameters = parse_url($this->url, PHP_URL_QUERY);
        parse_str($query_parameters ?? '', $this->request_parameters); 

        if(isset($this->options[CURLOPT_POSTFIELDS])){
            parse_str($this->options[CURLOPT_POSTFIELDS], $post_parameters); 
            
            $this->request_parameters+= $post_parameters;
        }        
    }

    public function __get(string $name){
        return $this->{$name};        
    }

}
