# Klaviyo Notes

[Klaviyo HTTP API](https://www.klaviyo.com/docs/http-api)

## Add cart items to a Klaviyo email content section

From the Klaviyo email content editor, click "View source" in text editor toolbar.

Enter your code. Here's an example:

```twig
<ul>
{% for Item in event.Line_Items %}
	<li><a href="https://www.example.com{{ Item.URL }}">{{ Item.Title }}</a></li>
{% endfor %}
</ul>
```

Save and test the email to make sure it looks good.

## Klaviyo Special Properties

The Klaviyo API includes the following special properties via HTTP. These properties are implemented in Klaviyo Connect without the `$`. For instance, a typical `Profile` model would look like this:

```php
new Profile([
  'email' => 'hi@mysite.com',
  'first_name' => 'Jane',
  'last_name' => 'Doe',
  // ...
])
```

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