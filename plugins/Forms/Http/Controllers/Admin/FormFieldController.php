<?php

namespace Plugins\Forms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Plugins\Forms\Enums\FormFieldType;
use Plugins\Forms\Http\Requests\StoreFormFieldRequest;
use Plugins\Forms\Http\Requests\UpdateFormFieldRequest;
use Plugins\Forms\Models\Form;
use Plugins\Forms\Models\FormField;

class FormFieldController extends Controller
{
    public function index(Form $form): View
    {
        return view('forms::admin.fields.index', [
            'pageTitle' => 'Manage Fields',
            'pageSubtitle' => 'Campos estruturados do formulario selecionado, sem builder visual nem logica condicional.',
            'formRecord' => $form,
            'fields' => $form->fields()->ordered()->get(),
        ]);
    }

    public function create(Form $form): View
    {
        return view('forms::admin.fields.form', [
            'pageTitle' => 'Create Field',
            'pageSubtitle' => 'Adicione um campo simples e validado ao formulario selecionado.',
            'formRecord' => $form,
            'fieldRecord' => new FormField([
                'type' => FormFieldType::Text,
                'sort_order' => (($form->fields()->max('sort_order') ?? 0) + 10),
            ]),
            'submitRoute' => $this->adminFieldsPath($form),
            'submitMethod' => 'POST',
            'fieldTypes' => FormFieldType::cases(),
        ]);
    }

    public function store(StoreFormFieldRequest $request, Form $form): RedirectResponse
    {
        $form->fields()->create($request->validated());

        return redirect()
            ->to($this->adminFieldsPath($form))
            ->with('status', 'Field created successfully.');
    }

    public function edit(Form $form, FormField $field): View
    {
        abort_unless($field->form_id === $form->getKey(), 404);

        return view('forms::admin.fields.form', [
            'pageTitle' => 'Edit Field',
            'pageSubtitle' => 'Atualize label, nome tecnico, tipo e comportamento do campo.',
            'formRecord' => $form,
            'fieldRecord' => $field,
            'submitRoute' => $this->adminFieldsPath($form).'/'.$field->getKey(),
            'submitMethod' => 'PUT',
            'fieldTypes' => FormFieldType::cases(),
        ]);
    }

    public function update(UpdateFormFieldRequest $request, Form $form, FormField $field): RedirectResponse
    {
        abort_unless($field->form_id === $form->getKey(), 404);

        $field->update($request->validated());

        return redirect()
            ->to($this->adminFieldsPath($form))
            ->with('status', 'Field updated successfully.');
    }

    protected function adminFieldsPath(Form $form): string
    {
        return '/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms/'.$form->getKey().'/fields';
    }
}
