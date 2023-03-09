Compatibility
=============

OpenCart **version 3.0.x.x**

If you are running OpenCart **version 2.0 - 2.2**, please refer to the [corresponding branch](https://github.com/checkout/checkout-opencart-plugin/tree/OpenCart-2.0---2.2)

If you are running OpenCart **version 2.3.x.x**, please refer to the [corresponding branch](https://github.com/checkout/checkout-opencart-plugin/tree/master)

Installation
============

It’s easy to install and use Checkout.com module. Download the extension from the Github releases and upload the content to the below path

[Root Directory]/upload/admin/...
[Root Directory]/upload/catalog/...

Or download the package from [Opencart marketplace](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=41648)

Configuration
============================

After installing the plugin, access the back office of your store and click Extensions > Extensions. Select Payments in the drop down list. Click the Install button after you find the Checkout.com plugin, and once the installation is finished, click Edit.

* Select your environment (sandbox/production)
* Enter your API keys with the prefix _Bearer_, e.g. Bearer sk_sbox_77s12....
* Select Status Enabled
* Hit the Save button


Webhook / Redirection URLs
============================

Webhook creation is done automatically when you save the plugin's configurations.
It supports the following events:

card_verification_declined
card_verified
payment_approved
payment_pending
payment_declined
payment_expired
payment_canceled
payment_voided
payment_void_declined
payment_captured
payment_capture_declined
payment_capture_pending
payment_refunded
payment_refund_declined
payment_refund_pending

The URL registered will be:
example.com/index.php?route=extension/payment/checkout_com/webhook
