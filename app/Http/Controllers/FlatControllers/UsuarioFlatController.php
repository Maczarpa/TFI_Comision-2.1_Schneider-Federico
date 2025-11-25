<?php

namespace App\Http\Controllers\FlatControllers;

use App\Helpers\AplanadorHelper;
use App\Helpers\CampoMapper;
use App\Helpers\ToggleHelper;
use App\Http\Controllers\UsuarioController;
use App\Models\DatosPersonales;
use App\Models\EstadoUsuario;
use App\Models\Usuario;
use App\QueryBuilder\UsuarioQueryBuilder;
use App\Services\EstadoUsuarioService;
use App\Services\UsuarioService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsuarioFlatController
{
    public function __construct()
    {
    }
    public function index(Request $request)
    {
        $paginado = UsuarioService::obtenerPaginado($request);
        UsuarioService::Aplanar($paginado->getCollection());
        return response()->json($paginado);
    }
    
    public function store(Request $request)
    {
        ValidationService::ValidarRequest($request, array_merge(
            [
                'email' => 'required|email|unique:usuario,email',
                'username' => 'required|unique:usuario,username',
                'password' => 'required|confirmed|string|min:6'],
            DatosPersonales::rules(DatosPersonales::$default_prefix)));

        $datos_personales = CampoMapper::extraerDatosDesdeRequest(DatosPersonales::$campos, $request, DatosPersonales::$default_prefix);
        $request->merge(['datos_personales' => $datos_personales]);
        $usuario = UsuarioService::crear($request->all());

        AplanadorHelper::AplanarUsuarioCompleto($usuario,$usuario, false);
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
            DatosPersonales::rules(DatosPersonales::$default_prefix,$usuario->datosPersonales->id)));

        //Verificamos que la contraseña del dueño de la cuenta quien solicita la accion
        ValidationService::ValidarOwnPassUser($request);        
        
        $datos_personales = CampoMapper::extraerDatosDesdeRequest(DatosPersonales::$campos, $request, DatosPersonales::$default_prefix);
        $request->merge(['datos_personales' => $datos_personales]);

        $usuario = UsuarioService::actualizar($id, $request->all());
        AplanadorHelper::AplanarUsuarioCompleto($usuario,$usuario, false);
        return response()->json($usuario);
    }
}
