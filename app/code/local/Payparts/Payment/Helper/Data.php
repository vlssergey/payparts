<?php
class Payparts_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_AVAILABLE_PERIOD = 'payment/payparts_payment/available_period';
    const XML_PATH_MERCHANT_TYPE    = 'payment/payparts_payment/merchant_type';
    const XML_PATH_SHOP_ID          = 'payment/payparts_payment/shopident';
    const XML_PATH_ORDER_STATUS_PROCESSING = 'payment/payparts_payment/order_status_processing';
    const XML_PATH_ORDER_STATUS_NEW        = 'payment/payparts_payment/order_status_new';
    const XML_PATH_PAYMENT_DESCRIPTION     = 'payment/payparts_payment/payment_description';

    /**
     * Description
     * @return string
     */
    public function getPaymentDescription() {
        return $this->escapeHtml(Mage::getStoreConfig(self::XML_PATH_PAYMENT_DESCRIPTION));
    }
    
    /**
     * Order status new
     * @return string
     */
    public function getOrderStatusNew() {
        return Mage::getStoreConfig(self::XML_PATH_ORDER_STATUS_NEW);
    }
    
    /**
     * Order status processing
     * @return string
     */
    public function getOrderStatusProcessing() {
        return Mage::getStoreConfig(self::XML_PATH_ORDER_STATUS_PROCESSING);
    }
    
    /**
     * Merchant id
     * @return string
     */
    public function getShopId() {
        return Mage::getStoreConfig(self::XML_PATH_SHOP_ID);
    }
    
    /**
     * Merchant type
     * @return string
     */
    public function getMerchantType() {
        return Mage::getStoreConfig(self::XML_PATH_MERCHANT_TYPE);
    }
    
    /**
     * Periods
     * @return array
     */
    public function getAvailablePeriods() {
        return explode(",", Mage::getStoreConfig(self::XML_PATH_AVAILABLE_PERIOD));
    }
}
