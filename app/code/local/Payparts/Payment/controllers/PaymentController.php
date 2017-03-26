<?php
class Payparts_Payment_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * Payparts helper
     * @return Payparts_Payment_Helper_Data
     */
    protected function paypartsHelper() {
        return Mage::helper('payparts');
    }
    
    /**
     * Checkout session
     * @return Mage_Checkout_Model_Session
     */
    protected function checkoutSession() {
        return Mage::getSingleton('checkout/session');
    }
    
    /**
     * Load order by increment id
     * @param mixed $incrementId Order transaction id
     * @return Mage_Sales_Model_Order Order object
     */
    protected function getOrderByIncrementId($incrementId) {
        if ($incrementId){
            return Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        }
    }
    
    /**
     * Retrieve order id
     * @return mixed
     */
    protected function getTransactionId() {
        return $this->getRequest()->getParam('transaction_id', false);
    }
    
    /**
     * Retrieve shop id
     * @return mixed
     */
    protected function getShopId() {
        return $this->getRequest()->getParam('shop_id', false);
    }
    
    /**
     * When a customer cancel payment from PayParts.
     */
    public function failAction()
    {
        if ($this->getTransactionId()
                && $this->paypartsHelper()->getShopId() == $this->getShopId()){
            $order = $this->getOrderByIncrementId($this->getTransactionId());
            
            if (is_object($order) && $order->getEntityId()){
                
                if ($order->canCancel()) {
                    $order->cancel()->save();
                }
                
                $order->addStatusHistoryComment(Mage::helper('payparts')->__('Order was canceled.'),
                Mage_Sales_Model_Order::STATE_CANCELED)
                        ->save();
                
            }

            $this->checkoutSession()->addError(Mage::helper('payparts')->__('Payment failed. Pleas try again later.'));
        }

        $this->_redirect('checkout/cart');
    }

    /**
     * Customer return processing
     */
    public function returnAction()
    {
        try {
            
            $postData = Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
            
            if (is_array($postData)){
                if (isset($postData['paymentState']) && isset($postData['orderId'])
                        && $this->paypartsHelper()->getShopId() == $this->getShopId()
                        ){
                    
                    $orderId = $postData['orderId'];
                    
                    /*@var $order Mage_Sales_Model_Order*/
                    $order = $this->getOrderByIncrementId($orderId);
                    
                    if (is_object($order) && $order->getId()){
                        
                        $comment = (isset($postData['message'])) ? $postData['message'] : null;
                        
                        switch($postData['paymentState']){
                            case Payparts_Payment_Model_Method_Payment::STATUS_SUCCESS:
                                if ($comment){
                                    $order->addStatusHistoryComment($comment, $this->paypartsHelper()->getOrderStatusProcessing())
                                        ->save();
                                }
                                break;
                            case Payparts_Payment_Model_Method_Payment::STATUS_CANCEL:
                                
                                if ($order->canCancel()){
                                    $order->cancel();
                                }
                                
                                if ($comment){
                                    $order->addStatusHistoryComment($comment, Mage_Sales_Model_Order::STATE_CANCELED)->save();
                                }
                                
                                break;
                        }
                        
                        $order->save();
                    }
                }
            }
            
            $this->_redirect('checkout/onepage/success');
            
            return;
            
        } catch (Mage_Core_Exception $e) {
            $this->checkoutSession()->addError($e->getMessage());
        } catch(Exception $e) {
            $this->checkoutSession()->addError($e->getMessage());
        }
        
        $this->_redirect('checkout/cart');
    }

}