<?php

/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 * 
 * TODO: Finish this iFrame implemenation... :/
 */

namespace Bitpay\Core\Block;

class Iframe extends \Magento\Framework\View\Element\Template {
	protected $_bitpayModel;
	/**
	 *
	 * @var \Bitpay\Core\Helper\Data
	 */
	protected $_dataHelper;
	
	/**
	 *
	 * @param \Magento\Framework\View\Element\Template\Context $context        	
	 * @param \Bitpay\Core\Model\Invoice $_bitpayModel        	
	 * @param \Bitpay\Core\Helper\Data $_dataHelper        	
	 * @param array $data        	
	 */
	public function __construct(\Magento\Framework\View\Element\Template\Context $context, \Magento\Framework\Registry $coreRegistry, \Bitpay\Core\Model\Invoice $_bitpayModel, \Bitpay\Core\Helper\Data $_dataHelper, array $data = []) {
		$this->_bitpayModel = $_bitpayModel;
		$this->_dataHelper = $_dataHelper;
		$this->_coreRegistry = $coreRegistry;
		parent::__construct ( $context, $data );
	}
	
	/**
	 */
	protected function getHelper() {
		$bitpayHelper = $this->_dataHelper;
		return $bitpayHelper;
	}
	
	/**
	 * create an invoice and return the url so that iframe.phtml can display it
	 *
	 * @return string
	 */
	public function getFrameActionUrl() {
		$last_success_quote_id = $this->getLastQuoteId ();
		$invoiceFactory = $this->_bitpayModel;
		$invoice = $invoiceFactory->load ( $last_success_quote_id, 'quote_id' );
		return $invoice->getData ( 'url' ) . '&view=model&v=2';
	}
	public function getLastQuoteId() {
		$lastSuccessQuoteId = $this->_coreRegistry->registry ( 'last_success_quote_id' );
		return $lastSuccessQuoteId;
	}
	public function getValidateUrl() {
		$validateUrl = $this->getUrl ( 'bitpay/index/index' );
		return $validateUrl;
	}
	public function getSuccessUrl() {
		$successUrl = $this->getUrl ( 'checkout/onepage/success' );
		return $successUrl;
	}
	
	 public function logg($data) {
    	$writer = new \Zend\Log\Writer\Stream ( BP . '/var/log/payment_bitpay.log' );
    	$logger = new \Zend\Log\Logger ();
    	$logger->addWriter ( $writer );
    	$logger->info ( print_r ( $data, true ) );
    }

    public function getCartUrl() {
		$cartUrl = $this->getUrl ( 'checkout/cart/index' );
		return $cartUrl;
	}

	public function isTestMode() {
		$mode = $this -> _scopeConfig -> getValue('payment/bitpay/network', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if ($mode == 'testnet') {
			return true;
		}
		return false;
	}
	
	
	
	
	
	
}
