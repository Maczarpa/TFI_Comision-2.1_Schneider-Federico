<?php

use App\Http\Controllers\Api\RespuestaController;
use App\Http\Controllers\Api\SurveyController; 
use App\Models\EncuestaModel;
use App\Models\RespuestaModel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;








Route::prefix('/respuestas')->group(function(){
   
    Route::get('/pageable', [RespuestaController::class, 'index']);
    Route::post('/', [RespuestaController::class, 'store']);
    Route::post('/sincronizar', [RespuestaController::class, 'sincronizar']);
});


Route::middleware(['auth:sanctum'])->prefix('/encuestas')->group(function(){
    // Rutas limpias que llaman al controlador:
    Route::get('/pageable', [SurveyController::class, 'index']); 
    Route::get('/', [SurveyController::class, 'listAll']);
    Route::get('/{id}', [SurveyController::class, 'show']);
    Route::post('/', [SurveyController::class, 'store']);
    Route::put('/{id}', [SurveyController::class, 'update']);
  
});
require __DIR__.'/auth.php';
include('UsuarioRoutes.php');
include('RolesRoutes.php');

route::get('', function(){
    throw new HttpResponseException(response()->json([
        'error' => "Error de Autorización"
    ], 401));
})->name("login");


Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user()->load("roles");
});

