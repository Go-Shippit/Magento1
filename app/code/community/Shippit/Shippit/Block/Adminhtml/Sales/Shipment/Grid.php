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

class Shippit_Shippit_Block_Adminhtml_Sales_Shipment_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('shippit_shipment_grid');
        $this->setDefaultSort('sync_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('shippit/sync_shipment_collection')
            ->addFieldToSelect(
                array(
                    'sync_id',
                    'order_increment',
                    'courier_allocation',
                    'shipment_increment',
                    'track_number',
                    'status',
                    'created_at',
                    'synced_at',
                )
            );

        $this->setCollection($collection);
        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('shippit');

        $this->addColumn('sync_id', array(
            'header' => $helper->__('ID'),
            'index'  => 'sync_id',
            'column_css_class' => 'no-display',
            'header_css_class' => 'no-display'
        ));

        $this->addColumn('shipment_increment', array(
            'header' => $helper->__('Shipment #'),
            'index'  => 'shipment_increment',
        ));

        $this->addColumn('order_increment', array(
            'header' => $helper->__('Order #'),
            'index'  => 'order_increment',
        ));

        $this->addColumn('items', array(
            'filter' => false,
            'header' => $helper->__('Items'),
            'getter' => 'getItems',
            'renderer' => 'shippit/adminhtml_sales_shipment_items',
        ));

        $this->addColumn('track_number', array(
            'header' => $helper->__('Shippit Reference'),
            'index'  => 'track_number',
            'frame_callback' => array($this, 'decorateTrackNumber'),
        ));

        $this->addColumn('courier_allocation', array(
            'header' => $helper->__('Courier'),
            'index'  => 'courier_allocation',
            'frame_callback' => array($this, 'decorateCourierAllocation'),
        ));

        $this->addColumn('status', array(
            'header'  => $helper->__('Status'),
            'index'   => 'status',
            'type'    => 'options',
            'options' => Mage::getSingleton('shippit/sync_shipment_config')->getStatus(),
            'frame_callback' => array($this, 'decorateStatus'),
        ));

        $this->addColumn('created_at', array(
            'header' => $helper->__('Created At'),
            'type'   => 'datetime',
            'index'  => 'created_at'
        ));

        $this->addColumn('synced_at', array(
            'header' => $helper->__('Synced At'),
            'type'   => 'datetime',
            'index'  => 'synced_at'
        ));

        $this->addColumn('actions', array(
            'header' => Mage::helper('shippit')->__('Action'),
            'width' => '150px',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('shippit')->__('Sync Now'),
                    'url' => array('base'=>'*/*/sync'),
                    'field' => 'id'
                ),
                array('caption' => Mage::helper('shippit')->__('Schedule Sync'),
                    'url' => array('base'=>'*/*/schedule'),
                    'field' => 'id'
                ),
                array('caption' => Mage::helper('shippit')->__('Remove'),
                    'url' => array('base'=>'*/*/remove'),
                    'field' => 'id'
                ),
                'filter' => false,
                'sortable' => false
            )
        ));

        return parent::_prepareColumns();
    }

    public function decorateCourierAllocation($value)
    {
        return ucfirst($value);
    }

    public function decorateTrackNumber($value)
    {
        if (empty($value)) {
            return $value;
        }

        $cell = sprintf(
            '<a href="https://www.shippit.com/track/%s" title="Track Order" target="_blank">%s</a>',
            $value,
            $value
        );

        return $cell;
    }

    public function decorateStatus($value)
    {
        $cell = sprintf(
            '<span class="grid-severity-%s"><span>%s</span></span>',
            $this->getGridSeverity($value),
            $value
        );
        return $cell;
    }

    public function getGridSeverity($value)
    {
        $gridSeverity = 'critical';

        switch ($value) {
            case Shippit_Shippit_Model_Sync_Shipment::STATUS_PENDING_TEXT:
                $gridSeverity = 'minor';
                break;
            case Shippit_Shippit_Model_Sync_Shipment::STATUS_FAILED_TEXT:
                $gridSeverity = 'critical';
                break;
            case Shippit_Shippit_Model_Sync_Shipment::STATUS_SYNCED_TEXT:
                $gridSeverity = 'notice';
                break;
        }

        return $gridSeverity;
    }

    protected function _prepareMassaction(){
        $this->setMassactionIdField('sync_id');
        $this->getMassactionBlock()->setFormFieldName('sync_ids');

        $this->getMassactionBlock()->addItem(
            'Sync Now',
            array(
                'label' => Mage::helper('shippit')->__('Sync Now'),
                'url' => $this->getUrl('*/*/massSync')
            )
        );

        $this->getMassactionBlock()->addItem(
            'Schedule Sync',
            array(
                'label' => Mage::helper('shippit')->__('Schedule Sync'),
                'url' => $this->getUrl('*/*/massSchedule')
            )
        );

        $this->getMassactionBlock()->addItem(
            'Remove',
            array(
                'label' => Mage::helper('shippit')->__('Remove'),
                'url' => $this->getUrl('*/*/massRemove')
            )
        );
    }

    public function getRowUrl($row)
    {

    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
