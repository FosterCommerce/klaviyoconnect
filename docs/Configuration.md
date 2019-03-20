# Configuration

Within the Craft Control Panel, navigate to Settings and click the Klaviyo Connect icon to open the plugin's configuration section.

*Navigate to Settings → Plugins → Klaviyo Connect → Settings*

![Configuration](./images/configuration.png)

**Note**: Site ID and Private API Key can be found here: [https://www.klaviyo.com/account#api-keys-tab](https://www.klaviyo.com/account#api-keys-tab)

## Public and Private API Keys

First, include your Klaviyo Public and Private API keys and click save to connect to Klaviyo.

You can access these keys by logging into Klaviyo, and clicking your account icon (in the upper right) and selecting Account, then clicking the Settings menu and then selecting "API Keys". Or click here: [https://www.klaviyo.com/account#api-keys-tab](https://www.klaviyo.com/account#api-keys-tab)

Copy your Public API Key/Site ID and your Private API Keys. If you do not see any Private API Keys, you may need to create on first by clicking the Create API Key button.

*Save these settings in the Klaviyo Connect configuration section before setting the other configuration options.*

## Lists

Once you have saved your API Keys, this field will be automatically populated with your Klaviyo Profile Lists allowing you to select which ones will be available to the "Klaviyo List" and "Klaviyo Lists" field types.

## User Groups

Optional. Select which user groups you would like to limit tracking to. Default is all guests and users.

## Default Profile Mapping

Klaviyo Connect will use this default site-wide mapping when one isn't explicitly set in your template code. The mappings included are:

### Form-Data Profile Mapping
Useful when you want to track all users, even if they are not logged in or have not registered. With this mapping you can use form fields in your templates to send Klaviyo information about them. Most common settings.

### User Model Profile Mapping
If you only want to track logged in registered users, this mapping will map their user data (names, email, etc.) to Klaviyo fields.

## Cart URL

The URL to your store's cart.

## Image Field Handle

The handle of the asset field you use for product images. These images will then be available within Klaviyo.

## Image Transform

The handle of the native Craft image transform you would like to use on the above product images.
