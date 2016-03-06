<?php

/**
 * @package     Zookal_Parcelpoint_Model_Api_Booking
 * @author      Abiral Shakya @shakyaabiral
 * @author      Chris Zaharia @chrisjz
 * @copyright   Copyright (c) Zookal Pty Ltd
 */
class Zookal_Parcelpoint_Model_Api_Booking
{

    # staging url
    const API_URL_TEST = "http://apitest.parcelpoint.com.au/api/v1/parcel";
    # production url
    const API_URL_PRODUCTION = "http://api.parcelpoint.com.au/api/v1/parcel";
    const USER_AGENT = "pp-csv-booking-1";

    /** @var Zookal_Parcelpoint_Helper_Data _helper */
    protected $_helper;
    protected $_bookingFields;

    public function __construct()
    {
        $this->_helper = Mage::helper('zookalparcelpoint');
        $this->_bookingFields = [
            'originUserFirstName',
            'originUserLastName',
            'originUserEmail',
            'originUserMobile',
            'externalReference',
            'externalReference2',
            'externalOrderValue',
            'destinationAgentId',
            'carrierName',
            'supplierName',
            'trackingLabel'
        ];
    }

    /**
     * @param $request
     * @return bool
     * Check if all the required fields are present in the request and they aren't empty
     */
    protected function _validateRequest($request)
    {
        $requiredFields = array('originUserFirstName', 'originUserLastName', 'originUserEmail', 'destinationAgentId');
        foreach ($requiredFields as $field) {
            if (!isset($request[$field]) || $request[$field] == '') return false;
        }
        return true;
    }

    protected function _queryApi($order, $apiKey, $testMode = false)
    {
        Mage::dispatchEvent('before_parcelpoint_api_query', array('order_data' => $order));

        //prepare request fields
        $fieldsToSend = $this->_bookingFields;
        $request = [];
        foreach ($order->toArray() as $index => $value) {
            if (in_array($index, $fieldsToSend)) {
                if (is_null($value)) {
                    $value = '';//replace null with empty string
                }
                $request[$index] = $value;
            }
        }
        if (!$this->_validateRequest($request)) {
            Mage::log('Invalid Fields for Shipment : ' . $order->getData('externalReference'), Zend_Log::ERR, $this->_helper->getLogFilename());
            return false;
        }
        $data_string = json_encode($request);
        $apiUrl = $testMode ? self::API_URL_TEST : self::API_URL_PRODUCTION;
        $url = $apiUrl . "?apiKey=" . $apiKey;
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//return the response to the assigned variable instead of echoing
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string))
            );
            $response = curl_exec($ch);
            Mage::log($response, Zend_Log::INFO, $this->_helper->getLogFilename());
            $response = json_decode($response, true);
            Mage::dispatchEvent('after_parcelpoint_api_query', array('order_data' => $order, 'response' => $response));
            return $response;
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }

    /**
     * @param $website
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    protected function _getOrderDataForWebsite($website)
    {
        if ($website->getWebsiteId()) {
            /**
             * get all stores for the website
             */
            $storeIds = [];
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $storeIds[] = $store->getId();
                }
            }
            $websiteId = $website->getName();
            /** @var Mage_Sales_Model_Resource_Order_Collection $ordersCollection */
            $ordersCollection = Mage::getModel('sales/order')->getCollection();

            $ordersCollection
                ->addFieldToSelect('increment_id', 'externalReference2')
                ->addFieldToSelect('total_paid', 'externalOrderValue')
                ->addFieldToSelect('parcelpoint_store_id', 'destinationAgentId')
                ->addFieldToSelect('customer_email', 'originUserEmail')
                ->addFieldToFilter('main_table.store_id', array('in' => implode(',', $storeIds)))
                ->addFieldToFilter('parcelpoint_store_id', array('gt' => 0))
                ->addFieldToFilter('parcelpoint_booking_parcel_id', array('null' => true));
            $ordersCollection->getSelect()->join(
                'sales_flat_order_address as oa', 'main_table.shipping_address_id = oa.entity_id', array('firstname as originUserFirstName', 'lastname as originUserLastName', 'telephone as originUserMobile'));
            $ordersCollection->getSelect()->join(
                'sales_flat_shipment as s', 'main_table.entity_id = s.order_id', array('s.increment_id as externalReference'));
            if ($this->_helper->getExcludeOrdersWithoutTracking($website)) {
                $ordersCollection->getSelect()->join(
                    'sales_flat_shipment_track as st', 'main_table.entity_id = st.order_id', array('title as carrierName', 'track_number as trackingLabel')
                );
            } else {
                $ordersCollection->getSelect()->joinLeft(
                    'sales_flat_shipment_track as st', 'main_table.entity_id = st.order_id', array('title as carrierName', 'track_number as trackingLabel')
                );
            }
            return $ordersCollection;
        }
    }

    public function sendParcelpointBooking()
    {
        $websites = Mage::app()->getWebsites();
        foreach ($websites as $website) {
            /**
             * check if website is enabled and Parcelpoint booking is enabled for the website
             */
            if (!$this->_helper->checkApiEnabledForWebsite($website)) continue;

            /**
             * get orders for the website
             */
            /** @var Mage_Sales_Model_Resource_Order_Collection $ordersCollection */
            $ordersCollection = $this->_getOrderDataForWebsite($website);
            if ($ordersCollection->getSize()) {
                $apiKey = $this->_helper->getApiKeyForWebsite($website);
                foreach ($ordersCollection as $order) {
                    $order->addData(array('supplierName' => $website->getName()));
                    $response = $this->_queryApi($order, $apiKey, $this->_helper->isTestModeEnabled($website));
                    if (!$response) {
                        continue;
                    }
                    /**
                     * Store originUserId in order and parcelId in Shipment table
                     */
                    if (isset($response['size']) && $response['size'] > 0) {
                        $originUserId = $response['results']['originUserId'];
                        $parcelId = $response['results']['parcelId'];

                        /**
                         * Save parcelId in shipment table
                         */
                        try {
                            $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($order->getData('externalReference'));
                            if ($shipment->getId()) {
                                $shipment
                                    ->setParcelpointBookingParcelId($parcelId)
                                    ->save();
                            }
                        } catch (Exception $e) {
                            Mage::log($e->getMessage(), Zend_Log::ERR, $this->_helper->getLogFilename());
                        }

                        /**
                         * Save originUserId in orders table
                         */
                        try {
                            $orderModel = Mage::getModel('sales/order')->loadByIncrementId($order->getData('externalReference2'));
                            if ($orderModel->getId()) {
                                $orderModel
                                    ->setParcelpointUserId($originUserId)
                                    ->save();
                            }
                        } catch (Exception $e) {
                            Mage::log($e->getMessage(), Zend_Log::ERR, $this->_helper->getLogFilename());
                        }
                    } else {
                        Mage::log($order['externalReference'] . ' : ' . $response['status'] . " : " . $response['message'], Zend_Log::ERR, $this->_helper->getLogFilename());
                    }
                }
            }
        }
    }

}
