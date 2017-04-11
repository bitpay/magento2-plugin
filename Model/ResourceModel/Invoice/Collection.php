<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */
namespace Bitpay\Core\Model\ResourceModel\Invoice;

/**
 * Invoice Collection
 *
 *
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_isPkAutoIncrement = false;

    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Bitpay\Core\Model\Invoice', 'Bitpay\Core\Model\ResourceModel\Invoice');
    }
}
