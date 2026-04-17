<?php

namespace App\Core\Themes\Http\Controllers;

use App\Core\Settings\CoreSettingsManager;
use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeSlotRenderer;
use App\Core\Themes\ThemeViewResolver;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ThemeHomeController extends Controller
{
    public function __invoke(
        ThemeViewResolver $views,
        ThemeManager $themes,
        ThemeSlotRenderer $slots,
        CoreSettingsManager $settings,
    ): View {
        return view($views->resolve('home', 'front.home'), [
            'siteName' => $settings->get('site_name', config('app.name')),
            'siteTagline' => $settings->get('site_tagline', ''),
            'footerText' => $settings->get('footer_text', ''),
            'heroSlot' => $slots->render('hero', ['surface' => 'home']),
            'sidebarSlot' => $slots->render('sidebar', ['surface' => 'home']),
            'footerCtaSlot' => $slots->render('footer_cta', ['surface' => 'home']),
            'activeTheme' => $themes->activeTheme()?->toRegistryArray(),
        ]);
    }
}
