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
        // get emulation model
        $appEmulation = Mage::getSingleton('core/app_emulation');

        $configApiKeys = Mage::getModel('core/config_data')->getCollection()
            ->addFieldToFilter('path', 'shippit/general/api_key');

        foreach ($configApiKeys as $configApiKey) {
            $storeId = $this->getStoreIdFromScope($configApiKey->getScope(), $configApiKey->getScopeId());

            // Start Store Emulation
            $environment = $appEmulation->startEnvironmentEmulation($storeId);

            try {
                $apiKeyValid = false;

                $merchant = $this->api->getMerchant();

                if (property_exists($merchant, 'error')) {
                    if ($merchant->error == 'invalid_merchant_account') {
                        Mage::getSingleton('adminhtml/session')->addError(
                            $this->helper->__('Shippit configuration error - Please check the API Key for store "%s"', Mage::app()->getStore()->getName())
                        );
                    }
                    else {
                        Mage::getSingleton('adminhtml/session')->addError(
                            $this->helper->__('Shippit API error for store "%s" - ' . $merchant->error, Mage::app()->getStore()->getName())
                        );
                    }
                }
                else {
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        $this->helper->__('Shippit API Key Validated for store "%s"', Mage::app()->getStore()->getName())
                    );

                    $apiKeyValid = true;
                }
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    $this->helper->__('Shippit API error: An error occured while communicating with the Shippit API for store "%s"', Mage::app()->getStore()->getName())
                );
            }

            if ($apiKeyValid && $this->syncShippingHelper->isActive()) {
                $this->registerWebhook();
            }

            // Stop Store Emulation
            $appEmulation->stopEnvironmentEmulation($environment);
        }
    }

    public function registerWebhook()
    {
        try {
            $apiKey = $this->helper->getApiKey();
            $store = Mage::app()->getStore();

            if ($store->getId() == Mage_Core_Model_App::ADMIN_STORE_ID) {
                $webhookUrl = Mage::getUrl('shippit/order/update/', array(
                    'api_key' => $apiKey,
                    '_secure' => true,
                ));
            }
            else {
                $webhookUrl = Mage::getUrl('shippit/order/update/', array(
                    'api_key' => $apiKey,
                    '_store' => $store->getCode(),
                    '_store_to_url' => true,
                    '_secure' => true,
                ));
            }

            $requestData = new Varien_Object;
            $requestData->setWebhookUrl($webhookUrl);
            $merchant = $this->api->putMerchant($requestData, true);

            if (property_exists($merchant, 'error')) {
                Mage::getSingleton('adminhtml/session')->addError(
                    $this->helper->__('Shippit Webhook Registration Error: An error occured while registering the webhook with Shippit for store "%s" - ' . $merchant->error, Mage::app()->getStore()->getName())
                );
            }
            else {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->helper->__('Shippit Webhook Registered for store "%s": ' . $webhookUrl, Mage::app()->getStore()->getName())
                );
            }
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError(
                $this->helper->__('Shippit Webhook Registration Error: An unknown error occured while registering the webhook with Shippit for store "%s" ' . $e->getMessage(), Mage::app()->getStore()->getName())
            );
        }

        return;
    }

    /**
     * Returns the Store Id given the scope/scopeId
     */
    public function getStoreIdFromScope($scope, $scopeId)
    {
        $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;

        if ($scope === 'websites') {
            $storeId = Mage::app()->getWebsite($scopeId)->getDefaultGroup()->getDefaultStoreId();
        }
        elseif ($scope === 'stores') {
            $storeId = $scopeId;
        }

        return $storeId;
    }
}
