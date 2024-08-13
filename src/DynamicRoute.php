<?php

class DynamicRoute
{
    private static $httpMethods = ['any', 'get', 'post', 'put', 'patch', 'delete'];
    private const EMIT_ROUTE_STATEMENTS = false;

    /**
     * Main entry point to register routes dynamically for a given controller.
     *
     * @param string $path
     * @param string $controllerClassName
     */
    public static function controller($path, $controllerClassName)
    {
        $reflection = self::getClassReflection($controllerClassName);
        $routes = self::buildRoutes($path, $reflection);

        foreach ($routes as $route) {
            Route::{$route->httpMethod}($route->slug, $route->target);
        }

        if (self::EMIT_ROUTE_STATEMENTS) {
            self::emitRoutes($controllerClassName, $routes);
        }
    }

    /**
     * Get a reflection instance of the controller class.
     *
     * @param string $controllerClassName
     * @return ReflectionClass
     */
    private static function getClassReflection($controllerClassName)
    {
        return class_exists($controllerClassName) ?
            new ReflectionClass($controllerClassName) :
            new ReflectionClass(app()->getNamespace() . 'Http\Controllers\\' . $controllerClassName);
    }

    /**
     * Build routes based on the public methods of the controller.
     *
     * @param string $path
     * @param ReflectionClass $class
     * @return array
     */
    private static function buildRoutes($path, ReflectionClass $class)
    {
        $routes = [];
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->name === 'getMiddleware') {
                continue;
            }

            $slug = self::generateSlug($method);
            $slugPath = trim($path . '/' . $slug, '/');

            $route = self::createRouteObject($slugPath, $method, $class);
            if ($route) {
                $routes[] = $route;
            }
        }

        return self::prioritizeRoutes($routes);
    }

    /**
     * Generate a URL slug based on the method name and its parameters.
     *
     * @param ReflectionMethod $method
     * @return string
     */
    private static function generateSlug(ReflectionMethod $method)
    {
        $slug = \Illuminate\Support\Str::slug(\Illuminate\Support\Str::snake(preg_replace(self::getMethodPattern(), '', $method->name), '-'));

        if ($slug === "index") {
            $slug = '';
        }

        foreach ($method->getParameters() as $parameter) {
            // Skip the parameter if it is of type Request
            if ($parameter->hasType() && $parameter->getType()->getName() === 'Illuminate\\Http\\Request') {
                continue;
            }

            $slug .= sprintf('/{%s%s}', self::getParameterName($parameter), $parameter->isDefaultValueAvailable() ? '?' : '');
        }

        return $slug;
    }

    /**
     * Get the pattern to match HTTP method names.
     *
     * @return string
     */
    private static function getMethodPattern()
    {
        return '/^(' . implode('|', self::$httpMethods) . ')/';
    }

    /**
     * Extract parameter name from the method parameter.
     *
     * @param ReflectionParameter $parameter
     * @return string
     */
    private static function getParameterName(ReflectionParameter $parameter)
    {
        if ($parameter->hasType() && !$parameter->getType()->isBuiltin()) {
            return strtolower(class_basename($parameter->getType()->getName()));
        }
        return strtolower($parameter->getName());
    }

    /**
     * Create a route object with method and path details.
     *
     * @param string $slugPath
     * @param ReflectionMethod $method
     * @param ReflectionClass $class
     * @return stdClass|null
     */
    private static function createRouteObject($slugPath, ReflectionMethod $method, ReflectionClass $class)
    {
        foreach (self::$httpMethods as $httpMethod) {
            if (self::startsWith($method->name, $httpMethod)) {
                $route = new \stdClass();
                $route->httpMethod = $httpMethod;
                $route->slug = $slugPath;
                $route->target = $class->getName() . '@' . $method->name;

                return $route;
            }
        }
        return null;
    }

    /**
     * Prioritize routes to ensure specific routes are handled before parameterized ones.
     *
     * @param array $routes
     * @return array
     */
    private static function prioritizeRoutes(array $routes)
    {
        usort($routes, function ($a, $b) {
            $aHasParam = strpos($a->slug, '{') !== false;
            $bHasParam = strpos($b->slug, '{') !== false;

            return $aHasParam === $bHasParam ? strcmp($a->slug, $b->slug) : ($aHasParam ? 1 : -1);
        });

        return $routes;
    }

    /**
     * Check if the string starts with a specific match.
     *
     * @param string $string
     * @param string $match
     * @return bool
     */
    private static function startsWith($string, $match)
    {
        return strpos($string, $match) === 0;
    }

    /**
     * Optionally emit the route statements to a file.
     *
     * @param string $controllerClassName
     * @param array $routes
     */
    private static function emitRoutes($controllerClassName, array $routes)
    {
        $directory = '/tmp/dynamicRoutes';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $routeList = "<?php\n// Routes for $controllerClassName\n";
        foreach ($routes as $route) {
            $routeList .= sprintf("Route::%s('%s', '%s');\n", $route->httpMethod, $route->slug, $route->target);
        }

        file_put_contents("$directory/{$controllerClassName}.php", $routeList . PHP_EOL);
    }
}
