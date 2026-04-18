<h1>New form submission</h1>

<p><strong>Form:</strong> {{ $form->title }}</p>
<p><strong>Submitted at:</strong> {{ $submission->submitted_at?->toDateTimeString() }}</p>

<ul>
@foreach ($submission->values as $value)
    <li><strong>{{ $value->field_label }}:</strong> {{ $value->value ?? '--' }}</li>
@endforeach
</ul>
