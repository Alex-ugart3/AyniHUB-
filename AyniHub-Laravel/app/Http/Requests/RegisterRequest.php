<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del registro. El rol "gestor" NO se puede autoasignar:
 * lo crea un administrador (ver DatabaseSeeder).
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normaliza antes de validar la unicidad (correos en minúscula).
        if (is_string($this->contact)) {
            $this->merge(['contact' => \App\Models\User::normalizeContact($this->contact)]);
        }
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:3', 'max:120'],
            'contact'  => ['required', 'string', 'min:5', 'max:120', 'unique:users,contact'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
            'role'     => ['required', 'in:comprador,productor'],
            'location' => ['nullable', 'string', 'max:180', 'required_if:role,productor'],
        ];
    }

    public function messages(): array
    {
        return [
            'location.required_if' => __('La ubicación es obligatoria para productores.'),
            'contact.unique'       => __('Ese correo o celular ya está registrado.'),
            'password.regex'       => __('La contraseña debe contener letras y números.'),
            'password.min'         => __('La contraseña debe tener al menos 8 caracteres.'),
            'password.max'         => __('La contraseña no puede superar 72 caracteres.'),
            'name.required'        => __('Escribe tu nombre completo.'),
            'name.min'             => __('El nombre debe tener al menos 3 caracteres.'),
            'contact.required'     => __('Escribe tu correo o celular.'),
            'contact.min'          => __('El correo o celular es demasiado corto.'),
            'role.in'              => __('Solo puedes registrarte como comprador o productor.'),
        ];
    }
}
