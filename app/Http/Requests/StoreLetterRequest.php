<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLetterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'letter_format_id' => ['required', 'exists:letter_formats,id'],
            'user_id' => ['required', 'exists:users,id'],
        ];

        switch ((int) $this->input('letter_format_id')) {
            case 1: // Resign
                $rules['resignation_date'] = ['required', 'date'];
                $rules['reason_resign'] = ['required', 'string'];
                break;

            case 2: // Transfer
                $rules['current_division'] = ['required', 'string'];
                $rules['requested_division'] = ['required', 'string'];
                $rules['reason_transfer'] = ['required', 'string'];
                break;

            case 3: // Kenaikan Gaji
                $rules['current_salary'] = ['required', 'integer'];
                $rules['Requested_salary'] = ['required', 'integer'];
                $rules['reason_salary'] = ['required', 'string'];
                break;

            case 4: // Cuti
                $rules['leave_start'] = ['required', 'date'];
                $rules['return_to_work'] = ['required', 'date', 'after_or_equal:leave_start'];
                $rules['reason_for_leave'] = ['required', 'string'];
                break;
        }

        return $rules;
    }
}