Route::middleware(['auth:sanctum'])->prefix('/encuestas')->group(function(){
    Route::get('/pageable', function (Request $request) {
        return EncuestaModel::with('usuario')->paginate(10);
    });
    Route::get('/', function (Request $request) {
        return EncuestaModel::with('usuario')->get();
    });
    Route::get('/{id}', function (Request $request, $id) {
        return EncuestaModel::with('usuario')->find($id);
    });
    Route::get('/{id}/estadisticas', function (Request $request, $id) {
        $encuesta = EncuestaModel::with(['respuestas', 'campos'])->findOrFail($id);
        $estructura = json_decode($encuesta->jsonEncuesta, true);
        //$campos = collect($estructura['pages'][0]['elements']); // simplificado
        $campos = collect($estructura['pages'])
            ->pluck('elements')   // extrae los arrays de elementos
            ->flatten(1);         // aplana a un solo array

        $resultados = [];
        $noContestadosPorCampo = []; // inicializamos

        foreach ($campos as $campo) {
            $name = $campo['name'];

            // buscar metadata en encuesta_campos
            $meta = $encuesta->campos->firstWhere('name', $name);

            $alias = $meta->alias ?? $campo['title'] ?? $name;
            $descripcion = $campo['description'] ?? '';
            $tipo  = $meta->tipo  ?? $campo['type'];

            $respuestas = $encuesta->respuestas
                ->pluck("json_data")
                ->map(fn($d) => json_decode($d, true)[$name] ?? null)
                ->filter();

            switch ($tipo) {
                case 'rating':
                    $rateMin = $campo['rateMin'] ?? 1;
                    $rateMax = $campo['rateMax'] ?? 5;
                    $step = $campo['rateStep'] ?? 1;

                    $distribucion = [];
                    for ($i = $rateMin; $i <= $rateMax; $i += $step) {
                        $distribucion[$i] = 0;
                    }

                    foreach ($respuestas as $resp) {
                        if ($resp !== null && isset($distribucion[$resp])) {
                            $distribucion[$resp]++;
                        }
                    }

                    $noContestados = $encuesta->respuestas->count() - $respuestas->count();
                    if ($noContestados > 0) {
                        $distribucion['No Contestados'] = $noContestados;
                        $noContestadosPorCampo[$name] = [
                            'alias' => $alias,
                            'no_contestados' => $noContestados
                        ];
                    }

                    $resultados[$name] = [
                        'alias' => $alias,
                        'descripcion' => $descripcion,
                        'cantidad_de_resultados' => $respuestas->count(),
                        'promedio' => number_format($respuestas->avg(), 2),
                        'distribucion_de_valores' => $distribucion,
                    ];
                    break;

                case 'radiogroup':
                case 'dropdown':
                    $opciones = $campo['choices'] ?? [];

                    $distribucion = [];
                    foreach ($opciones as $op) {
                        $distribucion[$op] = 0;
                    }

                    foreach ($respuestas as $resp) {
                        if ($resp !== null && isset($distribucion[$resp])) {
                            $distribucion[$resp]++;
                        }
                    }

                    $noContestados = $encuesta->respuestas->count() - $respuestas->count();
                    if ($noContestados > 0) {
                        $distribucion['No Contestados'] = $noContestados;
                        $noContestadosPorCampo[$name] = [
                            'alias' => $alias,
                            'no_contestados' => $noContestados
                        ];
                    }

                    $resultados[$name] = [
                        'alias' => $alias,
                        'descripcion' => $descripcion,
                        'cantidad_de_resultados' => $respuestas->count(),
                        'distribucion_de_valores' => $distribucion,
                    ];
                    break;

                case 'boolean':
                    $totalEncuestas = $encuesta->respuestas->count();

                    $trueCount = $respuestas->filter(fn($v) => $v === true || $v === 'true' || $v === 1 || $v === '1')->count();
                    $falseCount = $respuestas->filter(fn($v) => $v === false || $v === 'false' || $v === 0 || $v === '0')->count();
                    $answeredCount = $trueCount + $falseCount;
                    $noAnswerCount = $totalEncuestas - $answeredCount;

                    if ($noAnswerCount > 0) {
                        $noContestadosPorCampo[$name] = [
                            'alias' => $alias,
                            'no_contestados' => $noAnswerCount
                        ];
                    }

                    $resultados[$name] = [
                        'alias' => $alias,
                        'descripcion' => $descripcion,
                        'cantidad_true' => $trueCount,
                        'cantidad_false' => $falseCount,
                        'cantidad_no_contestado' => $noAnswerCount,
                        'cantidad_de_resultados' => $answeredCount,
                        'porcentaje_true' => $totalEncuestas > 0 ? round(($trueCount / $totalEncuestas) * 100, 2) : 0,
                        'porcentaje_false' => $totalEncuestas > 0 ? round(($falseCount / $totalEncuestas) * 100, 2) : 0,
                        'porcentaje_no_contestado' => $totalEncuestas > 0 ? round(($noAnswerCount / $totalEncuestas) * 100, 2) : 0,
                    ];
                    break;

                case 'checkbox':
                    $opciones = $campo['choices'] ?? [];
                    $maxOpciones = count($opciones);

                    $distribucion = [];
                    foreach ($opciones as $op) {
                        $distribucion[$op] = 0;
                    }

                    $cantidadPorCantRespuestas = [];
                    for ($i = 1; $i <= $maxOpciones; $i++) {
                        $cantidadPorCantRespuestas[$i] = 0;
                    }

                    $noContestados = 0;
                    $conRespuesta = 0;

                    foreach ($encuesta->respuestas as $r) {
                        $data = json_decode($r->json_data, true);
                        $valor = $data[$name] ?? null;

                        if (empty($valor)) {
                            $noContestados++;
                            continue;
                        }

                        $conRespuesta++;
                        $numSeleccionadas = count((array) $valor);

                        if (isset($cantidadPorCantRespuestas[$numSeleccionadas])) {
                            $cantidadPorCantRespuestas[$numSeleccionadas]++;
                        }

                        foreach ($valor as $op) {
                            if (isset($distribucion[$op])) {
                                $distribucion[$op]++;
                            }
                        }
                    }

                    if ($noContestados > 0) {
                        $noContestadosPorCampo[$name] = [
                            'alias' => $alias,
                            'no_contestados' => $noContestados
                        ];
                    }

                    $resultados[$name] = [
                        'alias' => $alias,
                        'descripcion' => $descripcion,
                        'cantidad_no_contestado' => $noContestados,
                        'cantidad_con_respuesta' => $conRespuesta,
                        'cantidades_por_cantidad_de_respuestas' => $cantidadPorCantRespuestas,
                        'distribucion_de_valores' => $distribucion,
                    ];
                    break;

                default:
                    $resultados[$name] = [
                        'alias' => $alias,
                        'descripcion' => $descripcion,
                        'tipo'  => $tipo,
                        'nota'  => 'No se procesan estadísticas para este tipo de campo'
                    ];
                    $noContestadosDefault = $encuesta->respuestas->count() - $respuestas->count();
                    if ($noContestadosDefault > 0) {
                        $noContestadosPorCampo[$name] = [
                            'alias' => $alias,
                            'no_contestados' => $noContestadosDefault
                        ];
                    }
                    break;
            }
        }

        $totalEncuestas = $encuesta->respuestas->count();

        /*
        
        $respuestasPorUsuario = $encuesta->respuestas
            ->groupBy('usuario_id')
            ->map(function ($respuestas, $usuarioId) {
                $usuario = $respuestas->first()->usuario; // el mismo para todas
                return [
                    'usuario_id' => $usuarioId,
                    'nombre' => $usuario->nombre ?? 'Desconocido',
                    'cantidad_respuestas' => $respuestas->count()
                ];
            })
            ->values(); // que sea un array numérico
        
        */

        $global = [
            'total_encuestas' => $totalEncuestas,
            //'total_contestadas' => $totalEncuestas,
            //'total_no_contestadas' => 0,
            'campos_no_contestados' => array_values(
                collect($noContestadosPorCampo)
                    ->sortByDesc('no_contestados')
                    ->toArray()
            ),
            //'respuestas_por_usuario' => $respuestasPorUsuario,
        ];

        
        return response()->json([
            'global' => $global,
            "encuesta" => $encuesta,
            'estadisticas' => $resultados
        ], 200);
    });

    Route::put('/{id}', function (Request $request, $id) {
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

        $encuesta->update($data);
        return response()->json($encuesta, 200);
    });
    Route::post('/', function (Request $request) {
        $data = $request->validate([
            'jsonEncuesta' => 'required|json',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
            'titulo' => 'required|string|max:255'
        ]);
        $data['usuario_id'] = $request->user()->id;
        $encuesta = EncuestaModel::create($data);
        return response()->json($encuesta, 201);
    });
});
Route::middleware(['auth:sanctum'])->prefix('/respuestas')->group(function(){
    Route::get('/pageable', function (Request $request) {
        return RespuestaModel::with("encuesta")->paginate(10);
    });
    Route::post('/', function (Request $request) {

    $validated = $request->validate([
        'id_temp' => 'required|string|unique:respuestas,id_temp',
        'encuestas_id' => 'required|integer|exists:encuestas,id', // uso tu tabla tal cual
        'json_data' => 'required|json',
        'dispositivo_id' => 'required|string',
        'fecha_respuesta' => 'required|date',
        'metadatos' => 'nullable|json'
    ]);

    Log::info('✅ Datos validados', $validated);
    $validated['usuario_id'] = $request->user()->id;
    $respuesta = RespuestaModel::firstOrCreate(
        ['id_temp' => $validated['id_temp']],
        $validated
    );

    Log::info('📝 Respuesta creada o encontrada', [
        'id' => $respuesta->id ?? null,
        'id_temp' => $respuesta->id_temp,
        'fecha_respuesta' => $respuesta->fecha_respuesta,
        'wasRecentlyCreated' => $respuesta->wasRecentlyCreated
    ]);

    return response()->json(
        $respuesta,
        $respuesta->wasRecentlyCreated ? 201 : 200
    );
});
    Route::post('/sincronizar', function (Request $request) {
        $request->validate([
            'respuestas' => 'required|array',
            'respuestas.*.id_temp' => 'required|string',
            'respuestas.*.encuesta_id' => 'required|integer|exists:encuestas,id',
            'respuestas.*.json_data' => 'required|json',
            'respuestas.*.dispositivo_id' => 'required|string',
            'respuestas.*.fecha_respuesta' => 'nullable|date',
            'respuestas.*.metadatos' => 'nullable|json',
        ]);

        $respuestas = $request->input('respuestas', []);
        $creadas = [];
        $existentes = [];

        DB::beginTransaction();
        try {
            foreach ($respuestas as $data) {
                $data['usuario_id'] = $request->user()->id;
                // si existe, devuelve el existente
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
                'total_recibidas' => count($respuestas),
                'total_guardadas' => count($creadas) + count($existentes)
            ], 200);

        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al sincronizar',
                'detalles' => $e->getMessage()
            ], 500);
        }
    });
});



//php artisan serve --host=0.0.0.0 --port=8000
