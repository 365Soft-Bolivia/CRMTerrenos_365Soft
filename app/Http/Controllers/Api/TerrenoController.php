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
     * Listar proyectos para filtros
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
}