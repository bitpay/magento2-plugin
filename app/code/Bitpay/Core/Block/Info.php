<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bitpay\Core\Block;

/**
 * Base payment iformation block
 */
class Info extends \Magento\Payment\Block\Info
{

    /**
     * @var string
     */
    protected $_template = 'Bitpay_Core::bitpay/info/default.phtml';

    public function getBitpayInvoiceUrl()
    {
        $order       = $this->getInfo()->getOrder();
        $bitpayHelper = \Magento\Framework\App\ObjectManager::getInstance()->get('\Bitpay\Core\Helper\Data');
        $bitpayModelInvoice = \Magento\Framework\App\ObjectManager::getInstance()->get('\Bitpay\Core\Model\Invoice');

        if (false === isset($order) || true === empty($order)) {
            $bitpayHelper->debugData('[ERROR] In Bitpay_Core_Block_Info::getBitpayInvoiceUrl(): could not obtain the order.');
            throw new \Exception('In Bitpay_Core_Block_Info::getBitpayInvoiceUrl(): could not obtain the order.');
        }

        $incrementId = $order->getIncrementId();

        if (false === isset($incrementId) || true === empty($incrementId)) {
            $bitpayHelper->debugData('[ERROR] In Bitpay_Core_Block_Info::getBitpayInvoiceUrl(): could not obtain the incrementId.');
            throw new \Exception('In Bitpay_Core_Block_Info::getBitpayInvoiceUrl(): could not obtain the incrementId.');
        }

        $bitpayInvoice = $bitpayModelInvoice->load($incrementId, 'increment_id');

        if (true === isset($bitpayInvoice) && false === empty($bitpayInvoice)) {
            return $bitpayInvoice->getUrl();
        }
    }
    
}
