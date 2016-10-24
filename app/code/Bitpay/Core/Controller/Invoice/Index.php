<?php

/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 */
namespace Bitpay\Core\Controller\Invoice;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

/**
 * @route bitpay/invoice/
 */
class Index extends \Magento\Framework\App\Action\Action {
	protected $_bitpayHelper;
	protected $_bitpayModel;
	protected $_checkoutSession;
	/**
	 * Core registry
	 *
	 * @var \Magento\Framework\Registry
	 */
	protected $_coreRegistry;
	protected $configResource;
	protected $resultPageFactory;
	public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry, \Bitpay\Core\Helper\Data $_bitpayHelper, \Bitpay\Core\Model\Invoice $_bitpayModel, \Magento\Framework\App\Config\MutableScopeConfigInterface $config, \Magento\Framework\View\Result\PageFactory $resultPageFactory) {
		$this->_coreRegistry = $coreRegistry;
		$this->_bitpayHelper = $_bitpayHelper;
		$this->_bitpayModel = $_bitpayModel;
		$this->config = $config;
		$this->resultPageFactory = $resultPageFactory;
		parent::__construct ( $context );
	}
	
	/**
	 * @route bitpay invoice url
	 */
	public function execute() {
		
		$objectmanager = \Magento\Framework\App\ObjectManager::getInstance ();
		$quote = $objectmanager->get ( '\Magento\Checkout\Model\Session' );		
		if (empty($quote->getData ( 'last_success_quote_id' ))) {
			return $this->_redirect ( 'checkout/cart' );
		}
		
		$this->_coreRegistry->register ( 'last_success_quote_id', $quote->getData ( 'last_success_quote_id' ) );
		
		if ($this->config->getValue ( 'payment/bitpay/fullscreen' )) {
			$invoiceFactory = $this->_bitpayModel;
			$invoice = $invoiceFactory->load ( $quote->getData ( 'last_success_quote_id' ), 'quote_id' );
			$resultRedirect = $this->resultFactory->create ( ResultFactory::TYPE_REDIRECT );
			$resultRedirect->setUrl ( $invoice->getData ( 'url' ) );
			return $resultRedirect;
		} else {
			$resultPage = $this->resultPageFactory->create ();
			$resultPage->getConfig ()->getTitle ()->set ( __ ( 'Pay with BitCoin' ) );
			return $resultPage;
		}
	}
}
