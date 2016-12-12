# Using the BitPay plugin for Magento

## Prerequisites
You must have a BitPay business account to use this plugin.  It's free to [sign-up for a BitPay business account](https://bitpay.com/start).


## Server Requirements

* Last Cart Version Tested: 2.1.2
* [Magento CE](http://magento.com/resources/system-requirements) 2.0.0 or higher. Older versions might work, however this plugin has been validated to work against the 2.1.2 Community Edition release.
* [GMP](http://us2.php.net/gmp) or [BC Math](http://us2.php.net/manual/en/book.bc.php) PHP extensions.  GMP is preferred for performance reasons but you may have to install this as most servers do not come with it installed by default.  BC Math is commonly installed however and the plugin will fall back to this method if GMP is not found.
* [OpenSSL](http://us2.php.net/openssl) Must be compiled with PHP and is used for certain cryptographic operations.
* [PHP](http://us2.php.net/downloads.php) 5.4 or higher. This plugin will not work on PHP 5.3 and below. This plugin was tested with PHP 5.6 and PHP 7.0


## Installation

**From the Magento Marketplace:**

Goto [http://www.magentocommerce.com/magento-connect/bitpay-payment-method.html](http://www.magentocommerce.com/magento-connect/bitpay-payment-method.html) and click the *Install Now* link which will give you the *Extension Key* needed for the next step.

Once you have the key, log into you Magento Store's Admin Panel and navigate to **System > Magento Connect > Magento Connect Manager**.

**NOTE:** It may ask you to log in again using the same credentials that you use to log into the Admin Panel.

All you need to do is paste the extension key and click on the *Install* button.

**WARNING:** It is good practice to backup your database before installing extensions. Please make sure you Create Backups.


**From the Releases Page:**

Visit the [Releases](https://github.com/bitpay/magento2-plugin/releases) page of this repository and download the latest version. Once this is done, you can just unzip the contents and use any method you want to put them on your server. The contents will mirror the Magento directory structure.

1. Disable cache, system -> cache management
2. Copy in app and lib directories from plugin zip file to Magento2 root directory, app and lib
3. Run 'php -f bin/magento setup:upgrade' from command line on server
4. Run 'php -f bin/magento setup:di:compile' from command line on server
5. In admin interface, go to stores -> configuration -> advanced -> advanced, turn on 'Bitpay_Core'
6. Then in stores -> configuration -> sales -> payment methods, you should have Bitpay as a payment method

**NOTE:** These files can also up uploaded using the *Magento Marketplace* that comes with your Magento Store

**WARNING:** It is good practice to backup your database before installing extensions. Please make sure you Create Backups.


**Using Modman:**

Using [modman](https://github.com/colinmollenhour/modman) you can install the BitPay Magento Plugin. Once you have modman installed, run `modman init` if you have not already done so. Next just run `modman clone https://github.com/bitpay/magento2-plugin.git` in the root of the Magento installation. In this case it is `/var/www/magento`.


**NOTE:** You will need to follow the above steps under "From Releases Page" after retrieving the code by running modman.


## Configuration

Configuration can be done using the Administrator section of your Magento store. Once Logged in, you will find the configuration settings under **Stores > Configuration > Sales > Payment Methods**.

![BitPay Magento Settings](https://raw.githubusercontent.com/bitpay/magento2-plugin/master/docs/Magento2Settings.png "BitPay Magento Settings")

Here your will need to create a [pairing code](https://bitpay.com/api-tokens) using your BitPay merchant account. Once you have a Pairing Code, put the code in the Pairing Code field. This will take care of the rest for you.

**NOTE:** Pairing Codes are only valid for a short period of time. If it expires before you get to use it, you can always create a new one an use the new one.

**NOTE:** You will only need to do this once since each time you do this, the extension will generate public and private keys that are used to identify you when using the API.

You are also able to configure how BitPay's IPN (Instant Payment Notifications) changes the order in your Magento store.

![BitPay Invoice Settings](https://raw.githubusercontent.com/bitpay/magento2-plugin/master/docs/Magento2InvoiceSettings.png "BitPay Invoice Settings")


## Usage

Once enabled, your customers will be given the option to pay with Bitcoins. Once they checkout they are redirected to a full screen BitPay invoice to pay for the order.

As a merchant, the orders in your Magento store can be treated as any other order. You may need to adjust the Invoice Settings depending on your order fulfillment.
