Shopify extensions
==============

With this extension you can integrate Live Helper Chat with your own shopify store.

### Requirements

* Live Helper Chat 3.91
* PHP 7.3 >=

### Instructions

Watch YouTube video with same steps - https://youtu.be/HMACPeJBtaI

1. Clone this repository in `extension` folder. If you are in Live Helper Chat root folder where `index.php` is located.
   1. `cd extensions && git clone https://github.com/LiveHelperChat/pluginshopify.git`
2. Copy `extension/pluginshopify/settings/settings.ini.default.php` to `extension/pluginshopify/settings/settings.ini.php`
3. Create a Shopify APP (https://partners.shopify.com) and enter `API key` and `API secret key` in `extension/pluginshopify/settings/settings.ini.php`
    1. In `App URL` enter `https://example.com/site_admin/shopify/index`
    2. In `Allowed redirection URL(s)` enter
        1. `https://example.com/site_admin/shopify/token`
        2. `https://example.com/site_admin/shopify/install`

![See image](https://raw.githubusercontent.com/LiveHelperChat/pluginshopify/main/doc/shopify.png)

3. Activate extension in `settings/settings.ini.php` by putting `pluginshopify` in `extensions` section.

```
'extensions' =>
    array (
        'pluginshopify'
    ),
```

