<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRefillSpotRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only staff reach these routes; the staff middleware checks that.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            // Kana only (and spaces between words), so it can be turned into Latin letters.
            'name_kana' => ['nullable', 'string', 'max:100', 'regex:/^[\p{Hiragana}\p{Katakana}ー・\s　]+$/u'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description_ja' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name_kana.regex' => __('spots.form.kana_invalid'),
        ];
    }
}
