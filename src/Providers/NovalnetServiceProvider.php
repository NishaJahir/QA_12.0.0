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

namespace Novalnet\Providers;

use Plenty\Plugin\ServiceProvider;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodContainer;
use Novalnet\Helper\PaymentHelper;
use Novalnet\Services\PaymentService;
use Novalnet\Methods\NovalnetPaymentAbstract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Novalnet\Constants\NovalnetConstants;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\Log\Loggable;

/**
 * Class NovalnetServiceProvider
 *
 * @package Novalnet\Providers
 */
class NovalnetServiceProvider extends ServiceProvider
{
    use Loggable;

    /**
     * Register the route service provider
     */
    public function register()
    {
        $this->getApplication()->register(NovalnetRouteServiceProvider::class);
    }

    /**
     * Boot additional services for the payment method
     * 
     * @param Dispatcher $eventDispatcher
     * @param BasketRepositoryContract $basketRepository
     * @param PaymentMethodContainer $payContainer
     * @param PaymentHelper $paymentHelper
     * @param PaymentService $paymentService
     * @param FrontendSessionStorageFactoryContract $sessionStorage
     * @param Twig $twig
     */
    public function boot(Dispatcher $eventDispatcher,
                        BasketRepositoryContract $basketRepository,
                        PaymentMethodContainer $payContainer,
                        PaymentHelper $paymentHelper,
                        PaymentService $paymentService,
                        FrontendSessionStorageFactoryContract $sessionStorage,
                        Twig $twig
                        )
    {
        $this->registerPaymentMethods($payContainer);
    }
     
    /**
     * Register the Novalnet payment methods in the payment method container
     *
     * @param PaymentMethodContainer $payContainer
     */
    protected function registerPaymentMethods(PaymentMethodContainer $payContainer)
    {
        foreach(PaymentHelper::getPaymentMethods() as $paymentMethodKey => $paymentMethodClass) {
            $payContainer->register('plenty_novalnet::' . $paymentMethodKey, $paymentMethodClass,
            [
                AfterBasketChanged::class,
                AfterBasketItemAdd::class,
                AfterBasketCreate::class
            ]);
        }
    }
}
