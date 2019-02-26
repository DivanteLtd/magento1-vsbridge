Wishlist module

#### RESPONSE CODES:
- 200 when success
- 500 in case of error



### GET /vsbridge/wishlist/pull
This method is used to get wishlist items for customer

#### GET PARAMS
token - token obtained from /vsbridge/user/login

#### EXAMPLE CALL
curl -XGET 'https://your-domain.example.com/vsbridge/wishlist/pull?token=xu8h02nd66yq0gaayj4x3kpqwity02or'

#### RESPONSE BODY
```json
{  
   "code":200,
   "result":{  
      "items":[  
         {  
            "wishlist_item_id":1,
            "product_id":101,
            "store_id":2,
            "added_at":"2018-10-23 21:08:25",
            "qty":1
         }
      ]
   }
}
```



### POST /vsbridge/wishlist/update
This method is used to update the wishlist server side.

#### GET PARAMS
token - token obtained from /vsbridge/user/update

### REQUEST BODY
```json
{"wishListItem": {"productId": "38389"}}
```

Force wishlist update -> override all items in wishlist

### REQUEST BODY
```json
{"wishListItem": {"forceUpdate": true, "productIds": [38389, 31100, 1]}}
```

#### EXAMPLE CALL
curl 'https://your-domain.example.com/vsbridge/wishlist/update?token=xu8h02nd66yq0gaayj4x3kpqwity02or' -H 'content-type: application/json' -H 'accept: */*' --data-binary '{"wishListItem": {"productId": "38389"}}' --compressed

#### RESPONSE BODY
```json
{  
   "code":200,
   "result":{  
      "items":[  
         {  
            "wishlist_item_id":1,
            "product_id":101,
            "store_id":2,
            "added_at":"2018-10-23 21:08:25",
            "qty":1
         }
      ]
   }
}
```


### POST /vsbridge/wishlist/delete
This method is used to remove the wishlist item server side.

#### GET PARAMS
token - token obtained from /vsbridge/user/login

#### REQUEST BODY
```json
{"wishListItem": {"productId": "1"}}
```

#### EXAMPLE CALL
curl 'https://your-domain.example.com/vsbridge/wishlist/delete?token=xu8h02nd66yq0gaayj4x3kpqwity02or' -H 'content-type: application/json' -H 'accept: */*' --data-binary '{"wishListItem": {"productId": "38389"}}' --compressed

#### RESPONSE BODY
```json
{  
   "code":200,
   "result": true
}
```
