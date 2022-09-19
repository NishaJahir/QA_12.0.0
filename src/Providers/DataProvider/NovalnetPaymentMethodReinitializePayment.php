<?php
/**
 * This module is used for real time processing of
 * Novalnet payment module of customers.
 * This free contribution made by request.
 * 
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author       Novalnet AG
 * @copyright(C) Novalnet 
 * All rights reserved. https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */

namespace Novalnet\Providers\DataProvider;

use Plenty\Plugin\Templates\Twig;
use Novalnet\Services\PaymentService;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;


class NovalnetPaymentMethodReinitializePayment
{
    public function call(Twig $twig, $arg)
    {
        $order = $arg[0];
        $paymentService = pluginApp(PaymentService::class);
        $basketRepository = pluginApp(BasketRepositoryContract::class);
        $sessionStorage = pluginApp(FrontendSessionStorageFactoryContract::class);
        // Load the basket object
        $basket = $basketRepository->load();
        
        // Get the Novalnet payment method Id
        foreach($order['properties'] as $orderProperty) {
            if($orderProperty['typeId'] == 3)
            {
                $mopId = $orderProperty['value'];
            }
        }
        
        // Get the Novalnet payment key and MOP Id
        $transactionDetails = $paymentService->getDetailsFromPaymentProperty($order['id']);
        
        // Build the payment request parameters
        if(!empty($basket)) {
            // Assign the billing and shipping Id
            $basket->customerInvoiceAddressId = !empty($basket->customerInvoiceAddressId) ? $basket->customerInvoiceAddressId: $order['billingAddress']['id'];
            $basket->customerShippingAddressId = !empty($basket->customerShippingAddressId) ? $basket->customerShippingAddressId : $order['deliveryAddress']['id'];
            // Payment request parameters
            $paymentRequestData = $paymentService->generatePaymentParams($basket, strtoupper($transactionDetails['paymentName']));
            
            // Assign the requested paramters into session
            $sessionStorage->getPlugin()->setValue('nnPaymentData', $paymentRequestData);
            $sessionStorage->getPlugin()->setValue('nnOrderNo', $order['id']);
            
        }

        // If the Novalnet payments are rejected do the reinitialize payment
        if(strpos($transactionDetails['paymentName'], 'novalnet') !== false &&  ((!empty($transactionDetails['tx_status']) && !in_array($transactionDetails['tx_status'], ['PENDING', 'ON_HOLD', 'CONFIRMED', 'DEACTIVATED'])) || empty($transactionDetails['tx_status']))) {
            return $twig->render('Novalnet::NovalnetPaymentMethodReinitializePayment', [
                                'order' => $order,
                                'paymentMethodId' => $mopId,
                                'paymentKey' => $transactionDetails['paymentName']
                                ]);
        } else {
            return '';
        }
    }
}
