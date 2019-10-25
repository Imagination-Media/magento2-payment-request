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

namespace ImaginationMedia\PaymentRequest\Controller\Checkout;

use ImaginationMedia\PaymentRequest\Model\Checkout;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Checkout
     */
    protected $checkout;

    const REQUIRED_FIELDS = [
        "paymentMethod",
        "shippingMethod",
        "quoteId",
        "shippingAddress",
        "billingAddress",
        "contactInfo"
    ];

    /**
     * Index constructor.
     * @param JsonFactory $jsonFactory
     * @param Context $context
     * @param Checkout $checkout
     */
    public function __construct(
        JsonFactory $jsonFactory,
        Context $context,
        Checkout $checkout
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->checkout = $checkout;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $jsonResult = $this->jsonFactory->create();

        $error = "";
        $result = false;

        /**
         * Validate info
         */
        foreach (self::REQUIRED_FIELDS as $REQUIRED_FIELD) {
            if (!isset($params[$REQUIRED_FIELD]) ||
                in_array($params[$REQUIRED_FIELD], ["undefined", "null"])) {
                $error = __("Not provided " . $REQUIRED_FIELD . " field.");
            }
        }

        /**
         * Check if provided info is correct
         */
        if ($error === "") {
            /**
             * Process Braintree
             */
            if ($params["paymentMethod"] === "braintree") {
                $token = (isset($params["token"]) &&
                    !in_array($params["token"], ["undefined", "null"]))
                    ? $params["token"] : null;

                $customerId = (isset($params["customerId"]) &&
                    !in_array($params["customerId"], ["undefined", "null"]))
                    ? $params["customerId"] : null;

                if ($token) {
                    $result = $this->checkout->createOrder(
                        [
                            "code" => $params["paymentMethod"],
                            "token" => $token
                        ],
                        $params["shippingMethod"]["carrier_code"] . "_" . $params["shippingMethod"]["method_code"],
                        (int)$params["quoteId"],
                        $params["billingAddress"],
                        $params["shippingAddress"],
                        $params["contactInfo"],
                        $customerId
                    );
                } else {
                    $error = __("No token was provided");
                }
            }
        }

        if ($error !== "") {
            $result = false;
        }

        $jsonResult->setData([
            "error" => $error,
            "result" => $result
        ]);
        return $jsonResult;
    }
}
