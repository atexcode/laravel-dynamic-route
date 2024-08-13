# Laravel Dynamic Route by Atex Code
A dynamic routing library for Laravel that brings back implicit controller routes, automatically mapping methods to routes with support for parameters.

## Background ##
I drew inspiration from the AdvancedRoute class, which laid the groundwork for dynamically defining routes based on controller methods. However, the AdvancedRoute class lacked the capability to handle routes with parameters automatically. To address this limitation, I created a new class named DynamicRoute that not only detects methods but also handles methods with parameters, ensuring routes are defined automatically with the appropriate parameters.

## Description ##
The DynamicRoute class is designed to dynamically define routes in Laravel based on the methods present in a given controller. It operates by inspecting the public methods of a specified controller class using PHP's Reflection API. For each method, it generates a route slug based on the method name, following the convention of removing HTTP method prefixes (like get, post, etc.) and converting the name into a URL-friendly format. The class goes a step further by examining method parameters to incorporate them into the route slug. It identifies typed parameters, such as Product or string, and adds corresponding placeholders to the route path. This ensures that routes are automatically defined not only for simple methods but also for those requiring dynamic parameters. The class handles all HTTP methods (GET, POST, PUT, DELETE, etc.), and can optionally output the generated routes to a file for easier integration. This approach eliminates the need for manual route definitions and provides a flexible, automated solution for managing routes in Laravel applications.

## Sample Controller Methods ##
```php
<?php

namespace App\Http\Controllers;

class FrontendController extends Controller {
    /**
     * Responds to any (GET,POST, etc) request to given path
     */
    public function anyIndex() {
        //
    }

    /**
     * Responds to requests to GET /blog
     */
    public function getBlog() {
        //
    }

    /**
     * Responds to requests to POST /article
     */
    public function postArticle() {
        //
    }

    /**
     * Responds to requests to PUT /article/{article}
     */
    public function putArticle(Article $article) {
        //
    }

    /**
     * Responds to requests to GET /article/{article}
     */
    public function getArticle(Article $article) {
        //
    }
}
```

## Installation ##

### via composer ###

```
composer require atexcode/laravel-dynamic-route
```

## Usage ##

Add the following line to where you want your controller to be mapped:

```php
DynamicRoute::controller('/{PATH}', '{CONTROLLER}');
```

### Example: ###

```php
DynamicRoute::controller('/', 'FrontendController');
```

## Found Bugs? ##

If you found any bugs or issues, please contact at athar.techs@gmail.com and contribute to fix the bugs.
