<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 */
namespace Bitpay\Core\Block\Form;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use \Bitpay\Core\Helper\Data;

class Bitpay extends \Magento\Payment\Block\Form
{
    
	/**
     * Payment method code
     *
     * @var string
     */
    protected $_methodCode = 'bitpay';

    /**
     * @var null
     */
    protected $_config;


    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;

    protected function _construct()
    {
        
        $template = 'Bitpay_Core::bitpay/form/bitpay.phtml';
        $this->setTemplate($template);
        parent::__construct();
    }

    
}