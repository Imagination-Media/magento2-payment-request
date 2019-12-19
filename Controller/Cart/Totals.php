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

use ImaginationMedia\PaymentRequest\Model\Cart\Total;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

class Totals extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Total
     */
    protected $totalCalculator;

    /**
     * Totals constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Total $totalCalculator
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Total $totalCalculator
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->totalCalculator = $totalCalculator;
    }

    /**
     * @return Json
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute() : Json
    {
        $params = $this->getRequest()->getParams();
        $jsonResult = $this->jsonFactory->create();

        $result = $this->totalCalculator->getTotals($params);

        if ($result['error'] !== "") {
            $result['totals'] = [];
        }

        $jsonResult->setData($result);

        return $jsonResult;
    }
}
