# Klaviyo Set Up

## Klaviyo API

[Klaviyo HTTP API](https://www.klaviyo.com/docs/http-api)

## Add cart items to a Klaviyo email content section

From the Klaviyo email content editor, click "View source" in text editor toolbar.

Enter your code. Here's an example:

```
<ul>
{% for Item in event.Line_Items %}
	<li><a href="https://www.example.com{{ Item.URL }}">{{ Item.Title }}</a></li>
{% endfor %}
</ul>
```

Save and test the email to make sure it looks good.
