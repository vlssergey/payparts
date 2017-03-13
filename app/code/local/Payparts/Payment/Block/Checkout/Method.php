<?php
/**
 * Payparts notification "form"
 */
class Payparts_Payment_Block_Checkout_Method extends Mage_Payment_Block_Form
{
    /**
     * Set template with message
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payparts/checkout/method.phtml');
    }
    
    /**
     * Payparts helper
     * @return Payparts_Payment_Helper_Data
     */
    protected function paypartsHelper() {
        return Mage::helper('payparts');
    }
    
    /**
     * Payment description
     * @return string
     */
    public function getDescription() {
        return $this->paypartsHelper()->getPaymentDescription();
    }
    
    /**
     * Method is select
     * @return bool
     */
    public function isSelect() {
        return $this->getRequest()->getParam('payparts', false);
    }
    
    /**
     * Retrieve available periods
     * @return array
     */
    public function getAvailablePeriods() {
        return $this->helper('payparts')->getAvailablePeriods();
    }
}