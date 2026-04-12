<?php

namespace App\Core\Themes;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Registry\ExtensionLifecycleStateManager;
use App\Core\Settings\CoreSettingsManager;
use App\Core\Settings\Enums\CoreSettingType;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ThemeManager
{
    public function __construct(
        protected CoreSettingsManager $settings,
        protected ExtensionLifecycleStateManager $lifecycle,
    ) {
    }

    public function activeThemeSlug(): ?string
    {
        $slug = $this->settings->get('active_theme_slug', null, 'themes');

        return is_string($slug) && trim($slug) !== '' ? trim($slug) : null;
    }

    public function activeTheme(): ?ExtensionRecord
    {
        if (! $this->registryIsAvailable()) {
            return null;
        }

        $slug = $this->activeThemeSlug();

        if ($slug === null) {
            return null;
        }

        $theme = ExtensionRecord::query()
            ->where('type', ExtensionType::Theme->value)
            ->where('slug', $slug)
            ->first();

        if (! $theme instanceof ExtensionRecord) {
            return null;
        }

        if (! $this->meetsFrontendRequirements($theme)) {
            return null;
        }

        return $theme;
    }

    public function isActive(ExtensionRecord $theme): bool
    {
        return $theme->type === ExtensionType::Theme
            && $theme->slug !== null
            && $theme->slug === $this->activeThemeSlug()
            && $this->activeTheme()?->is($theme);
    }

    public function eligibilityFor(ExtensionRecord $theme): ThemeActivationEligibility
    {
        $blocks = [];
        $warnings = [];

        if ($theme->type !== ExtensionType::Theme) {
            $blocks[] = $this->issue('not_a_theme', 'A extensao selecionada nao e um tema.');
        }

        if (blank($theme->slug)) {
            $blocks[] = $this->issue('missing_slug', 'O tema nao possui slug persistido e nao pode ser ativado com seguranca.');
        }

        if (blank($theme->path) || blank($theme->manifest_path)) {
            $blocks[] = $this->issue('registry_incomplete', 'O registro do tema esta incompleto para ativacao segura.');
        }

        if ($theme->discovery_status !== ExtensionDiscoveryStatus::Valid) {
            $blocks[] = $this->issue(
                'invalid_discovery_status',
                sprintf(
                    'O tema nao pode ser ativado porque seu discovery status e [%s].',
                    $theme->discovery_status?->value ?? 'unknown',
                ),
            );
        }

        if ($this->activeThemeSlug() !== null && $this->activeThemeSlug() === $theme->slug && $this->activeTheme()?->is($theme)) {
            $blocks[] = $this->issue('already_active', 'Este tema ja esta selecionado como tema ativo.');
        }

        if (! $theme->isAdministrativelyInstalled()) {
            $warnings[] = $this->issue(
                'theme_will_be_installed',
                'O tema ainda nao esta instalado no lifecycle administrativo e sera preparado automaticamente antes da ativacao.',
            );
        }

        if (! is_dir($this->viewsPathFor($theme))) {
            $warnings[] = $this->issue(
                'theme_without_views_directory',
                'O tema ainda nao possui a pasta views preparada. O frontend continuara usando fallback do core quando necessario.',
            );
        }

        if (! is_dir($this->assetsPathFor($theme))) {
            $warnings[] = $this->issue(
                'theme_without_assets_directory',
                'O tema ainda nao possui a pasta assets preparada para evolucao futura.',
            );
        }

        return new ThemeActivationEligibility(
            allowed: $blocks === [],
            message: $blocks === []
                ? 'O tema pode ser selecionado como ativo.'
                : 'A troca de tema foi bloqueada por restricao operacional.',
            blocks: $blocks,
            warnings: $warnings,
        );
    }

    public function activateRecord(ExtensionRecord $theme): ThemeActivationResult
    {
        $evaluation = $this->eligibilityFor($theme);

        if (! $evaluation->allowed()) {
            return new ThemeActivationResult(
                success: false,
                changed: false,
                message: $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                theme: $theme,
            );
        }

        if (! $theme->isAdministrativelyInstalled()) {
            $install = $this->lifecycle->installRecord($theme);

            if (! $install->success()) {
                return new ThemeActivationResult(
                    success: false,
                    changed: false,
                    message: $install->message(),
                    theme: $install->record(),
                );
            }

            $theme = $install->record() ?? $theme->fresh();
        }

        $this->settings->put('active_theme_slug', $theme->slug, CoreSettingType::String, 'themes');

        return new ThemeActivationResult(
            success: true,
            changed: true,
            message: 'Active theme updated successfully.',
            theme: $theme->fresh(),
        );
    }

    public function viewsPathFor(ExtensionRecord $theme): string
    {
        return rtrim($theme->path ?? '', DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'views';
    }

    public function assetsPathFor(ExtensionRecord $theme): string
    {
        return rtrim($theme->path ?? '', DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'assets';
    }

    protected function meetsFrontendRequirements(ExtensionRecord $theme): bool
    {
        return $theme->type === ExtensionType::Theme
            && $theme->discovery_status === ExtensionDiscoveryStatus::Valid
            && $theme->isAdministrativelyInstalled();
    }

    protected function registryIsAvailable(): bool
    {
        try {
            return Schema::hasTable('extension_records');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array{code: string, message: string}
     */
    protected function issue(string $code, string $message): array
    {
        return [
            'code' => $code,
            'message' => $message,
        ];
    }
}
