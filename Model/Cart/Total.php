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

namespace ImaginationMedia\PaymentRequest\Model\Cart;

use ImaginationMedia\PaymentRequest\Model\Address\Action as Address;
use ImaginationMedia\PaymentRequest\Model\Cart\Validator;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\RateFactory as ShippingRateFactory;
use Magento\Store\Model\StoreManagerInterface;

class Total
{

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var ShippingRateFactory
     */
    protected $shippingRateFactory;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var Validator
     */
    protected $totalValidator;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * Total constructor.
     * @param StoreManagerInterface $storeManager
     * @param CartRepositoryInterface $cartRepository
     * @param ShippingRateFactory $shippingRateFactory
     * @param Address $address
     * @param Validator $totalValidator
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CartRepositoryInterface $cartRepository,
        ShippingRateFactory $shippingRateFactory,
        Address $address,
        Validator $totalValidator,
        CheckoutSession $checkoutSession
    ) {
        $this->storeManager = $storeManager;
        $this->cartRepository = $cartRepository;
        $this->shippingRateFactory = $shippingRateFactory;
        $this->address = $address;
        $this->totalValidator = $totalValidator;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Calculate totals
     * @param array $params
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getTotals(array $params) : array
    {
        $error = "";
        $totals = [];

        $validationResult = $this->totalValidator->validate($params);

        if (!$validationResult['error']) {
            $quote = $this->checkoutSession->getQuote();
            $quoteId = (int)$quote->getId();
            $store = $this->storeManager->getStore();
            $cart = $this->cartRepository->get($quoteId);
            $customerId = ($quote->getCustomerId()) ? (int)$quote->getCustomerId() : null;

            /**
             * Validate if there is an active quote
             */
            if (!$cart instanceof Quote || is_null($cart->getEntityId())) {
                $error = __("Invalid cart");
            }

            /**
             * Check if cart is empty
             */
            if ((int)$cart->getItemsCount() === 0) {
                $error = __("Empty cart");
            }

            $cart->setStore($store);
            $cart->setCurrency();

            /**
             * Set shipping addreess
             */
            $magentoAddress = $this->address->execute(
                $params["shippingAddress"],
                [],
                $customerId
            );
            $cart->getShippingAddress()->addData($magentoAddress);

            /**
             * Set shipping method
             */
            $shippingMethod = $params["shippingMethod"]["carrier_code"] . "_" .
                $params["shippingMethod"]["method_code"];

            $shippingRate = $this->shippingRateFactory->create();

            $shippingRate
                ->setCode($shippingMethod)
                ->getPrice();
            $shippingAddress = $cart->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($shippingMethod);
            $cart->getShippingAddress()->addShippingRate($shippingRate);

            /**
             * Apply all the changes
             */
            $cart->collectTotals();
            $cart->save();

            $magentoTotals = $cart->getTotals();

            foreach ($magentoTotals as $total) {
                if ($total["value"] > 0) {
                    $totals[$total["code"]] = $total["value"];
                }
            }

            /**
             * Add discounts by coupon code
             */
            $couponCode = (string)$cart->getCouponCode();
            if ($couponCode !== "") {
                $totals["discount"] = [
                    "label" => sprintf(__("Discount (%s)"), $cart->getCouponCode()),
                    "amount" => -($cart->getSubtotal() - $cart->getSubtotalWithDiscount())
                ];
            }
        } else {
            $error = $validationResult['error_message'];
        }

        return [
            'error' => $error,
            'totals' => $totals
        ];
    }
}
