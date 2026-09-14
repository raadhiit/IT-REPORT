<?php

namespace App\Http\Requests;

use App\Enums\ActivityCategory;
use App\Enums\ActivityStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('activity'));
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
            'deskripsi' => ['required', 'string'],
            'status' => ['required', new Enum(ActivityStatus::class)],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'target_selesai' => ['nullable', 'date'],
        ];
    }
}
