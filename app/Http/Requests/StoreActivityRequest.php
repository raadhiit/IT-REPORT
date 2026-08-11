<?php

namespace App\Http\Requests;

use App\Enums\ActivityCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'kategori' => ['required', new Enum(ActivityCategory::class)],
            'deskripsi' => ['required', 'string', 'max:500'],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => [Rule::file()->max(2048)->extensions(['pdf', 'png', 'jpg', 'jpeg', 'docx'])],
        ];
    }
}
