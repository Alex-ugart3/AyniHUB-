<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validación del inicio de sesión. */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact'  => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:255'],
            'role'     => ['required', 'in:comprador,productor,gestor'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact.required'  => __('Escribe tu correo o celular.'),
            'password.required' => __('Escribe tu contraseña.'),
            'role.required'     => __('Elige un rol.'),
            'role.in'           => __('El rol elegido no es válido.'),
        ];
    }
}
