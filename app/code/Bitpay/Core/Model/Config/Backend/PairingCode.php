<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */
namespace Bitpay\Core\Model\Config\Backend;

/**
 * This class will take the pairing code the merchant entered and pair it with
 * BitPay's API.
 */
class PairingCode extends \Magento\Framework\App\Config\Value 
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Bitpay\Core\Helper\Data $_helper,
        array $data = [])
    {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->messageManager = $messageManager;
        $this->helper = $_helper;
    }

    
    /**
     * @inheritdoc
     */
    public function save()
    {
        /**
         * If the user has put a paring code into the text field, we want to
         * pair the magento store to the stores keys. If the merchant is just
         * updating a configuration setting, we could care less about the
         * pairing code.
         */
        $pairingCode = trim($this->getValue()); 

        if (true === empty($pairingCode)) {
            return;
        }

        $this->helper->debugData('[INFO] In Bitpay_Core_Model_Config_PairingCode::save(): attempting to pair with BitPay with pairing code ' . $pairingCode);
        try {
            $this->helper->sendPairingRequest($pairingCode);
        } catch (\Exception $e) {
            $this->helper->debugData(sprintf('[ERROR] Exception thrown while calling the sendPairingRequest() function. The specific error message is: "%s"', $e->getMessage()));
            $this->messageManager->addError('There was an error while trying to pair with BitPay using the pairing code '.$pairingCode.'. Please try again or enable debug mode and send the "payment_bitpay.log" file to support@bitpay.com for more help.');

            return;
        }
       $this->messageManager->addSuccess('Pairing with BitPay was successful.');
    }
}
