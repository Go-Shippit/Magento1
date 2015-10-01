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
    const API_ENDPOINT_DEBUG = 'http://shippit-staging.herokuapp.com/api/3';
    const API_TIMEOUT = 5;
    const API_USER_AGENT = 'Mamis_Shippit for Magento v3.0.0';

    protected $api;
    protected $apiUrl;

    public function __construct()
    {
        $this->api = new Varien_Http_Client;

        $this->api->setConfig(
                array(
                    'timeout' => self::API_TIMEOUT,
                    'useragent' => self::API_USER_AGENT
                )
            )
            ->setHeaders('Content-Type', 'application/json');
    }

    public function getApiUri($path, $authToken = null)
    {
        if (is_null($authToken)) {
            $authToken = Mage::helper('mamis_shippit')->getApiKey();
        }

        return self::API_ENDPOINT . '/' . $path . '?auth_token=' . $authToken;
    }

    public function call($uri, $requestData, $method = Zend_Http_Client::POST)
    {
        $uri = $this->getApiUri($uri);
        $jsonRequestData = json_encode($requestData);

        Mage::log('API REQUEST:');
        Mage::log($uri);
        Mage::log($requestData);

        $apiRequest = $this->api
            ->setMethod($method)
            ->setUri($uri)
            ->setRawData($jsonRequestData);

        $apiResponse = $apiRequest->request();

        if ($apiResponse->getStatus() != 200) {
            switch ($apiResponse->getStatus()) {
                case 400:
                    Mage::throwException('Invalid Request');
                    break;
                case 500:
                    Mage::throwException('A Server Error Occurred');
                    break;
                default:
                    Mage::throwException('Other Error - Request Unsuccessful');
            }
        }

        $apiResponseBody = json_decode($apiResponse->getBody());

        Mage::log('API RESPONSE');
        Mage::log($apiResponseBody);

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