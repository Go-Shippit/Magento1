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

$installer = $this;
$installer->startSetup();

/**
 * Add Australian States and Territories if they are not present
 */
$regions = array(
    array(
        'country_id'   => 'AU',
        'code'         => 'VIC',
        'default_name' => 'Victoria'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'NSW',
        'default_name' => 'New South Wales'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'ACT',
        'default_name' => 'Australian Capital Territory'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'QLD',
        'default_name' => 'Queensland'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'TAS',
        'default_name' => 'Tasmania'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'SA',
        'default_name' => 'South Australia'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'NT',
        'default_name' => 'Northern Territory'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'WA',
        'default_name' => 'Western Australia'
    ),
);

foreach ($regions as $region) {
    // Attempt to load the region, checking if it already exists
    $hasRegion = Mage::getModel('directory/region')->loadByCode($region['code'], $region['country_id'])
        ->hasData();

    if (!$hasRegion) {
        // Insert the region data
        $installer->getConnection()->insert(
            $installer->getTable('directory/country_region'),
            $region
        );

        // Get the newly created region
        $regionId = $installer->getConnection()->lastInsertId(
            $installer->getTable('directory/country_region')
        );

        // Setup the region name data
        $regionName = array(
            'locale'    => 'en_US',
            'region_id' => $regionId,
            'name'      => $region['default_name'],
        );

        // Add the region name data
        $installer->getConnection()->insert(
            $installer->getTable('directory/country_region_name'),
            $regionName
        );
    }
}

$installer->endSetup();