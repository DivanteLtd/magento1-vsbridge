# Vue Storefront support for Magento 1.9
This projects enables You to use Magento 1.9 as a backend platform for [Vue Storefront - first Progressive Web App for e-Commerce](https://github.com/DivanteLtd/vue-storefront). 

<a href="http://demo-magento1.vuestorefront.io">![doc/media/magento1-hp.png](Home page of Magento1 demo)</a>

Vue Storefront is a standalone PWA storefront for eCommerce. It can be conencted with any eCommerce backend (eg. Magento, Pimcore, Prestashop or Shopware) through the API.

[![See how it works!](doc/vs-video.png)](https://www.youtube.com/watch?v=L4K-mq9JoaQ)

Sign up for a demo at https://vuestorefront.io/ (Vue Storefront integrated with Magento2 or Magento1).

# Magento 1.9 data bridge
Vue Storefront is platform agnostic - which mean: it can be connected to virtually any eCommerce CMS. This project is a data connector for *Magento 1.9*.

**This bridge at current stage is synchronizing the catalog part: *products, categories, attributes and taxRules*  the missing part is *users, orders and cart* synchronization. If you like to have two way communication just contact us at contributors@vuestorefront.io**.

Areas for improvements:
- performance (right now it's single threaded but can be easily modified to use node-cluster),
- dynamic-requests handling (user accounts, shopping carts, orders).

# Setup and installation
Magento 1.9 bridge uses it's own Magento API which is available as [standard Magento module](https://github.com/DivanteLtd/magento1-vsbridge/tree/master/magento1-module/). The second part is a node.js app which is used to consume the API results.

Requirements: Magento 1.9x Community or Enterprise; Node.js >= 8; [vue-storefront](https://github.com/DivanteLtd/vue-storefront) and [vue-storefront-api](https://github.com/DivanteLtd/vue-storefront-api) installed.

## Magento module setup

First, please clone the whole repository locally:

```
git clone https://github.com/DivanteLtd/magento1-vsbridge.git magento1-vsbridge
cd magento1-vsbridge
cp magento1-module/app/* <MAGENTO_FOLDER>/app/
```

Magento module uses JWT tokens authorization method based on standard Magento admin users account system. **Please create a dedicated admin account** for magento1-bridge purposes only with minimal access (ACL) to the catalog part.

Then, please setup the JWT "secretToken" by modifying: [`magento1-module/app/etc/modules/Divante_VueStorefrontBridge.xml`](https://github.com/DivanteLtd/magento1-vsbridge/blob/master/magento1-module/app/code/local/Divante/VueStorefrontBridge/etc/config.xml)

https://github.com/DivanteLtd/magento1-vsbridge/blob/3f92a6d842e6afb4c7e34e789229848710127b8b/magento1-module/app/code/local/Divante/VueStorefrontBridge/etc/config.xml#L5

Then please clean the Magento cache.
To make sure that the module works just fine You can try to authorize the user using Postman:

![doc/media/postman.png]

## Node app setup

As You've probably noticed in the cloned directory there is a second folder called [`node-app`](https://github.com/DivanteLtd/magento1-vsbridge/tree/master/node-app). This is a consumer application that's responsible for synchronizing the Magento1 data with the ElasticSearch instance.

This tool required ElasticSearch instance up and running. The simplest way to have one is to install [vue-storefront](https://github.com/DivanteLtd/vue-storefront) and [vue-storefront-api](https://github.com/DivanteLtd/vue-storefront-api) and run `docker-compose up` inside `vue-storefront-api` installation as the project [contains Docker file](https://github.com/DivanteLtd/vue-storefront-api/blob/master/docker-compose.yml) for Vue Storefront.

Then you need to modify the configs:

```
cd node-app/src
cp config.example.json config.json
nano config.json
```

In the config file please setup the following variables:
- [`auth`](https://github.com/DivanteLtd/magento1-vsbridge/blob/5d4b9285c2dd2a20900e6075f50ebc2d7802499e/node-app/config.example.json#L9) section to setup Magento's user login and password and the previously set [JWT secret](https://github.com/DivanteLtd/magento1-vsbridge/blob/5d4b9285c2dd2a20900e6075f50ebc2d7802499e/magento1-module/app/code/local/Divante/VueStorefrontBridge/etc/config.xml#L6)
- ['endpoint'](https://github.com/DivanteLtd/magento1-vsbridge/blob/5d4b9285c2dd2a20900e6075f50ebc2d7802499e/node-app/config.example.json#L14) should match your Magento 1.9 URL,
- [`elasticsearch.indexName`](https://github.com/DivanteLtd/magento1-vsbridge/blob/5d4b9285c2dd2a20900e6075f50ebc2d7802499e/node-app/config.example.json#L4) should be set to Your ElasticSearch index which then will be connected to the Vue Storefront. It can be fresh / non-existient index as well (will be created then). For example You may have: `vue_storefront_mangento1`


# Available commands
The bridge works on temporary, versioned ES indexes. You decide when the index should be published (when all data objects are properly set).

Create new version of index (for example: vue_storefront_mangento1_1): 
```
node index.js new
```

Publish new version of index (creates an alias with prod. name of the index: vue_pimcore_1 -> vue_pimcore): 
```
node index.js publish
```

All the list-based commands (like indexing list of products or categores) support `--limit`, `--offset`, `--switchPage=true` parameters.
The following commands works on temp indexes - so you can publish index in v. 1 and then work on v. 2 without disturbing users with partially indexed products or categories.

Index categories: 
```
node index.js categories --limit=50 --offset=0
```

Index products: 
```
node index.js products --limit=20 --offset=0
```

Index tax rules: 
```
node index.js taxrulse
```


# Data formats and architecture
As Pimcore is a very extensible Framework, the data structures and format may vary. By default we do support official eCommerce Framework data structures which you can check in [Pimcore Advanced eCommerce demo](https://pimcore.com/en/try).
For demonstration purposes we do support pretty basic elements of eCommerce Framework data structures:
- set of required attributes,
- categories,
- products: localized attributes, single photo (can be easily extendend), variants, prices.


![Pimcore2vuestorefront architecture](doc/pimcore2vuestorefront-architecture.png)

# Screenshots

Please visit [Vue Storefront site](http://vuestorefront.io) to check out why it's so cool!

![Vue Storefront with Pimcore products](doc/vs-pimcore-1.png)

As you may observed, configured products do work perfectly well after imported to Vue Storefront!

![Vue Storefront with Pimcore products](doc/vs-pimcore-2.png)

# Customization
Architecture is very flexible, based on JSON templates for entities and attributes, using dynamic Strategy and Decorator design patterns.

If you wan't to map custom attributes or sub-objects you need just to:

1. Add custom mapper as copy of `importers/product.js` or `importers/category.js`. For example, you can create a speciall class under `./importers/my-product-importer.js`:

```js
const _ = require('lodash');
const attribute = require('../lib/attribute')

module.exports = class {
    constructor(config, api, db) {
        this.config = config
        this.db = db
        this.api = api
        this.single = this.single.bind(this)
    }

    /**
     * This is an EXAMPLE of custom Product / entity mapper; you can write your own to map the Pimcore entities to vue-storefront data format (see: templates/product.json for reference)
     * @returns Promise
     */
    single(pimcoreObjectData, convertedObject, childObjects, level = 1, parent_id = null) {
        return new Promise((resolve, reject) => {
            console.debug('Helo from custom product converter for', convertedObject.id)
            convertedObject.url_key = pimcoreObjectData.key // pimcoreObjectData.path?
            convertedObject.type_id = (childObjects.length > 0) ? 'configurable' : 'simple'

            let elements = pimcoreObjectData.elements
            let features = elements.find((elem) => elem.name === 'features')
            let categories = elements.find((elem) => elem.name === 'categories')
            let images = elements.find((elem) => elem.name === 'images')
            let materialComposition = elements.find((elem) => elem.name === 'materialComposition')
            let color = elements.find((elem) => elem.name === 'color')
            let gender = elements.find((elem) => elem.name === 'gender')
            let size = elements.find((elem) => elem.name === 'size')

            let localizedFields = elements.find((itm)=> { return itm.name === 'localizedfields'})
            
            let subPromises = []            
            Promise.all(subPromises).then(results => {
                resolve({ src: pimcoreObjectData, dst: convertedObject }) // after all the mapping processes have been executed - we can just give back the controll to master process
            }).catch((reason) => { console.error(reason) })
        })
    }
}

```

2. Modify the `index.js` base methods to use this new strategy. For example:

**CHANGE:**
```js
   importListOf('product', new BasicImporter('product', new ProductImpoter(config, api, client), config, api, client), config, api, offset = cli.options.offset, count = cli.options.limit, recursive = false).then((result) => 
```

**TO:**
```js
   const MyProductImporter = require('./importers/my-product-importer.js')
   importListOf('product', new BasicImporter('product', new MyProductImpoter(config, api, client), config, api, client), config, api, offset = cli.options.offset, count = cli.options.limit, recursive = false).then((result) => 
```
# Templates
Another way to extend or customize this bridge is to change the entities and attributes templates as you can find under `./src/importers/templates`. Templates base on Vue-storefront expected data formats and can be customized just by editing the files.

Each custom Pimcore element is mapped to attribute regarding it's name (for example `attribute_code_color`) or type (eg. `attribute_type_select.json`). Color and size are kind of special attributes because Vue Storefront configurable products by default use for products customization. This is the reason we've prepared templates for these particular attributes. Other ones are created just by adjusting the specific element type.

# TODO
A lot of features and things are to be added! For sure we should work on the performance and parallel processing. You can take a look at [Issues](https://github.com/pimcore/demo-ecommerce/issues) and pick some of them for your first Pull Requests!

# Licence 
Pimcore2vuestorefront source code is completely free and released under the [MIT License](https://github.com/DivanteLtd/vue-storefront/blob/master/LICENSE).

