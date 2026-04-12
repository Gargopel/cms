<?php

namespace App\Core\Themes\Http\Controllers;

use App\Core\Settings\CoreSettingsManager;
use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeViewResolver;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ThemeHomeController extends Controller
{
    public function __invoke(
        ThemeViewResolver $views,
        ThemeManager $themes,
        CoreSettingsManager $settings,
    ): View {
        return view($views->resolve('home', 'front.home'), [
            'siteName' => $settings->get('site_name', config('app.name')),
            'siteTagline' => $settings->get('site_tagline', ''),
            'footerText' => $settings->get('footer_text', ''),
            'activeTheme' => $themes->activeTheme()?->toRegistryArray(),
        ]);
    }
}
