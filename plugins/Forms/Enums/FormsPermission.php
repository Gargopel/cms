<?php

namespace Plugins\Forms\Enums;

enum FormsPermission: string
{
    case ViewForms = 'forms.view_forms';
    case CreateForms = 'forms.create_forms';
    case EditForms = 'forms.edit_forms';
    case PublishForms = 'forms.publish_forms';
    case DeleteForms = 'forms.delete_forms';
    case ViewFormSubmissions = 'forms.view_form_submissions';
    case ManageSettings = 'forms.manage_settings';
}
