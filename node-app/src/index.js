const config = require('../config.json')
const VsBridgeApiClient = require('./lib/vsbridge-api')
const api = new VsBridgeApiClient(config)

const BasicImporter = require('./importers/basic')

const _ = require('lodash')

const promiseLimit = require('promise-limit')
const limit = promiseLimit(3) // limit N promises to be executed at time
const promise = require('./lib/promise') // right now we're using serial execution because of recursion stack issues
const path = require('path')
const shell = require('shelljs')
const fs = require('fs')
const jsonFile = require('jsonfile')


let INDEX_VERSION = 1
let INDEX_META_DATA
const INDEX_META_PATH = path.join(__dirname, '../var/indexMetadata.json')

const { spawn } = require('child_process');

const es = require('elasticsearch')
let client = new es.Client({ // as we're runing tax calculation and other data, we need a ES indexer
    host: config.elasticsearch.host,
    log: 'error',
    apiVersion: '5.5',
    requestTimeout: 10000
})

const CommandRouter = require('command-router')
const cli = CommandRouter()

cli.option({ name: 'page'
, alias: 'p'
, default: 0
, type: Number
})
cli.option({ name: 'pageSize'
, alias: 'l'
, default: 25
, type: Number
})

cli.option({ name: 'partitions'
, alias: 't'
, default: 20
, type: Number
})

cli.option({ name: 'runSerial'
, alias: 's'
, default: false
, type: Boolean
})


function showWelcomeMsg() {
    console.log('** CURRENT INDEX VERSION', INDEX_VERSION, INDEX_META_DATA.created)
}


function readIndexMeta() {
    let indexMeta = { version: 0, created: new Date(), updated: new Date() }

    try {
        indexMeta = jsonFile.readFileSync(INDEX_META_PATH)
    } catch (err){
        console.log('Seems like first time run!', err.message)
    }
    return indexMeta
}

function recreateTempIndex() {

    let indexMeta = readIndexMeta()

    try { 
        indexMeta.version ++
        INDEX_VERSION = indexMeta.version
        indexMeta.updated = new Date()
        jsonFile.writeFileSync(INDEX_META_PATH, indexMeta)
    } catch (err) {
        console.error(err)
    }

    let step2 = () => { 
        client.indices.create({ index: `${config.elasticsearch.indexName}_${INDEX_VERSION}` }).then(result=>{
            console.log('Index Created', result)
            console.log('** NEW INDEX VERSION', INDEX_VERSION, INDEX_META_DATA.created)
        })
    }


    return client.indices.delete({
        index: `${config.elasticsearch.indexName}_${INDEX_VERSION}`
    }).then((result) => {
        console.log('Index deleted', result)
        step2()
    }).catch((err) => {
        console.log('Index does not exst')
        step2()
    })
}

function publishTempIndex() {
    let step2 = () => { 
        client.indices.putAlias({ index: `${config.elasticsearch.indexName}_${INDEX_VERSION}`, name: config.elasticsearch.indexName }).then(result=>{
            console.log('Index alias created', result)
        })
    }


    return client.indices.deleteAlias({
        index: `${config.elasticsearch.indexName}_${INDEX_VERSION-1}`,
        name: config.elasticsearch.indexName 
    }).then((result) => {
        console.log('Public index alias deleted', result)
        step2()
    }).catch((err) => {
        console.log('Public index alias does not exists', err.message)
        step2()
    })  
}

function storeResults(singleResults, entityType) {
    singleResults.map((ent) => {
        client.index({
            index: `${config.elasticsearch.indexName}_${INDEX_VERSION}`,
            type: entityType,
            id: ent.id,
            body: ent
        })                    
    })
}


/**
 * Import full list of specific entites
 * @param {String} entityType 
 * @param {Object} importer 
 */
function importListOf(entityType, importer, config, api, page = 0, pageSize = 100, recursive = true) {

    if (!config.vsbridge[entityType + '_endpoint'])
    {
        console.error('No endpoint defined for ' + entityType)
        return
    }

    return new Promise((resolve, reject) => {

        let query = {
            entityType: entityType,
            page: page,
            pageSize: pageSize
        }


        let generalQueue = []
        console.log('*** Getting objects list for', query)
        api.get(config.vsbridge[entityType + '_endpoint']).query(query).end((resp) => {
            console.log(resp)
            let queue = []
            let index = 0
            for(let obj of resp.body) { // process single record
                let promise = importer.single(obj).then((singleResults) => {
                    storeResults(singleResults, entityType)
                    console.log('* Record done for ', obj.id, index, count)
                    index++
                })
                if(cli.params.runSerial)
                    queue.push(() => promise)
                else
                    queue.push(promise)
            }
            let resultParser = (results) => {
                console.log('** Page done ', page, resp.body.length)
                
                if(resp.body.length === pageSize)
                {
                    if(recursive) {
                        console.log('*** Switching page!')
                        return importListOf(entityType, importer, config, api, page + 1, pageSize) 
                    }
                }
            }
            if(cli.params.runSerial)
                promise.serial(queue).then(resultParser).then((res) => resolve(res)).catch((reason) => { console.error(reason); reject() })
            else 
                Promise.all(queue).then(resultParser).then((res) => resolve(res)).catch((reason) => { console.error(reason); reject() })
        })
    })
}

