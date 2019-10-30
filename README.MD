# Payment Request API for Magento 2

## About
This Magento Extension will allow you to use the [W3C's payment request api](https://www.w3.org/TR/payment-request/) for checkout in Magento 2.

The Payment Request Api uses the in-browser stored data to provid all the required information to complete an order, it will get the available addresses, contact info and payment methods that are saved in your browser.

The Payment Request API is meant to reduce the number of steps needed to complete a payment online, potentially doing away with checkout forms. This can help to improve Conversion as well as Revenue Per Visitor particularly on mobile. 

![enter image description here](https://developers.google.com/web/fundamentals/payments/images/deep-dive/pr-top.png)


## Installation
You can install this package using composer:
```terminal
composer require imaginationmedia/magento2-payment-request
```

## Configuration
This extension adds a section inside the Sales > Payment Methods on Magento admin for configuration.

You can enable the Payment Request Api using one of the available modes. The first mode will replace all the checkout buttons, opening the Payment Request popup instead of redirecting to the Magento checkout page (when the browser supports the payment request api). The second mode is just a button that is added to the cart page, allowing the customer to checkout through the payment request api.

![enter image description here](https://i.ibb.co/Swpbm23/1.png)

The available payment methods at the moment are PayPal Express and Debit/Credit card.

PayPal Express is not ready for production use, the integration is still under development, but at this point we can see the option as one of the available payment methods. In the near future it will be available for production use.

The Debit/Credit card option can be integrated with any payment provider, at this point we have Braintree ready to be used. In the Payment Request Api config there is an option where we choose the Magento payment method that will be used to process the debit/credit cart payments.

![enter image description here](https://i.ibb.co/r74jWSz/2.png)

We also have options to setup the available card flags and the card types (debit, credit, or both).

## Supported Browsers
You can check a list with all the supported browsers [here.](https://developer.mozilla.org/en-US/docs/Web/API/Payment_Request_API#Browser_compatibility)

## Enabling multiple payments
If you enable multiple payment methods on payment request api, if one of those is credit/debit card it will show you only this option.

To see multiple payment methods you need to enable the Web Payments Experimental Features in your Google Chrome. To do that access this url in your browser chrome://flags/#enable-web-payments-experimental-features and enable that option. Restart your browser and you will be able to see all multiple payment methods.

You also need Chrome 79 or newest to be able to enable this option.

## Demo
We have a demo website where you can test the Payment Request Api module. The demo website is available at https://innovations.imaginationmedia.com/.

## License
This is a Magento community project, lead by [Imagination Media](https://www.imaginationmedia.com/) in partnership with [PayPal](https://www.paypal.com/) and [Mobile Optimization Initiative](https://www.mobileoptimized.org/).

All code is under the OSL 3.0 license.
