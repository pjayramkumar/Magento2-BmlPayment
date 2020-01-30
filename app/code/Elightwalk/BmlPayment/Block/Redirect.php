<?php

namespace Elightwalk\BmlPayment\Block;

class Redirect extends \Magento\Framework\View\Element\Template
{
    protected $_coreRegistry;
    protected $_helper;
    protected $_bmlPayment;
    protected $_urlHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Elightwalk\BmlPayment\Helper\Data $helper,
        \Magento\Framework\Registry $coreRegistry,
        \Elightwalk\BmlPayment\Model\BmlPayment $bmlPayment,
        \Magento\Framework\Url $urlHelper
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_helper = $helper;
        $this->_bmlPayment = $bmlPayment;
        $this->_urlHelper = $urlHelper;
        parent::__construct($context);
    }

    public function getOrder(){
        return $this->_coreRegistry->registry('order');
    }
    
    public function getOrderIncrementId(){
        return $this->getOrder()->getIncrementId();
    }

    public function getSignatureMethod(){
        return 'SHA1';
    }

    public function getMerID(){
        return $this->_helper->getConfigValue('merchant_gateway_id',$this->getOrder()->getStoreId());
    }

    public function getAcqID(){
        return $this->_helper->getConfigValue('acquirer_gateway_id',$this->getOrder()->getStoreId());
    }

    public function getMerRespURL(){
        return $this->_urlHelper->getUrl(
            $this->_bmlPayment->getConfigData('response_url',$this->getOrder()->getStoreId())
        );
    }

    protected function getPassword(){
        return $this->_helper->getConfigValue('password',$this->getOrder()->getStoreId());
    }

    public function getSignature(){

        $password = $this->getPassword();
        $merID = $this->getMerID();
        $acqID = $this->getAcqID();
        $purchaseAmt = $this->getPurchaseAmt();
        $orderId = $this->getOrderIncrementId();
        $currencyCode = \Elightwalk\BmlPayment\Model\BmlPayment::CURRENCY;

        $signString  = $password.$merID.$acqID.$orderId.$purchaseAmt.$currencyCode;
        $signature = base64_encode(sha1($signString, true));

        return $signature;
    }

    public function getPurchaseCurrency(){
        //return $this->_helper->getConfigValue('acquirer_gateway_id',$this->getOrder->getStoreId());
        return \Elightwalk\BmlPayment\Model\BmlPayment::CURRENCY;
    }

    public function getPurchaseAmt(){

        $orderAmount=$this->getOrder()->getGrandTotal();
        $roundAmount = $orderAmount*100;

        $noOfDigit = 12;
        $length = strlen((string)$roundAmount);
        for($i = $length; $i<$noOfDigit; $i++)
        {
            $roundAmount = '0'.$roundAmount;
        }

        return $roundAmount;
    }
    
}