<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Terreno;
use App\Models\Proyecto;
use App\Models\CategoriaTerreno;
use Illuminate\Http\Request;

class TerrenoController extends Controller
{
    /**
     * Listar terrenos disponibles para dropdowns
     * GET /api/terrenos
     */
    public function index(Request $request)
    {
        try {
            $query = Terreno::with([
                'proyecto',
                'categoria',
                'cuadra.barrio'
            ]);

            // Filtrar solo terrenos disponibles (estado = 0)
            if ($request->get('solo_disponibles', true)) {
                $query->disponibles();
            }

            // Filtrar por proyecto
            if ($request->has('proyecto_id')) {
                $query->where('idproyecto', $request->proyecto_id);
            }

            // Filtrar por categoría
            if ($request->has('categoria_id')) {
                $query->where('idcategoria', $request->categoria_id);
            }

            // Filtrar por cuadra
            if ($request->has('cuadra_id')) {
                $query->where('idcuadra', $request->cuadra_id);
            }

            // Búsqueda por ubicación o número
            if ($request->has('buscar')) {
                $termino = $request->buscar;
                $query->where(function($q) use ($termino) {
                    $q->where('ubicacion', 'like', "%{$termino}%")
                      ->orWhere('numero_terreno', 'like', "%{$termino}%");
                });
            }

            $perPage = $request->get('per_page', 50);
            $terrenos = $query->orderBy('ubicacion')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $terrenos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los terrenos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver detalle de un terreno
     * GET /api/terrenos/{id}
     */
    public function show($id)
    {
        try {
            $terreno = Terreno::with([
                'proyecto',
                'categoria',
                'cuadra.barrio',
                'documentos'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $terreno
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terreno no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Listar proyectos para filtros o busqueda por ubicacion
     * GET /api/terrenos/proyectos
     */
    public function proyectos()
    {
        try {
            $proyectos = Proyecto::orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $proyectos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los proyectos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar categorías para filtros
     * GET /api/terrenos/categorias
     */
    public function categorias(Request $request)
    {
        try {
            $query = CategoriaTerreno::query();

            // Filtrar por proyecto si se proporciona
            if ($request->has('proyecto_id')) {
                $query->where('idproyecto', $request->proyecto_id);
            }

            // Solo categorías activas
            $query->activas();

            $categorias = $query->orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $categorias
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las categorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información resumida para dropdown simplificado
     * GET /api/terrenos/dropdown
     */
    public function dropdown(Request $request)
    {
        try {
            $query = Terreno::disponibles();

            // Filtrar por proyecto si se proporciona
            if ($request->has('proyecto_id')) {
                $query->where('idproyecto', $request->proyecto_id);
            }

            $terrenos = $query->select('id', 'ubicacion', 'numero_terreno', 'precio_venta')
                ->orderBy('ubicacion')
                ->get()
                ->map(function($terreno) {
                    return [
                        'id' => $terreno->id,
                        'label' => $terreno->ubicacion . ($terreno->numero_terreno ? ' - Nº ' . $terreno->numero_terreno : ''),
                        'precio' => $terreno->precio_venta
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $terrenos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los terrenos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 1. Optener terreno por ubicacion
     */
    public function buscarPorCodigo(Request $request)
    {
        try {
            $codigo = strtoupper(trim($request->codigo ?? ''));
            $proyectoId = $request->proyecto_id ?? null;

            // Validar datos de entrada
            if (!$codigo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Código vacío.'
                ], 400);
            }

            if (!$proyectoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proyecto no enviado.'
                ], 400);
            }

            // Normalizar espacios (QUITA espacios dobles)
            $codigo = preg_replace('/\s+/', ' ', $codigo);

            // Buscar terreno (Coincidencia flexible)
            $terreno = \App\Models\Terreno::whereRaw('UPPER(ubicacion) LIKE ?', ["%{$codigo}%"])
                ->where('idproyecto', $proyectoId)
                ->select('id', 'ubicacion')
                ->first();

            // No encontrado
            if (!$terreno) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terreno no encontrado.'
                ], 404);
            }

            // Encontrado
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $terreno->id,
                    'ubicacion' => $terreno->ubicacion
                ]
            ], 200);

        } catch (\Throwable $e) {
            // Error interno
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * 2. OBTENER BARRIOS POR PROYECTO
     * GET /api/terrenos/barrios?proyecto_id=1
     */
    public function barrios(Request $request)
    {
        try {
            $proyectoId = $request->query('proyecto_id');

            if (!$proyectoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'proyecto_id es requerido'
                ], 400);
            }

            $barrios = \App\Models\Barrio::where('idproyecto', $proyectoId)
                ->orderBy('nombre')
                ->select('id', 'nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $barrios
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener barrios'
            ], 500);
        }
    }

    /**
     * 3. OBTENER CUADRAS POR BARRIO
     * GET /api/terrenos/cuadras?barrio_id=5
     */
    public function cuadras(Request $request)
    {
        try {
            $barrioId = $request->query('barrio_id');

            if (!$barrioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'barrio_id es requerido'
                ], 400);
            }

            $cuadras = \App\Models\Cuadra::where('idbarrio', $barrioId)
                ->orderBy('nombre')
                ->select('id', 'nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $cuadras
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cuadras'
            ], 500);
        }
    }

    /**
     * 4. OBTENER TERRENOS POR CUADRA
     * GET /api/terrenos/por-cuadra?cuadra_id=10
     */
    public function porCuadra(Request $request)
    {
        $request->validate([
            'cuadra_id' => 'required|numeric'
        ]);

        $terrenos = Terreno::where('idcuadra', $request->cuadra_id)
                            ->select('id', 'numero_terreno')
                            ->get()
                            ->map(function($terreno) {
                                return [
                                    'id' => $terreno->id,
                                    'nombre' => $terreno->numero_terreno  // ← solo número de terreno
                                ];
                            });
        return response()->json([
            'success' => true,
            'data' => $terrenos
        ], 200);
    }

}