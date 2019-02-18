Contacts module

#### RESPONSE CODES:
- 200 when success
- 500 in case of error


### POST /vsbridge/contact/send
This method is used to submit user inquiry

### REQUEST BODY
```json
{
  "form": {
    "checker": null,
    "name": "User Name",
    "email": "test@test.com",
    "comment": "Text",
    "telephone": ""
  }
}
```

#### EXAMPLE CALL
curl 'https://your-domain.example.com/vsbridge/contact/submit' -H 'content-type: application/json' -H 'accept: */*' --data-binary '{"form": {"checker": null, "name":"User Name", "email":"test@test.com", "message":"Text"}}' --compressed

#### RESPONSE BODY
```json
{  
   "code":200,
   "result": true
}
```