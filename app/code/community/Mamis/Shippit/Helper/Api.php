<?php
/**
*  Mamis.IT
*
*  NOTICE OF LICENSE
*
*  This source file is subject to the EULA
*  that is available through the world-wide-web at this URL:
*  http://www.mamis.com.au/licencing
*
*  @category   Mamis
*  @copyright  Copyright (c) 2015 by Mamis.IT Pty Ltd (http://www.mamis.com.au)
*  @author     Matthew Muscat <matthew@mamis.com.au>
*  @license    http://www.mamis.com.au/licencing
*/

class Mamis_Shippit_Helper_Api extends Mage_Core_Helper_Abstract
{
    const API_ENDPOINT = 'http://goshippit.herokuapp.com/api/3';
    const API_ENDPOINT_STAGING = 'http://shippit-staging.herokuapp.com/api/3';
    const API_TIMEOUT = 5;
    const API_USER_AGENT = 'Mamis_Shippit for Magento';

    protected $api;
    protected $apiUrl;
    protected $bugsnag;

    public function __construct()
    {
        $this->helper = Mage::helper('mamis_shippit');

        $this->api = new Varien_Http_Client;
        $this->api->setConfig(
                array(
                    'timeout' => self::API_TIMEOUT,
                    'useragent' => self::API_USER_AGENT . ' v' . $this->helper->getModuleVersion(),
                )
            )
            ->setHeaders('Content-Type', 'application/json');

        if ($this->helper->isDebugActive()) {
            $this->bugsnag = Mage::helper('mamis_shippit/bugsnag')->init();
        }
    }

    public function getApiUri($path, $authToken = null)
    {
        if (is_null($authToken)) {
            $authToken = $this->helper->getApiKey();
        }

        return self::API_ENDPOINT . '/' . $path . '?auth_token=' . $authToken;
    }

    public function call($uri, $requestData, $method = Zend_Http_Client::POST)
    {
        $uri = $this->getApiUri($uri);
        $jsonRequestData = json_encode($requestData);

        if ($this->helper->isDebugActive()) {
            Mage::log('-- SHIPPIT - API REQUEST: --');
            Mage::log($uri);
            Mage::log($requestData);
        }

        $apiRequest = $this->api
            ->setMethod($method)
            ->setUri($uri)
            ->setRawData($jsonRequestData);

        try {
            if ($this->helper->isDebugActive() && $this->bugsnag) {
                // get the core meta data
                $metaData = Mage::helper('mamis_shippit/bugsnag')->getMetaData();

                // add the request meta data
                $requestMetaData = array(
                    'api_request' => array(
                        'request_uri' => $uri,
                        'request_body' => $jsonRequestData,
                        'response_code' => $apiResponse->getStatus(),
                        'response_body' => $apiResponse->getBody(),
                    )
                );

                $metaData = array_merge($metaData, $requestMetaData);

                $this->bugsnag->setMetaData($metaData);
            }

            $apiResponse = $apiRequest->request();
        }
        catch (Exception $e) {
            throw Mage::Exception('Mamis_Shippit', 'An API Communication Error Occurred - ' . "\n" . $e->getTraceAsString());
        }

        if ($apiResponse->isError()) {
            $message = 'API Response Error' . "\n";
            $message .= 'Response: ' . $apiResponse->getStatus() . ' - ' . $apiResponse->getMessage() . "\n";
            
            throw Mage::Exception('Mamis_Shippit', $message);
        }

        $apiResponseBody = json_decode($apiResponse->getBody());

        if ($this->helper->isDebugActive()) {
            Mage::log('-- SHIPPIT - API RESPONSE --');
            Mage::log($apiResponseBody);
        }

        return $apiResponseBody->response;
    }

    public function getQuote(Varien_Object $requestData)
    {
        $requestData = array(
            'quote' => $requestData->toArray()
        );

        return $this->call('quotes', $requestData);
    }

    public function sendOrder(Varien_Object $requestData)
    {
        $requestData = array(
            'order' => $requestData->toArray()
        );

        return $this->call('orders', $requestData);
    }
}