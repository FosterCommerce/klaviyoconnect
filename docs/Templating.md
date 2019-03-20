# Templating

## Add a user to a Klaviyo List _(hard-coded values)_

```html
<form method="POST">
    <input type="hidden" name="action" value="klaviyoconnect/api/update-profile">
    <!-- Add to the list -->
    <input type="hidden" name="list" value="FOO123">
    <input type="hidden" name="confirmOptIn" value="0" />
    <label>Email</label><input type="email" name="email" />
    <input type="submit" value="Submit"/>
</form>
```
## Add a user to Klaviyo List

_using the Klaviyo List Field from a global entry_

```twig
<form method="POST">
    <input type="hidden" name="action" value="klaviyoconnect/api/update-profile">
    <input type="hidden" name="list" value="{{ global.myKlaviyoList.id }}">
    <input type="hidden" name="confirmOptIn" value="0" />
    <label>Email</label><input type="email" name="email" />
    <input type="submit" value="Submit"/>
</form>
```

## Add a user to multiple Klaviyo Lists

_Using the Klaviyo Lists Field from a global entry_

```twig
<form method="POST">
    <input type="hidden" name="action" value="klaviyoconnect/api/update-profile">
    {% for id, name in global.myKlaviyoLists %}
      <input type="hidden" name="lists[]" value="{{ id }}" />
    {% endfor %}
    <input type="hidden" name="confirmOptIn" value="0" />
    <label>Email</label><input type="email" name="email" />
    <input type="submit" value="Submit"/>
</form>
```

## Track Event

```html
<form method="POST">
    <input type="hidden" name="action" value="klaviyoconnect/api/update-profile">
    <!-- Event to track -->
    <input type="hidden" name="event[name]" value="Event Foo" />
    <input type="hidden" name="event[event_id]" value="a1b2c3" />
    <input type="hidden" name="event[value]" value="Foobar">
    <input type="hidden" name="event[extra][FooBar]" value="Foo Bar">
    <!-- Profile Details -->
    <label>Email</label><input type="email" name="email" />
    <input type="submit" value="Submit"/>
</form>
```

## Track an event and add a user to a Klaviyo List

```html
<form method="POST">
    <input type="hidden" name="action" value="klaviyoconnect/api/update-profile">
    <!-- Add to the list -->
    <input type="hidden" name="list" value="FOO123">
    <input type="hidden" name="confirmOptIn" value="0" />
    <!-- Event to track -->
    <input type="hidden" name="event[name]" value="Event Foo" />
    <input type="hidden" name="event[event_id]" value="a1b2c3" />
    <input type="hidden" name="event[value]" value="Foobar">
    <input type="hidden" name="event[extra][FooBar]" value="Foo Bar">
    <!-- Profile Details -->
    <label>Email</label><input type="email" name="email" />
    <input type="submit" value="Submit"/>
</form>
```

## Identify a user

```html
<form method="POST">
    <input type="hidden" name="action" value="klaviyoconnect/api/identify">
    <label>Email</label><input type="email" name="email" />
    <input type="submit" value="Submit"/>
</form>
```

## Forward a POST request after Klaviyo Connect has identified a user

```twig
<form method="POST">
    <input type="hidden" name="action" value="klaviyoconnect/api/identify">
    <input type="hidden" name="forward" value="commerce/cart/update-cart">
    {{ redirectInput('commerce/cart') }}
    <label>Email</label><input type="email" name="email" />
    <input type="hidden" name="shippingAddressId" value="{{ address.id }}">
    <input type="hidden" name="sameAddress" value="1">
    <input type="submit" value="Submit"/>
</form>
```
