ParcelPoint Booking API integration for Magento
==========

Handles saving Magento sales shipments to ParcelPoint via their Booking API.

You will need to implement ParcelPoint's Store Locator Widget on your checkout. You would need to save the ParcelPoint store id against the sales order in the attribute `parcelpoint_store_id`.

ParcelPoint's API will return the following response parameters on successful bookings:
- parcel id: saved against the shipment in Magento.
- user id: saved against the order in Magento.

Handles multiple shipments per order. ParcelPoint bookings use 2 references, which will be:
1. Shipment increment number
2. Order increment number


Installation Instructions
-------------------------

### Via modman

- Install [modman](https://github.com/colinmollenhour/modman)
- Use the command from your Magento installation folder: `modman clone https://github.com/Zookal/parcelpoint/`

### Via composer

- Install [composer](http://getcomposer.org/download/)
- Install [Magento Composer](https://github.com/magento-hackathon/magento-composer-installer)
- Create a composer.json into your project like the following sample:

```json
{
    ...
    "require": {
        "zookal/parcelpoint":"*"
    },
    "repositories": [
	    {
            "type": "composer",
            "url": "http://packages.firegento.com"
        }
    ],
    "extra":{
        "magento-root-dir": "./"
    }
}
```

- Then from your `composer.json` folder: `php composer.phar install` or `composer install`

### Manually

- You can copy the files from the folders of this repository to the same folders of your installation


### Installation in ALL CASES

* Clear the cache, logout from the admin panel and then login again.

Uninstallation
--------------

* Remove all extension files from your Magento installation
* Via modman: `modman remove parcelpoint`
* Via composer, remove the line of your composer.json related to `Zookal/parcelpoint`

License
-------

[Open Software License (OSL 3.0)](http://opensource.org/licenses/osl-3.0.php)


Author
------

[@shakyaabiral](https://github.com/shakyaabiral)

[@chrisjz](https://github.com/chrisjz)
