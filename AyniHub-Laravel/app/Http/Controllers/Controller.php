<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controlador base. Incluye AuthorizesRequests para usar las Policies
 * mediante $this->authorize() en los controladores hijos.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
