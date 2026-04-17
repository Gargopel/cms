<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $seoView = 'seo::partials.meta';
    @endphp
    @if (isset($seo) && $seo !== null && view()->exists($seoView))
        @include($seoView, ['seo' => $seo])
    @else
        <title>{{ $formRecord->title }}</title>
    @endif
    <style>
        :root {
            --bg: #050b16;
            --panel: rgba(15, 24, 43, 0.82);
            --border: rgba(125, 100, 255, 0.22);
            --text: #edf2ff;
            --muted: #a4b5d4;
            --primary: #8c63ff;
            --accent: #33d1ff;
            --success: #4ade80;
            --danger: #fb7185;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 32px;
            color: var(--text);
            font-family: "Segoe UI Variable Text", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(140, 99, 255, 0.24), transparent 30%),
                radial-gradient(circle at bottom right, rgba(51, 209, 255, 0.18), transparent 26%),
                linear-gradient(180deg, #08101f 0%, var(--bg) 100%);
        }

        .shell {
            width: min(820px, 100%);
            margin: 0 auto;
            padding: 36px;
            border-radius: 32px;
            background: var(--panel);
            border: 1px solid var(--border);
            box-shadow: 0 30px 100px rgba(0, 0, 0, 0.35);
        }

        .eyebrow {
            display: inline-flex;
            margin-bottom: 18px;
            padding: 8px 14px;
            border-radius: 999px;
            color: var(--accent);
            border: 1px solid rgba(51, 209, 255, 0.22);
            background: rgba(51, 209, 255, 0.08);
            font-size: 0.78rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(2.2rem, 5vw, 4.2rem);
            line-height: 0.98;
            letter-spacing: -0.05em;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }

        form {
            display: grid;
            gap: 18px;
            margin-top: 28px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        label {
            font-weight: 600;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(148, 170, 226, 0.14);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text);
        }

        textarea {
            min-height: 160px;
            resize: vertical;
        }

        .checkbox-row {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(148, 170, 226, 0.14);
            background: rgba(255, 255, 255, 0.03);
        }

        .checkbox-row input {
            width: auto;
            margin: 0;
        }

        .help {
            color: var(--muted);
            font-size: 0.92rem;
        }

        .error {
            color: var(--danger);
            font-size: 0.92rem;
        }

        .notice {
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(148, 170, 226, 0.14);
            background: rgba(255, 255, 255, 0.03);
        }

        .notice--success {
            color: var(--success);
            border-color: rgba(74, 222, 128, 0.24);
            background: rgba(74, 222, 128, 0.08);
        }

        .submit-button {
            display: inline-flex;
            justify-content: center;
            padding: 14px 20px;
            border: 0;
            border-radius: 999px;
            color: #03101f;
            font-weight: 700;
            cursor: pointer;
            background: linear-gradient(135deg, #33d1ff 0%, #8c63ff 100%);
        }
    </style>
</head>
<body>
    <main class="shell">
        <span class="eyebrow">Official Forms Plugin</span>
        <h1>{{ $formRecord->title }}</h1>
        <p>{{ $formRecord->description ?: 'Formulario publico simples do plugin oficial Forms.' }}</p>

        @if (session('status'))
            <div class="notice notice--success" style="margin-top: 24px;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ url('/forms/'.$formRecord->slug) }}">
            @csrf

            @foreach ($formRecord->fields as $field)
                <div class="field">
                    @if ($field->type === \Plugins\Forms\Enums\FormFieldType::Checkbox)
                        <label class="checkbox-row">
                            <input name="{{ $field->name }}" type="checkbox" value="1" @checked(old($field->name))>
                            <span>{{ $field->label }}</span>
                        </label>
                    @else
                        <label for="{{ $field->name }}">{{ $field->label }}</label>

                        @if ($field->type === \Plugins\Forms\Enums\FormFieldType::Textarea)
                            <textarea id="{{ $field->name }}" name="{{ $field->name }}" placeholder="{{ $field->placeholder }}">{{ old($field->name) }}</textarea>
                        @elseif ($field->type === \Plugins\Forms\Enums\FormFieldType::Select)
                            <select id="{{ $field->name }}" name="{{ $field->name }}">
                                <option value="">Select an option</option>
                                @foreach ($field->optionValues() as $option)
                                    <option value="{{ $option }}" @selected(old($field->name) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        @else
                            <input
                                id="{{ $field->name }}"
                                name="{{ $field->name }}"
                                type="{{ $field->type === \Plugins\Forms\Enums\FormFieldType::Email ? 'email' : 'text' }}"
                                value="{{ old($field->name) }}"
                                placeholder="{{ $field->placeholder }}"
                            >
                        @endif
                    @endif

                    @if ($field->help_text)
                        <span class="help">{{ $field->help_text }}</span>
                    @endif

                    @error($field->name)
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>
            @endforeach

            <button type="submit" class="submit-button">Send Form</button>
        </form>
    </main>
</body>
</html>
