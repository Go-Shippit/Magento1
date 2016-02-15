<?php
/**
*  Shippit Pty Ltd
*
*  NOTICE OF LICENSE
*
*  This source file is subject to the terms
*  that is available through the world-wide-web at this URL:
*  http://www.shippit.com/terms
*
*  @category   Shippit
*  @copyright  Copyright (c) 2016 by Shippit Pty Ltd (http://www.shippit.com)
*  @author     Matthew Muscat <matthew@mamis.com.au>
*  @license    http://www.shippit.com/terms
*/

class Shippit_Shippit_Helper_Api extends Mage_Core_Helper_Abstract
{
    const API_ENDPOINT_PRODUCTION = 'https://www.shippit.com/api/3';
    const API_ENDPOINT_STAGING = 'http://shippit-staging.herokuapp.com/api/3';
    const API_TIMEOUT = 5;
    const API_USER_AGENT = 'Shippit_Shippit for Magento';

    protected $api;
    protected $apiUrl;
    protected $bugsnag;

    public function __construct()
    {
        $this->helper = Mage::helper('shippit');

        $this->api = new Varien_Http_Client;
        $this->api->setConfig(
                array(
                    'timeout' => self::API_TIMEOUT,
                    'useragent' => self::API_USER_AGENT . ' v' . $this->helper->getModuleVersion(),
                )
            )
            ->setHeaders('Content-Type', 'application/json');

        if ($this->helper->isDebugActive()) {
            $this->bugsnag = Mage::helper('shippit/bugsnag')->init();
        }
    }

    public function getApiEndpoint()
    {
        $environment = Mage::helper('shippit')->getEnvironment();

        if ($environment == 'production') {
            return self::API_ENDPOINT_PRODUCTION;
        }
        else {
            return self::API_ENDPOINT_STAGING;
        }
    }

    public function getApiUri($path, $authToken = null)
    {
        if (is_null($authToken)) {
            $authToken = $this->helper->getApiKey();
        }

        return $this->getApiEndpoint() . '/' . $path . '?auth_token=' . $authToken;
    }

    public function call($uri, $requestData, $method = Zend_Http_Client::POST, $exceptionOnResponseError = true)
    {
        $uri = $this->getApiUri($uri);

        $jsonRequestData = json_encode($requestData);
        // if (PHP_VERSION_ID >= 50400) {
        //     $jsonRequestData = json_encode($requestData, JSON_UNESCAPED_SLASHES);
        // }
        // else {
        //     $jsonRequestData = str_replace('\\/', '/', json_encode($requestData));
        // }

        if (true||$this->helper->isDebugActive()) {
            Mage::log('-- SHIPPIT - API REQUEST: --', null, 'shippit.log');
            Mage::log($uri, null, 'shippit.log');
            Mage::log($jsonRequestData, null, 'shippit.log');
        }

        $apiRequest = $this->api
            ->setMethod($method)
            ->setUri($uri);

        if (!is_null($requestData)) {
            $apiRequest->setRawData($jsonRequestData)
                ->setMethod('put')
                ->setEncType('application/json');
        }

        try {
            Mage::log('s-1', null, 'shippit.log');
            $apiResponse = $apiRequest->request();
            Mage::log('s-2', null, 'shippit.log');
        }
        catch (Exception $e) {
            Mage::log('s-3', null, 'shippit.log');
            $this->prepareBugsnagReport($uri, $jsonRequestData, $apiResponse);

            throw Mage::Exception('Shippit_Shippit', 'An API Communication Error Occurred - ' . "\n" . $e->getTraceAsString());
        }

        if ($exceptionOnResponseError && $apiResponse->isError()) {
            $message = 'API Response Error' . "\n";
            $message .= 'Response: ' . $apiResponse->getStatus() . ' - ' . $apiResponse->getMessage() . "\n";
            
            $this->prepareBugsnagReport($uri, $jsonRequestData, $apiResponse);

            throw Mage::Exception('Shippit_Shippit', $message);
        }

        $apiResponseBody = json_decode($apiResponse->getBody());

        if (true||$this->helper->isDebugActive()) {
            Mage::log('-- SHIPPIT - API RESPONSE --', null, 'shippit.log');
            Mage::log($apiResponse, null, 'shippit.log');
        }

        return $apiResponseBody;
    }

    protected function prepareBugsnagReport($uri, $jsonRequestData, $apiResponse)
    {
        if ($this->helper->isDebugActive() && $this->bugsnag) {
            // get the core meta data
            $metaData = Mage::helper('shippit/bugsnag')->getMetaData();

            // add the request meta data
            $requestMetaData = array(
                'api_request' => array(
                    'request_uri' => $uri,
                    'request_body' => $jsonRequestData,
                )
            );

            if (!is_null($apiResponse)) {
                $requestMetaData['api_request']['response_code'] = $apiResponse->getStatus();
                $requestMetaData['api_request']['response_body'] = $apiResponse->getBody();
            }

            $metaData = array_merge($metaData, $requestMetaData);

            $this->bugsnag->setMetaData($metaData);
        }
    }

    public function getQuote(Varien_Object $requestData)
    {
        $requestData = array(
            'quote' => $requestData->toArray()
        );

        return $this->call('quotes', $requestData)
            ->response;
    }

    public function sendOrder(Varien_Object $requestData)
    {
        $requestData = array(
            'order' => $requestData->toArray()
        );

        return $this->call('orders', $requestData)
            ->response;
    }

    public function getMerchant()
    {
        return $this->call('merchant', null, Zend_Http_Client::GET, false);
    }

    public function putMerchant($requestData, $exceptionOnResponseError = false)
    {
        $requestData = array(
            'merchant' => $requestData->toArray()
        );

        $url = $this->getApiUri('merchant');

        // return $this->call('merchant', $requestData, Zend_Http_Client::PUT, $exceptionOnResponseError)
        //     ->response;

        $jsonRequestData = json_encode($requestData);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonRequestData)
            )
        );

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequestData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $jsonResponse  = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($jsonResponse);

        return $response;
    }
}