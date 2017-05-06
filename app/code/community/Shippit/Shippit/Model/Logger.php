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

class Shippit_Shippit_Model_Logger
{
    protected $helper;
    protected $debugMode;
    protected $metaData = array();

    public function __construct()
    {
        $this->helper = Mage::helper('shippit');
        $this->debugMode = $this->helper->isDebugActive();
    }

    public function log($errorType, $message, $level = Zend_Log::DEBUG)
    {
        // if debug mode is disabled, only log when the level is above notice
        if (!$this->debugMode && $level <= Zend_Log::NOTICE
            || $this->debugMode) {
            Mage::log($errorType . "\n" . $message, $level, 'shippit.log');

            if (!empty($this->metaData)) {
                Mage::log($this->metaData, $level, 'shippit.log');
            }
        }

        return $this;
    }

    public function logException($e, $level = Zend_Log::ERR)
    {
        Mage::log($e->getMessage(), $level, 'shippit.log');

        if (!empty($this->metaData)) {
            Mage::log($this->metaData, $level, 'shippit.log');
        }

        return $this;
    }

    public function setMetaData($metaData)
    {
        $this->metaData = $metaData;

        return $this;
    }
}
