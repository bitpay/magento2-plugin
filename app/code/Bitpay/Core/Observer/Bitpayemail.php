<?php
namespace Bitpay\Core\Observer;
use Magento\Framework\Event\ObserverInterface;
class Bitpayemail implements ObserverInterface {
	protected $_logger;
	protected $order;
	public function __construct(\Magento\Sales\Model\Order $order, \Bitpay\Core\Helper\Data $logger, array $data = []) {
		$this -> _logger = $logger;

	}

	// If the customer has not already been notified by email
	// send the notification now that there's a new order.
	public function execute(\Magento\Framework\Event\Observer $observer) {
		$orderIds = $observer -> getEvent() -> getOrderIds();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$order = $objectManager -> get('Magento\Sales\Model\Order') -> load($orderIds[0]);
		$payment = $order -> getPayment();
		$paymentMethodCode = $payment -> getMethodInstance() -> getCode();
		if ($paymentMethodCode == 'bitpay' && (!$order -> getEmailSent())) {
			$this -> _logger -> debugData('Order email not sent so I am calling NewOrderEmail now...');
			$orderSender = $objectManager -> get('Magento\Sales\Model\Order\Email\Sender\OrderSender');
			if ($orderSender -> send($order)) {
				$this -> _logger -> debugData('Order email sent successfully.');
			}
		}

	}

}

