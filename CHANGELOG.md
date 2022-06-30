# Release Notes for KlaviyoConnect
## 5.0.2 - 2022-06-30

- Fixing messed up releases where it was possible to install 5.0.1 on Craft 3
## 5.0.1 - 2022-06-29

- Fix issue with PHP Typing error on settings screen
  
## 5.0.0 - 2022-06-15

- Separate Craft 4 version

## 4.0.10 - 2022-06-13
## Added

- Added a service method to retrieve a Klaviyo Person ID using an email address
- Added a service method to update a Klaviyo profile 
- Added a controller method for updating a Klaviyo profile

## 4.0.9 - 2022-06-07

## Fixed

- Fixes issue in Lists field type where list selection isn't saved, or previous setting is nulled, if the entry is first saved by an automatic draft.

### Added

- The plugin now works with Craft 4
  
## 4.0.8 - 2021-10-21

### Fixed

- Fixes error with guest checkouts when custom product image field doesn't exist on products or variants

## 4.0.7 - 2021-09-27

### Added

- Klaviyo Connect will now use a variants product image if present before using
  the products image ([#67](https://github.com/FosterCommerce/klaviyoconnect/pull/67))

## 4.0.6 - 2021-08-23

### Added

- Added support for Guzzle 7.2

## 4.0.5 - 2021-07-16

### Added

- Added the `useSubscribeEndpoint` parameter to the `klaviyoconnect/api/track` action to make it possible to use Klaviyo’s `subscribe` endpoint instead of `members`, which has the benefit of respecting the double opt-in setting of the list

## 4.0.4 - 2021-01-11

### Fixed

- Fixes error when updating cart without a billing address

## 4.0.3 - 2020-11-23

### Added

- Passes customer billing name to Klaviyo when the user completes an order as guest

## 4.0.2 - 2020-10-02

#### Fixed

- Value of Product Type is now the `name` not the full object

## 4.0.1 - 2020-10-01

#### Added

- Product Type with line item data sent with events

#### Fixed

- Value on Ordered Product events

## 4.0.0 - 2020-09-29

#### Added

- Add tracking for order status changes
- Add tracking for refund transactions
- Add ability to disable tracking for specific events
- Add ability to push historical Commerce orders to Klaviyo

#### Updated

- Fix bug where events triggered by admins cause admin account to get tracked in Klaviyo.
- Fix bug with line item formatting on events where they would show up as strings instead of arrays.

## 3.2.0 - 2020-09-18

#### Updated

- Merged #27
- Fix line items when passed as a string (such as through hidden fields in Twig) to `json_decode` the string before sending to Klaviyo.

## 3.1.3 - 2020-01-24

#### Updated

- Fix an error that occurs when saving a user due to `klaviyoAvailableGroups` setting being an array.

## 3.1.2 - 2019-12-04

#### Updated

- Allow tracking events with a timestamp.
- Allow using environment variables for public and private keys in plugin config.

## 3.1.1 - 2019-07-29

#### Updated

- Use response code for error message instead of relying on an error code in the Klaviyo API response body.

## 3.1.0 - 2019-05-20

#### Removed

- Removed `Base::getSpecialProperties()`
- Removed `confirmOptIn` from add to list code

#### Updated

- Updated Klaviyo List API calls to V2

#### Added
- Added GDPR consent related fields on the `Profile` model

## 3.0.4 - 2019-04-29

#### Updated

- `Track::createProfile` is now protected
- Fix an error when a line item's purchasable has no Product attached
- Users can now listen to the `Track::ADD_LINE_ITEM_CUSTOM_PROPERTIES` event for any line item regardless whether it has a Product attached or not

## 3.0.3 - 2019-04-04

#### Updated

- Fetch the first product image for each line item

## 3.0.2 - 2019-04-04

#### Updated

- Replaced deprecated `includecss` and `includejs` Twig tags

## 3.0.1 - 2019-04-01

#### Updated

- Fixed `profileMappings` on Settings page
- Ensure Craft Commerce is installed and enabled before adding event listeners
- Check for Craft Commerce before tracking order events
- Check for Craft Commerce when calling the restore cart action

## 3.0.0 - 2019-03-28

#### Removed

- Profile mappings and related events, variables, etc

#### Updated

- Restructured data sent to Klaviyo
- Custom properties are added to the root of models instead of to the `$extra` property
- User profile mapping is based on logged in user and data sent with the `profile[]` form parameter
- Replaced `events` service with `track`
- Renamed events to align with Klaviyo language (`AddProfilePropertiesEvent`, `AddCustomPropertiesEvent`, etc)
- Changed `api/update-profile` to `api/track`
- Fixed error when adding a user to a Klaviyo list
- Added `addToLists` to Track service
- Added docs site

#### Added

- Order and Line Item events for users to add custom properties onto event bodies
- Profile event to add custom properties to user profiles

### 2.0.6 - 2019-02-28

- Changed `trackOrder` and `getOrderDetails` visibility.

## 2.0.1 - 2018-09-19

- Fixed user service events.

## 2.0.0 - 2018-06-26

- Migrated to Craft 3.
