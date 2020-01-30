<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Elightwalk\BmlPayment\Model;



/**
 * Pay In Store payment method model
 */
class BmlPayment extends \Magento\Payment\Model\Method\AbstractMethod
{

    
    const CODE = 'bml_payment_gateway';
    const CURRENCY = '462';
    protected $_code = self::CODE;
    protected $_isInitializeNeeded = true;
    protected $_isGateway = true;
    protected $_isOffline = true;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = self::CURRENCY;
    protected $_formBlockType = 'Elightwalk\BmlPayment\Block\Form';
    protected $_infoBlockType = 'Elightwalk\BmlPayment\Block\Info';
    protected $urlBuilder;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

        $this->_minAmount = "0.10";
        $this->_maxAmount = "1000000";
        $this->urlBuilder = $urlBuilder;
    }

    public function getOrderPlaceRedirectUrl(){

        
        $url = $this->getConfigData('transaction_url');
        file_put_contents(BP . '/var/log/events.log', print_r('payment redirectUrl ==> '.$url,true)."\n", FILE_APPEND);
        return $url;
    }

    public function getRedirectUrl()
    {
        $url = $this->getConfigData('transaction_url');
        return $url;
    }

}
