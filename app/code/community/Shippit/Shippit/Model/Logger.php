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

class Shippit_Shippit_Model_Logger
{
    protected $helper;
    protected $debugMode;
    
    public $bugsnag = false;
    protected $metaData = array();

    public function __construct()
    {
        $this->helper = Mage::helper('shippit');
        $this->debugMode = $this->helper->isDebugActive();
        $this->bugsnag = Mage::helper('shippit/bugsnag')->init();
    }

    public function log($errorType, $message, $level = Zend_Log::DEBUG)
    {
        // if debug mode is disabled, only log when the level is above notice
        if (!$this->debugMode && $level <= Zend_Log::NOTICE
            || $this->debugMode) {
            $this->bugsnagLog($errorType, $message, $level);

            Mage::log($errorType . "\n" . $message, $level, 'shippit.log');
            
            if (!empty($this->metaData)) {
                Mage::log($this->metaData, $level, 'shippit.log');
            }
        }

        return $this;
    }

    public function bugsnagLog($errorType, $message, $level = Zend_Log::DEBUG)
    {
        if (!$this->bugsnag) {
            return $this;
        }

        $this->bugsnag->notifyError($errorType, $message, $this->metaData, $this->_getBugsnagErrorLevel($level));

        return $this;
    }

    public function bugsnagException($exception)
    {
        if (!$this->bugsnag) {
            return $this;
        }

        return $this->bugsnag->notifyException($exception, $this->metaData);
    }

    public function _getBugsnagErrorLevel($level)
    {
        if ($level <= 3) {
            return 'error';
        }
        elseif ($level == 4) {
            return 'warning';
        }
        else {
            return 'info';
        }
    }

    public function logException($e, $level = Zend_Log::ERR)
    {
        $this->bugsnagException($e);

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