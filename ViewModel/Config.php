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

namespace ImaginationMedia\PaymentRequest\ViewModel;

use Magento\Braintree\Gateway\Config\Config as BraintreeConfig;
use Magento\Braintree\Gateway\Request\PaymentDataBuilder;
use Magento\Braintree\Model\Adapter\BraintreeAdapterFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config implements ArgumentInterface
{
    const SYSTEM_CONFIG_ACTIVE = "payment/payment_request/active";
    const SYSTEM_CONFIG_BUTTON_MODE = "payment/payment_request/button_mode";

    const SYSTEM_CONFIG_CARD_ENABLED = "payment/payment_request/payments/card/enable";
    const SYSTEM_CONFIG_CARD_TYPES = "payment/payment_request/payments/card/card_types";
    const SYSTEM_CONFIG_CARD_FLAG = "payment/payment_request/payments/card/card_flags";
    const SYSTEM_CONFIG_CARD_PRE_PAID = "payment/payment_request/payments/card/pre_paid";
    const SYSTEM_CONFIG_CARD_PAYMENT_METHOD = "payment/payment_request/payments/card/payment_method";
    const SYSTEM_CONFIG_CARD_SORT_ORDER = "payment/payment_request/payments/card/sort_order";

    const SYSTEM_CONFIG_PAYPAL_ENABLED = "payment/payment_request/payments/paypal/enable";
    const SYSTEM_CONFIG_PAYPAL_SORT_ORDER = "payment/payment_request/payments/paypal/sort_order";

    const BUTTON_MODE_REPLACE_CHECKOUT = 1;
    const BUTTON_MODE_ADDITIONAL_BUTTON = 2;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var BraintreeAdapterFactory
     */
    protected $braintreeAdapterFactory;

    /**
     * @var BraintreeConfig
     */
    protected $braintreeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var float
     */
    protected $totalPrice = 0;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $session
     * @param BraintreeAdapterFactory $braintreeAdapterFactory
     * @param BraintreeConfig $braintreeConfig
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $url
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $session,
        BraintreeAdapterFactory $braintreeAdapterFactory,
        BraintreeConfig $braintreeConfig,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        UrlInterface $url
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->braintreeAdapterFactory = $braintreeAdapterFactory;
        $this->braintreeConfig = $braintreeConfig;
        $this->storeManager = $storeManager;
        $this->url = $url;
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * Is W3C Web Payments enabled
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::SYSTEM_CONFIG_ACTIVE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the button mode (replace checkout or show additional button)
     * @return int
     */
    public function getButtonMode() : int
    {
        return (int)$this->scopeConfig->getValue(
            self::SYSTEM_CONFIG_BUTTON_MODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * =============================== CARD CONFIG ===================================
     */

    /**
     * Is credit/debit card payments enabled
     * @return bool
     */
    protected function isCardEnabled(): bool
    {
        $enabled = $this->scopeConfig->isSetFlag(
            self::SYSTEM_CONFIG_CARD_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
        $paymentMethod = $this->getCardPaymentMethod();
        return ($enabled && $paymentMethod !== "");
    }

    /**
     * Get available card types (debit, credit or both)
     * @return array
     */
    protected function getCardTypes(): array
    {
        $value = (string)$this->scopeConfig->getValue(
            self::SYSTEM_CONFIG_CARD_TYPES,
            ScopeInterface::SCOPE_STORE
        );
        return explode(',', $value);
    }

    /**
     * Get all the enabled credit card flags (mastercard, visa, amex etc)
     * @return array
     */
    protected function getCardFlags() : array
    {
        $value = (string)$this->scopeConfig->getValue(
            self::SYSTEM_CONFIG_CARD_FLAG,
            ScopeInterface::SCOPE_STORE
        );
        return explode(',', $value);
    }

    /**
     * Is pre paid payments enabled
     * @return bool
     */
    protected function isPrePaidEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::SYSTEM_CONFIG_CARD_PRE_PAID,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the credit card payment method
     * @return string
     */
    protected function getCardPaymentMethod() : string
    {
        return (string)$this->scopeConfig->getValue(
            self::SYSTEM_CONFIG_CARD_PAYMENT_METHOD,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get additional debit/card additional info (client tokens, api keys etc)
     * @return array
     */
    public function getCardAdditionalInfo() : array
    {
        $additionalData = [];

        try {
            $paymentMethod = $this->getCardPaymentMethod();
            if ($paymentMethod === "braintree") {
                $storeId = $this->session->getQuote()->getStoreId();
                $merchantAccountId = (string)$this->braintreeConfig->getMerchantAccountId($storeId);
                $merchantAccountId = ($merchantAccountId !== "")
                    ? $merchantAccountId
                    : (string)$this->braintreeConfig->getMerchantId($storeId);
                if ($merchantAccountId !== "") {
                    $params[PaymentDataBuilder::MERCHANT_ACCOUNT_ID] = $merchantAccountId;
                    $additionalData["clientToken"] = $this->braintreeAdapterFactory
                        ->create($storeId)
                        ->generate($params);
                }
            }
        } catch (\Exception $ex) {
            /**
             * Error
             */
        }

        return $additionalData;
    }

    /**
     * Get card payments sort order
     * @return int
     */
    protected function getCardSortOrder() : int
    {
        $value = (string)$this->scopeConfig->getValue(
            self::SYSTEM_CONFIG_CARD_SORT_ORDER,
            ScopeInterface::SCOPE_STORE
        );
        return ($value !== "") ? (int)$value : 0;
    }

    /**
     * =============================== PAYPAL CONFIG ===================================
     */

    /**
     * Is PayPal express enabled
     * @return bool
     */
    protected function isPayPalEnabled() : bool
    {
        return $this->scopeConfig->isSetFlag(
            self::SYSTEM_CONFIG_PAYPAL_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get card payments sort order
     * @return int
     */
    protected function getPayPalSortOrder() : int
    {
        $value = (string)$this->scopeConfig->getValue(
            self::SYSTEM_CONFIG_PAYPAL_SORT_ORDER,
            ScopeInterface::SCOPE_STORE
        );
        return ($value !== "") ? (int)$value : 0;
    }

    /**
     * =============================== OPERATIONS ======================================
     */

    /**
     * Get debit/credit card config
     * @return array
     */
    public function getCardConfig() : array
    {
        return [
            "enabled" => $this->isCardEnabled(),
            "types" => $this->getCardTypes(),
            "flags" => $this->getCardFlags(),
            "prePaid" => $this->isPrePaidEnabled(),
            "paymentMethod" => $this->getCardPaymentMethod(),
            "additionalInfo" => $this->getCardAdditionalInfo(),
            "sortOrder" => $this->getCardSortOrder()
        ];
    }

    /**
     * Get PayPal config
     * @return array
     */
    public function getPayPalConfig() : array
    {
        return [
            "enabled" => $this->isPayPalEnabled(),
            "sortOrder" => $this->getPayPalSortOrder()
        ];
    }

    /**
     * Generate product label using the sku and quantity
     * @param array $product
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function generateProductLabel(array $product) : string
    {
        $quantity = $product["qty"];
        if ($quantity > 0) {
            $currencyCode = $this->getCurrency();
            if (filter_var($quantity, FILTER_VALIDATE_INT) === false) {
                return $product["name"] . " (#" . $product["sku"] . ") (" . $currencyCode .
                    number_format((float)$product["price"], 2, '.', '') .
                    ") x" . (int)$quantity;
            } else {
                return $product["name"] . " (#" . $product["sku"] . ") (" . $currencyCode .
                    number_format((float)$product["price"], 2, '.', '') . ") x" .
                    number_format((float)$quantity, 2, '.', '');
            }
        } else {
            return $product["name"] . " (#" . $product["sku"] . ")";
        }
    }

    /**
     * Get all the current cart items
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuoteItems() : array
    {
        $products = [];

        /**
         * Get current quote
         */
        $quote = $this->session->getQuote();
        $quoteId = $quote->getId();

        /**
         * Get quote items
         */
        $quoteItemTable = $this->connection->getTableName("quote_item");
        $query = $this->connection->select()->from(
            $quoteItemTable,
            [
                "name",
                "sku",
                "price",
                "qty"
            ]
        )->where($quoteItemTable . ".quote_id = " . $quoteId);

        foreach ($this->connection->fetchAll($query) as $product) {
            $products[$product['sku']] = [
                "label" => $this->generateProductLabel($product),
                "price" => (float)$product["price"] * (float)$product["qty"]
            ];
            $this->totalPrice = $this->totalPrice + ((float)$product["price"] * (float)$product["qty"]);
        }

        return $products;
    }

    /**
     * Get controller urls
     * @return array
     * @throws NoSuchEntityException
     */
    public function getUrls() : array
    {
        $maskedQuoteId = $this->getQuoteIdMask();
        return [
            "checkout" => $this->url->getUrl("paymentRequest/checkout/index"),
            "updateAddress" => $this->url->getUrl("paymentRequest/cart/updateAddress"),
            "success" => $this->url->getUrl("checkout/onepage/success"),
            "estimateShipping" => [
                "guest" => $this->url->getDirectUrl(
                    "rest/" . $this->storeManager->getStore()->getCode() .
                    "/V1/guest-carts/" . $maskedQuoteId . "/estimate-shipping-methods"
                ),
                "customer" => $this->url->getDirectUrl(
                    "rest/" . $this->storeManager->getStore()->getCode() .
                    "/V1/carts/mine/estimate-shipping-methods"
                )
            ],
            "totals" => $this->url->getUrl("paymentRequest/cart/totals")
        ];
    }

    /**
     * Get current quote id
     * @return int
     */
    public function getQuoteId() : int
    {
        return (int)$this->session->getQuoteId();
    }

    /**
     * Get the quote total
     * @return float
     */
    public function getQuoteTotal() : float
    {
        return $this->totalPrice;
    }

    /**
     * Get quote currency
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrency() : string
    {
        return (string)$this->session->getQuote()->getCurrency()->getQuoteCurrencyCode();
    }

    /**
     * Get masked quote id
     * @return string
     */
    public function getQuoteIdMask() : string
    {
        $quoteIdMaskTable = $this->connection->getTableName("quote_id_mask");
        $query = $this->connection->select()
            ->from(
                $quoteIdMaskTable,
                [
                    "masked_id"
                ]
            )->where($quoteIdMaskTable . ".quote_id = " . $this->getQuoteId());
        $item = $this->connection->fetchRow($query);
        if (is_array($item) && key_exists("masked_id", $item)) {
            return $item["masked_id"];
        }
        return "";
    }

    /**
     * Get discount by coupon code
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getDiscount() : array
    {
        $coupon = (string)$this->session->getQuote()->getCouponCode();
        if ($coupon !== "") {
            return [
                "label" => sprintf(__("Discount (%s)"), $coupon),
                "amount" => -($this->session->getQuote()->getSubtotal() -
                    $this->session->getQuote()->getSubtotalWithDiscount())
            ];
        }
        return [];
    }

    /**
     * Get current customer id
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomerId() : int
    {
        $customerId = $this->session->getQuote()->getCustomerId();
        if ($customerId !== null) {
            return (int)$customerId;
        }
        return 0;
    }
}
