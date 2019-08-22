<?php
namespace FbCosts;

class FbApiExecutor
{
    var $ch;

    function __construct($proxy)
    { 
        $this->ch= curl_init();
        $proxy_addr = $proxy['proxy_address'] . ':' . $proxy['proxy_port'];
        $proxy_auth = $proxy['proxy_user'] . ':' . $proxy['proxy_password'];
        curl_setopt($this->ch, CURLOPT_PROXY, $proxy_addr);
        curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $proxy_auth);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 5);
    }

    function __destruct()
    {
        curl_close($this->ch);
    }
    
    function getFacebookApi($path, $data)
    {
        $url = 'https://graph.facebook.com/v3.0' . $path . '?' . http_build_query($data);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        $result = curl_exec($this->ch);
        usleep(500000);
        return json_decode($result, TRUE);
    }
}