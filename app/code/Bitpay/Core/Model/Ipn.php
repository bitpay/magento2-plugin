<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

namespace Bitpay\Core\Model;

use Magento\Framework\Exception\SampleException;

/**
 * Ipntab model
 */
class Ipn extends \Magento\Framework\Model\AbstractModel
{

    protected $_ipnCollectionFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Quote\Model\Quote
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\Db $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Bitpay\Core\Model\ResourceModel\Ipn\Collection $_ipnCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_ipnCollectionFactory = $_ipnCollectionFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Bitpay\Core\Model\ResourceModel\Ipn');
    }

    /**
     * @param string $quoteId
     * @param array  $statuses
     *
     * @return boolean
     */
    function GetStatusReceived($quoteId, $statuses)
    {
        $objectmanager = \Magento\Framework\App\ObjectManager::getInstance();
        if (!$quoteId)
        {
            return false;
        }
        $quote_model = $objectmanager->get('\Magento\Quote\Model\Quote');
        $quote = $quote_model->load($quoteId, 'entity_id');
        if (!$quote)
        {
            $objectmanager->get('\Bitpay\Core\Helper\Data')->debugData('[ERROR] quote not found.');
            return false;
        }

        $collection = $this->_ipnCollectionFactory->load();//$objectmanager->create('\Bitpay\Core\Model\Ipn')->getCollection();

        foreach ($collection as $i)
        {
            $postData = json_decode($i->getData('pos_data'), true);
            if (isset($postData['quoteId']) && $quoteId == $postData['quoteId']) {
                if (in_array($i->getData('status'), $statuses)) {
                    return true;
                }
            }
        }
        return false;       
    }

    /**
     * @param string $quoteId
     *
     * @return boolean
     */
    function GetQuotePaid($quoteId)
    {
        return $this->GetStatusReceived($quoteId, array('paid', 'confirmed', 'complete'));
    }
   
}