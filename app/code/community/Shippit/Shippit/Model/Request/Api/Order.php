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

// Creates an API Request based on
// passed data or a sync order object

class Shippit_Shippit_Model_Request_Api_Order extends Varien_Object
{
    protected $helper;
    protected $api;
    protected $carrierCode;
    protected $itemHelper;
    protected $order;
    protected $serviceLevels;
    protected $couriers;

    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const RETAILER_INVOICE          = 'retailer_invoice';
    const AUTHORITY_TO_LEAVE        = 'authority_to_leave';
    const DELIVERY_INSTRUCTIONS     = 'delivery_instructions';
    const USER_ATTRIBUTES           = 'user_attributes';
    const COURIER_TYPE              = 'courier_type';
    const DELIVERY_DATE             = 'delivery_date';
    const DELIVERY_WINDOW           = 'delivery_window';
    const RECEIVER_NAME             = 'receiver_name';
    const RECEIVER_CONTACT_NUMBER   = 'receiver_contact_number';
    const DELIVERY_COMPANY          = 'delivery_company';
    const DELIVERY_ADDRESS          = 'delivery_address';
    const DELIVERY_SUBURB           = 'delivery_suburb';
    const DELIVERY_POSTCODE         = 'delivery_postcode';
    const DELIVERY_STATE            = 'delivery_state';
    const DELIVERY_COUNTRY          = 'delivery_country_code';
    const PARCEL_ATTRIBUTES         = 'parcel_attributes';

    // Shippit Service Class API Mappings
    const SHIPPING_SERVICE_STANDARD        = 'standard';
    const SHIPPING_SERVICE_EXPRESS         = 'express';
    const SHIPPING_SERVICE_PRIORITY        = 'priority';
    const SHIPPING_SERVICE_CLICKANDCOLLECT = 'click_and_collect';
    const SHIPPING_SERVICE_PLAINLABEL      = 'PlainLabel';

    public function __construct()
    {
        $this->helper = Mage::helper('shippit/sync_order');
        $this->api = Mage::helper('shippit/api');
        $this->carrierCode = $this->helper->getCarrierCode();
        $this->itemHelper = Mage::helper('shippit/sync_item');
        $this->serviceLevels = Shippit_Shippit_Model_System_Config_Source_Shippit_Shipping_Methods::$serviceLevels;
        $this->couriers = Shippit_Shippit_Model_System_Config_Source_Shippit_Shipping_Methods::$couriers;
    }

    public function processSyncOrder(Shippit_Shippit_Model_Sync_Order $syncOrder)
    {
        // get the order attached to the syncOrder object
        $order = $syncOrder->getOrder();
        // get the shipping method attached to the syncOrder object
        $shippingMethod = $syncOrder->getShippingMethod();
        // get the order items attached to the syncOrder queue
        $items = $syncOrder->getItemsCollection();

        // Build the order request
        $orderRequest = $this->setOrder($order)
            ->setItems($items)
            ->setShippingMethod($shippingMethod);

        return $this;
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
            ->setUserAttributes($order->getCustomerEmail(), $billingAddress->getFirstname(), $billingAddress->getLastname())
            ->setReceiverName($shippingAddress->getName())
            ->setReceiverContactNumber($shippingAddress->getTelephone())
            ->setDeliveryCompany($shippingAddress->getCompany())
            ->setDeliveryAddress(implode(' ', $shippingAddress->getStreet()))
            ->setDeliverySuburb($shippingAddress->getCity())
            ->setDeliveryPostcode($shippingAddress->getPostcode())
            ->setDeliveryState($shippingAddress->getRegionCode())
            ->setDeliveryCountry($shippingAddress->getCountry());

        $this->setOrderAfter($order);

        return $this;
    }

