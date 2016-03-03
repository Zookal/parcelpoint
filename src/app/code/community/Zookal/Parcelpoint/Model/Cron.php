<?php

/**
 * @package     Zookal_Parcelpoint_Model_Cron
 * @author      Abiral Shakya @shakyaabiral
 * @author      Chris Zaharia @chrisjz
 * @copyright   Copyright (c) Zookal Pty Ltd
 */
class Zookal_Parcelpoint_Model_Cron
{
    public function book()
    {
        /** @var Zookal_Parcelpoint_Model_Api_Booking $api */
        $api = Mage::getModel('zookalparcelpoint/api_booking');

        try {
            $api->sendParcelpointBooking();
        } catch (Exception $e) {
            Mage::log('Cron failed to run.', Zend_Log::WARN, Mage::helper('zookalparcelpoint')->getLogFilename());
        }
    }
}