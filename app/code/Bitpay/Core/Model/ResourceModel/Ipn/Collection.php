<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */
namespace Bitpay\Core\Model\ResourceModel\Ipn;

/**
 * Ipn Collection
 *
 * 
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Bitpay\Core\Model\Ipn', 'Bitpay\Core\Model\ResourceModel\Ipn');
    }
}
