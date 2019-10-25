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

namespace ImaginationMedia\PaymentRequest\Controller\Cart;

use ImaginationMedia\PaymentRequest\Model\Address;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Rate as ShippingRate;
use Magento\Store\Model\StoreManagerInterface;

class Totals extends Action
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
     * @var ShippingRate
     */
    protected $shippingRate;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Address
     */
    protected $address;

    const REQUIRED_FIELDS = [
        "quoteId",
        "shippingAddress",
        "shippingMethod"
    ];

    /**
     * Totals constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param CartRepositoryInterface $cartRepository
     * @param ShippingRate $shippingRate
     * @param Session $session
     * @param JsonFactory $jsonFactory
     * @param Address $address
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CartRepositoryInterface $cartRepository,
        ShippingRate $shippingRate,
        Session $session,
        JsonFactory $jsonFactory,
        Address $address
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->cartRepository = $cartRepository;
        $this->shippingRate = $shippingRate;
        $this->session = $session;
        $this->jsonFactory = $jsonFactory;
        $this->address = $address;
    }

    /**
     * Get cart totals
     * @return bool|ResponseInterface|Json|ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $error = "";
        $totals = [];
        $jsonResult = $this->jsonFactory->create();

        /**
         * Validate info
         */
        foreach (self::REQUIRED_FIELDS as $REQUIRED_FIELD) {
            if (!isset($params[$REQUIRED_FIELD]) ||
                in_array($params[$REQUIRED_FIELD], ["undefined", "null"])) {
                $error = __("Not provided " . $REQUIRED_FIELD . " field.");
            }
        }

        if ($error === "") {
            $quoteId = (int)$params["quoteId"];
            $store = $this->storeManager->getStore();
            $cart = $this->cartRepository->get($quoteId);
            $customerId = $this->session->isLoggedIn() ? (int)$this->session->getCustomerId() : 0;

            /**
             * Validate if there is an active quote
             */
            if (!$cart instanceof Quote || is_null($cart->getData("entity_id"))) {
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
            $magentoAddress = $this->address->convertAddressToMagentoAdress(
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
            $this->shippingRate
                ->setCode($shippingMethod)
                ->getPrice();
            $shippingAddress = $cart->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($shippingMethod);
            $cart->getShippingAddress()->addShippingRate($this->shippingRate);

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
        }

        if ($error !== "") {
            $totals = [];
        }

        $jsonResult->setData([
            "error" => $error,
            "totals" => $totals
        ]);

        return $jsonResult;
    }
}
