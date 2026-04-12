<?php

namespace App\Support;

class PlatformPaths
{
    public function __construct(
        protected string $basePath
    ) {
    }

    public function base(string $path = ''): string
    {
        return $this->join($this->basePath, $path);
    }

    public function appCore(string $path = ''): string
    {
        return $this->base($this->normalize('app/Core', $path));
    }

    public function plugins(string $path = ''): string
    {
        return $this->base($this->normalize('plugins', $path));
    }

    public function themes(string $path = ''): string
    {
        return $this->base($this->normalize('themes', $path));
    }

    public function docs(string $path = ''): string
    {
        return $this->base($this->normalize('docs', $path));
    }

    protected function normalize(string $prefix, string $path): string
    {
        return trim($prefix.'/'.$path, '/');
    }

    protected function join(string $prefix, string $path): string
    {
        $path = trim($path, '/');

        return $path === '' ? $prefix : $prefix.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
