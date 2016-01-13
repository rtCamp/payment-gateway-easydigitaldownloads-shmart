# Shmart Payment Gateway plugin 

**Tested up to:** 4.3  
**License:** GPLv2 or later.  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  


### Description

This plugin is an extension for Easy Digital Download plugin. Use this plugin to setup shmart payment gateway on your store. The shmart payment gateway (http://shmart.in/) is only available for users from India. 
Shmart only accepts INR currency. 

Shmart accepts payments via all VISA & MasterCard debit and credit cards and internet banking of 27 major banks and IMPS payment option of 55+ banks.

Once the order is placed in Easy Digital Downloads and user select option to make payment through Shmart payment gateway then he will be taken to Shmart secure payment page for making payment. After payment process is completed user will be taken back to the main site.


## How it Works:

This plugin only work with shmart account [https://shmart.in/](https://shmart.in/). You must have valid merchant ID, API key and secret keys.
First, you can contact to Shmart team and complete all the documentation work to get live account details.

For the conversion of any other currency to INR, we are using openexchangerate API (https://openexchangerates.org/) . By default, [https://openexchangerates.org/](https://openexchangerates.org/) provide free API if we are converting USD to INR.
If your store using INR currency only, then openexchange API not required.

If your live store running with any other currency like Euro, Yen, etc. Then you must need to purchase paid API from https://openexchangerates.org/.

## Want to use another currency exchange API

We have provided a filter for the same. Check the below sample code:

```
add_filter( 'edd_shmart_currency_conversion', 'alter_edd_shmart_currency_conversion', 10, 4 );

function alter_edd_shmart_currency_conversion( $converted_amount, $amount, $base_currency, $currency ){
	// $converted_amount: Amount after conversion
	// $amount: Actual product amount, may be in USD
	// $base_currency: Base currency, for example INR
	// $currency: INR
	$exchange_rate = 60; // exchange rate for example 1 USD into INR
	$converted_amount = ( $amount * $exchange_rate );
	return $converted_amount;
}
```
## Troubleshooting:

### Error Invalid IP

[![shmart_invalid_ip](https://cloud.githubusercontent.com/assets/7771963/10606506/0b9fc314-7751-11e5-84c6-e7bbe754840a.png)](https://cloud.githubusercontent.com/assets/7771963/10606506/0b9fc314-7751-11e5-84c6-e7bbe754840a.png)

Kindly cross check if the Merchant ID and secret key added in setting page is correct and associate with shmart account where you have provided the same domain name, URL or IP address.

### Invalid Mobile Number:

[![shmart_invalid_mobile_no](https://cloud.githubusercontent.com/assets/7771963/10606530/2b72ae5e-7751-11e5-8cdc-a985f8d69658.png)](https://cloud.githubusercontent.com/assets/7771963/10606530/2b72ae5e-7751-11e5-8cdc-a985f8d69658.png)

Kindly check if the mobile number is added by customer or not. Also verify that there should not be any special character in mobile number like +91.

The contact number is required fields by shmart payment gateway, hence in plugin we have implemented.


If you are getting errors related to shmart API or openexchnage, please try to contact respective support team.

For licensing, [EDD-License-handler](https://github.com/easydigitaldownloads/EDD-License-handler) lib has been used. Use this lib as a git subtree.

First add this repo as a git remote.

    git remote add edd-license-subtree https://github.com/easydigitaldownloads/EDD-License-handler.git

Run following command to add remote repo as subtree

    git subtree add --prefix=lib/edd-license edd-license-subtree master

To pull subtree changes, run following command

    git subtree pull --prefix=lib/edd-license edd-license-subtree master

### Requirements

* Easy Digital Download plugin.
* Shmart approved account to get all the required keys. 
* https://openexchangerates.org/ free or paid API access to convert foreign currency into INR. 

### Installation

 * Download a plugin. 
 * Go to WordPress dashboard -> Plugins -> Add New and upload a zip file. 
 * Activate the plugin in your WordPress admin area.

### Configuration

 * Navigate to Downloads > Settings
 * Click on the tab labeled "Payment Gateway"
 * Enable check box "Shmart (recommended for Indian users)".
 * Fill up all the required values under "Shmart Settings". 

### Changelog
**== Version 1.0.1 ==**
Change product name and slug

**== Version 1.0 ==**
* Initial Release
* Settings to enable payment gateway.
