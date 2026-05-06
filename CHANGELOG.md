# Release Notes for KlaviyoConnect

## 7.2.4 - 2026-05-05

### Fixed

- Klaviyo API failures no longer break host requests. Errors are caught, logged to the `klaviyoconnect` category, and the request continues.
- Klaviyo API calls now hard-cap at 5s (5s connect) with retries disabled, so an unresponsive Klaviyo can't stall the host request.

## 7.2.3 - 2025-06-21

### Fixed

- Fix an issue in the ListsField where setting the value wasn't normalizing correctly.

## 7.2.2 - 2025-04-28

### Fixed

- Fix issue where Klaviyo lists on the plugin settings page were not being loaded correctly.

## 7.2.1 - 2025-04-25

### Fixed

- Fixed an issue where accessing custom line item purchasables was causing an exception and preventing orders from being updated.

## 7.2.0 - 2025-04-17

### Updated

- Updated to use klaviyo/api v14, revision [2025-04-15](https://developers.klaviyo.com/en/docs/changelog_#revision-2025-04-15-ga)

## 7.1.1 - 2025-03-24

## Updated

- Updated profile data that is used for order events.

## 7.1.0 - 2025-03-24

### Added

- Added order billing address location to profiles on order events. 

### Fixed

- Use correct timestamp value when syncing past orders
- Use correct value format for normalizing list field values


## 7.0.0 - 2024-09-23

- Migrated to Craft 5 and Commerce 5

