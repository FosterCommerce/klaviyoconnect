# Tracking script

How Klaviyo Connect adds klaviyo.js to your storefront, keeps it off chosen pages, and tracks Viewed Product for browse abandonment.

## Add the tracking script

Go to **Settings -> Plugins -> Klaviyo Connect**. Under **Tracking**, switch on **Add Klaviyo Tracking Script**. It is off by default.

Before switching it on, remove any klaviyo.js script tags from your templates. Otherwise the script loads twice.

With the setting on, Klaviyo Connect adds klaviyo.js to every front-end page Craft renders from a template. Each site uses the **Public API Key** from its row in the **Sites** table. A site with a blank **Public API Key** does not get the script.

### Logged-in users

After each page loads, the page requests the current session from Craft. If a user is logged in, the script identifies them to Klaviyo by their email. Klaviyo then attributes that browser's activity to their profile.

The email is not written into the page HTML, so a statically cached page contains no visitor's email.

## Exclude pages

**Exclude URI Patterns** appears once **Add Klaviyo Tracking Script** is on. A page whose URI matches an enabled pattern does not get the tracking script.

Use it to keep klaviyo.js off pages such as checkout payment pages.

Each row has three columns:

| Column | Sets |
|---|---|
| **Enabled** | Whether the pattern applies. Turn it off to keep a row without applying it. |
| **Site** | **All Sites**, or the one site the pattern applies to. |
| **URI Pattern** | What to match. |

How a pattern matches:

| Pattern | Matches |
|---|---|
| Blank | The homepage only. |
| `*` | Every page. |
| `checkout/payment` | Any URI containing `checkout/payment`. |
| `checkout` | Every checkout step, and any other URI containing `checkout`. |
| `^checkout/payment$` | Only `checkout/payment`. |

A pattern is a regular expression, matched against the URI relative to the site's base URL. Slashes at either end of the pattern and the URI are ignored. A pattern matches anywhere in the URI unless you anchor it with `^` and `$`.

If a pattern is not a valid regular expression, the settings do not save and the field shows the error.

To set the patterns per environment in `config/klaviyoconnect.php`, see the [configuration reference](../reference/configuration.md). When the config file sets them, the field says so.

## Track Viewed Product

Klaviyo's browse abandonment flows start from a Viewed Product event. Klaviyo Connect sends one from a product template:

```twig
{% do craft.klaviyoConnect.viewedProduct(product) %}
```

To track a specific variant, pass it as the second argument. Without it, the event uses the product's default variant.

```twig
{% do craft.klaviyoConnect.viewedProduct(product, variant) %}
```

The call adds two calls to klaviyo.js on the page:

- A **Viewed Product** event, with the product's name, IDs, SKU, URL, image, brand and price. For every property, see [event properties](../reference/event-properties.md).
- A recently viewed item, which Klaviyo uses for recently viewed products in emails.

The event records the variant passed when the page renders. A variant picker that changes the page in the browser does not send another event.

The event name takes the site's **Event Prefix**, like the events Klaviyo Connect sends from the server.

### What the event needs

Klaviyo records Viewed Product only when both of these are true:

- **klaviyo.js is on the page.** Turn on **Add Klaviyo Tracking Script**, or load klaviyo.js from your own templates. The call works with either one. A page that matches an exclude pattern does not get the plugin's klaviyo.js.
- **Klaviyo can identify the visitor.** With the tracking script on, a logged-in user is identified. Klaviyo also identifies a visitor who arrived from a link in a Klaviyo email or who submitted a Klaviyo signup form. An anonymous visitor's views are not recorded against a profile.

If your templates load klaviyo.js themselves, Klaviyo Connect does not identify logged-in users. Call `klaviyo.identify()` from your own templates instead.

To build the flow, create a browse abandonment flow in Klaviyo triggered by the Viewed Product metric.
