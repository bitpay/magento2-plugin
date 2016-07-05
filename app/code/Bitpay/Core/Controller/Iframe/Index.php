<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 */
namespace Bitpay\Core\Controller\Iframe;

use Magento\Framework\App\Action\Context;
/**
 * @route bitpay/index/
 */
class Index extends \Magento\Framework\App\Action\Action
{
    protected $_bitpayHelper;

    protected $_bitpayModel;
    protected $configResource;
    protected $quoteFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $cart;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bitpay\Core\Helper\Data $_bitpayHelper,\Bitpay\Core\Model\Ipn $_bitpayModel,\Magento\Framework\App\Config\MutableScopeConfigInterface $config,\Magento\Checkout\Model\Cart $cart,\Magento\Quote\Model\QuoteFactory $quoteFactory
    ) {
        $this->config  = $config;
        $this->_bitpayHelper = $_bitpayHelper;
        $this->_bitpayModel = $_bitpayModel;
        $this->cart = $cart;
        $this->quoteFactory = $quoteFactory;
        parent::__construct($context);
    }


    /**
     * @route bitpay/iframe/index
     */
    public function execute()
    {
    
        if($this->config->getValue('payment/bitpay/fullscreen')){
            $html = 'You will be transfered to <a href="https://bitpay.com" target="_blank\">BitPay</a> to complete your purchase when using this payment method.';
        }else{
            
            $html = '';
        }
        
        $this->getResponse()->setBody(json_encode(array('html' => $html)));
    }

}
