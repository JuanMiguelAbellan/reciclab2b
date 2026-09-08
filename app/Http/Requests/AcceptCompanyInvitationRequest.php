<?php

namespace App\Http\Requests;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AcceptCompanyInvitationRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * The invitation's validity (pending, signature not expired) is
     * enforced by the route's "signed" middleware and the controller;
     * anyone holding a valid link may fill in the registration form.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'password' => $this->passwordRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $invitation = $this->route('invitation');

            if ($invitation !== null && User::where('email', $invitation->email)->exists()) {
                $validator->errors()->add('email', 'Ya existe una cuenta con ese email. Inicia sesión en su lugar.');
            }
        });
    }
}
