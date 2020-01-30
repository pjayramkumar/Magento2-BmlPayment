<?php

namespace Elightwalk\BmlPayment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\UrlInterface as UrlInterface;
use Magento\Framework\View\Asset\Repository;

class BmlPaymentConfigProvider implements ConfigProviderInterface
{
    protected $method;
    
    protected $urlBuilder;

    protected $repository;

    public function __construct(
        PaymentHelper $paymentHelper,
        UrlInterface $urlBuilder,
        Repository $repository
    ) {
        $this->method = $paymentHelper->getMethodInstance(\Elightwalk\BmlPayment\Model\BmlPayment::CODE);
        $this->urlBuilder = $urlBuilder;
        $this->repository = $repository;
    }

    public function getPaymentLogo()
	{
		return $this->repository->getUrl('Elightwalk_BmlPayment::images/Payment Gateway logos_1.png');
	}

    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                \Elightwalk\BmlPayment\Model\BmlPayment::CODE => [
                    'paymentlogo' => $this->getPaymentLogo(),
                    'redirectUrl' => $this->urlBuilder->getUrl('bmlpayment/standard/redirect', ['_secure' => true])
                ]
            ]
        ] : [];
    }
}
