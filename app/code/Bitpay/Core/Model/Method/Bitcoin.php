<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */
namespace Bitpay\Core\Model\Method;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\TransparentInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\TMRobokassa\Model\Config\Source\Order\Status\Paymentreview;
use Magento\Sales\Model\Order;

/**
 * Bitcoin payment method
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class Bitcoin extends AbstractMethod
{
    
    const CODE = 'bitpay';

    protected $_code                        = self::CODE;
    protected $_formBlockType               = 'Bitpay\Core\Block\Form\Bitpay';
    protected $_infoBlockType               = 'Bitpay\Core\Block\Info';

    protected $_isGateway                   = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = false;
    protected $_canUseInternal              = false;
    protected $_isInitializeNeeded          = false;
    protected $_canFetchTransactionInfo     = false;
    protected $_canManagerRecurringProfiles = false;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = true;
    protected $_canCapturePartial           = false;
    protected $_canRefund                   = false;
    protected $_canVoid                     = false;


    protected $_debugReplacePrivateDataKeys = array();

    protected static $_redirectUrl;

    protected $priceCurrency;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Authorizenet\Model\Directpost\Response
     */
    protected $response;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * Order factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    protected $_objectManager;

    protected $quoteManagement;

    // protected $helperdata;

    protected $invoiceFactory;

     protected $quoteFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $cart;


    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        PriceCurrencyInterface $priceCurrency,
        ZendClientFactory $httpClientFactory,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Sales\Model\Order $orderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Bitpay\Core\Model\Invoice $invoiceFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->orderFactory = $orderFactory;
        $this->_storeManager = $storeManager;
        $this->quoteRepository = $quoteRepository;
        $this->orderSender = $orderSender;
        $this->transactionRepository = $transactionRepository;
        $this->priceCurrency = $priceCurrency;
        $this->quoteManagement = $quoteManagement;
        $this->invoiceFactory = $invoiceFactory;
        $this->cart = $cart;
        $this->quoteFactory = $quoteFactory;
        
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $_scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        
    }


    /**
    *
    *
    **/
    protected function getHelper()
    {
    	$bitpayHelper = \Magento\Framework\App\ObjectManager::getInstance()->get('\Bitpay\Core\Helper\Data');
    	return $bitpayHelper;
    }
    

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
    	return parent::isAvailable($quote);
    }

    /**
     * @param  Mage_Sales_Model_Order_Payment  $payment
     * @param  float                           $amount
     * @return Bitpay_Core_Model_PaymentMethod
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount,$iframe = false)
    {
        
        // Check if coming from iframe or submit button
/**
        if ((!$this->_scopeConfig->getValue('payment/bitpay/fullscreen') && $iframe === false)
            || ($this->_scopeConfig->getValue('payment/bitpay/fullscreen') && $iframe === true)) {
            $quoteId = $payment->getOrder()->getQuoteId();
            $ipn     = \Magento\Framework\App\ObjectManager::getInstance()->get('\Bitpay\Core\Model\Ipn');

            if (!$ipn->GetQuotePaid($quoteId))
            {
                $this->getHelper()->debugData('[ERROR] Order not paid for. Please pay first and then Place Your Order.');
                // This is the error that is displayed to the customer during checkout.
                throw new \Magento\Framework\Exception\CouldNotSaveException("Order not paid for.  Please pay first and then Place your Order.");
                
            }

            return $this;
        } */

        if (false === isset($payment) || false === isset($amount) || true === empty($payment) || true === empty($amount)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::authorize(): missing payment or amount parameters.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::authorize(): missing payment or amount parameters.');
        }

        $this->getHelper()->debugData('[INFO] \Bitpay\Core\Model\Method\Bitcoin::authorize(): authorizing new order.');

        // Create BitPay Invoice
        $invoice = $this->initializeInvoice();

        if (false === isset($invoice) || true === empty($invoice)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::authorize(): could not initialize invoice.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::authorize(): could not initialize invoice.');
        }

        $objectmanager = \Magento\Framework\App\ObjectManager::getInstance();

        $quote = $objectmanager->get('\Magento\Checkout\Model\Session')->getQuote();

        $invoice = $this->prepareInvoice($invoice, $payment, $amount);

        try {
            $bitpayInvoice = $this->getHelper()->getBitpayClient()->createInvoice($invoice);
        } catch (\Exception $e) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::authorize(): ' . $e->getMessage());
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::authorize(): Could not authorize transaction.');
        }

        self::$_redirectUrl = ($this->_scopeConfig->getValue('payment/bitpay/fullscreen')) ? $bitpayInvoice->getUrl(): $bitpayInvoice->getUrl().'&view=iframe';

        
        $this->getHelper()->debugData('[INFO] BitPay Invoice created. Invoice URL: '.$bitpayInvoice->getUrl());

        
        $order = $payment->getOrder();

        // $mirrorInvoice = $this->invoiceFactory->create()->prepareWithBitpayInvoice($bitpayInvoice,$order)->save();//->prepareWithOrder(array('increment_id' => $order->getIncrementId(), 'quote_id'=> $quote->getId()));//->save();
        // throw new \Magento\Framework\Exception\LocalizedException("Error Processing Request");
        // $this->getHelper()->debugData(json_encode($mirrorInvoice->getData()));
        
        if (false === isset($bitpayInvoice) || true === empty($bitpayInvoice)) {
            $this->getHelper()->debugData('[ERROR] In Bitpay_Core_Model_Invoice::prepareWithBitpayInvoice(): Missing or empty $invoice parameter.');
            throw new \Exception('In Bitpay_Core_Model_Invoice::prepareWithBitpayInvoice(): Missing or empty $invoice parameter.');
        }

        if (false === isset($order) || true === empty($order)) {
            $this->getHelper()->debugData('[ERROR] In Bitpay_Core_Model_Invoice::prepateWithOrder(): Missing or empty $order parameter.');
            throw new \Exception('In Bitpay_Core_Model_Invoice::prepateWithOrder(): Missing or empty $order parameter.');
        }

        $id               = $invoice->getId();
        $url              = $invoice->getUrl();
        $pos_data         = $invoice->getPosData();
        $status           = $invoice->getStatus();
        $btc_price        = $invoice->getBtcPrice();
        $price            = $invoice->getPrice();
        $currency         = $invoice->getCurrency()->getCode();
        $order_id         = $invoice->getOrderId();
        $invoice_time     = intval($invoice->getInvoiceTime() / 1000);
        $expiration_time  = intval($invoice->getExpirationTime() / 1000);
        $current_time     = intval($invoice->getCurrentTime() / 1000);
        $btc_paid         = $invoice->getBtcPaid();
        $rate             = $invoice->getRate();
        $exception_status = !empty($invoice->getExceptionStatus()) ? $invoice->getExceptionStatus() : null;
        $quote_id         = $order['quote_id'];
        $increment_id     = $order['increment_id'];
        
        $resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $connection= $resources->getConnection();
        $invoiceTable = $resources->getTableName('bitpay_invoices');
        

        $sql = "Insert into " . $invoiceTable . "(id,quote_id,url,increment_id,pos_data,status,btc_price,price,currency,order_id,invoice_time,expiration_time,btc_paid,rate,exception_status) Values ('" . $id . "','".$quote_id."','".$url."','".$increment_id."','" . $pos_data . "','" . $status . "','" . $btc_price . "','" . $price . "','" . $currency . "','" .$order_id ."','". $invoice_time . "','" . $expiration_time . "','".$btc_paid."','" . $rate . "','" . $exception_status . "')";
        $connection->query($sql);

        $this->getHelper()->debugData($invoiceTable. ' table updated');

        $this->getHelper()->debugData('[INFO] Leaving \Bitpay\Core\Model\Method\Bitcoin::authorize(): invoice id ' . $bitpayInvoice->getId());

        return $this;
    }

    /**
     * This makes sure that the merchant has setup the extension correctly
     * and if they have not, it will not show up on the checkout.
     *
     * @see Mage_Payment_Model_Method_Abstract::canUseCheckout()
     * @return bool
     */
    public function canUseCheckout()
    {
        $token = $this->_scopeConfig->getValue('payment/bitpay/token');

        if (false === isset($token) || true === empty($token)) {
            /**
             * Merchant must goto their account and create a pairing code to
             * enter in.
             */
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::canUseCheckout(): There was an error retrieving the token store param from the database or this Magento store does not have a BitPay token.');

            return false;
        }

        $this->getHelper()->debugData('[INFO] Leaving \Bitpay\Core\Model\Method\Bitcoin::canUseCheckout(): token obtained from storage successfully.');

        return true;
    }

    /**
     * Fetchs an invoice from BitPay
     *
     * @param string $id
     * @return Bitpay\Invoice
     */
    public function fetchInvoice($id)
    {
        if (false === isset($id) || true === empty($id)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::fetchInvoice(): missing or invalid id parameter.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::fetchInvoice(): missing or invalid id parameter.');
        } else {
            $this->getHelper()->debugData('[INFO] In \Bitpay\Core\Model\Method\Bitcoin::fetchInvoice(): function called with id ' . $id);
        }

       $this->getHelper()->registerAutoloader();

        $client  = $this->getHelper()->getBitpayClient();

        if (false === isset($client) || true === empty($client)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::fetchInvoice(): could not obtain BitPay client.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::fetchInvoice(): could not obtain BitPay client.');
        } else {
            $this->getHelper()->debugData('[INFO] In \Bitpay\Core\Model\Method\Bitcoin::fetchInvoice(): obtained BitPay client successfully.');
        }

        $invoice = $client->getInvoice($id);

        if (false === isset($invoice) || true === empty($invoice)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::fetchInvoice(): could not retrieve invoice from BitPay.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::fetchInvoice(): could not retrieve invoice from BitPay.');
        } else {
            $this->getHelper()->debugData('[INFO] In \Bitpay\Core\Model\Method\Bitcoin::fetchInvoice(): successfully retrieved invoice id ' . $id . ' from BitPay.');
        }

        return $invoice;
    }

    /**
     * given Mage_Core_Model_Abstract, return api-friendly address
     *
     * @param $address
     *
     * @return array
     */
    public function extractAddress($address)
    {
        if (false === isset($address) || true === empty($address)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::extractAddress(): missing or invalid address parameter.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::extractAddress(): missing or invalid address parameter.');
        } else {
            $this->getHelper()->debugData('[INFO] In \Bitpay\Core\Model\Method\Bitcoin::extractAddress(): called with good address parameter, extracting now.');
        }

        $options              = array();
        $options['buyerName'] = $address->getName();

        if ($address->getCompany()) {
            $options['buyerName'] = $options['buyerName'].' c/o '.$address->getCompany();
        }

        $options['buyerAddress1'] = $address->getStreet1();
        $options['buyerAddress2'] = $address->getStreet2();
        $options['buyerAddress3'] = $address->getStreet3();
        $options['buyerAddress4'] = $address->getStreet4();
        $options['buyerCity']     = $address->getCity();
        $options['buyerState']    = $address->getRegionCode();
        $options['buyerZip']      = $address->getPostcode();
        $options['buyerCountry']  = $address->getCountry();
        $options['buyerEmail']    = $address->getEmail();
        $options['buyerPhone']    = $address->getTelephone();

        // trim to fit API specs
        foreach (array('buyerName', 'buyerAddress1', 'buyerAddress2', 'buyerAddress3', 'buyerAddress4', 'buyerCity', 'buyerState', 'buyerZip', 'buyerCountry', 'buyerEmail', 'buyerPhone') as $f) {
            if (true === isset($options[$f]) && strlen($options[$f]) > 100) {
                $this->getHelper()->debugData('[WARNING] In \Bitpay\Core\Model\Method\Bitcoin::extractAddress(): the ' . $f . ' parameter was greater than 100 characters, trimming.');
                $options[$f] = substr($options[$f], 0, 100);
            }
        }

        return $options;
    }

    /**
     * This is called when a user clicks the `Place Order` button
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $this->getHelper()->debugData('[INFO] In \Bitpay\Core\Model\Method\Bitcoin::getOrderPlaceRedirectUrl(): $_redirectUrl is ' . self::$_redirectUrl);

        return self::$_redirectUrl;

    }

    
    /**
     * Create a new invoice with as much info already added. It should add
     * some basic info and setup the invoice object.
     *
     * @return Bitpay\Invoice
     */
    private function initializeInvoice()
    {
        $this->getHelper()->registerAutoloader();

        $invoice = new \Bitpay\Invoice();

        if (false === isset($invoice) || true === empty($invoice)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::initializeInvoice(): could not construct new BitPay invoice object.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::initializeInvoice(): could not construct new BitPay invoice object.');
        } else {
            $this->getHelper()->debugData('[INFO] In \Bitpay\Core\Model\Method\Bitcoin::initializeInvoice(): constructed new BitPay invoice object successfully.');
        }
        
        $invoice->setFullNotifications(true);
        $invoice->setTransactionSpeed($this->_scopeConfig->getValue('payment/bitpay/speed'));
        $invoice->setNotificationUrl($this->_storeManager->getStore()->getUrl($this->_scopeConfig->getValue('payment/bitpay/notification_url')));
        $invoice->setRedirectUrl($this->_storeManager->getStore()->getUrl($this->_scopeConfig->getValue('payment/bitpay/redirect_url')));

        return $invoice;
    }

    /**
     * Prepares the invoice object to be sent to BitPay's API. This method sets
     * all the other info that we have to rely on other objects for.
     *
     * @param Bitpay\Invoice                  $invoice
     * @param  Mage_Sales_Model_Order_Payment $payment
     * @param  float                          $amount
     * @return Bitpay\Invoice
     */
    private function prepareInvoice($invoice, $payment, $amount)
    {
        if (false === isset($invoice) || true === empty($invoice) || false === isset($payment) || true === empty($payment) || false === isset($amount) || true === empty($amount)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::prepareInvoice(): missing or invalid invoice, payment or amount parameter.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::prepareInvoice(): missing or invalid invoice, payment or amount parameter.');
        } else {
            $this->getHelper()->debugData('[INFO] In \Bitpay\Core\Model\Method\Bitcoin::prepareInvoice(): entered function with good invoice, payment and amount parameters.');
        }
        $objectmanager = \Magento\Framework\App\ObjectManager::getInstance();

        $quote_session = $objectmanager->get('\Magento\Checkout\Model\Cart')->getQuote();
        $quote = $objectmanager->create('\Magento\Quote\Model\Quote')->load($quote_session->getId());
        $order = $payment->getOrder();

        //Passing Order Ids
        $invoice->setOrderId($order->getIncrementId());
        $invoice->setPosData(json_encode(array('orderId' => $order->getIncrementId())));
        
        $invoice = $this->addCurrencyInfo($invoice, $order);
        $invoice = $this->addPriceInfo($invoice, $order->getGrandTotal());
        $invoice = $this->addBuyerInfo($invoice, $order);

        return $invoice;
    }

    /**
     * This adds the buyer information to the invoice.
     *
     * @param Bitpay\Invoice         $invoice
     * @param Mage_Sales_Model_Order $order
     * @return Bitpay\Invoice
     */
    private function addBuyerInfo($invoice, $order)
    {
        if (false === isset($invoice) || true === empty($invoice) || false === isset($order) || true === empty($order)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::addBuyerInfo(): missing or invalid invoice or order parameter.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::addBuyerInfo(): missing or invalid invoice or order parameter.');
        } else {
            $this->getHelper()->debugData('[INFO] In \Bitpay\Core\Model\Method\Bitcoin::addBuyerInfo(): function called with good invoice and order parameters.');
        }

        $buyer = new \Bitpay\Buyer();

        if (false === isset($buyer) || true === empty($buyer)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::addBuyerInfo(): could not construct new BitPay buyer object.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::addBuyerInfo(): could not construct new BitPay buyer object.');
        }


        $buyer->setFirstName($order->getCustomerFirstname());
        $buyer->setLastName($order->getCustomerLastname());
        $objectmanager = \Magento\Framework\App\ObjectManager::getInstance();

        if ($this->_scopeConfig->getValue('payment/bitpay/fullscreen')) {
            $address = $order->getBillingAddress();
        } else {
            $quote = $objectmanager->get('\Magento\Checkout\Model\Session')->getQuote();
            $address = $quote->getBillingAddress();
        }

        $street = $address->getStreet1();
        if (null !== $street && '' !== $street) {
            $buyer->setAddress(
                array(
                    $street,
                    $address->getStreet2(),
                    $address->getStreet3(),
                    $address->getStreet4()
                    )
                );
        }

        $region     = $address->getRegion();
        $regioncode = $address->getRegionCode();
        if (null !== $regioncode && '' !== $regioncode) {
            $buyer->setState($regioncode);
        } else if (null !== $region && '' !== $region) {
            $buyer->setState($region);
        }

        $country = $address->getCountry();
        if (null !== $country && '' !== $country) {
            $buyer->setCountry($country);
        }

        $city = $address->getCity();
        if (null !== $city && '' !== $city) {
            $buyer->setCity($city);
        }

        $postcode = $address->getPostcode();
        if (null !== $postcode && '' !== $postcode) {
            $buyer->setZip($postcode);
        }

        $email = $address->getEmail();
        if (null !== $email && '' !== $email) {
            $buyer->setEmail($email);
        }

        $telephone = $address->getTelephone();
        if (null !== $telephone && '' !== $telephone) {
            $buyer->setPhone($telephone);
        }

        $invoice->setBuyer($buyer);

        return $invoice;
    }

    /**
     * Adds currency information to the invoice
     *
     * @param Bitpay\Invoice         $invoice
     * @param Mage_Sales_Model_Order $order
     * @return Bitpay\Invoice
     */
    private function addCurrencyInfo($invoice, $order)
    {
        if (false === isset($invoice) || true === empty($invoice) || false === isset($order) || true === empty($order)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::addCurrencyInfo(): missing or invalid invoice or order parameter.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::addCurrencyInfo(): missing or invalid invoice or order parameter.');
        } else {
            $this->getHelper()->debugData('[INFO] In \Bitpay\Core\Model\Method\Bitcoin::addCurrencyInfo(): function called with good invoice and order parameters.');
        }

        $currency = new \Bitpay\Currency();

        if (false === isset($currency) || true === empty($currency)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::addCurrencyInfo(): could not construct new BitPay currency object.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::addCurrencyInfo(): could not construct new BitPay currency object.');
        }

        $currency->setCode($order->getOrderCurrencyCode());
        $invoice->setCurrency($currency);

        return $invoice;
    }

    /**
     * Adds pricing information to the invoice
     *
     * @param Bitpay\Invoice  invoice
     * @param float           $amount
     * @return Bitpay\Invoice
     */
    private function addPriceInfo($invoice, $amount)
    {
        if (false === isset($invoice) || true === empty($invoice) || false === isset($amount) || true === empty($amount)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::addPriceInfo(): missing or invalid invoice or amount parameter.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::addPriceInfo(): missing or invalid invoice or amount parameter.');
        } else {
            $this->getHelper()->debugData('[INFO] In \Bitpay\Core\Model\Method\Bitcoin::addPriceInfo(): function called with good invoice and amount parameters.');
        }

        $item = new \Bitpay\Item();

        if (false === isset($item) || true === empty($item)) {
            $this->getHelper()->debugData('[ERROR] In \Bitpay\Core\Model\Method\Bitcoin::addPriceInfo(): could not construct new BitPay item object.');
            throw new \Exception('In \Bitpay\Core\Model\Method\Bitcoin::addPriceInfo(): could not construct new BitPay item object.');
        }

        $item->setPrice($amount);
        $invoice->setItem($item);

        return $invoice;
    }

    
}
