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

class Shippit_Shippit_Model_Request_Api_Order extends Varien_Object
{
    protected $helper;
    protected $api;
    protected $carrierCode;
    protected $itemsHelper;
    protected $order;

    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const RETAILER_INVOICE          = 'retailer_invoice';
    const AUTHORITY_TO_LEAVE        = 'authority_to_leave';
    const DELIVERY_INSTRUCTIONS     = 'delivery_instructions';
    const USER_ATTRIBUTES           = 'user_attributes';
    const COURIER_TYPE              = 'courier_type';
    const RECEIVER_NAME             = 'receiver_name';
    const RECEIVER_CONTACT_NUMBER   = 'receiver_contact_number';
    const DELIVERY_ADDRESS          = 'delivery_address';
    const DELIVERY_SUBURB           = 'delivery_suburb';
    const DELIVERY_POSTCODE         = 'delivery_postcode';
    const DELIVERY_STATE            = 'delivery_state';
    const PARCEL_ATTRIBUTES         = 'parcel_attributes';

    public function __construct() {
        $this->helper = Mage::helper('shippit');
        $this->api = Mage::helper('shippit/api');
        $this->carrierCode = $this->helper->getCarrierCode();
        $this->itemsHelper = Mage::helper('shippit/order_items');
    }

    /**
     * Set the order to be sent to the api request
     *
     * @param object $order The Order Request
     */
    public function setOrder($order)
    {
        if ($order instanceof Mage_Sales_Model_Order) {
            $this->order = $order;
        }
        else {
            $order = Mage::getModel('sales/order')->load($order);
            $this->order = $order;
        }

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        $this->setRetailerInvoice($order->getIncrementId())
            ->setAuthorityToLeave($order->getShippitAuthorityToLeave())
            ->setDeliveryInstructions($order->getShippitDeliveryInstructions())
            ->setUserAttributes($billingAddress->getEmail(), $billingAddress->getFirstname(), $billingAddress->getLastname())
            ->setCourierType()
            ->setReceiverName($shippingAddress->getName())
            ->setReceiverContactNumber($shippingAddress->getTelephone())
            ->setDeliveryAddress(implode(' ', $shippingAddress->getStreet()))
            ->setDeliverySuburb($shippingAddress->getCity())
            ->setDeliveryPostcode($shippingAddress->getPostcode())
            ->setDeliveryState($shippingAddress->getRegionCode());

        return $this;
    }

    public function setItems(Shippit_Shippit_Model_Resource_Sync_Order_Item_Collection $items)
    {
        if (count($items) == 0) {
            // If we don't have specific items in the request, build
            // the request dynamically from the order object
            $items = Mage::getModel('shippit/request_sync_order')
                ->setOrder($this->order)
                ->setItems()
                ->getItems();

            $this->setParcelAttributes($items);
        }
        else {
            // Otherwise, use the data requested in the sync event
            foreach ($items as $item) {
                $this->addItem(
                    $item->getSku(),
                    $item->getTitle(),
                    $item->getQty(),
                    $item->getWeight(),
                    $item->getLocation()
                );
            }
        }

        return $this;
    }

    public function reset()
    {
        // reset the order reference
        $this->order = null;

        // reseet the request data
        $this->setRetailerInvoice(null)
            ->setAuthorityToLeave(null)
            ->setDeliveryInstructions(null)
            ->setUserAttributes(null)
            ->setCourierType(null)
            ->setReceiverName(null)
            ->setReceiverContactNumber(null)
            ->setDeliveryAddress(null)
            ->setDeliverySuburb(null)
            ->setDeliveryPostcode(null)
            ->setDeliveryState(null)
            ->setParcelAttributes(null);
    }

    /**
     * Get the Retailer Invoice Referance
     *
     * @return string|null
     */
    public function getRetailerInvoice()
    {
        return $this->getData(self::RETAILER_INVOICE);
    }

    /**
     * Set the Retailer Invoice Referance
     *
     * @param string $orderDate
     * @return string
     */
    public function setRetailerInvoice($retailerInvoice)
    {
        return $this->setData(self::RETAILER_INVOICE, $retailerInvoice);
    }

    /**
     * Get the Authority To Leave
     *
     * @return bool|null
     */
    public function getAuthorityToLeave()
    {
        return $this->getData(self::AUTHORITY_TO_LEAVE);
    }

    /**
     * Set the Authority To Leave
     *
     * @param bool $authorityToLeave
     * @return bool
     */
    public function setAuthorityToLeave($authorityToLeave)
    {
        if ($authorityToLeave) {
            $authorityToLeave = "Yes";
        }
        else {
            $authorityToLeave = "No";
        }

        return $this->setData(self::AUTHORITY_TO_LEAVE, $authorityToLeave);
    }

    /**
     * Get the Delivery Instructions
     *
     * @return string|null
     */
    public function getDeliveryInstructions()
    {
        return $this->getData(self::DELIVERY_INSTRUCTIONS);
    }

    /**
     * Set the Delivery Instructions
     *
     * @param string $deliveryInstructions
     * @return string
     */
    public function setDeliveryInstructions($deliveryInstructions)
    {
        return $this->setData(self::DELIVERY_INSTRUCTIONS, $deliveryInstructions);
    }

    /**
     * Get the User Attributes
     *
     * @return array|null
     */
    public function getUserAttributes()
    {
        return $this->getData(self::USER_ATTRIBUTES);
    }

    /**
     * Set the User Attributes
     *
     * @param array $userAttributes
     * @return array
     */
    public function setUserAttributes($email, $firstname, $lastname)
    {
        $userAttributes = array(
            'email' => $email,
            'first_name' => $firstname,
            'last_name' => $lastname,
        );

        return $this->setData(self::USER_ATTRIBUTES, $userAttributes);
    }

