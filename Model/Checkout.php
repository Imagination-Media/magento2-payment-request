<?php

/**
 * W3C Payment Request (https://www.w3.org/TR/payment-request/)
 *
 * Add the W3C payment request api to Magento 2
 *
 * @package     ImaginationMedia\PaymentRequest
 * @author      Igor Ludgero Miura <igor@imaginationmedia.com>
 * @copyright   Copyright (c) 2019 Imagination Media (https://www.imaginationmedia.com/)
 * @license     https://opensource.org/licenses/OSL-3.0.php Open Software License 3.0
 */

declare(strict_types=1);

namespace ImaginationMedia\PaymentRequest\Model;

use ImaginationMedia\PaymentRequest\Model\Address;
use ImaginationMedia\PaymentRequest\Model\Checkout\Braintree as BraintreeCheckout;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Quote\Api\CartManagementInterface as CartManagement;
use Magento\Quote\Api\CartRepositoryInterface as CartRepository;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Rate as ShippingRate;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

class Checkout
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CartRepository
     */
    protected $cartRepository;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var ShippingRate
     */
    protected $shippingRate;

    /**
     * @var CartManagement
     */
    protected $cartManagement;

    /**
     * @var BraintreeCheckout
     */
    protected $braintreeCheckout;

    /**
     * @var Address
     */
    protected $address;

    /**
     * Checkout constructor.
     * @param CheckoutSession $checkoutSession
     * @param CartRepository $cartRepository
     * @param StoreManager $storeManager
     * @param CustomerRepository $customerRepository
     * @param ShippingRate $shippingRate
     * @param CartManagement $cartManagement
     * @param BraintreeCheckout $braintreeCheckout
     * @param Address $address
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartRepository $cartRepository,
        StoreManager $storeManager,
        CustomerRepository $customerRepository,
        ShippingRate $shippingRate,
        CartManagement $cartManagement,
        BraintreeCheckout $braintreeCheckout,
        Address $address
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->shippingRate = $shippingRate;
        $this->cartManagement = $cartManagement;
        $this->braintreeCheckout = $braintreeCheckout;
        $this->address = $address;
    }

    /**
     * Create Magento order
     * @param array $paymentInfo
     * @param string $shippingMethod
     * @param int $quoteId
     * @param array $billingAddress
     * @param array $shippingAddress
     * @param array $contactInfo
     * @param int $customerId
     * @return bool
     */
    public function createOrder(
        array $paymentInfo,
        string $shippingMethod,
        int $quoteId,
        array $billingAddress,
        array $shippingAddress,
        array $contactInfo,
        int $customerId = null
    ): bool {
        try {
            $store = $this->storeManager->getStore();
            $cart = $this->cartRepository->get($quoteId);

            /**
             * Validate if there is an active quote
             */
            if (!$cart instanceof Quote || is_null($cart->getData("entity_id"))) {
                return false;
            }

            /**
             * Check if cart is empty
             */
            if ((int)$cart->getItemsCount() === 0) {
                return false;
            }

            $cart->setStore($store);
            $cart->setCurrency();

            /**
             * Assign customer to the order
             */
            if ($customerId !== null) {
                $customer = $this->customerRepository->getById($customerId);
                $cart->assignCustomer($customer);
            } else {
                $fullName = $contactInfo["name"];
                $names = explode(" ", $fullName);

                $cart->setCustomerEmail($contactInfo["email"]);
                $cart->setCustomerFirstname($names[0]);
                $cart->setCustomerLastname(end($names));
                $cart->setCustomerIsGuest(true);
            }

            /**
             * Set billing and shipping addreess
             */
            $magentoBillingAddress = $this->address->convertAddressToMagentoAdress(
                $billingAddress,
                $contactInfo,
                $customerId
            );
            $cart->getBillingAddress()->addData($magentoBillingAddress);

            $magentoShippingAddress = $this->address->convertAddressToMagentoAdress(
                $shippingAddress,
                $contactInfo,
                $customerId
            );
            $cart->getShippingAddress()->addData($magentoShippingAddress);

            /**
             * Set shipping method
             */
            $this->shippingRate
                ->setCode($shippingMethod)
                ->getPrice();
            $shippingAddress = $cart->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($shippingMethod);
            $cart->getShippingAddress()->addShippingRate($this->shippingRate);

            /**
             * Set payment
             */
            if ($paymentInfo["code"] === "braintree") {
                $this->braintreeCheckout->setPaymentInfo($cart, $paymentInfo);
            }

            /**
             * Apply all the changes
             */
            $cart->collectTotals();
            $cart->save();

            /**
             * Place order but don't redirect to anywhere
             */
            $orderId = $this->cartManagement->placeOrder($cart->getId());

            if ($orderId) {
                return true;
            }
        } catch (\Exception $ex) {
            return false;
        }
    }
}
