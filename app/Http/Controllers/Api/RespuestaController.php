// app/Http/Controllers/Api/RespuestaController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RespuestaModel; // Tu modelo de respuesta
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RespuestaController extends Controller
{
    /**
     * Guarda una sola respuesta (para /respuestas POST).
     */
    public function store(Request $request)
    {
        // Esta lógica ya la tenías en la función anónima de la ruta
        $validated = $request->validate([
            'id_temp' => 'required|string|unique:respuestas,id_temp',
            'encuesta_id' => 'required|integer|exists:encuestas,id',
            'json_data' => 'required|json',
            'dispositivo_id' => 'required|string',
            'fecha_respuesta' => 'required|date',
            'metadatos' => 'nullable|json'
        ]);

        $validated['usuario_id'] = Auth::check() ? Auth::id() : null; // Asignar ID si está logueado
        
        $respuesta = RespuestaModel::firstOrCreate(
            ['id_temp' => $validated['id_temp']],
            $validated
        );

        return response()->json(
            $respuesta,
            $respuesta->wasRecentlyCreated ? 201 : 200
        );
    }
    
    /**
     * Sincroniza múltiples respuestas (para /respuestas/sincronizar POST).
     */
    public function sincronizar(Request $request)
    {
        // Toda la lógica de la transacción (DB::transaction) y el bucle firstOrCreate debe ir aquí.
        // ... (Implementar la lógica completa de sincronización) ...
        
        // Simplemente copiamos la lógica que estaba en la ruta:
        $request->validate([
            'respuestas' => 'required|array',
            'respuestas.*.id_temp' => 'required|string',
            'respuestas.*.encuesta_id' => 'required|integer|exists:encuestas,id',
            // ... (resto de validaciones) ...
        ]);

        $creadas = [];
        $existentes = [];

        DB::beginTransaction();
        try {
            foreach ($request->input('respuestas', []) as $data) {
                $data['usuario_id'] = Auth::check() ? Auth::id() : null; 
                
                $respuesta = RespuestaModel::firstOrCreate(
                    ['id_temp' => $data['id_temp']],
                    $data
                );

                if ($respuesta->wasRecentlyCreated) {
                    $creadas[] = $respuesta->id_temp;
                } else {
                    $existentes[] = $respuesta->id_temp;
                }
            }
            DB::commit();

            return response()->json([
                'nuevas' => $creadas,
                'duplicadas' => $existentes,
                'total_recibidas' => count($request->input('respuestas')),
                'total_guardadas' => count($creadas) + count($existentes)
            ], 200);

        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al sincronizar'], 500);
        }
    }
}