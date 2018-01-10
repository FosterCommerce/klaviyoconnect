# KlaviyoConnect

A [Craft Commerce](https://craftcommerce.com/) plugin for [Klaviyo](https://www.klaviyo.com).

## Installation

- Download and unzip, rename to `klaviyoconnect`
- Copy the `klaviyoconnect` folder to your `craft/plugins` directory
- From inside `klaviyoconnect` directory, run `composer install`
- Install the plugin from Craft CP

## Configure (Settings -> Plugins -> Klaviyo Connect -> Settings)

Site ID and API Key can be found here: [https://www.klaviyo.com/account#api-keys-tab](https://www.klaviyo.com/account#api-keys-tab)

**Subscription Field Handle** is an optional Custom Field which can be used to set the `WithSubscription` property in the `people_properties` object in Klaviyo profiles.


## Notes

Klaviyo only knows about events which have already been tracked. I.e. if the "Completed Order" event hasn't been tracked yet, you will not be able to set up a Flow which depends on that event.