# skyapi-command-line-oauth

simple script to generate skyapi access tokens. requires php command line to be installed.

- copy config.example.json to config.json
- update config.json with your Application ID and Secret
- run the file `php skyauth.php`

```
❯ php skyauth.php
Array
(
    [Ip] => 127.0.0.1
    [Port] => 8080
    [ClientId] => 8*********d
    [ClientSecret] => 2************=
)
Open the following URL in a browser to continue
https://oauth2.sky.blackbaud.com/authorization?response_type=code&client_id=8*********d&redirect_uri=http%3A%2F%2F127.0.0.1%3A8080%2Fauthorization-code%2Fcallback&state=xxxxx
Received code=0xxxx5 state=xxx
Getting an access token...

Access Token:
eyJhbGciOiJSUzIxxxx - will show full token in terminal

Refresh Token: 46d8... - will show full token in terminal

Environment Name: Test Environment 1

Environment Id: p-**************

User Email: ******@gmail.com
```


