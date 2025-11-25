<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    //
}

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EncuestaModel; // Usar tu modelo EncuestaModel
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurveyController extends Controller
{
    /**
     * Muestra una lista de las encuestas (paginada).
     * Corresponde a la ruta: GET /api/surveys/pageable
     */
    public function index(Request $request)
    {
        // Se puede añadir lógica de filtrado o se trae todo, usando paginación
        return EncuestaModel::with('usuario')->paginate(10);
    }
    
    /**
     * Muestra una lista sin paginación.
     * Corresponde a la ruta: GET /api/surveys
     */
    public function listAll(Request $request)
    {
        return EncuestaModel::with('usuario')->get();
    }

    /**
     * Muestra una encuesta específica.
     * Corresponde a la ruta: GET /api/surveys/{id}
     */
    public function show($id)
    {
        return EncuestaModel::with('usuario')->find($id);
    }

    /**
     * Almacena una nueva encuesta (desde SurveyJS Creator).
     * Corresponde a la ruta: POST /api/surveys
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'jsonEncuesta' => 'required|json',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
            'titulo' => 'required|string|max:255'
        ]);
        
        // El usuario está autenticado gracias al middleware, usamos su ID
        $data['usuario_id'] = Auth::id(); // o $request->user()->id;
        
        $encuesta = EncuestaModel::create($data);
        return response()->json($encuesta, 201);
    }

    /**
     * Actualiza una encuesta existente.
     * Corresponde a la ruta: PUT /api/surveys/{id}
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'jsonEncuesta' => 'required|json',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
            'titulo' => 'required|string|max:255',
        ]);

        $encuesta = EncuestaModel::find($id);
        if (!$encuesta) {
            return response()->json(['error' => 'Encuesta no encontrada'], 404);
        }
        
        // Opcional: Añadir autorización (ej: solo el creador puede editar)
        if ($encuesta->usuario_id !== Auth::id() && !Auth::user()->tieneRol(['SUPER_ADMIN', 'ADMIN'])) {
             return response()->json(['error' => 'No autorizado para editar esta encuesta'], 403);
        }

        $encuesta->update($data);
        return response()->json($encuesta, 200);
    }
    
    // Aquí iría el método destroy si lo necesitas.
    
    // NOTA: El método submitResponse iría en el RespuestaController o aquí, dependiendo de tu organización.
}