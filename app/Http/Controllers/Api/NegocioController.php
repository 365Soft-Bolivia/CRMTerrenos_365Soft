<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Negocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NegocioController extends Controller
{
    /**
     * Listar todos los negocios
     * GET /api/negocios
     */
    public function index(Request $request)
    {
        try {
            $query = Negocio::with([
                'lead',
                'terreno.proyecto',
                'terreno.categoria',
                'asesor',
                'seguimientos'
            ]);

            // Filtrar por asesor si se proporciona
            if ($request->has('asesor_id')) {
                $query->where('asesor_id', $request->asesor_id);
            }

            // Filtrar por etapa
            if ($request->has('etapa')) {
                $query->where('etapa', $request->etapa);
            }

            // Filtrar solo activos (excluir ganados y perdidos)
            if ($request->has('solo_activos') && $request->solo_activos) {
                $query->activos();
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $negocios = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $negocios
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los negocios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos para el tablero Kanban agrupados por etapa
     * GET /api/negocios/tablero
     */
    public function tablero(Request $request)
    {
        try {
            $asesorId = $request->get('asesor_id', null);
            
            // Obtener todas las etapas
            $etapas = Negocio::etapas();
            
            $tablero = [];

            foreach ($etapas as $etapa) {
                $query = Negocio::with([
                    'lead',
                    'terreno.proyecto',
                    'terreno.categoria',
                    'asesor'
                ])->where('etapa', $etapa);

                // Filtrar por asesor si se proporciona
                if ($asesorId) {
                    $query->where('asesor_id', $asesorId);
                }

                $negocios = $query->orderBy('created_at', 'desc')->get();

                $tablero[] = [
                    'etapa' => $etapa,
                    'cantidad' => $negocios->count(),
                    'negocios' => $negocios
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $tablero
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tablero',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver detalle de un negocio con su historial de seguimientos
     * GET /api/negocios/{id}
     */
    public function show($id)
    {
        try {
            $negocio = Negocio::with([
                'lead',
                'terreno.proyecto',
                'terreno.categoria',
                'terreno.cuadra.barrio',
                'asesor',
                'seguimientos.asesor'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $negocio
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Negocio no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar la etapa de un negocio (para drag & drop del Kanban)
     * PUT /api/negocios/{id}/etapa
     */
    public function actualizarEtapa(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'etapa' => 'required|string|max:100|in:' . implode(',', Negocio::etapas()),
        ], [
            'etapa.required' => 'La etapa es obligatoria',
            'etapa.in' => 'La etapa seleccionada no es válida',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $negocio = Negocio::findOrFail($id);
            
            $etapaAnterior = $negocio->etapa;
            $negocio->etapa = $request->etapa;

            // Si la etapa es "Cierre / Venta Concretada", marcar como convertido a cliente
            if ($request->etapa === Negocio::ETAPA_CIERRE) {
                $negocio->convertido_cliente = true;
                // Aquí podrías agregar lógica adicional para crear el cliente en otra tabla
            }

            $negocio->save();
            $negocio->load(['lead', 'terreno', 'asesor']);

            return response()->json([
                'success' => true,
                'message' => 'Etapa actualizada exitosamente',
                'data' => $negocio,
                'etapa_anterior' => $etapaAnterior
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la etapa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un negocio completo
     * PUT /api/negocios/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'terreno_id' => 'required|exists:terrenos,id',
            'etapa' => 'required|string|max:100|in:' . implode(',', Negocio::etapas()),
            'fecha_inicio' => 'required|date',
            'monto_estimado' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string',
        ], [
            'terreno_id.required' => 'Debe seleccionar un terreno',
            'terreno_id.exists' => 'El terreno seleccionado no existe',
            'etapa.required' => 'La etapa es obligatoria',
            'etapa.in' => 'La etapa seleccionada no es válida',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $negocio = Negocio::findOrFail($id);

            $negocio->update([
                'terreno_id' => $request->terreno_id,
                'etapa' => $request->etapa,
                'fecha_inicio' => $request->fecha_inicio,
                'monto_estimado' => $request->monto_estimado,
                'notas' => $request->notas,
            ]);

            // Si la etapa es "Cierre / Venta Concretada", marcar como convertido a cliente
            if ($request->etapa === Negocio::ETAPA_CIERRE) {
                $negocio->convertido_cliente = true;
                $negocio->save();
            }

            $negocio->load(['lead', 'terreno', 'asesor']);

            return response()->json([
                'success' => true,
                'message' => 'Negocio actualizado exitosamente',
                'data' => $negocio
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el negocio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un negocio
     * DELETE /api/negocios/{id}
     */
    public function destroy($id)
    {
        try {
            $negocio = Negocio::findOrFail($id);
            $negocio->delete();

            return response()->json([
                'success' => true,
                'message' => 'Negocio eliminado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el negocio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de negocios
     * GET /api/negocios/estadisticas
     */
    public function estadisticas(Request $request)
    {
        try {
            $asesorId = $request->get('asesor_id', null);
            
            $query = Negocio::query();
            
            if ($asesorId) {
                $query->where('asesor_id', $asesorId);
            }

            $total = $query->count();
            $activos = $query->clone()->activos()->count();
            $ganados = $query->clone()->ganados()->count();
            $perdidos = $query->clone()->perdidos()->count();
            $montoTotal = $query->clone()->ganados()->sum('monto_estimado');

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'activos' => $activos,
                    'ganados' => $ganados,
                    'perdidos' => $perdidos,
                    'monto_total_ganado' => $montoTotal,
                    'tasa_conversion' => $total > 0 ? round(($ganados / $total) * 100, 2) : 0
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}