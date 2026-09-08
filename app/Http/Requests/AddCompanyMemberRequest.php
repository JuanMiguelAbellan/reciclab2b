<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AddCompanyMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('company'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $member = User::where('email', $this->input('email'))->first();

            if ($member === null) {
                $validator->errors()->add('email', 'No existe ninguna cuenta con ese email. Si todavía no tiene cuenta, invítala por email más abajo.');

                return;
            }

            if ($member->status !== UserStatus::Approved) {
                $validator->errors()->add('email', 'Esa cuenta todavía no está aprobada.');

                return;
            }

            if ($member->companies()->exists()) {
                $validator->errors()->add('email', 'Esa persona ya pertenece a una empresa.');
            }
        });
    }
}
