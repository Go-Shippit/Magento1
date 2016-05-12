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
 * @copyright  Copyright (c) 2016 by Shippit Pty Ltd (http://www.shippit.com)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.shippit.com/terms
 */

class Shippit_Shippit_Model_Observer_System_Config
{
    protected $helper;
    protected $syncShippingHelper;
    protected $api;

    public function __construct()
    {
        $this->helper = Mage::helper('shippit');
        $this->syncShippingHelper = Mage::helper('shippit/sync_shipping');
        $this->api = Mage::helper('shippit/api');
    }

    public function checkApiKey(Varien_Event_Observer $observer)
    {
        $request = Mage::app()->getRequest();

        if ($request->getParam('section') != 'shippit') {
            return;
        }

        try {
            $apiKeyValid = false;

            $merchant = $this->api->getMerchant();

            if (property_exists($merchant, 'error')) {
                if ($merchant->error == 'invalid_merchant_account') {
                    Mage::getSingleton('adminhtml/session')->addError(
                        $this->helper->__('Shippit configuration error: Please check the API Key')
                    );
                }
                else {
                    Mage::getSingleton('adminhtml/session')->addError(
                        $this->helper->__('Shippit API error: ' . $merchant->error)
                    );
                }
            }
            else {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->helper->__('Shippit API Key Validated')
                );
                
                $apiKeyValid = true;
            }
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError('Shippit API error: An error occured while communicating with the Shippit API');
        }

        if ($apiKeyValid && $this->syncShippingHelper->isActive()) {
            $this->registerWebhook();
        }
    }

    public function registerWebhook()
    {
        try {
            $apiKey = $this->helper->getApiKey();

            $webhookUrl = Mage::getUrl('shippit/order/update/', array(
                'api_key' => $apiKey,
                '_secure' => true,
            ));
            
            $requestData = new Varien_Object;
            $requestData->setWebhookUrl($webhookUrl);
            $merchant = $this->api->putMerchant($requestData, true);

            if (property_exists($merchant, 'error')) {
                Mage::getSingleton('adminhtml/session')->addError(
                    $this->helper->__('Shippit Webhook Registration Error: An error occured while registering the webhook with Shippit' . $merchant->error)
                );
            }
            else {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->helper->__('Shippit Webhook Registered: ' . $webhookUrl)
                );
            }
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError(
                $this->helper->__('Shippit Webhook Registration Error: An unknown error occured while registering the webhook with Shippit ' . $e->getMessage())
            );
        }

        return;
    }
}