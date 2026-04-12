<?php

namespace App\Core\Extensions\Boot;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Throwable;

class PluginProviderBootstrapper
{
    protected PluginBootReport $lastReport;

    public function __construct(
        protected Application $app,
        protected BootablePluginResolver $resolver,
        protected PluginBootstrapReportStore $reportStore,
    ) {
        $this->lastReport = new PluginBootReport();
    }

    public function bootstrap(): PluginBootReport
    {
        $resolution = $this->resolver->resolve();
        $considered = array_map(
            static fn (BootablePluginCandidate $candidate): array => $candidate->toArray(),
            $resolution->candidates()
        );
        $registered = [];
        $failed = [];

        foreach ($resolution->systemErrors() as $error) {
            Log::warning('Plugin bootstrap skipped because the registry is unavailable.', $error);
        }

        foreach ($resolution->candidates() as $candidate) {
            $provider = $candidate->provider();
            $record = $candidate->record();

            if (! class_exists($provider)) {
                $failed[] = $this->failedEntry($candidate, 'provider_class_not_found', 'Provider class does not exist.');
                Log::warning('Plugin provider class was not found.', [
                    'slug' => $record->slug,
                    'provider' => $provider,
                    'path' => $record->path,
                ]);

                continue;
            }

            if (! is_subclass_of($provider, ServiceProvider::class)) {
                $failed[] = $this->failedEntry($candidate, 'provider_not_a_service_provider', 'Provider class must extend Illuminate\Support\ServiceProvider.');
                Log::warning('Plugin provider class is not a valid Laravel service provider.', [
                    'slug' => $record->slug,
                    'provider' => $provider,
                    'path' => $record->path,
                ]);

                continue;
            }

            try {
                $alreadyLoaded = $this->app->providerIsLoaded($provider);

                $providerInstance = $this->app->register($provider);

                if ($providerInstance instanceof ServiceProvider) {
                    $this->bootProviderWhenNeeded($providerInstance, $alreadyLoaded);
                }

                $this->syncRouterAndUrlGenerator();

                $registered[] = array_merge($candidate->toArray(), [
                    'already_loaded' => $alreadyLoaded,
                ]);
            } catch (Throwable $exception) {
                $failed[] = $this->failedEntry(
                    $candidate,
                    'provider_registration_failed',
                    $exception->getMessage(),
                );

                Log::warning('Plugin provider registration failed safely.', [
                    'slug' => $record->slug,
                    'provider' => $provider,
                    'path' => $record->path,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $report = new PluginBootReport(
            considered: $considered,
            registered: $registered,
            ignored: $resolution->ignored(),
            failed: $failed,
            systemErrors: $resolution->systemErrors(),
        );

        $this->reportStore->remember($report);

        return $this->lastReport = $report;
    }

    public function lastReport(): PluginBootReport
    {
        return $this->lastReport;
    }

    protected function failedEntry(BootablePluginCandidate $candidate, string $reason, string $message): array
    {
        return array_merge($candidate->toArray(), [
            'reason' => $reason,
            'message' => $message,
        ]);
    }

    protected function bootProviderWhenNeeded(ServiceProvider $provider, bool $alreadyLoaded): void
    {
        if ($alreadyLoaded || $this->app->isBooted()) {
            return;
        }

        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            $this->app->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    protected function syncRouterAndUrlGenerator(): void
    {
        if (! $this->app->bound('router') || ! $this->app->bound('url')) {
            return;
        }

        $this->app['url']->setRoutes($this->app['router']->getRoutes());
    }
}
