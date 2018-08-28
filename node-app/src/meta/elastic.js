const config = require('../../config.json')
const VsBridgeApiClient = require('../lib/vsbridge-api')
const api = new VsBridgeApiClient(config)

function putAlias(db, originalName, aliasName, next) {
    let step2 = () => {
        db.indices.putAlias({ index: originalName, name: aliasName }).then(result=>{
            console.log('Index alias created', result)
        }).then(next).catch(err => {
            console.log(err.message)
            next()
        })
    }

    return db.indices.deleteAlias({
        index: aliasName,
        name:  originalName
    }).then((result) => {
        console.log('Public index alias deleted', result)
        step2()
    }).catch((err) => {
        console.log('Public index alias does not exists', err.message)
        step2()
    })
}

function deleteIndex(db, indexName, next) {
    db.indices.delete({
        "index": indexName
      }).then((res) => {
        console.dir(res, { depth: null, colors: true })
        next()
      }).catch(err => {
        console.error(err)
        next(err)
      })
}

function reIndex(db, fromIndexName, toIndexName, next) {
    db.reindex({
      waitForCompletion: true,
      body: {
        "source": {
          "index": fromIndexName
        },
        "dest": {
          "index": toIndexName
        }
      }
    }).then(res => {
      console.dir(res, { depth: null, colors: true })
      next()
    }).catch(err => {
      console.error(err)
      next(err)
    })
}

function createIndex(db, indexName, next) {
    const step2 = () => {
        db.indices.delete({
            "index": indexName
            }).then(res1 => {
                console.dir(res1, { depth: null, colors: true })
                db.indices.create(
                    {
                        "index": indexName
                    }).then(res2 => {
                        console.dir(res2, { depth: null, colors: true })
                        next()
                    }).catch(err => {
                        console.error(err)
                        next(err)
                    })
                }).catch(() => {
                    db.indices.create(
                        {
                        "index": indexName
                        }).then(res2 => {
                            console.dir(res2, { depth: null, colors: true })
                            next()
                        }).catch(err => {
                            console.error(err)
                            next(err)
                        })
                })
    }

    return db.indices.deleteAlias({
        index: '*',
        name:  indexName
    }).then((result) => {
        console.log('Public index alias deleted', result)
        step2()
    }).catch((err) => {
        console.log('Public index alias does not exists', err.message)
        step2()
    })
}

async function putMappings(db, indexName, next, token) {
    let attributeData = await getAttributeData(token);
    db.indices.putMapping({
        index: indexName,
        type: "product",
        body: attributeData
        }).then(res => {
            console.dir(res, { depth: null, colors: true })

            db.indices.putMapping({
                index: indexName,
                type: "product",
                body: {
                    properties: {
                        created_at: {
                            type: "date",
                            format: "yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
                        },
                        updated_at: {
                            type: "date",
                            format: "yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
                        },
                        configurable_options: {
                            properties: {
                                attribute_id: { type: "long" },
                                default_label: { type: "text"},
                                label: { type: "text"},
                                frontend_label: { type: "text"},
                                store_label: { type: "text"},
                                values: {
                                    properties: {
                                        default_label: { type: "text"},
                                        label: { type: "text"},
                                        frontend_label: { type: "text"},
                                        store_label: { type: "text"},
                                        value_index:  { type: "keyword" }
                                    }
                                }
                            }
                        },
                        eco_collection: { type: "integer" },
                        eco_collection_options: { type: "integer" },
                        erin_recommends: { type: "integer" },
                        stock: {
                            properties: {
                                is_in_stock: {
                                    "type": "boolean"
                                },
                                qty: {
                                    "type": "long"
                                }
                            }
                        }
                    }
                }
            }).then(res1 => {
                console.dir(res1, { depth: null, colors: true })

                db.indices.putMapping({
                    index: indexName,
                    type: "taxrule",
                    body: {
                        properties: {
                            id: { type: "long" },
                            rates: {
                                properties: {
                                    rate: { type: "float" }
                                }
                            }
                        }
                    }
                }).then(res2 => {
                    console.dir(res2, { depth: null, colors: true })

                    db.indices.putMapping({
                        index: indexName,
                        type: "attribute",
                        body: {
                            properties: {
                                id: { type: "long" },
                                attribute_id: { type: "long" },
                                options: {
                                    properties: {
                                        value:  { type: "text", "index" : "not_analyzed" }
                                    }
                                }
                            }
                        }
                    }).then(res3 => {
                        console.dir(res3, { depth: null, colors: true })
                        next()
                    }).catch(err3 => {
                        throw new Error(err3)
                    })
                }).catch(err2 => {
                    throw new Error(err2)
                })
            })
        }).catch(err1 => {
                console.error(err1)
                next(err1)
            })
  }

/**
 * Get attribute data for mappings
 */
function getAttributeData(token) {
    let promise = new Promise((resolve, reject) => {
        console.log('*** Getting attribute data')
        api.authWith(token);
        api.get(config.vsbridge['attribute_data_endpoint']).type('json').end((resp) => {
            if (resp.body && resp.body.code !== 200) { // unauthroized request
                console.log(resp.body.result);
                process.exit(-1)
            }
            resolve(resp.body.result);
            reject('Attribute data not available now, please try again later');
        })
    });

    return promise
        .then(
            result => (result),
            error => (error)
        );
}

module.exports = {
    putMappings,
    putAlias,
    createIndex,
    deleteIndex,
    reIndex
}