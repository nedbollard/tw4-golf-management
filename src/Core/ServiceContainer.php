<?php

namespace App\Core;

class ServiceContainer
{
    private array $instances = [];

    public function __construct(Database $database)
    {
        $this->instances[Database::class] = $database;
    }

    public function set(string $className, object $service): void
    {
        if (!$service instanceof $className) {
            throw new \InvalidArgumentException("Service must be an instance of {$className}");
        }

        $this->instances[$className] = $service;
    }

    public function get(string $className): object
    {
        if (isset($this->instances[$className])) {
            return $this->instances[$className];
        }

        if (!class_exists($className)) {
            throw new \RuntimeException("Service class not found: {$className}");
        }

        if (!str_starts_with($className, 'App\\Services\\')
            && !str_starts_with($className, 'App\\Repositories\\')) {
            throw new \RuntimeException("Service is outside the allowed namespace: {$className}");
        }

        $reflection = new \ReflectionClass($className);
        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("Service is not instantiable: {$className}");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $this->instances[$className] = $reflection->newInstance();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new \RuntimeException(
                "Cannot resolve {$className} dependency: {$parameter->getName()}"
            );
        }

        return $this->instances[$className] = $reflection->newInstanceArgs($dependencies);
    }
}