<?php

namespace App\Core\Install\Environment;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class EnvironmentFileManager
{
    public function __construct(
        protected Filesystem $files,
    ) {
    }

    public function ensureEnvironmentFile(): void
    {
        if ($this->files->exists($this->envPath())) {
            return;
        }

        if ($this->files->exists($this->envExamplePath())) {
            $this->files->copy($this->envExamplePath(), $this->envPath());

            return;
        }

        $this->files->put($this->envPath(), '');
    }

    /**
     * @param  array<string, scalar|null>  $values
     */
    public function write(array $values): void
    {
        $this->ensureEnvironmentFile();

        $contents = (string) $this->files->get($this->envPath());

        foreach ($values as $key => $value) {
            $serialized = $this->serializeValue($value);
            $pattern = "/^{$key}=.*$/m";

            if (preg_match($pattern, $contents) === 1) {
                $contents = (string) preg_replace($pattern, "{$key}={$serialized}", $contents);

                continue;
            }

            $contents = rtrim($contents).PHP_EOL."{$key}={$serialized}".PHP_EOL;
        }

        $this->files->put($this->envPath(), $contents);
    }

    public function envPath(): string
    {
        return (string) config('platform.install.env_path', base_path('.env'));
    }

    public function envExamplePath(): string
    {
        return (string) config('platform.install.env_example_path', base_path('.env.example'));
    }

    protected function serializeValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $string = (string) $value;

        if ($string === '') {
            return '""';
        }

        if (Str::contains($string, [' ', '#', '"'])) {
            return '"'.str_replace('"', '\"', $string).'"';
        }

        return $string;
    }
}
