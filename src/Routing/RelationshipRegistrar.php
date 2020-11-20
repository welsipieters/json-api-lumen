<?php
/*
 * Copyright 2020 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Laravel\Routing;

use Illuminate\Contracts\Routing\Registrar as RegistrarContract;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Routing\RouteCollection;

class RelationshipRegistrar
{

    /**
     * @var RegistrarContract
     */
    private RegistrarContract $router;

    /**
     * @var string
     */
    private string $resourceType;

    /**
     * @var string
     */
    private string $controller;

    /**
     * @var string
     */
    private string $parameter;

    /**
     * RelationshipRegistrar constructor.
     *
     * @param RegistrarContract $router
     * @param string $resourceType
     * @param string $controller
     * @param string $parameter
     */
    public function __construct(
        RegistrarContract $router,
        string $resourceType,
        string $controller,
        string $parameter
    ) {
        $this->router = $router;
        $this->resourceType = $resourceType;
        $this->controller = $controller;
        $this->parameter = $parameter;
    }

    /**
     * @param string $fieldName
     * @param bool $hasMany
     * @param array $options
     * @return RouteCollection
     */
    public function register(string $fieldName, bool $hasMany, array $options = []): RouteCollection
    {
        $routes = new RouteCollection();

        foreach ($this->getRelationMethods($hasMany, $options) as $method) {
            $fn = 'add' . ucfirst($method);
            $route = $this->{$fn}($fieldName, $options);
            $routes->add($route);
        }

        return $routes;
    }

    /**
     * Add the read related action.
     *
     * @param string $fieldName
     * @param array $options
     * @return IlluminateRoute
     */
    protected function addReadRelated(string $fieldName, array $options): IlluminateRoute
    {
        $uri = $this->getRelationshipUri($fieldName, $options);
        $action = $this->getRelationshipAction('readRelated', $fieldName, $options);

        $route = $this->router->get($uri, $action);
        $route->defaults(Route::RESOURCE_TYPE, $this->resourceType);
        $route->defaults(Route::RESOURCE_ID_NAME, $this->parameter);
        $route->defaults(Route::RESOURCE_RELATIONSHIP, $fieldName);

        return $route;
    }

    /**
     * Add the read relationship action.
     *
     * @param string $fieldName
     * @param array $options
     * @return IlluminateRoute
     */
    protected function addReadRelationship(string $fieldName, array $options): IlluminateRoute
    {
        $uri = $this->getRelationshipUri($fieldName, $options);
        $action = $this->getRelationshipAction('readRelationship', "{$fieldName}.read", $options);

        $route = $this->router->get("relationships/{$uri}", $action);
        $route->defaults(Route::RESOURCE_TYPE, $this->resourceType);
        $route->defaults(Route::RESOURCE_ID_NAME, $this->parameter);
        $route->defaults(Route::RESOURCE_RELATIONSHIP, $fieldName);

        return $route;
    }

    /**
     * Add the update relationship action.
     *
     * @param string $fieldName
     * @param array $options
     * @return IlluminateRoute
     */
    protected function addUpdateRelationship(string $fieldName, array $options): IlluminateRoute
    {
        $uri = $this->getRelationshipUri($fieldName, $options);
        $action = $this->getRelationshipAction('updateRelationship', "{$fieldName}.update", $options);

        $route = $this->router->patch("relationships/{$uri}", $action);
        $route->defaults(Route::RESOURCE_TYPE, $this->resourceType);
        $route->defaults(Route::RESOURCE_ID_NAME, $this->parameter);
        $route->defaults(Route::RESOURCE_RELATIONSHIP, $fieldName);

        return $route;
    }

    /**
     * Add the attach relationship action.
     *
     * @param string $fieldName
     * @param array $options
     * @return IlluminateRoute
     */
    protected function addAttachRelationship(string $fieldName, array $options): IlluminateRoute
    {
        $uri = $this->getRelationshipUri($fieldName, $options);
        $action = $this->getRelationshipAction('attachRelationship', "{$fieldName}.attach", $options);

        $route = $this->router->post("relationships/{$uri}", $action);
        $route->defaults(Route::RESOURCE_TYPE, $this->resourceType);
        $route->defaults(Route::RESOURCE_ID_NAME, $this->parameter);
        $route->defaults(Route::RESOURCE_RELATIONSHIP, $fieldName);

        return $route;
    }

    /**
     * Add the detach relationship action.
     *
     * @param string $fieldName
     * @param array $options
     * @return IlluminateRoute
     */
    protected function addDetachRelationship(string $fieldName, array $options): IlluminateRoute
    {
        $uri = $this->getRelationshipUri($fieldName, $options);
        $action = $this->getRelationshipAction('detachRelationship', "{$fieldName}.detach", $options);

        $route = $this->router->delete("relationships/{$uri}", $action);
        $route->defaults(Route::RESOURCE_TYPE, $this->resourceType);
        $route->defaults(Route::RESOURCE_ID_NAME, $this->parameter);
        $route->defaults(Route::RESOURCE_RELATIONSHIP, $fieldName);

        return $route;
    }

    /**
     * @param bool $hasMany
     * @param array $options
     * @return string[]
     */
    private function getRelationMethods(bool $hasMany, array $options): array
    {
        $methods = [
            'readRelated',
            'readRelationship',
            'updateRelationship',
        ];

        if ($hasMany) {
            $methods = array_merge($methods, [
                'attachRelationship',
                'detachRelationship',
            ]);
        }

        if (isset($options['only'])) {
            $methods = array_intersect($methods, (array) $options['only']);
        }

        if (isset($options['except'])) {
            $methods = array_diff($methods, (array) $options['except']);
        }

        return $methods;
    }

    /**
     * @param string $method
     * @param string $defaultName
     * @param array $options
     * @return array
     */
    private function getRelationshipAction(string $method, string $defaultName, array $options): array
    {
        $name = $this->getRelationRouteName($method, $defaultName, $options);

        $action = ['as' => $name, 'uses' => $this->controller.'@'.$method];

        if (isset($options['middleware'])) {
            $action['middleware'] = $options['middleware'];
        }

        if (isset($options['excluded_middleware'])) {
            $action['excluded_middleware'] = $options['excluded_middleware'];
        }

        return $action;
    }

    /**
     * @param string $fieldName
     * @param array $options
     * @return string
     */
    private function getRelationshipUri(string $fieldName, array $options): string
    {
        if (isset($options['relationship_uri'])) {
            return $options['relationship_uri'];
        }

        return $fieldName;
    }

    /**
     * Get the route name.
     *
     * @param string $method
     * @param string $default
     * @param array $options
     * @return string
     */
    protected function getRelationRouteName(string $method, string $default, array $options): string
    {
        $custom = $options['names'] ?? [];

        return $custom[$method] ?? $default;
    }

}