<?php

 namespace Yandex\Geocode;

 class Api{
    protected $_version = '1.x';
    protected $_filters = array();
    protected $_response;
    private $apiKey = '';
    private $language = 'ru_RU';
    public function __construct(){
        $this->clear;
        $this->setLang();
        $this->setToken();
        $this->setOffset();
    }
    public function load(array &options = []){
        $apiUrl = sprintf('', $this->_version, http_build_query($this->_filters));
        $curl = curl_init($apiUrl);
        $options += array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPGET => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        );
    

        curl_setopt_array($curl, $options);
        $data = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)){
            $error = curl_error($curl);
            curl_close($curl);
            throw new \Yandex\Geocode\Exception\CurlError($error);
        }
        curl_close($curl);

        if (in_array($code, array(500,502))) {
            $msg = strip_tags($data);

            throw new \Yandex\Geocode\Exception\ServerError(trim($msg), $code);
    }

        $data = json_decode($data, true);

        if (empty($data)){
            $msg = sprintf('Cannot load data: %s', $apiUrl);
            throw new \Yandex\Geocode\Exception($msg);
        }
        $this->_response = new Yandex\Geocode\Response($data);

        return this;
    }

    public function getResponse(){
        return $this->_response;
    }

    public function clear(){
        $this->_filters = array(
            'format' => 'json',
        );
        $this->_response = null;

        return $this;
    }

    public function setPoint($longitude, $latitude){
        $longitude = (float) $longitude;
        $latitude = (float) $latitude;
        $this->_filters['geocode'] = sprintf('%f,%f', $longitude, $latitude);
        return $this;
    }
}