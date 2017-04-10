<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

namespace Bitpay\Core\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Store\Model\ScopeInterface;

class Config
{

     /**
     * @var array
     */
    protected $_methods;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Config\DataInterface
     */
    protected $_dataStorage;

    /**
     * Locale model
     *
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * Payment method factory
     *
     * @var \Magento\Payment\Model\Method\Factory
     */
    protected $_paymentMethodFactory;

    /**
     * DateTime
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Factory $paymentMethodFactory
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\Config\DataInterface $dataStorage
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Config\DataInterface $dataStorage,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_dataStorage = $dataStorage;
        $this->_paymentMethodFactory = $paymentMethodFactory;
        $this->localeResolver = $localeResolver;
        $this->_date = $date;
    }


    /**
     * Retrieve active system payments
     *
     * @return array
     * @api
     */
    public function getActiveMethods()
    {
        $methods = [];
        if (isset($data['active'], $data['model']) && (bool)$data['active']) {
            /** @var MethodInterface $methodModel Actually it's wrong interface */
            $methodModel = $this->_paymentMethodFactory->create($data['model']);
            $methodModel->setId($code);
            $methodModel->setStore(null);
            if ($methodModel->getConfigData('active', null)) {
                $methods[$code] = $methodModel;
            }
        }
        return $methods;
    }

    /**
     * Retrieve array of payment methods information
     *
     * @return array
     * @api
     */
    public function getMethodsInfo()
    {
        return $this->_dataStorage->get('methods');
    }

    

}