    /**
     * Get the Courier Type
     *
     * @return array|null
     */
    public function getCourierType()
    {
        return $this->getData(self::COURIER_TYPE);
    }

    /**
     * Set the Courier Type
     *
     * @param string|null $courierType
     * @return array
     */
    public function setCourierType($courierType = null)
    {
        if (!is_null($courierType)) {
            return $this->setData(self::COURIER_TYPE, $courierType);
        }
        // determine the courier from the order shipping method
        else {
            $shippingMethod = $this->order->getShippingMethod();

            // If the shipping method is a shippit method,
            // processing using the selected shipping options
            if (strpos($shippingMethod, $this->carrierCode) !== FALSE) {
                $shippingOptions = str_replace($this->carrierCode . '_', '', $shippingMethod);
                $shippingOptions = explode('_', $shippingOptions);
                $courierData = array();
                
                if (isset($shippingOptions[0])) {
                    if ($shippingOptions[0] == 'Bonds') {
                        return $this->setData(self::COURIER_TYPE, $shippingOptions[0])
                            ->setDeliveryDate($shippingOptions[1])
                            ->setDeliveryWindow($shippingOptions[2]);
                    }
                    else {
                        return $this->setData(self::COURIER_TYPE, $shippingOptions[0]);
                    }
                }
            }
            // Otherwise, use the default "CouriersPlease" courier type
            else {
                return $this->setData(self::COURIER_TYPE, 'CouriersPlease');
            }
        }
    }

    /**
     * Get the Receiver Name
     *
     * @return string|null
     */
    public function getReceiverName()
    {
        return $this->getData(self::RECEIVER_NAME);
    }

    /**
     * Set the Reciever Name
     *
     * @param string $receiverName    Receiver Name
     * @return string
     */
    public function setReceiverName($receiverName)
    {
        return $this->setData(self::RECEIVER_NAME, $receiverName);
    }

    /**
     * Get the Receiver Contact Number
     *
     * @return string|null
     */
    public function getReceiverContactNumber()
    {
        return $this->getData(self::RECEIVER_CONTACT_NUMBER);
    }

    /**
     * Set the Reciever Contact Number
     *
     * @param string $receiverContactNumber    Receiver Contact Number
     * @return string
     */
    public function setReceiverContactNumber($receiverContactNumber)
    {
        return $this->setData(self::RECEIVER_CONTACT_NUMBER, $receiverContactNumber);
    }

    /**
     * Get the Delivery Address
     *
     * @return string|null
     */
    public function getDeliveryAddress()
    {
        return $this->getData(self::DELIVERY_ADDRESS);
    }

    /**
     * Set the Delivery Address
     *
     * @param string $deliveryAddress   Delivery Address
     * @return string
     */
    public function setDeliveryAddress($deliveryAddress)
    {
        return $this->setData(self::DELIVERY_ADDRESS, $deliveryAddress);
    }

    /**
     * Get the Delivery Suburb
     *
     * @return string|null
     */
    public function getDeliverySuburb()
    {
        return $this->getData(self::DELIVERY_SUBURB);
    }

    /**
     * Set the Delivery Suburb
     *
     * @param string $deliverySuburb   Delivery Suburb
     * @return string
     */
    public function setDeliverySuburb($deliverySuburb)
    {
        return $this->setData(self::DELIVERY_SUBURB, $deliverySuburb);
    }

    /**
     * Get the Delivery Postcode
     *
     * @return string|null
     */
    public function getDeliveryPostcode()
    {
        return $this->getData(self::DELIVERY_POSTCODE);
    }

    /**
     * Set the Delivery Postcode
     *
     * @param string $deliveryPostcode   Delivery Postcode
     * @return string
     */
    public function setDeliveryPostcode($deliveryPostcode)
    {
        return $this->setData(self::DELIVERY_POSTCODE, $deliveryPostcode);
    }

    /**
     * Get the Delivery State
     *
     * @return string|null
     */
    public function getDeliveryState()
    {
        return $this->getData(self::DELIVERY_STATE);
    }

    /**
     * Set the Delivery State
     *
     * @param string $deliveryState   Delivery State
     * @return string
     */
    public function setDeliveryState($deliveryState)
    {
        if (empty($deliveryState)) {
            $deliveryState = $this->helper->getStateFromPostcode($this->getDeliveryPostcode());
        }

        return $this->setData(self::DELIVERY_STATE, $deliveryState);
    }
    /**
     * Get the Parcel Attributes
     *
     * @return string|null
     */
    public function getParcelAttributes()
    {
        return $this->getData(self::PARCEL_ATTRIBUTES);
    }

    /**
     * Set the Parcel Attributes
     *
     * @param string $parcelAttributes
     * @return string|null
     */
    public function setParcelAttributes($parcelAttributes)
    {
        return $this->setData(self::PARCEL_ATTRIBUTES, $parcelAttributes);
    }

    /**
     * Add a parcel with attributes
     *
     */
    public function addItem($sku, $title, $qty, $weight = 0, $location = null)
    {
        $parcelAttributes = $this->getParcelAttributes();

        if (empty($parcelAttributes)) {
            $parcelAttributes = array();
        }

        $newParcel = array(
            'sku' => $sku,
            'title' => $title,
            'qty' => $qty,
            'weight' => $weight,
            'location' => $location
        );

        $parcelAttributes[] = $newParcel;

        return $this->setParcelAttributes($parcelAttributes);
    }
}