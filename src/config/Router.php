<?php

namespace App\Config;

class Router
{
    private $routes = [];
    private $baseUrl = '/siapkak';
    private $routeParams = [];

    /**
     * Register GET route
     */
    public function get($path, $callback)
    {
        $this->routes['GET'][$path] = $callback;
    }

    /**
     * Register POST route
     */
    public function post($path, $callback)
    {
        $this->routes['POST'][$path] = $callback;
    }

    /**
     * Register PUT route
     */
    public function put($path, $callback)
    {
        $this->routes['PUT'][$path] = $callback;
    }

    /**
     * Register DELETE route
     */
    public function delete($path, $callback)
    {
        $this->routes['DELETE'][$path] = $callback;
    }

    /**
     * Dispatch request to appropriate handler
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Check if route is passed as query parameter
        if (isset($_GET['route'])) {
            $path = $_GET['route'];
        } else {
            // Remove base URL from path
            if (strpos($path, $this->baseUrl) === 0) {
                $path = substr($path, strlen($this->baseUrl));
            }
        }
        
        // Remove trailing slash
        $path = rtrim($path, '/');
        
        // If path is empty, set to /
        if ($path === '') {
            $path = '/';
        }

        // Check for exact route match
        if (isset($this->routes[$method][$path])) {
            $callback = $this->routes[$method][$path];
            return $this->executeCallback($callback);
        }

        // Check for parameterized routes
        foreach ($this->routes[$method] ?? [] as $route => $callback) {
            if ($this->matchRoute($route, $path)) {
                return $this->executeCallback($callback);
            }
        }

        // Route not found
        Response::error('Route not found', null, 404);
    }

    /**
     * Match parameterized routes
     */
    private function matchRoute($route, $path)
    {
        $routeParts = explode('/', trim($route, '/'));
        $pathParts = explode('/', trim($path, '/'));

        if (count($routeParts) !== count($pathParts)) {
            return false;
        }

        // Check each part and collect parameters
        $params = [];
        for ($i = 0; $i < count($routeParts); $i++) {
            if (preg_match('/^{(\w+)}$/', $routeParts[$i], $matches)) {
                // This is a parameter
                $params[$matches[1]] = $pathParts[$i];
            } elseif ($routeParts[$i] !== $pathParts[$i]) {
                // Not a parameter and doesn't match
                return false;
            }
        }

        // Store params for use in callback
        $this->routeParams = $params;
        return true;
    }

    /**
     * Execute callback
     */
    private function executeCallback($callback)
    {
        if (is_callable($callback)) {
            return $callback();
        } elseif (is_string($callback) && strpos($callback, '@') !== false) {
            [$controller, $method] = explode('@', $callback);
            $controllerClass = "App\\Controllers\\{$controller}";
            
            if (class_exists($controllerClass)) {
                $instance = new $controllerClass();
                
                if (method_exists($instance, $method)) {
                    // Pass route params if any exist
                    if (!empty($this->routeParams)) {
                        return $instance->$method(...array_values($this->routeParams));
                    }
                    return $instance->$method();
                }
            }
        }

        Response::error('Handler not found', null, 500);
    }
}
