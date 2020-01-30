<?php

namespace Elightwalk\BmlPayment\Controller\Standard;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Response extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    protected $_orderFactory;

    protected $_urlHelper;

    protected $_errorCode;

    protected $_responceCode;

    protected $_helper;

    protected $_orderSender;

    protected $_messageManager;

    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Elightwalk\BmlPayment\Helper\Data $helper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\Url $urlHelper,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Message\ManagerInterface $messageManager
    )
	{
        $this->_orderFactory = $orderFactory;
        $this->_urlHelper = $urlHelper;
        $this->_helper = $helper;
        $this->_orderSender = $orderSender;
        $this->_eventManager = $eventManager;
        $this->_messageManager = $messageManager;
        $this->setErrorCodes();
        $this->setResponceCodes();
        return parent::__construct($context);
	}

    public function execute()
    {
        
        $responseCode=  $_REQUEST['ResponseCode'];
        $reasonCode=  $_REQUEST['ReasonCode'];
        $reasonDescription=  $_REQUEST['ReasonCodeDesc'];
        $comment = "";
        if(isset($this->_errorCode[$reasonCode])){
            $comment = $this->_errorCode[$reasonCode];
        }

        $merchantOrderId = $_REQUEST['OrderID'];
        $order = $this->getOrderById($merchantOrderId);

        if($responseCode==1){

            $merchantId=  $_REQUEST['MerID'];
            $acquirerId=  $_REQUEST['AcqID'];
            $signature = $_REQUEST['Signature'];
            $referenceNumber = $_REQUEST['ReferenceNo'];
            $cardNumber =  $_REQUEST['PaddedCardNo'];
            $authorizationCode = $_REQUEST['AuthCode'];
            $password = $this->getPassword($order);

            if($this->getSignature($password,$merchantId,$acquirerId,$merchantOrderId,$responseCode,$reasonCode)==$signature){
                    $orderTotal = round($order->getGrandTotal(), 2);
                    
                    $payment = $order->getPayment();
                    $payment->setTransactionId($referenceNumber)       
                    ->setPreparedMessage(__('BmlPayment transaction has been successful.'))
                    ->setShouldCloseParentTransaction(true)
                    ->setIsTransactionClosed(0)
                    ->setAdditionalInformation([$cardNumber,$authorizationCode])		
                    ->registerCaptureNotification(
                        $orderTotal,
                        true 
                    );
                    $invoice = $payment->getCreatedInvoice();
                    $comment .=  __('Payment Success.');
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                    $order->setStatus($order::STATE_PROCESSING);
                    $order->setExtOrderId($merchantOrderId);
                    $order->addStatusToHistory($order->getStatus(), $comment);
                    $order->save();
                    $this->sendOrderEmail($order);
                    $this->_eventManager->dispatch(
                        'bml_payment_responce_after',
                        ['order' => $order]
                    );
                    $returnUrl = $this->_urlHelper->getUrl('checkout/onepage/success');
                    $this->_messageManager->addSuccess( __('BML Payment transaction has been successful.') );
            }else{
                $order->setStatus($order::STATE_CANCELED);
                $order->addStatusToHistory($order->getStatus(), __('Signature not Matched'));
                $order->save();
                $returnUrl = $this->_urlHelper->getUrl('checkout/onepage/failure');
                $this->_messageManager->addSuccess( __('BML Payment transaction has been failed.') );
            }        
            
        }else{
            $order->setStatus($order::STATE_CANCELED);
            $order->addStatusToHistory($order->getStatus(), $comment);
	        $order->save();
            $returnUrl = $this->_urlHelper->getUrl('checkout/onepage/failure');
            $this->_messageManager->addSuccess( __('BML Payment transaction has been failed.') );
        }
        
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($returnUrl);
        return $resultRedirect;
        
    }

    public function getSignature($password,$merID,$acqID,$orderId,$responseCode,$reasonCode){

        $signString  = $password.$merID.$acqID.$orderId.$responseCode.$reasonCode;
        $signature = base64_encode(sha1($signString, true));

        return $signature;
    }

    /**
     * Send the orderconfirmation mail to the customer
     *
     * @param  \Magento\Sales\Model\Order $order
     * @return void
     */
    public function sendOrderEmail($order)
    {
        try {
            $this->_orderSender->send($order);
            $order->addStatusHistoryComment(__("Notified customer about order #%1", $order->getId()))
                ->setIsCustomerNotified(1)
                ->save();
        } catch (\Exception $ex) {
            $order->addStatusHistoryComment(__("Could not send order confirmation for order #%1", $order->getId()))
                ->setIsCustomerNotified(0)
                ->save();
        }
    }

    protected function getPassword($order){
        return $this->_helper->getConfigValue('password',$order->getStoreId());
    }

    public function getMerID($order){
        return $this->_helper->getConfigValue('merchant_gateway_id',$order->getStoreId());
    }

    public function getAcqID($order){
        return $this->_helper->getConfigValue('acquirer_gateway_id',$order->getStoreId());
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrderById($merchantOrderId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($merchantOrderId);
    }

    protected function setErrorCodes(){

        $this->_errorCode = [
            '000'=>__('Transaction is successful'),
            '101'=>__('Invalid field passed to 3D Secure MPI'),
            '109'=>__('ACS Not Available'),
            '201'=>__('Invalid ACS response format. Transaction is aborted.'),
            '202'=>__('Cardholder failed the 3D authentication, password entered by cardholder is incorrect and transaction is aborted'),
            '203'=>__('3D PaRes has invalid signature. Transaction is aborted'),
            '205'=>__('Issuer ACS is unavailable to authenticate'),
            '300'=>__('Transaction not approved'),
            '301'=>__('Record not found'),
            '302'=>__('Transation Not Allowed'),
            '303'=>__('Invalid Merchant ID'),
            '304'=>__('Transaction blocked by error 901'),
            '308'=>__('Transaction is aborted. The Transaction was cancelled by the user.'),
            '900'=>__('3D Transaction Timeout'),
            '903'=>__('Comm. Timeout'),
            '901'=>__('System Error'),
            '902'=>__('Time out')
        ];
    }

    protected function setResponceCodes(){

        $this->_errorCode = [
            ''=>__('Approved'),
            '01'=>__('Refer to card issuer'),
            '02'=>__('Refer to card issuer’s special conditions'),
            '03'=>__('HELP - SN.'),
            '05'=>__('DO NOT HONOUR'),
            '09'=>__('ACCEPTED'),
            '10'=>__('Approved for partial amount'),
            '12'=>__('Invalid transaction'),
            '13'=>__('Invalid amount'),
            '14'=>__('Invalid card reader'),
            '19'=>__('Re-enter transaction'),
            '25'=>__('Unable to locate record on file'),
            '30'=>__('Format error.'),
            '31'=>__('Bank not supported by switch'),
            '41'=>__('Lost card'),
            '43'=>__('Stolen card, pick up'),
            '51'=>__('Not sufficient funds'),
            '54'=>__('Expired card'),
            '55'=>__('Incorrect PIN'),
            '58'=>__('Transaction not permitted to terminal'),
            '76'=>__('Invalid product codes'),
            '77'=>__('Reconcile error (or host text is send)'),
            '78'=>__('Trace number not found'),
            '79'=>__('DECLINED – CVV2'),
            '80'=>__('Batch number not found'),
            '82'=>__('NO CLOSED SOC SLOTS'),
            '83'=>__('NO SUSP. SOC SLOTS'),
            '85'=>__('BATCH NOT FOUND'),
            '89'=>__('Bad terminal id'),
            '91'=>__('Issuer or switch inoperative'),
            '94'=>__('Duplicate transmission'),
            '95'=>__('Reconcile error, Batch upload started'),
            '96'=>__('System malfunction')
        ];
    }
}