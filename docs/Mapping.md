# Mappings

Mappings are used to translate the data you collect from users into a format that Klaviyo can use to record events and signing up users to lists. You can select the default mapping that Klaviyo uses in the plugins configuration section, and/or override the default mapping by including a "klaviyoProfileMapping" field.

Klaviyo Connect comes with two useful mappings:

## formdata_mapping

Useful when there's no user logged in and Craft does not know the users email address. With this mapping, you can use form fields to tell the plugin about the user (email address, first and last name, etc).

## usermodel_mapping

The user profile mapping can be used when there's a logged in user, it just maps user properties to a Klaviyo representation directly based on what Craft knows about the logged in user.

## Custom Mappings

Custom mappings can be used as well by creating a plugin that uses Klaviyo Connect hooks to define the mapping. See the "Developers" section for more details.
