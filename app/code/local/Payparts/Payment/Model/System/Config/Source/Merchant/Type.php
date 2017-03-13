<?php
/**
 * Used in creating options for Merchant type
 *
 */
class Payparts_Payment_Model_System_Config_Source_Merchant_Type
{

    /**
     * Options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'II', 'label'=>Mage::helper('payment')->__('Мгновенная рассрочка')),
            array('value' => 'PP', 'label'=>Mage::helper('payment')->__('Оплата частями')),
            array('value' => 'PB', 'label'=>Mage::helper('payment')->__('Оплата частями. Деньги в периоде')),
            array('value' => 'IA', 'label'=>Mage::helper('payment')->__('Мгновенная рассрочка. Акционная')),
        );
    }

}
