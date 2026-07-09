<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionFormRequest extends FormRequest
{
    /**
     * Authorization
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Gabungkan module + action menjadi name sebelum divalidasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'module' => strtolower(trim((string) $this->module)),
            'action' => strtolower(trim((string) $this->action)),
        ]);

        if ($this->filled('module') && $this->filled('action')) {
            $this->merge([
                'name' => $this->input('module') . '.' . $this->input('action'),
            ]);
        }
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [
            'module' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
            ],
            'action' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
            ],
            'name' => [
                'required',
                'string',
                'max:101',
                Rule::unique('permissions', 'name')->where('guard_name', config('faos.guard')),
            ],
        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [
            'module.required' => 'Module wajib diisi.',
            'module.regex' => 'Module hanya boleh huruf kecil, angka, dan underscore.',

            'action.required' => 'Action wajib diisi.',
            'action.regex' => 'Action hanya boleh huruf kecil, angka, dan underscore.',

            'name.required' => 'Module dan Action wajib diisi.',
            'name.unique' => 'Permission dengan module dan action tersebut sudah ada.',
        ];
    }
}