    public function setOrderAfter($order)
    {
        $deliveryState = $this->getDeliveryState();

        // If the delivery state is empty
        // Attempt to retrieve from the postcode lookup for AU Addresses
        if (empty($deliveryState) && $this->getDeliveryCountry() == 'AU') {
            $postcodeState = $this->helper->getStateFromPostcode($this->getDeliveryPostcode());

            if ($postcodeState) {
                $this->setData(self::DELIVERY_STATE, $postcodeState);
            }
        }

        $deliveryState = $this->getDeliveryState();
        $deliverySuburb = $this->getDeliverySuburb();

        // If the delivery state is empty
        // Copy the suburb field to the state field
        if (empty($deliveryState) && !empty($deliverySuburb)) {
            $this->setData(self::DELIVERY_STATE, $deliverySuburb);
        }

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
                    $item->getPrice(),
                    $item->getWeight(),
                    $item->getLength(),
                    $item->getWidth(),
                    $item->getDepth(),
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

        // reset the request data
        $this->setData(self::RETAILER_INVOICE, null)
            ->setData(self::AUTHORITY_TO_LEAVE, null)
            ->setData(self::DELIVERY_INSTRUCTIONS, null)
            ->setData(self::USER_ATTRIBUTES, null)
            ->setData(self::COURIER_TYPE, null)
            ->setData(self::DELIVERY_DATE, null)
            ->setData(self::DELIVERY_WINDOW, null)
            ->setData(self::RECEIVER_NAME, null)
            ->setData(self::RECEIVER_CONTACT_NUMBER, null)
            ->setData(self::DELIVERY_COMPANY, null)
            ->setData(self::DELIVERY_ADDRESS, null)
            ->setData(self::DELIVERY_SUBURB, null)
            ->setData(self::DELIVERY_POSTCODE, null)
            ->setData(self::DELIVERY_STATE, null)
            ->setData(self::PARCEL_ATTRIBUTES, null);
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
     * Get the Courier Type
     *
     * @return array|null
     */
    public function setCourierType($courierType)
    {
        return $this->setData(self::COURIER_TYPE, $courierType);
    }

    /**
     * Get the Delivery Date
     *
     * @return string|null
     */
    public function getDeliveryDate()
    {
        return $this->getData(self::DELIVERY_DATE);
    }

    /**
     * Set the Delivery Date
     *
     * @param string $deliveryDate   Delivery Date
     * @return string
     */
    public function setDeliveryDate($deliveryDate)
    {
        return $this->setData(self::DELIVERY_DATE, $deliveryDate);
    }

    /**
     * Get the Delivery Window
     *
     * @return string|null
     */
    public function getDeliveryWindow()
    {
        return $this->getData(self::DELIVERY_WINDOW);
    }

    /**
     * Set the Delivery Window
     *
     * @param string $deliveryWindow   Delivery Window
     * @return string
     */
    public function setDeliveryWindow($deliveryWindow)
    {
        return $this->setData(self::DELIVERY_WINDOW, $deliveryWindow);
    }

    /**
     * Set the Shipping Method Values
     *
     * - Values may include the courier_type, delivery_date and delivery_window
     *
     * @param string|null $shippingMethod
     * @return array
     */
    public function setShippingMethod($shippingMethod = null)
    {
        // If the shipping method is a service level,
        // set the courier type attribute
        if (array_key_exists($shippingMethod, $this->serviceLevels)) {
            $this->setCourierType($shippingMethod);

            if ($shippingMethod == self::SHIPPING_SERVICE_PRIORITY) {
                // get the special delivery attributes
                $deliveryDate = $this->_getOrderDeliveryDate($this->order);
                $deliveryWindow = $this->_getOrderDeliveryWindow($this->order);

                if (!empty($deliveryDate) && !empty($deliveryWindow)) {
                    $this->setDeliveryDate($deliveryDate);
                    $this->setDeliveryWindow($deliveryWindow);
                }
            }
        }
        // If shipping method is in the list of available
        // couriers then set a courier allocation
        elseif (array_key_exists($shippingMethod, $this->couriers)) {
            $this->setCourierAllocation($shippingMethod);
        }
        // Otherwise, if no matches are found, send
        // the order as a standard service level
        else {
            $this->setCourierType(self::SHIPPING_SERVICE_STANDARD);
        }
    }

    private function _getOrderDeliveryDate($order)
    {
        $shippingMethod = $order->getShippingMethod();

        // If the shipping method is a shippit method,
        // processing using the selected shipping options
        if (strpos($shippingMethod, $this->carrierCode) !== FALSE) {
            $shippingOptions = str_replace($this->carrierCode . '_', '', $shippingMethod);
            $shippingOptions = explode('_', $shippingOptions);
            $courierData = array();

            if (isset($shippingOptions[0])) {
                if ($shippingOptions[0] == 'priority') {
                    return $shippingOptions[1];
                }
                else {
                    return null;
                }
            }
            else {
                return null;
            }
        }
        else {
            return null;
        }
    }

    private function _getOrderDeliveryWindow($order)
    {
        $shippingMethod = $order->getShippingMethod();

        // If the shipping method is a shippit method,
        // processing using the selected shipping options
        if (strpos($shippingMethod, $this->carrierCode) !== FALSE) {
            $shippingOptions = str_replace($this->carrierCode . '_', '', $shippingMethod);
            $shippingOptions = explode('_', $shippingOptions);
            $courierData = array();

            if (isset($shippingOptions[0])) {
                if ($shippingOptions[0] == 'priority') {
                    return $shippingOptions[2];
                }
                else {
                    return null;
                }
            }
            else {
                return null;
            }
        }
        else {
            return null;
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
     * Get the Delivery Company
     *
     * @return string|null
     */
    public function getDeliveryCompany()
    {
        return $this->getData(self::DELIVERY_COMPANY);
    }

    /**
     * Set the Delivery Company
     *
     * @param string $deliveryCompany   Delivery Company
     * @return string
     */
    public function setDeliveryCompany($deliveryCompany)
    {
        return $this->setData(self::DELIVERY_COMPANY, $deliveryCompany);
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
        return $this->setData(self::DELIVERY_STATE, $deliveryState);
    }

    /**
     * Get the Delivery Country
     *
     * @return string|null
     */
    public function getDeliveryCountry()
    {
        return $this->getData(self::DELIVERY_COUNTRY);
    }

    /**
     * Set the Delivery Country
     *
     * @param string $deliveryCountry   Delivery Country
     * @return string
     */
    public function setDeliveryCountry($deliveryCountry)
    {
        return $this->setData(self::DELIVERY_COUNTRY, $deliveryCountry);
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
    public function addItem($sku, $title, $qty, $price, $weight = 0, $length = null, $width = null, $depth = null, $location = null)
    {
        $parcelAttributes = $this->getParcelAttributes();

        if (empty($parcelAttributes)) {
            $parcelAttributes = array();
        }

        // Ensure weights are treated as units with 2 decimal places
        $weight = round($weight, 2);

        $newParcel = array(
            'sku' => $sku,
            'title' => $title,
            'qty' => (float) $qty,
            'price' => (float) $price,
            // if a 0 weight is provided, stub the weight to the default weight value
            'weight' => (float) ($weight == 0 ? $this->itemHelper->getDefaultWeight() : $weight),
            'location' => $location
        );

        // for dimensions, ensure the item has values for all dimensions
        if (!empty($length) && !empty($width) && !empty($depth)) {
            $newParcel = array_merge(
                $newParcel,
                array(
                    'length' => (float) $length,
                    'width' => (float) $width,
                    'depth' => (float) $depth
                )
            );
        }

        $parcelAttributes[] = $newParcel;

        return $this->setParcelAttributes($parcelAttributes);
    }
}
