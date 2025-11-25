<?php

namespace App\Http\Controllers;

use App\Models\EstadoUsuario;
use App\QueryBuilder\UsuarioQueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Usuario;
use Exception;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        /*Verificamos credenciales y emitimos tokens...*/
        $validator = Validator::make($request->all(),
            [
                'username' => 'required',
                'password' => 'required',
            ],
            [
                'username.required' => 'El nombre de usuario es obligatorio.',
                'password.required' => 'La contraseña es obligatoria.',
            ]
        );

        if ($validator->fails()) {
            $errores = implode(' ', $validator->errors()->all());
            return response()->json(['error' => $errores], 400);
        }

        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $usuarioQuery = Usuario::query();
        UsuarioQueryBuilder::ConEstadoActivacion($usuarioQuery);
        UsuarioQueryBuilder::SoloNombreDeUsuario($usuarioQuery, $credentials['username']);
        $user =  $usuarioQuery->first();
        /*Verificamos si existe el usuario y está habilitado*/
        if($user && $user -> habilitado == false){
            return response()->json(['error'=> "La cuenta de encuentra bloqueada. ({$user->estadoUsuario->descripcion})"],400);
        }
        
        //Verificamos si existe el usuario y la contraseña
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'error' => 'Credenciales inválidas.'
            ], 400);
        }
        
        /*Pasado todo los controles, lo damos por bueno y generamos el token y lo devolvemos*/
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'mensaje' => 'Login correcto',
            'token' => $token,
        ])->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    public function logout(Request $request)
    {
        /*Como ya está con la sesión iniciada, tenemos acceso al user, simplemente le borramos el token...*/
        try{
            $request->user()->currentAccessToken()->delete();
        } catch (Exception $e) {
        }

        return response()->json(['mensaje' => 'Logout exitoso']);
    }
    public function enviarLinkReset(Request $request)
    {
        /*Nos mandan el correo y debemos mandarle por correo el link para reiniciar la contraseña*/
        $validator = Validator::make($request->all(),
    [
                'email' => 'required|email',
            ],
            [
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'El correo electrónico debe tener un formato válido.',
            ]
        );

        if ($validator->fails()) {
            $errores = implode(' ', $validator->errors()->all());
            return response()->json(['error' => $errores], 400);
        }


        $status = Password::sendResetLink(
            ['email' => $request->email]
        );

        switch ($status) {
            case Password::RESET_LINK_SENT:
                return response()->json(['mensaje' => 'Se ha enviado el enlace de restablecimiento de contraseña.']);
    
            case Password::INVALID_USER:
                return response()->json(['error' => 'No se encontró un usuario con ese correo electrónico.'], 400);
    
            case Password::RESET_THROTTLED:
                return response()->json(['error' => 'Demasiadas solicitudes. Intente nuevamente más tarde.'], 429);
    
            default:
                return response()->json(['error' => 'No se pudo enviar el enlace.'], 500);
        }
    }

    public function resetearPassword(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:6|confirmed',
            ],
            [
                'token.required' => 'El token es obligatorio.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'El correo electrónico debe tener un formato válido.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
                'password.confirmed' => 'La confirmación de la contraseña "password_confirmation" no coincide o no está presente.',
            ]
        );
    
        if ($validator->fails()) {
            $errores = implode(' ', $validator->errors()->all());
            return response()->json(['error' => $errores], 400);
        }
    
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->password = Hash::make($request->password);
                $user->save();
            }
        );
    
        switch ($status) {
            case Password::PASSWORD_RESET:
                return response()->json(['mensaje' => 'Contraseña actualizada correctamente.']);
    
            case Password::INVALID_TOKEN:
                return response()->json(['error' => 'El token es inválido o ha expirado.'], 400);
    
            case Password::INVALID_USER:
                return response()->json(['error' => 'No se encontró un usuario con ese correo electrónico.'], 400);
    
            case Password::RESET_THROTTLED:
                return response()->json(['error' => 'Demasiados intentos. Por favor, espere antes de intentar nuevamente.'], 429);
    
            default:
                return response()->json(['error' => 'Error inesperado al intentar restablecer la contraseña.'], 500);
        }
    }    
}
