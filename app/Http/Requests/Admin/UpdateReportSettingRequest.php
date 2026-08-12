<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gm_email' => ['required', 'email', 'max:255'],
            'gm_name' => ['required', 'string', 'max:255'],
            'spv_email' => ['nullable', 'email', 'max:255'],
            'spv_name' => ['nullable', 'required_with:spv_email', 'string', 'max:255'],
            'send_day' => ['required', 'integer', 'between:0,6'],
            'send_time' => ['required', 'date_format:H:i'],
            'office_mail_host' => ['nullable', 'string', 'max:255'],
            'office_mail_port' => ['nullable', 'integer', 'between:1,65535'],
            'office_mail_encryption' => ['nullable', Rule::in(['ssl', 'tls'])],
        ];
    }
}
