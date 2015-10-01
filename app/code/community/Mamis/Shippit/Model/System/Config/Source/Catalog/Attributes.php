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

class Mamis_Shippit_Model_System_Config_Source_Catalog_Attributes
{
    /**
     * Returns code => code pairs of attributes for all product attributes
     *
     * @return array
     */
    public function toOptionArray()
    {
        $entityType = Mage::getModel('eav/entity_type')
            ->loadByCode(Mage_Catalog_Model_Product::ENTITY);

        $attributes = Mage::getModel('eav/entity_attribute')
            ->getCollection()
            ->addFieldToSelect('attribute_code')
            ->setEntityTypeFilter($entityType)
            ->setOrder('attribute_code', 'ASC');

        $attributeArray[] = array(
            'label' => ' -- Please Select -- ',
            'value' => ''
        );

        foreach ($attributes as $attribute)
        {
            $attributeArray[] = array(
                'label' => $attribute->getAttributeCode(),
                'value' => $attribute->getAttributeCode()
            );
        }
        
        return $attributeArray;
    }
}