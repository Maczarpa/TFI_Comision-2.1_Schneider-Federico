<?php

namespace App\Http\Controllers;
use App\Helpers\ToggleHelper;
use App\Models\DatosPersonales;
use App\Models\Usuario;
use App\QueryBuilder\UsuarioQueryBuilder;
use App\Services\EstadoUsuarioService;
use App\Services\UsuarioService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    //
    #region Usuario

    public function __construct()
    {
    }
    public function index(Request $request)
    {
        return response()->json(UsuarioService::obtenerPaginado($request));
    }

    
    public function store(Request $request)
    {
        ValidationService::ValidarRequest($request, array_merge(
            [
                'email' => 'required|email|unique:usuario,email',
                'username' => 'required|unique:usuario,username',
                'password' => 'required|confirmed|string|min:6'],
            DatosPersonales::rules("datos_personales.")));

        $usuario = UsuarioService::crear($request->all());

        if($usuario == null) {
            return response()->json(["error"=>"El usuario no ha sido creado, falla en los datos personales"]);
        }
        return response()->json($usuario, 201);
    }

    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);
        $usuarioQuery = Usuario::query();
        UsuarioQueryBuilder::ConDatosPersonales($usuarioQuery);
        $usuario = $usuarioQuery->findOrFail($id);
        ValidationService::ValidarRequest($request, array_merge(
            [
            'id' => 'required|exists:usuario,id',
            'username' => 'required|string|unique:usuario,username,' . $id,
            'email' => 'required|email|unique:usuario,email,' . $id],
            DatosPersonales::rules("datos_personales.",$usuario->datosPersonales->id)));

        //Verificamos que la contraseña del dueño de la cuenta quien solicita la accion
        ValidationService::ValidarOwnPassUser($request);        

        $usuario = UsuarioService::actualizar($id, $request->validated());
        return response()->json($usuario);
    }

    public function updateRoles(Request $request, $id)
    {
        ValidationService::ValidarRequest($request,[
            'rol_ids' => 'nullable|array|min:0',
            'rol_ids.*' => 'exists:rol,id'
        ]);        
        ValidationService::ValidarOwnPassUser($request);
        $usuario = UsuarioService::actualizarRoles($request, $id);

        // Devolver el usuario con los roles actualizados
        return response()->json($usuario);
    }
    public function cambiarPassword(Request $request, $id)
    {
        $request->merge(['id' => $id]);            
        // Validar los datos del request usando ValidationService
        ValidationService::ValidarRequest($request, [
            'id' => 'required|exists:usuario,id',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Verificar las credenciales del usuario (si la contraseña del usuario actual es válida)
        ValidationService::ValidarOwnPassUser($request);

        // Llamar al servicio de usuario para cambiar la contraseña
        $usuario = UsuarioService::cambiarPassword($id, $request->password);

        // Devolver la respuesta de éxito
        return response()->json(['mensaje' => 'Contraseña actualizada']);
    }
    public function toggleHabilitado(Request $request, $id)
    {
        // Validamos el ID del usuario con ValidationService
        ValidationService::ValidarRequest($request, [
            'id' => 'required|exists:usuario,id',  // Validar que el id exista en la tabla de usuarios
        ]);

        // Llamamos al servicio que maneja el cambio de estado del usuario
        $usuario = UsuarioService::toggleHabilitado($id);

        // Devolvemos la respuesta con el usuario actualizado
        return response()->json($usuario);
    }
    #endregion
}
