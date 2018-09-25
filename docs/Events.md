# Events

Klaviyo Connect provides the following events you can use in your own custom plugin, to define your own custom mappings.

## `addProfileMapping`

Add a custom mapping for user profiles.

### `class ProfileMapping`

- `name`
- `description`
- `handle` - A unique handle which Klaviyo Connect can associate the mapping with
- `method` - The method in your plugin to call to execute the mapping. Receives array of form parameters as an argument.

### Example

```php
use fostercommerce\klaviyoconnect\services\Map;
use fostercommerce\klaviyoconnect\events\AddProfileMappingEvent;
use fostercommerce\klaviyoconnect\models\Profile;
use fostercommerce\klaviyoconnect\models\ProfileMapping;
use yii\base\Event;

...

Event::on(
  Map::class,
  Map::EVENT_ADD_PROFILE_MAPPING,
  function (AddProfileMappingEvent $e) {
    // Define your mapping
    $myMapping = new ProfileMapping([
      'name' => 'My Custom Profile Mapping',
      'handle' => 'mycustom_mapping',
      'description' => 'A custom profile mapping',
      'method' => function ($params) {
        $profile = new Profile();
        // Perform profile mapping...
        return $profile;
      }
    ]);

    // Add it to list of mappings
    $e->mappings[] = $myMapping;
  }
);
```

Use your custom mappings as you would the built-in ones:

```html
<input type="hidden" name="klaviyoProfileMapping" value="mycustom_mapping" />
```

## `trackEventMapping($eventName)`

Add a custom mapper to set extra properties on an event. See the `extra` parameter in Event Form Parameters in [Actions](Actions.md)

### `class TrackEventMappingEvent`

- `extraProps` - Extra properties to pass through to Klaviyo with the tracked event.

### Example

```php
use fostercommerce\klaviyoconnect\controllers\ApiController;
use fostercommerce\klaviyoconnect\events\TrackEventMappingEvent;
use fostercommerce\klaviyoconnect\models\EventProperties;

...

Event::on(
  ApiController::class,
  ApiController::EVENT_TRACK_EVENT_MAPPING,
  function (TrackEventMappingEvent $e) {
    // Add your extra event properties to the tracking data
    $e->extraProps = [
      'Foo' => 'Bar',
    ];
  }
);
```

**Note:** Using this will overwrite any existing data in the `EventProperties::extra` field.

## Klaviyo Special Properties

The Klaviyo API includes the following special properties via HTTP.

### User Profile Properties

- `$id` - your unique identifier for a person
- `$email` - email address
- `$first_name` - first name
- `$last_name` - last name
- `$phone_number` - phone number
- `$title` - title at their business or organization
- `$organization` - business or organization they belong to
- `$city` - city they live in
- `$region` - region or state they live in
- `$country` - country they live in
- `$zip` - postal code where they live
- `$image` - url to a photo of the person

### Event Properties

- `$event_id` - an unique identifier for an event
- `$value` - a numeric value to associate with this event (e.g. the dollar value of a purchase)
