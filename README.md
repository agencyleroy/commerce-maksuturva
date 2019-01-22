# Maksuturva for Craft Commerce
This plugin provides [Maksuturva](https://www.maksuturva.fi/]) integrations for [Craft Commerce](https://craftcms.com/commerce).

## Requirements
This plugin requires Craft Commerce 2.0.0-alpha.5 or later.

## Installation
You can install this plugin from the Plugin Store or with Composer.

## Important
* `ShippingAddress` and `BillingAddress` are required at checkout.
* You need to set `cancelUrl` for payment cancelations to work.
````html
<input type="hidden" name="cancelUrl" value="/your/cancel/url">
````