cli.command('products',  () => { // TODO: add parallel processing
   showWelcomeMsg()

   importListOf('product', new BasicImporter('product', config, api, page = cli.options.page, pageSize = cli.options.pageSize), config, api, page = cli.options.page, pageSize = cli.options.pageSize).then((result) => {

   }).catch(err => {
      console.error(err)
   })
})    

cli.command('taxrules',  () => {
});

cli.command('attributes',  () => {
});

cli.command('categories',  () => { 
    showWelcomeMsg()
    let importer = new BasicImporter('category', new CategoryImpoter(config, api, client), config, api, client) // ProductImporter can be switched to your custom data mapper of choice
    importer.single({ id: config.pimcore.rootCategoryId }, level = 1, parent_id = 1).then((results) => {
        let fltResults = _.flattenDeep(results)
        storeResults(fltResults, 'category')
     })});



cli.command('new',  () => {
    showWelcomeMsg()
    recreateTempIndex()
});


cli.command('publish',  () => {
    showWelcomeMsg()
    publishTempIndex()
});


/**
 * Download asset and return the meta data as a JSON 
 */
cli.command('asset', () => {
    if(!cli.options.id) {
        console.log(JSON.stringify({ status: -1, message: 'Please provide asset Id' }))
        process.exit(-1)
    }
    api.get(`asset/id/${cli.options.id}`).end((resp) => {
        if(resp.body && resp.body.data) {
            const imageName =  resp.body.data.filename
            const imageRelativePath = resp.body.data.path
            const imageAbsolutePath = path.join(config.pimcore.assetsPath, imageRelativePath, imageName)
            
            shell.mkdir('-p', path.join(config.pimcore.assetsPath, imageRelativePath))
            fs.writeFileSync(imageAbsolutePath, Buffer.from(resp.body.data.data, 'base64'))
            console.log(JSON.stringify({ status: 0, message: 'Image downloaded!', absolutePath: imageAbsolutePath, relativePath: path.join(imageRelativePath, imageName) }))
        }
    })    
})

cli.on('notfound', (action) => {
  console.error('I don\'t know how to: ' + action)
  process.exit(1)
})
  
  
process.on('unhandledRejection', (reason, p) => {
  console.error('Unhandled Rejection at: Promise', p, 'reason:', reason);
   // application specific logging, throwing an error, or other logic here
});

process.on('uncaughtException', function (exception) {
    console.error(exception); // to see your exception details in the console
    // if you are on production, maybe you can send the exception details to your
    // email as well ?
});
  

INDEX_META_DATA = readIndexMeta()
INDEX_VERSION = INDEX_META_DATA.version
 
  // RUN
cli.parse(process.argv);


// FOR DEV/DEBUG PURPOSES

cli.command('testcategory',  () => {
    let importer = new BasicImporter('category', new CategoryImpoter(config, api, client), config, api, client) // ProductImporter can be switched to your custom data mapper of choice
    importer.single({ id: 11148 }).then((results) => {
        let fltResults = _.flattenDeep(results)
        let obj = fltResults.find((it) => it.dst.id === 11148)
        console.log('CATEGORIES', fltResults.length, obj, obj.dst.children_data)
        console.log('ATTRIBUTES', attribute.getMap())
        console.log('CO', obj.dst.configurable_options)
     }).catch((reason) => { console.error(reason) })
 });
 

cli.command('testproduct',  () => {
   let importer = new BasicImporter('product', new ProductImpoter(config, api, client), config, api, client) // ProductImporter can be switched to your custom data mapper of choice
   importer.single({ id: 1237 }).then((results) => {
       let fltResults = _.flatten(results)
       let obj = fltResults.find((it) => it.dst.id === 1237)
       console.log('PRODUCTS', fltResults.length, obj, obj.dst.configurable_children)
       console.log('ATTRIBUTES', attribute.getMap())
       console.log('CO', obj.dst.configurable_options)
    }).catch((reason) => { console.error(reason) })
});
  
// Using a single function to handle multiple signals
function handle(signal) {
    console.log('Received  exit signal. Bye!');
    process.exit(-1)
  }
process.on('SIGINT', handle);
process.on('SIGTERM', handle);
