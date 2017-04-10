<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */
namespace Bitpay\Core\Helper;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	
	protected $_autoloaderRegistered;
    protected $_bitpay;
    protected $_sin;
    protected $_publicKey;
    protected $_privateKey;
    protected $_keyManager;
    protected $_client;
    protected $_bitpayModel;
    protected $_directory_list;
    protected $logger;
    protected $config;
    protected $magento_st;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    // protected $scopeConfig;

	/**
     * @param \Magento\Framework\App\Helper\Context $context
     */
	public function __construct(\Magento\Framework\App\Helper\Context $context,\Bitpay\Core\Model\Method\Bitcoin $bitpayModel,\Magento\Framework\App\Filesystem\DirectoryList $directory_list,\Bitpay\Core\Logger\Logger $logger,\Magento\Config\Model\ResourceModel\Config $config, \Bitpay\Core\Model\MagentoStorage $magento_st)
	{
		
		$this->_bitpayModel = $bitpayModel;
        $this->directory_list = $directory_list;
        $this->logger = $logger;
        $this->config = $config;
        $this->magento_st=$magento_st;
        parent::__construct($context);
	}

	/**
     * @param mixed $debugData
     */
    public function debugData($debugData)
    {
        if($this->isDebug() && !empty($debugData))        
            $this->logger->debug($debugData);
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return (boolean) $this->scopeConfig->getValue('payment/bitpay/debug');
    }

    /**
     * Returns true if Transaction Speed has been configured
     *
     * @return boolean
     */
    public function hasTransactionSpeed()
    {
        $speed = $this->scopeConfig->getValue('payment/bitpay/speed');

        return !empty($speed);
    }

    /**
     * Returns the URL where the IPN's are sent
     *
     * @return string
     */
    public function getNotificationUrl()
    {
        return $this->_storeManager->getStore()->getUrl($this->scopeConfig->getValue('payment/bitpay/notification_url'));
    }

    /**
     * Returns the URL where customers are redirected
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->_storeManager->getStore()->getUrl($this->scopeConfig->getValue('payment/bitpay/redirect_url'));
    }

    /**
     * Registers the BitPay autoloader to run before Magento's. This MUST be
     * called before using any bitpay classes.
     */
    public function registerAutoloader()
    {
        if (true === empty($this->_autoloaderRegistered)) {            
            $base = $this->directory_list->getRoot();               
            $autoloader_filename = $base.'/vendor/bitpay/php-client/src/Bitpay/Autoloader.php';
            if (true === is_file($autoloader_filename) && true === is_readable($autoloader_filename)) {
                require_once $autoloader_filename;
                \Bitpay\Autoloader::register();
                $this->_autoloaderRegistered = true;
                $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::registerAutoloader(): autoloader file was found and has been registered.');
            } else {
                $this->_autoloaderRegistered = false;
                $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::registerAutoloader(): autoloader file was not found or is not readable. Cannot continue!');
                throw new \Exception('In \Bitpay\Core\Helper\Data::registerAutoloader(): autoloader file was not found or is not readable. Cannot continue!');
            }
        }
    }

    /**
     * This function will generate keys that will need to be paired with BitPay
     * using
     */
    public function generateAndSaveKeys()
    {
        $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::generateAndSaveKeys(): attempting to generate new keypair and save to database.');

        if (true === empty($this->_autoloaderRegistered)) {
            $this->registerAutoloader();
        }

        $this->_privateKey = new \Bitpay\PrivateKey('payment/bitpay/private_key');

        if (false === isset($this->_privateKey) || true === empty($this->_privateKey)) {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::generateAndSaveKeys(): could not create new Bitpay private key object. Cannot continue!');
            throw new \Exception('In \Bitpay\Core\Helper\Data::generateAndSaveKeys(): could not create new Bitpay private key object. Cannot continue!');
        } else {
            $this->_privateKey->generate();
        }

        $this->_publicKey = new \Bitpay\PublicKey('payment/bitpay/public_key');

        if (false === isset($this->_publicKey) || true === empty($this->_publicKey)) {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::generateAndSaveKeys(): could not create new Bitpay public key object. Cannot continue!');
            throw new \Exception('In \Bitpay\Core\Helper\Data::generateAndSaveKeys(): could not create new Bitpay public key object. Cannot continue!');
        } else {
            $this->_publicKey
                 ->setPrivateKey($this->_privateKey)
                 ->generate();
        }

        $this->getKeyManager()->persist($this->_publicKey);
        $this->getKeyManager()->persist($this->_privateKey);

        $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::generateAndSaveKeys(): key manager called to persist keypair to database.');
    }

    /**
     * Send a pairing request to BitPay to receive a Token
     */
    public function sendPairingRequest($pairingCode)
    {
        if (false === isset($pairingCode) || true === empty($pairingCode)) {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::sendPairingRequest(): missing or invalid pairingCode parameter.');
            throw new \Exception('In \Bitpay\Core\Helper\Data::sendPairingRequest(): missing or invalid pairingCode parameter.');
        } else {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::sendPairingRequest(): function called with the pairingCode parameter: ' . $pairingCode);
        }

        $configFactory = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Config\Model\Config\Factory');
        $configData = [
            'section' => 'payment',
            'website' => null,
            'store'   => null,
            'groups'  => [
                'bitpay' => [
                    'fields' => [
                        'public_key' => [
                            'value' => null,
                        ],
                        'private_key' => [
                            'value' => null,
                        ],
                        'token' => [
                            'value' => null,
                        ],
                    ],
                ],
            ],
        ];
        $configModel = $configFactory->create(['data' => $configData]);
        $configModel->save();

        if (true === empty($this->_autoloaderRegistered)) {
            $this->registerAutoloader();
        }

        // Generate/Regenerate keys
        $this->generateAndSaveKeys();
        $sin = $this->getSinKey();

        if (false === isset($sin) || true === empty($sin)) {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::sendPairingRequest(): could not retrieve the SIN parameter. Cannot continue!');
            throw new \Exception('In \Bitpay\Core\Helper\Data::sendPairingRequest(): could not retrieve the SIN parameter. Cannot continue!');
        } else {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::sendPairingRequest(): attempting to pair with the SIN parameter: ' . $sin);
        }

        // Sanitize label
        $store = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Store\Model\StoreManagerInterface');
        $label = preg_replace('/[^a-zA-Z0-9 ]/', '', $store->getStore()->getName());
        $label = substr('Magento ' . $label, 0, 59);

        $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::sendPairingRequest(): using the label "' . $label . '".');

        $token = $this->getBitpayClient()->createToken(
                                                       array(
                                                            'id'          => (string) $sin,
                                                            'pairingCode' => (string) $pairingCode,
                                                            'label'       => (string) $label,
                                                       )
                                           );

        if (false === isset($token) || true === empty($token)) {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::sendPairingRequest(): could not obtain the token from the pairing process. Cannot continue!');
            throw new \Exception('In \Bitpay\Core\Helper\Data::sendPairingRequest(): could not obtain the token from the pairing process. Cannot continue!');
        } else {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::sendPairingRequest(): token successfully obtained.');
        }

        $config = $this->config;

        if (false === isset($config) || true === empty($config)) {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::sendPairingRequest(): could not create new Mage_Core_Model_Config object. Cannot continue!');
            throw new \Exception('In \Bitpay\Core\Helper\Data::sendPairingRequest(): could not create new Mage_Core_Model_Config object. Cannot continue!');
        }

        if($config->saveConfig('payment/bitpay/token', $token->getToken(), $store->getStore()->getCode(), 0)) {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::sendPairingRequest(): token saved to database.');
        } else {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::sendPairingRequest(): token could not be saved to database.');
            throw new \Exception('In \Bitpay\Core\Helper\Data::sendPairingRequest(): token could not be saved to database.');
        }
    }

    /**
     * @return \Bitpay\SinKey
     */
    public function getSinKey()
    {
        if (false === empty($this->_sin)) {
            return $this->_sin;
        }

        $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getSinKey(): attempting to get the SIN parameter.');

        if (true === empty($this->_autoloaderRegistered)) {
            $this->registerAutoloader();
        }

        $this->_sin = new \Bitpay\SinKey();

        if (false === isset($this->_sin) || true === empty($this->_sin)) {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::getSinKey(): could not create new BitPay SinKey object. Cannot continue!');
            throw new \Exception('In \Bitpay\Core\Helper\Data::getSinKey(): could not create new BitPay SinKey object. Cannot continue!');
        }

        $this->_sin
             ->setPublicKey($this->getPublicKey())
             ->generate();

        if (false === isset($this->_sin) || true === empty($this->_sin)) {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::getSinKey(): could not generate a new SIN from the public key. Cannot continue!');
            throw new \Exception('In \Bitpay\Core\Helper\Data::getSinKey(): could not generate a new SIN from the public key. Cannot continue!');
        }

        return $this->_sin;
    }

    public function getPublicKey()
    {
        if (true === isset($this->_publicKey) && false === empty($this->_publicKey)) {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getPublicKey(): found an existing public key, returning that.');
            return $this->_publicKey;
        }

        if (true === empty($this->_autoloaderRegistered)) {
            $this->registerAutoloader();
        }

        $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getPublicKey(): did not find an existing public key, attempting to load one from the key manager.');

        $this->_publicKey = $this->getKeyManager()->load('payment/bitpay/public_key');

        if (true === empty($this->_publicKey)) {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getPublicKey(): could not load a public key from the key manager, generating a new one.');
            $this->generateAndSaveKeys();
        } else {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getPublicKey(): successfully loaded public key from the key manager, returning that.');
            return $this->_publicKey;
        }

        if (false === empty($this->_publicKey)) {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getPublicKey(): successfully generated a new public key.');
            return $this->_publicKey;
        } else {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::getPublicKey(): could not load or generate a new public key. Cannot continue!');
            throw new \Exception('In \Bitpay\Core\Helper\Data::getPublicKey(): could not load or generate a new public key. Cannot continue!');
        }
    }

    public function getPrivateKey()
    {
        if (false === empty($this->_privateKey)) {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getPrivateKey(): found an existing private key, returning that.');
            return $this->_privateKey;
        }

        if (true === empty($this->_autoloaderRegistered)) {
            $this->registerAutoloader();
        }

        $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getPrivateKey(): did not find an existing private key, attempting to load one from the key manager.');

        $this->_privateKey = $this->getKeyManager()->load('payment/bitpay/private_key');

        if (true === empty($this->_privateKey)) {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getPrivateKey(): could not load a private key from the key manager, generating a new one.');
            $this->generateAndSaveKeys();
        } else {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getPrivateKey(): successfully loaded private key from the key manager, returning that.');
            return $this->_privateKey;
        }

        if (false === empty($this->_privateKey)) {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getPrivateKey(): successfully generated a new private key.');
            return $this->_privateKey;
        } else {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::getPrivateKey(): could not load or generate a new private key. Cannot continue!');
            throw new \Exception('In \Bitpay\Core\Helper\Data::getPrivateKey(): could not load or generate a new private key. Cannot continue!');
        }
    }

    /**
     * @return \Bitpay\KeyManager
     */
    public function getKeyManager()
    {
        if (true === empty($this->_keyManager)) {
            if (true === empty($this->_autoloaderRegistered)) {
                $this->registerAutoloader();
            }

            $this->_keyManager = new \Bitpay\KeyManager($this->magento_st);

            if (false === isset($this->_keyManager) || true === empty($this->_keyManager)) {
                $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::getKeyManager(): could not create new BitPay KeyManager object. Cannot continue!');
                throw new \Exception('In \Bitpay\Core\Helper\Data::getKeyManager(): could not create new BitPay KeyManager object. Cannot continue!');
            } else {
                $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getKeyManager(): successfully created new BitPay KeyManager object.');
            }
        }

        return $this->_keyManager;
    }

    /**
     * @return \Bitpay\Client
     */
    public function getBitpayClient()
    {
        if (false === empty($this->_client)) {
            return $this->_client;
        }

        if (true === empty($this->_autoloaderRegistered)) {
            $this->registerAutoloader();
        }

        $this->_client = new \Bitpay\Client\Client();

        if (false === isset($this->_client) || true === empty($this->_client)) {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::getBitpayClient(): could not create new BitPay Client object. Cannot continue!');
            throw new \Exception('In \Bitpay\Core\Helper\Data::getBitpayClient(): could not create new BitPay Client object. Cannot continue!');
        } else {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getBitpayClient(): successfully created new BitPay Client object.');
        }

        if($this->scopeConfig->getValue('payment/bitpay/network') === 'livenet') {
          $network = new \Bitpay\Network\Livenet();
        } else {
          $network = new \Bitpay\Network\Testnet();
        }
        $adapter = new \Bitpay\Client\Adapter\CurlAdapter();

        $this->_client->setPublicKey($this->getPublicKey());
        $this->_client->setPrivateKey($this->getPrivateKey());
        $this->_client->setNetwork($network);
        $this->_client->setAdapter($adapter);
        $this->_client->setToken($this->getToken());

        return $this->_client;
    }

    public function getToken()
    {
        if (true === empty($this->_autoloaderRegistered)) {
            $this->registerAutoloader();
        }

        $token = new \Bitpay\Token();

        if (false === isset($token) || true === empty($token)) {
            $this->debugData('[ERROR] In \Bitpay\Core\Helper\Data::getToken(): could not create new BitPay Token object. Cannot continue!');
            throw new \Exception('In \Bitpay\Core\Helper\Data::getToken(): could not create new BitPay Token object. Cannot continue!');
        } else {
            $this->debugData('[INFO] In \Bitpay\Core\Helper\Data::getToken(): successfully created new BitPay Token object.');
        }

        $token->setToken($this->scopeConfig->getValue('payment/bitpay/token'));

        return $token;
    }
    
}
