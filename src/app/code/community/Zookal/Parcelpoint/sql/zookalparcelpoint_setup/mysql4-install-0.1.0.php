<?php
$installer = $this;
$installer->startSetup();
$installer->addAttribute('quote', 'parcelpoint_store_id', array(
    'type' => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'nullable' => true,
    'default' => null,
    'comment' => 'ParcelPoint Store Id'
));
$installer->addAttribute('order', 'parcelpoint_store_id', array(
    'type' => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'nullable' => true,
    'default' => null,
    'comment' => 'ParcelPoint Store Id'
));
$installer->addAttribute('shipment', 'parcelpoint_booking_parcel_id', array(
    'type'    => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'nullable' => true,
    'default'  => null,
    'comment' => 'ParcelPoint Booking Parcel Id'
));
$installer->addAttribute('order', 'parcelpoint_user_id', array(
    'type'    => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'nullable' => true,
    'default'  => null,
    'comment' => 'ParcelPoint User Id'
));
