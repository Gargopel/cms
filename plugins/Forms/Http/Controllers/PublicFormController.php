<?php

namespace Plugins\Forms\Http\Controllers;

use App\Core\Themes\ThemeViewResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plugins\Forms\Models\Form;
use Plugins\Forms\Support\FormSubmissionService;

class PublicFormController extends Controller
{
    public function show(string $slug, ThemeViewResolver $themes): View
    {
        $form = Form::query()
            ->with('fields')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view($themes->resolve('plugins.forms.show', 'forms::front.show'), [
            'formRecord' => $form,
            'seo' => $this->resolveSeo([
                'title' => $form->title,
                'description' => $form->description ?: 'Formulario publico simples do plugin oficial Forms.',
                'canonical' => url('/forms/'.$form->slug),
                'og_type' => 'website',
            ]),
        ]);
    }

    public function submit(string $slug, Request $request, FormSubmissionService $submissions): RedirectResponse
    {
        $form = Form::query()
            ->with('fields')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $result = $submissions->submit($form, $request);

        return redirect()
            ->to($result->redirectUrl ?? '/forms/'.$form->slug)
            ->with('status', $result->successMessage);
    }

    protected function resolveSeo(array $context): mixed
    {
        $resolver = \Plugins\Seo\Contracts\SeoMetadataResolver::class;

        if (! interface_exists($resolver) || ! app()->bound($resolver)) {
            return null;
        }

        return app($resolver)->resolve($context);
    }
}
