<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Registra un usuario (solo comprador o productor) e inicia su sesión. */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $contact = User::normalizeContact($data['contact']);

        $user = User::create([
            'name'     => $data['name'],
            'contact'  => $contact,
            'email'    => filter_var($contact, FILTER_VALIDATE_EMAIL) ? $contact : null,
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
            'location' => $data['location'] ?? null,
        ]);

        Auth::login($user);
        $request->session()->regenerate(); // evita fijación de sesión

        return response()->json([
            'message'  => __('Cuenta creada correctamente.'),
            'user'     => $user->toPublicArray(),
            'redirect' => $user->homeHash(),
        ], 201);
    }

    /**
     * Valida contacto + contraseña y luego el rol elegido.
     * - Usuario inexistente o contraseña mala: mismo error genérico (no revela qué falló).
     * - Siempre se ejecuta Hash::check (aunque el usuario no exista) para que el tiempo
     *   de respuesta no permita adivinar qué correos están registrados.
     * - El rol solo se contrasta DESPUÉS de verificar la contraseña.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('contact', User::normalizeContact($data['contact']))->first();

        $hash = $user?->password ?? Hash::make(bin2hex(random_bytes(8))); // costo igual al de un usuario real
        $passwordOk = Hash::check($data['password'], $hash);

        if (! $user || ! $passwordOk) {
            throw ValidationException::withMessages(['password' => __('Las credenciales no son correctas.')]);
        }

        if ($user->role !== $data['role']) {
            throw ValidationException::withMessages([
                'role' => __('Esta cuenta no pertenece al rol «:rol». Elige el rol correcto.', ['rol' => ucfirst($data['role'])]),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message'  => __('Bienvenido a AyniHub.'),
            'user'     => $user->toPublicArray(),
            'redirect' => $user->homeHash(),
        ]);
    }

    /** Cierra la sesión e invalida el token CSRF. */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => __('Sesión cerrada.')]);
    }
}
