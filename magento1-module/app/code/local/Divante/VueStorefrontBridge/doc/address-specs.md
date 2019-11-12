Address api

#### RESPONSE CODES:
- 200 when success
- 500 in case of error

### GET /vsbridge/address/list
This method is used to get addresses for customer

#### GET PARAMS
token - token obtained from /vsbridge/user/login

#### EXAMPLE CALL
curl -XGET 'https://your-domain.example.com/vsbridge/address/list?token=xu8h02nd66yq0gaayj4x3kpqwity02or'


#### RESPONSE BODY
```json
{
  "code": 200,
  "result": [
    {
      "increment_id": null,
      "parent_id": 222366,
      "is_active": true,
      "firstname": "Agata",
      "lastname": "test",
      "city": "wrocław",
      "country_id": "IL",
      "default_shipping": false,
      "default_billing": false,
      "region": {
        "region": null
      },
      "postcode": "10-111",
      "telephone": 1212112,
      "region_id": 0,
      "street": [
        "Kościuszki",
        "ABCD"
      ],
      "id": 206726
    },
    {
      "increment_id": null,
      "parent_id": 222366,
      "is_active": true,
      "firstname": "Agata",
      "lastname": "Test",
      "city": "Wrocław",
      "country_id": "IL",
      "postcode": "10-111",
      "telephone": 1212112,
      "default_shipping": false,
      "default_billing": false,
      "region_id": 0,
      "street": [
        "Kościuszki",
        1555
      ],
      "id": 206727,
      "region": {
        "region": null
      }
    }
  ]
}
```

### GET /vsbridge/address/get
This method is used to get customer address by id

#### GET PARAMS
token - token obtained from /vsbridge/user/login
addressId - customer address id

#### EXAMPLE CALL
curl -XGET 'https://your-domain.example.com/vsbridge/address/get?token=xu8h02nd66yq0gaayj4x3kpqwity02or&addressId=206727'

#### RESPONSE BODY
```json
{
  "code": 200,
  "result": {
    "increment_id": null,
    "parent_id": 222366,
    "is_active": true,
    "firstname": "Agata",
    "lastname": "Test",
    "city": "Wrocław",
    "country_id": "IL",
    "default_shipping": false,
    "default_billing": false,
    "postcode": "10-111",
    "telephone": 1212112,
    "region_id": 0,
    "street": [
      "Kościuszki",
      1555
    ],
    "id": 206727,
    "region": {
      "region": null
    }
  }
}
```

### POST /vsbridge/address/delete
This method is used to delete customer address by id

#### GET PARAMS
token - token obtained from /vsbridge/user/login

#### REQUEST BODY
```json
{"address": {"id": 206728}}
```

#### RESPONSE BODY
```json
{"code":200,"result":"הכתובת נמחקה בהצלחה"}
```

### POST /vsbridge/address/update
This method is used to add or update customer address

#### GET PARAMS
token - token obtained from /vsbridge/user/login

#### REQUEST BODY
```json
{
"address": {
"increment_id": null,
"parent_id": 222366,
"is_active": true,
"firstname": "Agata",
"lastname": "Test",
"city": "Wrocław",
"country_id": "IL",
"postcode": "10-111",
"telephone": 1212112,
"default_shipping": false,
"default_billing": false,
"region_id": 0,
"street": ["Kościuszki", 1555],
"id": 206727,
"region": {
"region": null}
}
}
```

### EXAMPLE CALL
curl -X POST -H 'Content-Type: application/json' -i 'https://your-domain.example.com/vsbridge/address/update?token=xu8h02nd66yq0gaayj4x3kpqwity02or' --data '{
"address": {
    "increment_id": null,
    "parent_id": 222366,
    "is_active": true,
    "firstname": "Agata",
    "lastname": "Test",
    "city": "Wrocław",
    "country_id": "IL",
    "postcode": "10-111",
    "telephone": 1212112,
    "region_id": 0,
    "street": ["Kościuszki", 1555],
    "id": 206727,
    "region": {
      "region": null
    }
}
}

#### RESPONSE BODY
```json
{
  "code": 200,
  "result": [
    {
      "increment_id": null,
      "parent_id": 222366,
      "is_active": true,
      "prefix": null,
      "firstname": "Agata",
      "middlename": null,
      "lastname": "test",
      "suffix": null,
      "company": null,
      "default_shipping": false,
      "default_billing": false,
      "city": "wroc\u0142aw",
      "country_id": "IL",
      "region": {
        "region": null
      },
      "postcode": "10-111",
      "telephone": 1212112,
      "fax": null,
      "vat_id": null,
      "region_id": 0,
      "street": [
        "Ko\u015bciuszki",
        12
      ],
      "customer_id": 222366,
      "id": 206726
    }
  ]
}
```