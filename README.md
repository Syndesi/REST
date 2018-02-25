# REST

This library is intended to be provide a small but effective way to write REST-ful API's with PHP.
It currently supports these formats:

| Format                            | Allowed as Input | Allowed as Output |
|-----------------------------------|:----------------:|:-----------------:|
| application/json                  |         ✓        |        ✓          |
| text/vnd.yaml                     |         ✓        |        ✓          |
| multipart/form-data (with files)  |         ✓        |        ✗          |
| application/x-www-form-urlencoded |         ✓        |        ✗          |

In order to use this library, you need to configure your webserver to route all API-calls to a single main file.

The following example assumes that `htdocs/` is your root directory and that the API-calls are redirected to `htdocs/api/api.php`, therefore they have the URL `domain/api/{parameters}`.
The remaining parameters will be removed from the URL and added as the `$_GET`-Parameter `__PATH`:

``` apacheconf
# stored as htdocs/api/.htaccess
RewriteEngine On

# rewrites API calls to the api file
RewriteRule ^api/(.*)$ api.php?__PATH=$1 [QSA,NC,L]
```

``` php
<?php
// stored as htdocs/api/api.php
require_once '../vendor/autoload.php';

// creates a new router
$router = new Router(new ClientRequest());

// add some routes
$router->setRoute(
  'GET',                                   // the HTTP-method which should be used (e.g. GET/POST/PUT/DELETE)
  'helloWorld',                            // the path for this route, here it is domain/api/helloWorld
  function($request, $args){               // this function is executed when this route is called
    $request->finish('Hello world! :D');   // finish($object, $description, $status) will send the result to the client and stops the execution
  },
  'Basic Hello-World-Example'              // a brief explanation for this route
);

// tell the router to resolve the routes
$router->resolve();
```

Result of calling `GET://domain/api/helloWorld`:
``` json
{
    "result": "Hello world! :D",
    "status": "OK",
    "environment": {
        "timestamp": "2018-25-02CET20:47:3655",
        "method": "GET",
        "url": "helloWorld"
    }
}
```

This and other examples are stored in `example/`.


Furthermore is this code unit-tested, but not some main classes because it's very difficult to create many mock functions. But they will eventually come.

```shell
./vendor/bin/phpunit --bootstrap ./vendor/autoload.php tests/
```

---
Created by Syndesi, 2018