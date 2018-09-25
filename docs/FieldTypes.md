# Field Types

Klaviyo Connect includes two field types which you can include in your entries to specify lists in your Klaviyo account. You can then use these values in your templates when setting up forms to gather user data and assign them to these lists.

## Klaviyo List

A drop down select field to select one list.

```twig
{# Get the list ID #}
{{ entry.customFieldName.id }}

{# Get the list name #}
{{ entry.customFieldName.name }}
```

## Klaviyo Lists

A checkbox group to allow for selecting multiple lists.

```twig
{% for list in entry.customFieldName %}
  {{ list.id }}
  {{ list.name }}
{% endfor %}
```
