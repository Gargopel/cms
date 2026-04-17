<?php

namespace Plugins\Forms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Plugins\Forms\Models\Form;

class FormSubmissionController extends Controller
{
    public function index(Form $form): View
    {
        return view('forms::admin.submissions.index', [
            'pageTitle' => 'Form Submissions',
            'pageSubtitle' => 'Consulta administrativa minima das respostas persistidas para o formulario selecionado.',
            'formRecord' => $form,
            'submissions' => $form->submissions()
                ->with(['values.field'])
                ->paginate(20),
        ]);
    }
}
