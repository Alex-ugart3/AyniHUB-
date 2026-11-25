<?php
return [
    'required' => 'El campo :attribute es obligatorio.',
    'required_if' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'in' => 'El valor de :attribute no es válido.',
    'regex' => 'El formato de :attribute no es válido.',
    'unique' => 'Ese valor de :attribute ya está registrado.',
    'exists' => 'El valor elegido en :attribute no existe.',
    'min' => ['numeric' => 'El campo :attribute debe ser al menos :min.', 'string' => 'El campo :attribute debe tener al menos :min caracteres.'],
    'max' => ['numeric' => 'El campo :attribute no puede ser mayor que :max.', 'string' => 'El campo :attribute no puede superar los :max caracteres.'],
    'attributes' => ['name' => 'nombre', 'contact' => 'correo o celular', 'password' => 'contraseña', 'role' => 'rol', 'price' => 'precio', 'quantity' => 'cantidad', 'location' => 'ubicación', 'category' => 'categoría', 'unit' => 'unidad', 'description' => 'descripción', 'producer_name' => 'productor', 'product_id' => 'producto', 'status' => 'estado', 'note' => 'nota'],
];
