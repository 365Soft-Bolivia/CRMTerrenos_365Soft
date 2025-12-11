<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\Terreno;
use App\Models\Cuadra;
use App\Models\Barrio;
use App\Models\CategoriaTerreno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MapaController extends Controller
{
    /**
     * Obtener lista de proyectos
     */
    public function getProyectos()
    {
        $proyectos = Proyecto::where('estado', 1)
            ->withCount([
                'terrenos as total_terrenos' => function ($query) {
                    $query->where('condicion', 1);
                },
                'terrenos as terrenos_disponibles' => function ($query) {
                    $query->where('estado', 0)->where('condicion', 1);
                },
                'terrenos as terrenos_vendidos' => function ($query) {
                    $query->where('estado', 1);
                },
                'terrenos as terrenos_reservados' => function ($query) {
                    $query->where('estado', 2);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($proyectos);
    }

    /**
     * Obtener información detallada de un proyecto específico
     */
    public function getProyecto($proyectoId)
    {
        try {
            $proyecto = Proyecto::withCount([
                'terrenos',
                'terrenos as terrenos_disponibles_count' => function ($query) {
                    $query->where('estado', 0)->where('condicion', 1);
                },
                'terrenos as terrenos_vendidos_count' => function ($query) {
                    $query->where('estado', 1);
                },
                'terrenos as terrenos_reservados_count' => function ($query) {
                    $query->where('estado', 2);
                }
            ])->findOrFail($proyectoId);

            return response()->json([
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
                'descripcion' => $proyecto->descripcion,
                'ubicacion' => $proyecto->ubicacion,
                'fecha_lanzamiento' => $proyecto->fecha_lanzamiento,
                'numero_lotes' => $proyecto->numero_lotes,
                'fotografia' => $proyecto->fotografia ? asset($proyecto->fotografia) : null,
                'total_terrenos' => $proyecto->terrenos_count,
                'terrenos_disponibles' => $proyecto->terrenos_disponibles_count,
                'terrenos_vendidos' => $proyecto->terrenos_vendidos_count,
                'terrenos_reservados' => $proyecto->terrenos_reservados_count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Obtener BARRIOS en formato GeoJSON (Zoom 1)
     * Para visualización cuando el zoom está alejado
     */
    public function getBarriosGeoJSON($proyectoId)
    {
        try {
            $barrios = DB::select("
                SELECT 
                    b.id,
                    b.nombre,
                    ST_AsGeoJSON(b.poligono) as poligono_json,
                    COUNT(t.id) as total_terrenos
                FROM barrios b
                LEFT JOIN cuadras cu ON cu.idbarrio = b.id
                LEFT JOIN terrenos t ON t.idcuadra = cu.id AND t.condicion = 1 AND t.estado = 0
                WHERE b.idproyecto = ?
                    AND b.poligono IS NOT NULL
                GROUP BY b.id, b.nombre, b.poligono
            ", [$proyectoId]);

            $features = [];

            foreach ($barrios as $barrio) {
                if (!$barrio->poligono_json) {
                    continue;
                }

                $geometry = json_decode($barrio->poligono_json, true);

                $features[] = [
                    'type' => 'Feature',
                    'properties' => [
                        'id' => $barrio->id,
                        'nombre' => $barrio->nombre,
                        'total_terrenos' => (int) $barrio->total_terrenos,
                        'tipo' => 'barrio',
                    ],
                    'geometry' => $geometry
                ];
            }

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en getBarriosGeoJSON: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los barrios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener CUADRAS en formato GeoJSON (Zoom 2)
     * Para visualización cuando el zoom está intermedio
     */
    public function getCuadrasGeoJSON($proyectoId)
    {
        try {
            $cuadras = DB::select("
                SELECT 
                    cu.id,
                    cu.nombre,
                    b.nombre as barrio_nombre,
                    ST_AsGeoJSON(cu.poligono) as poligono_json,
                    COUNT(t.id) as total_terrenos
                FROM cuadras cu
                INNER JOIN barrios b ON cu.idbarrio = b.id
                LEFT JOIN terrenos t ON t.idcuadra = cu.id AND t.condicion = 1 AND t.estado = 0
                WHERE b.idproyecto = ?
                    AND cu.poligono IS NOT NULL
                GROUP BY cu.id, cu.nombre, b.nombre, cu.poligono
            ", [$proyectoId]);

            $features = [];

            foreach ($cuadras as $cuadra) {
                if (!$cuadra->poligono_json) {
                    continue;
                }

                $geometry = json_decode($cuadra->poligono_json, true);

                $features[] = [
                    'type' => 'Feature',
                    'properties' => [
                        'id' => $cuadra->id,
                        'nombre' => $cuadra->nombre,
                        'barrio' => $cuadra->barrio_nombre,
                        'total_terrenos' => (int) $cuadra->total_terrenos,
                        'tipo' => 'cuadra',
                    ],
                    'geometry' => $geometry
                ];
            }

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en getCuadrasGeoJSON: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las cuadras',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener TERRENOS DISPONIBLES en formato GeoJSON (Zoom 3)
     * Solo devuelve terrenos con estado = 0 (disponibles)
     */
    public function getTerrenosDisponiblesGeoJSON($proyectoId)
    {
        try {
            $terrenos = DB::select("
                SELECT 
                    t.id,
                    t.numero_terreno,
                    t.ubicacion,
                    t.superficie,
                    t.precio_venta,
                    t.cuota_inicial,
                    t.cuota_mensual,
                    t.idcategoria,
                    t.idcuadra,
                    ST_AsGeoJSON(t.poligono) as poligono_json,
                    c.nombre as categoria_nombre,
                    c.color as categoria_color,
                    cu.nombre as cuadra_nombre,
                    b.nombre as barrio_nombre
                FROM terrenos t
                LEFT JOIN categorias_terrenos c ON t.idcategoria = c.id
                LEFT JOIN cuadras cu ON t.idcuadra = cu.id
                LEFT JOIN barrios b ON cu.idbarrio = b.id
                WHERE t.idproyecto = ?
                    AND t.condicion = 1
                    AND t.estado = 0
                    AND t.poligono IS NOT NULL
            ", [$proyectoId]);

            $features = [];

            foreach ($terrenos as $terreno) {
                if (!$terreno->poligono_json) {
                    continue;
                }

                $geometry = json_decode($terreno->poligono_json, true);

                $features[] = [
                    'type' => 'Feature',
                    'properties' => [
                        'id' => $terreno->id,
                        'codigo' => $terreno->numero_terreno ?? 'N/A',
                        'ubicacion' => $terreno->ubicacion,
                        'categoria' => $terreno->categoria_nombre ?? 'Sin categoría',
                        'categoria_color' => $terreno->categoria_color ?? '#6b7280',
                        'superficie' => (float) $terreno->superficie,
                        'precio_venta' => (float) $terreno->precio_venta,
                        'cuota_inicial' => (float) $terreno->cuota_inicial,
                        'cuota_mensual' => (float) $terreno->cuota_mensual,
                        'barrio' => $terreno->barrio_nombre,
                        'cuadra' => $terreno->cuadra_nombre,
                        'estado' => 0,
                        'estado_label' => 'Disponible',
                        'tipo' => 'terreno',
                    ],
                    'geometry' => $geometry
                ];
            }

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en getTerrenosDisponiblesGeoJSON: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los terrenos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener TODOS los TERRENOS en formato GeoJSON (para vista general)
     * Incluye disponibles, vendidos y reservados
     */
    public function getTerrenosGeoJSON($proyectoId)
    {
        try {
            $terrenos = DB::select("
                SELECT 
                    t.id,
                    t.numero_terreno,
                    t.ubicacion,
                    t.superficie,
                    t.precio_venta,
                    t.cuota_inicial,
                    t.cuota_mensual,
                    t.estado,
                    t.condicion,
                    ST_AsGeoJSON(t.poligono) as poligono_json,
                    c.nombre as categoria_nombre,
                    c.color as categoria_color,
                    cu.nombre as cuadra_nombre,
                    b.nombre as barrio_nombre
                FROM terrenos t
                LEFT JOIN categorias_terrenos c ON t.idcategoria = c.id
                LEFT JOIN cuadras cu ON t.idcuadra = cu.id
                LEFT JOIN barrios b ON cu.idbarrio = b.id
                WHERE t.idproyecto = ?
                    AND t.condicion = 1
                    AND t.poligono IS NOT NULL
            ", [$proyectoId]);

            $features = [];

            foreach ($terrenos as $terreno) {
                if (!$terreno->poligono_json) {
                    continue;
                }

                $geometry = json_decode($terreno->poligono_json, true);

                $features[] = [
                    'type' => 'Feature',
                    'properties' => [
                        'id' => $terreno->id,
                        'codigo' => $terreno->numero_terreno ?? 'N/A',
                        'ubicacion' => $terreno->ubicacion,
                        'categoria' => $terreno->categoria_nombre ?? 'Sin categoría',
                        'categoria_color' => $terreno->categoria_color ?? '#6b7280',
                        'superficie' => (float) $terreno->superficie,
                        'precio_venta' => (float) $terreno->precio_venta,
                        'cuota_inicial' => (float) $terreno->cuota_inicial,
                        'cuota_mensual' => (float) $terreno->cuota_mensual,
                        'barrio' => $terreno->barrio_nombre,
                        'cuadra' => $terreno->cuadra_nombre,
                        'estado' => $terreno->estado,
                        'condicion' => $terreno->condicion,
                        'estado_label' => $this->getEstadoLabel($terreno->estado),
                        'tipo' => 'terreno',
                    ],
                    'geometry' => $geometry
                ];
            }

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en getTerrenosGeoJSON: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los terrenos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener categorías activas de un proyecto
     */
    public function getCategorias($proyectoId)
    {
        $categorias = CategoriaTerreno::where('idproyecto', $proyectoId)
            ->where('estado', 1)
            ->withCount(['terrenos' => function ($query) {
                $query->where('condicion', 1);
            }])
            ->orderBy('nombre', 'asc')
            ->get()
            ->map(function ($categoria) {
                return [
                    'id' => $categoria->id,
                    'nombre' => $categoria->nombre,
                    'color' => $categoria->color,
                    'total_terrenos' => $categoria->terrenos_count ?? 0
                ];
            });

        return response()->json($categorias);
    }

    /**
     * Helper: Obtener etiqueta de estado
     */
    private function getEstadoLabel($estado)
    {
        switch ($estado) {
            case 0:
                return 'Disponible';
            case 1:
                return 'Vendido';
            case 2:
                return 'Reservado';
            default:
                return 'Desconocido';
        }
    }
}