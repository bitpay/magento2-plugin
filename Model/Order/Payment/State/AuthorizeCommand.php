<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bitpay\Core\Model\Order\Payment\State;

class AuthorizeCommand extends  \Magento\Sales\Model\Order\Payment\State\AuthorizeCommand
{

    public function execute(\Magento\Sales\Api\Data\OrderPaymentInterface $payment, $amount, \Magento\Sales\Api\Data\OrderInterface $order)
    {
        $paymentmethod = $order -> getPayment();
        $paymentMethodCode = $paymentmethod -> getMethodInstance() -> getCode();
        if ($paymentMethodCode != 'bitpay'){
        $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
        $status = false;
        $formattedAmount = $order->getBaseCurrency()->formatTxt($amount);
        if ($payment->getIsTransactionPending()) {
            $state = \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
            $message = __(
                'We will authorize %1 after the payment is approved at the payment gateway.',
                $formattedAmount
            );
        } else {
            if ($payment->getIsFraudDetected()) {
                $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $message = __(
                    'Order is suspended as its authorizing amount %1 is suspected to be fraudulent.',
                    $formattedAmount
                );
            } else {
                $message = __('Authorized amount of %1', $formattedAmount);
            }
        }
        
       }
       else {
                    $state = \Magento\Sales\Model\Order::STATE_NEW;
                    $status = false;
                    $formattedAmount = $order->getBaseCurrency()->formatTxt($amount);
                    $message = __('Authorized amount of %1', $formattedAmount);

       }
       if ($payment->getIsFraudDetected()) {
            $status = \Magento\Sales\Model\Order::STATUS_FRAUD;
        }
        $this->setOrderStateAndStatus($order, $status, $state);

        return $message;
    }


}
