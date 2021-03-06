Shopify extensions
==============

With this extension you can integrate Live Helper Chat with your own shopify store.

### Requirements

* Live Helper Chat 3.91
* PHP 7.3 >=

### Instructions Standalone

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

4. Activate extension in `settings/settings.ini.php` by putting `pluginshopify` in `extensions` section.
5. [Clear cache](https://doc.livehelperchat.com/docs/system/clearing-cache/) in system configuration

```
'extensions' =>
    array (
        'pluginshopify'
    ),
```

### Instructions Automated Hosting

1. Follow `Instructions Standalone` and in `extension/pluginshopify/settings/settings.ini.php` set `automated_hosting` to `true`
2. Extension has to be installed on client and manager at the same time.
3. Execute `doc/db.sql` on your manager database.