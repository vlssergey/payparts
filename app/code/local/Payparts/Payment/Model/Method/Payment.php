<?php
class Payparts_Payment_Model_Method_Payment extends Mage_Payment_Model_Method_Abstract {
    
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_CANCEL  = 'CANCELED';
    
    protected $_code = 'payparts_payment';
    
    protected $_formBlockType = 'payparts/checkout_method';
    protected $_infoBlockType = 'payparts/info_payment';

    protected $_canUseForMultishipping = false;
    protected $_canUseInternal = false;
    protected $_canOrder       = true;
  
    public function assignData($data) {
        
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        
        $info = $this->getInfoInstance();
        
        $partsCount = Mage::app()->getRequest()->getParam('partsCount', 2);
        
        $info->setAdditionalInformation('parts_count', $partsCount);

        return $this;
    }
  
    /**
     * Parts/periods count
     * @return int
     */
    public function getPartsCount(){
        return $this->getInfoInstance()->getAdditionalInformation('parts_count');
    }
    
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
     * Payment action getter compatible with payment model
     *
     * @see Mage_Sales_Model_Payment::place()
     * @return string
     */
    public function getConfigPaymentAction(){
        return Mage_Payment_Model_Method_Abstract::ACTION_ORDER;
    }


    /**
     * Return Order place redirect url
     * @return string
     */
    public function getOrderPlaceRedirectUrl() {
        
        $token = $this->checkoutSession()->getPaypartsToken();
        
        if ($token){
            return sprintf("//payparts2.privatbank.ua/ipp/v2/payment?token=%s", $token);
        }
        
        return Mage::getUrl('checkout/onepage', array('_secure' => true));
    }

    /**
     * Retrieve signature for request
     * 
     * @param array $result
     * @return string
     */
    public function getSignature($result){
        if (is_array($result)){
            
            $attributes = $this->getSignatureAttributes();
            $values     = "";
            
            foreach ($attributes as $attr=>$func){
                if (is_callable($func)){
                    $values .= (string)call_user_func($func, $result[$attr]);
                } else {
                    $values .= (string)$result[$func];
                }
            }
            
            return base64_encode(
                hex2bin(
                    SHA1($values)
                )
            );
        }
    }
    
    /**
     * Signature attributes
     * @return array
     */
    private function getSignatureAttributes() {
        return array(
            'store_passwd',
            'store_id',
            'order_id_unique',
            'amount' => function($arg){
                return str_replace(array(',','.'), '', $arg);
            },
            'currency',
            'partsCount',
            'merchantType',
            'responseUrl',
            'redirectUrl',
            'products_string',
            'store_passwd'
        );
    }

    /**
     * Order payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return Payparts_Payment_Model_Method_Payment
     */
    public function order(Varien_Object $payment, $amount)
    {
        /*@var $order Mage_Sales_Model_Order*/
        $order = $payment->getOrder();
        
        if ($order instanceof Mage_Sales_Model_Order){
            
            $result = $this->getPayPartsToken($order);
            
            if (!$result->getStatus()){
                Mage::throwException($result->getMessage());
            } elseif ($result->getToken()) {
                
                $payment->setIsTransactionPending(true);
                $payment->setIsFraudDetected(false);
                
                $this->checkoutSession()->setPaypartsToken($result->getToken());
            }
        }
        
        return $this;
    }

    /**
     * Prepare data
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    private function prepareData($order){

      $result = array();

      foreach ($order->getAllVisibleItems() as $item)
      {
        $result['orders_sales'][] = array(
            'name'  => $item->getName(),
            'price' => (string)number_format($item->getPrice(), 2, '.', ''),
            'count' => (int)$item->getQtyOrdered()

        );
      }

      $result['order_id_unique'] = $order->getRealOrderId();
      $result['store_passwd'] = $this->getConfigData('shoppassword');
      $result['store_id'] = $this->getConfigData('shopident');
      $result['amount'] = (string)number_format($order->getGrandTotal(), 2, '.', '');
      $result['merchantType'] = $this->paypartsHelper()->getMerchantType();
      $result['partsCount'] = $this->getPartsCount();
      $result['currency'] = $order->getOrderCurrencyCode();
      $result['responseUrl'] = Mage::getUrl('payparts/payment/return/', 
              array('transaction_id' => $order->getRealOrderId(), 'shop_id' => $this->getConfigData('shopident')));
      $result['redirectUrl'] = Mage::getUrl('payparts/payment/fail/',
              array('transaction_id' => $order->getRealOrderId(), 'shop_id' => $this->getConfigData('shopident')));
      $result['products_string'] = "";

      if($order->getShippingAmount()){
        $result['orders_sales'][] = array(
            'name' => Mage::helper('payparts')->__("Shipping"),
            'price' => (string)number_format($order->getShippingAmount(), 2, '.', ''),
            'count' => 1
        );
      }

      for ($i=0; $i<count($result['orders_sales']);$i++)
      {
        $result['products_string'] .= $result['orders_sales'][$i]['name']
            .(string)$result['orders_sales'][$i]['count']
            .str_replace('.', '', $result['orders_sales'][$i]['price']);
      }

      $requestData = json_encode(
          array(
              "storeId"      => $result['store_id'],
              "orderId"      => $result['order_id_unique'],
              "amount"       => $result['amount'],
              "currency"     => $result['currency'],
              "partsCount"   => $result['partsCount'],
              "merchantType" => $result['merchantType'],
              "products"     => $result['orders_sales'],
              "responseUrl"  => $result['responseUrl'],
              "redirectUrl"  => $result['redirectUrl'],
              "signature"    => $this->getSignature($result)
          )
      );
      
      return $requestData;
    }

    /**
     * Get response from gateway
     * 
     * @param Mage_Sales_Model_Order $order
     * @return Varien_Object
     */
    private function getPayPartsToken($order){

        $result = array('status' => false);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://payparts2.privatbank.ua/ipp/v2/payment/create');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->prepareData($order));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Accept-Encoding: UTF-8',
            'Content-Type: application/json; charset=UTF-8'
        ));

        $response = json_decode(curl_exec($ch));
        curl_close($ch);

        if (isset($response->token)){
          $result['status'] = true;
          $result['token'] = $response->token;
        } else{
          $result['message'] = (isset($response->errorMessage)) ? $response->errorMessage : $response->message;
        }

        return new Varien_Object($result);
    }

}
