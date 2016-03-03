<?php

/**
 * @package     Zookal_Parcelpoint_Helper_Data
 * @author      Abiral Shakya @shakyaabiral
 * @author      Chris Zaharia @chrisjz
 * @copyright   Copyright (c) Zookal Pty Ltd
 */
class Zookal_Parcelpoint_Helper_Data extends Mage_Core_Helper_Abstract
{
    const LOG_FILENAME = 'parcelpoint.log';

    function getLogFilename()
    {
        return self::LOG_FILENAME;
    }

    function checkApiEnabledForWebsite(Mage_Core_Model_Website $website)
    {
        return $website->getConfig('parcelpointapi/general/enabled');
    }

    function getApiKeyForWebsite(Mage_Core_Model_Website $website)
    {
        return $website->getConfig('parcelpointapi/general/api_key');
    }

    function isTestModeEnabled(Mage_Core_Model_Website $website)
    {
        return $website->getConfig('parcelpointapi/general/testing_mode') ? true : false;
    }

    function getIsParcelLoggingEnabled(Mage_Core_Model_Website $website)
    {
        return $website->getConfig('parcelpointapi/general/logparcelId') ? true : false;
    }

    function getExcludeOrdersWithoutTracking($website)
    {
        return $website->getConfig('parcelpointapi/booking/excludetracking') ? true : false;
    }
}
