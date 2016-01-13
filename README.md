# Shmart Payment Gateway plugin 

**Tested up to:** 4.3  
**License:** GPLv2 or later.  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  


### Description

This plugin is an extension for Easy Digital Download plugin. Use this plugin to setup shmart payment gateway on your store. The shmart payment gateway (http://shmart.in/) is only available for users from India. 
Shmart only accepts INR currency. Once the plugin is activated and respective settings saved, on checkout page the shmart payment gateway will be displayed.

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

**== 1.0 ==**

* Initial release with shmart payment gateway settings.
