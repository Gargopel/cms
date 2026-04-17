<?php

namespace Plugins\Forms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Plugins\Forms\Enums\FormStatus;
use Plugins\Forms\Http\Requests\StoreFormRequest;
use Plugins\Forms\Http\Requests\UpdateFormRequest;
use Plugins\Forms\Models\Form;
use Plugins\Forms\Models\FormSubmission;

class FormController extends Controller
{
    public function index(): View
    {
        return view('forms::admin.index', [
            'pageTitle' => 'Forms',
            'pageSubtitle' => 'Plugin oficial para formularios simples com campos estruturados e submissões persistidas.',
            'forms' => Form::query()
                ->withCount(['fields', 'submissions'])
                ->latest('updated_at')
                ->paginate(20),
            'summary' => [
                'total' => Form::query()->count(),
                'draft' => Form::query()->where('status', FormStatus::Draft->value)->count(),
                'published' => Form::query()->where('status', FormStatus::Published->value)->count(),
                'submissions' => FormSubmission::query()->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('forms::admin.form', [
            'pageTitle' => 'Create Form',
            'pageSubtitle' => 'Novo formulario publico simples do plugin oficial Forms.',
            'formRecord' => new Form([
                'status' => FormStatus::Draft,
                'success_message' => 'Your submission has been received successfully.',
            ]),
            'submitRoute' => $this->adminFormsPath(),
            'submitMethod' => 'POST',
        ]);
    }

    public function store(StoreFormRequest $request): RedirectResponse
    {
        $form = Form::query()->create($request->validated());

        return redirect()
            ->to($this->adminFormFieldsPath($form))
            ->with('status', 'Form created successfully. You can now add fields.');
    }

    public function edit(Form $form): View
    {
        return view('forms::admin.form', [
            'pageTitle' => 'Edit Form',
            'pageSubtitle' => 'Atualize o formulario, o slug publico e o status de publicacao.',
            'formRecord' => $form,
            'submitRoute' => $this->adminFormsPath().'/'.$form->getKey(),
            'submitMethod' => 'PUT',
        ]);
    }

    public function update(UpdateFormRequest $request, Form $form): RedirectResponse
    {
        $form->update($request->validated());

        return redirect()
            ->to($this->adminFormsPath())
            ->with('status', 'Form updated successfully.');
    }

    public function destroy(Form $form): RedirectResponse
    {
        $form->delete();

        return redirect()
            ->to($this->adminFormsPath())
            ->with('status', 'Form deleted successfully.');
    }

    protected function adminFormsPath(): string
    {
        return '/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms';
    }

    protected function adminFormFieldsPath(Form $form): string
    {
        return $this->adminFormsPath().'/'.$form->getKey().'/fields';
    }
}
