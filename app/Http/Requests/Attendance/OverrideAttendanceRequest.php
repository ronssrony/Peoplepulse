<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class OverrideAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clock_in' => ['sometimes', 'nullable', 'date'],
            'clock_out' => ['sometimes', 'nullable', 'date', 'after:clock_in'],
            'break_minutes' => ['sometimes', 'integer', 'min:0', 'max:480'],
            'is_late' => ['sometimes', 'boolean'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'clock_out.after' => 'Clock out time must be after clock in time.',
            'reason.required' => 'A reason is required for any attendance override.',
            'reason.min' => 'Please provide a detailed reason (at least 10 characters).',
        ];
    }
}
