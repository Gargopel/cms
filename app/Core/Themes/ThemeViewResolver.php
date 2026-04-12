<?php

namespace App\Core\Themes;

use Illuminate\View\Factory as ViewFactory;

class ThemeViewResolver
{
    protected bool $namespaceRegistered = false;

    public function __construct(
        protected ThemeManager $themes,
        protected ViewFactory $views,
    ) {
    }

    public function registerActiveThemeNamespace(): void
    {
        $activeTheme = $this->themes->activeTheme();

        $path = $activeTheme instanceof \App\Core\Extensions\Models\ExtensionRecord
            ? $this->themes->viewsPathFor($activeTheme)
            : base_path('themes/__inactive__/views');

        $this->views->getFinder()->replaceNamespace('theme', [$path]);
        $this->namespaceRegistered = true;
    }

    public function resolve(string $themeView, string $fallbackView): string
    {
        $this->registerActiveThemeNamespace();

        $activeTheme = $this->themes->activeTheme();

        if (
            $activeTheme !== null
            && is_dir($this->themes->viewsPathFor($activeTheme))
            && view()->exists('theme::'.$themeView)
        ) {
            return 'theme::'.$themeView;
        }

        return $fallbackView;
    }
}
