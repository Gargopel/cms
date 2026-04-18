<?php

namespace Plugins\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Plugins\Seo\Support\SeoSitemapGenerator;

class PublicSitemapController extends Controller
{
    public function __invoke(SeoSitemapGenerator $generator): Response
    {
        return response()
            ->view('seo::sitemap.index', [
                'urls' => $generator->generate(),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
