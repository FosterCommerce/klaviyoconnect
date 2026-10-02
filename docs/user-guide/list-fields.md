# List fields

How editors choose Klaviyo lists on an entry, and how a template uses that choice in a signup form.

## Add a list field

Klaviyo Connect adds two field types:

| Field type | Editors choose | Template value |
|---|---|---|
| Klaviyo List | One list, from a dropdown | A list, with `id` and `name` |
| Klaviyo Lists | Any number of lists, from checkboxes | An array of lists, each with `id` and `name` |

Create the field in **Settings -> Fields -> New field**, then add it to a field layout.

The field offers the lists chosen for the element's site under **Lists for {site}**, in **Settings -> Plugins -> Klaviyo Connect**. Choose **All** there to offer every list in the site's Klaviyo account.

## Refresh the lists

Klaviyo Connect stores each Klaviyo account's lists until they are refreshed, so loading an entry does not wait for Klaviyo. A list created in Klaviyo does not appear in the field until the next refresh.

To refresh the lists, click **Refresh lists from Klaviyo** under the field. The field reloads with the account's lists and keeps the lists already selected. Every list field using that Klaviyo account shows the refreshed lists.

**Check Connection and Load Lists** in the plugin settings also refreshes every site's lists.

Read-only views of the field, such as a revision or an entry you cannot edit, do not show the refresh link.

## Sites with different Klaviyo accounts

Each element uses its own site's Klaviyo account, and the field offers that account's lists.

A list ID saved from another account stays saved. The field shows the list ID followed by "(not in this Klaviyo account)", and saving the entry keeps it. To remove the list, clear the selection.

This happens when sites with different accounts share the field's value, through the field's **Translation Method** setting, or when a site changes accounts.

## Use the field in a signup form

A signup form posts the list to the `klaviyoconnect/api/track` action. Klaviyo Connect adds the profile to the list in the current site's Klaviyo account.

With a Klaviyo List field, send the list's `id` as `list`:

```twig
{% if entry.newsletterList %}
  <form method="post">
    {{ csrfInput() }}
    {{ actionInput('klaviyoconnect/api/track') }}
    {{ hiddenInput('list', entry.newsletterList.id) }}

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    <button type="submit">Sign up for {{ entry.newsletterList.name }}</button>
  </form>
{% endif %}
```

With a Klaviyo Lists field, send each list's `id` as `lists[]`:

```twig
{% for list in entry.newsletterLists %}
  {{ hiddenInput('lists[]', list.id) }}
{% endfor %}
```

In both fields, a list that isn't in the site's Klaviyo account, or any list when the lists can't load, has its ID as its `name`.

For the other parameters a form can send, such as `subscribe`, see [actions](../reference/actions.md). For complete forms, see [template examples](../dev-guide/template-examples.md).
