<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

namespace Bitpay\Core\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use \Magento\Customer\Helper\Session\CurrentCustomer;
use \Magento\Framework\UrlInterface;
use \Magento\Payment\Helper\Data as PaymentHelper;
use \Bitpay\Core\Helper\Data as bitpayHelper;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{


    /**
     * @var Config
     */
    private $config;
    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var BitpayHelper
     */
    protected $bitpayHelper;

    /**
     * Payment method code
     *
     * @var string
     */
    protected $methodCode;

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod
     */
    protected $method;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;


    /**
     * Constructor
     *
     * @param CurrentCustomer $currentCustomer
     * @param BitpayHelper $bitpayHelper
     * @param PaymentHelper $paymentHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Config $config,
        CurrentCustomer $currentCustomer,
        BitpayHelper $bitpayHelper,
        PaymentHelper $paymentHelper,
        UrlInterface $urlBuilder,
        $methodCode
    ) {
        $this->config = $config;
        $this->currentCustomer = $currentCustomer;
        $this->bitpayHelper = $bitpayHelper;
        $this->paymentHelper = $paymentHelper;
        $this->urlBuilder = $urlBuilder;
        $this->methodCode = $methodCode;
		$this->method = $this->paymentHelper->getMethodInstance($methodCode);
    }


    public function getConfig()
    {
       /* $config = [
            'payment' => [
                $this->methodCode => [
                    'fullScreen' => $this->config->getFullscreen(),//$this->getMethodRedirectUrl($this->methodCode)],
                ],
            ],
        ];
        // $config['payment']['redirectUrl'][$code] = $this->getMethodRedirectUrl($code);
        // $config['payment']['fullScreen'][$code] = $this->config->getValue('fullscreen');*/
        $config = [];
        return $config;
    }

    /**
     * Return redirect URL for method
     *
     * @param string $code
     * @return mixed
     */
    protected function getMethodRedirectUrl($code)
    {
        return $this->method->getOrderPlaceRedirectUrl();
    }

}