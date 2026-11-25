<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de producto (crear y editar). Solo se aceptan los campos
 * declarados aquí, lo que evita asignación masiva de campos no permitidos.
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la autorización por rol/propiedad la hace ProductPolicy
    }

    public function rules(): array
    {
        return [
            'category'      => ['required', 'in:agricola,artesanal'],
            'name'          => ['required', 'string', 'min:3', 'max:120'],
            'producer_name' => ['required', 'string', 'min:3', 'max:120'],
            'price'         => ['required', 'numeric', 'min:0.1', 'max:999999'],
            'unit'          => ['required', 'in:kilo,unidad,arroba'],
            'location'      => ['required', 'string', 'min:3', 'max:180'],
            'description'   => ['nullable', 'string', 'max:1000'],
        ];
    }
}
