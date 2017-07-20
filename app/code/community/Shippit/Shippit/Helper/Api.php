<?php
/**
 * Shippit Pty Ltd
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the terms
 * that is available through the world-wide-web at this URL:
 * http://www.shippit.com/terms
 *
 * @category   Shippit
 * @copyright  Copyright (c) Shippit Pty Ltd (http://www.shippit.com)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.shippit.com/terms
 */

class Shippit_Shippit_Helper_Api extends Mage_Core_Helper_Abstract
{
    const API_ENDPOINT_PRODUCTION = 'https://www.shippit.com/api/3';
    const API_ENDPOINT_STAGING = 'https://staging.shippit.com/api/3';
    const API_TIMEOUT = 15;
    const API_USER_AGENT = 'Shippit_Shippit for Magento';

    protected $api;
    protected $apiUrl;
    protected $logger;

    public function __construct()
    {
        $this->helper = Mage::helper('shippit');
        $this->logger = Mage::getModel('shippit/logger');

        // We use Zend_HTTP_Client instead of Varien_Http_Client,
        // as Varien_Http_Client does not handle PUT requests correctly
        $this->api = new Zend_Http_Client;
        $this->api->setConfig(
                array(
                    'timeout' => self::API_TIMEOUT,
                    'useragent' => self::API_USER_AGENT . ' v' . $this->helper->getModuleVersion(),
                )
            )
            ->setHeaders('Content-Type', 'application/json');
    }

    public function getApiEndpoint()
    {
        $environment = $this->helper->getEnvironment();

        if ($environment == 'production') {
            return self::API_ENDPOINT_PRODUCTION;
        }
        else {
            return self::API_ENDPOINT_STAGING;
        }
    }

    public function getApiUri($path, $apiKey = null)
    {
        if (is_null($apiKey)) {
            $apiKey = $this->helper->getApiKey();
        }

        return $this->getApiEndpoint() . '/' . $path . '?auth_token=' . $apiKey;
    }

    public function call($uri, $requestData, $method = Zend_Http_Client::POST, $exceptionOnResponseError = true, $apiKey = null)
    {
        $uri = $this->getApiUri($uri, $apiKey);

        $jsonRequestData = json_encode($requestData);

        $apiRequest = $this->api
            ->setMethod($method)
            ->setUri($uri);

        if (!is_null($requestData)) {
            $apiRequest->setRawData($jsonRequestData);
        }

        try {
            $apiResponse = null;
            $apiResponse = $apiRequest->request($method);

            // debug logging
            $this->prepareMatadata($uri, $requestData, $apiResponse);
            $this->logger->log('API Request', "Request to $uri");
        }
        catch (Exception $e) {
            $this->prepareMatadata($uri, $requestData, $apiResponse);
            $this->logger->log('API Request Error', 'An API Request Error Occurred', Zend_Log::ERR);

            throw Mage::Exception('Shippit_Shippit', 'An API Communication Error Occurred - ' . "\n" . $e->getTraceAsString());
        }

        if ($exceptionOnResponseError && $apiResponse->isError()) {
            $message = 'API Response Error' . "\n";
            $message .= 'Response: ' . $apiResponse->getStatus() . ' - ' . $apiResponse->getMessage() . "\n";

            $this->prepareMatadata($uri, $requestData, $apiResponse);
            $this->logger->log('API Response Error', 'An API Response Error Occurred');

            throw Mage::Exception('Shippit_Shippit', $message);
        }

        $apiResponseBody = json_decode($apiResponse->getBody());

        return $apiResponseBody;
    }

    protected function prepareMatadata($uri, $requestData, $apiResponse = null)
    {
        // add the request meta data
        $requestMetaData = array(
            'api_request' => array(
                'request_uri' => $uri,
                'request_body' => $requestData,
            )
        );

        if (!is_null($apiResponse)) {
            $requestMetaData['api_request']['response_code'] = $apiResponse->getStatus();
            $requestMetaData['api_request']['response_body'] = json_decode($apiResponse->getBody());
        }

        $this->logger->setMetaData($requestMetaData);
    }

    public function getQuote(Varien_Object $requestData)
    {
        $requestData = array(
            'quote' => $requestData->toArray()
        );

        return $this->call('quotes', $requestData)
            ->response;
    }

    public function sendOrder(Varien_Object $requestData, $apiKey = null)
    {
        $requestData = array(
            'order' => $requestData->toArray()
        );

        return $this->call('orders', $requestData, Zend_Http_Client::POST, true, $apiKey)
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

        return $this->call('merchant', $requestData, Zend_Http_Client::PUT, $exceptionOnResponseError)
            ->response;
    }
}
