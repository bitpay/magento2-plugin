<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 */
namespace Bitpay\Core\Controller\Index;

use Magento\Framework\App\Action\Context;
/**
 * @route bitpay/index/
 */
class Index extends \Magento\Framework\App\Action\Action
{
    protected $_bitpayHelper;

    protected $_bitpayModel;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bitpay\Core\Helper\Data $_bitpayHelper,\Bitpay\Core\Model\Ipn $_bitpayModel
    ) {
        parent::__construct($context);
        $this->_bitpayHelper = $_bitpayHelper;
        $this->_bitpayModel = $_bitpayModel;
    }


    /**
     * @route bitpay/index/index?quote=n
     */
    public function execute()
    {
        $params  = $this->getRequest()->getParams();
        $quoteId = $params['quote'];
        $this->_bitpayHelper->registerAutoloader();
        $this->_bitpayHelper->debugData(json_encode($params));
        $paid = $this->_bitpayModel->GetQuotePaid($quoteId);

        $this->_view->loadLayout();

        $this->getResponse()->setHeader('Content-type', 'application/json');
        
        $this->getResponse()->setBody(json_encode(array('paid' => $paid)));
    }
}
