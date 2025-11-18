<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seguimiento;
use App\Models\Negocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SeguimientoController extends Controller
{
    /**
     * Listar seguimientos de un negocio específico
     * GET /api/seguimientos/{negocioId}
     */
    public function index($negocioId)
    {
        try {
            // Verificar que el negocio existe
            $negocio = Negocio::findOrFail($negocioId);

            $seguimientos = Seguimiento::with(['asesor', 'negocio.lead'])
                ->where('negocio_id', $negocioId)
                ->orderBy('fecha_seguimiento', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $seguimientos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los seguimientos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo seguimiento
     * POST /api/seguimientos
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'negocio_id' => 'required|exists:negocios,id',
            'tipo' => 'required|string|max:50|in:' . implode(',', Seguimiento::tipos()),
            'descripcion' => 'required|string',
            'fecha_seguimiento' => 'required|date',
            'proximo_seguimiento' => 'nullable|date|after_or_equal:fecha_seguimiento',
        ], [
            'negocio_id.required' => 'El negocio es obligatorio',
            'negocio_id.exists' => 'El negocio seleccionado no existe',
            'tipo.required' => 'El tipo de seguimiento es obligatorio',
            'tipo.in' => 'El tipo de seguimiento no es válido',
            'descripcion.required' => 'La descripción es obligatoria',
            'fecha_seguimiento.required' => 'La fecha de seguimiento es obligatoria',
            'proximo_seguimiento.after_or_equal' => 'El próximo seguimiento debe ser posterior a la fecha actual',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $seguimiento = Seguimiento::create([
                'negocio_id' => $request->negocio_id,
                'tipo' => $request->tipo,
                'descripcion' => $request->descripcion,
                'fecha_seguimiento' => $request->fecha_seguimiento,
                'proximo_seguimiento' => $request->proximo_seguimiento,
                'recordatorio_enviado' => false,
                'asesor_id' => Auth::id(), // Auto-asignar al usuario autenticado
            ]);

            $seguimiento->load(['asesor', 'negocio.lead']);

            return response()->json([
                'success' => true,
                'message' => 'Seguimiento creado exitosamente',
                'data' => $seguimiento
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el seguimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver detalle de un seguimiento
     * GET /api/seguimientos/detalle/{id}
     */
    public function show($id)
    {
        try {
            $seguimiento = Seguimiento::with([
                'asesor',
                'negocio.lead',
                'negocio.terreno'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $seguimiento
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seguimiento no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar un seguimiento
     * PUT /api/seguimientos/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|string|max:50|in:' . implode(',', Seguimiento::tipos()),
            'descripcion' => 'required|string',
            'fecha_seguimiento' => 'required|date',
            'proximo_seguimiento' => 'nullable|date|after_or_equal:fecha_seguimiento',
        ], [
            'tipo.required' => 'El tipo de seguimiento es obligatorio',
            'tipo.in' => 'El tipo de seguimiento no es válido',
            'descripcion.required' => 'La descripción es obligatoria',
            'fecha_seguimiento.required' => 'La fecha de seguimiento es obligatoria',
            'proximo_seguimiento.after_or_equal' => 'El próximo seguimiento debe ser posterior a la fecha actual',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $seguimiento = Seguimiento::findOrFail($id);

            $seguimiento->update([
                'tipo' => $request->tipo,
                'descripcion' => $request->descripcion,
                'fecha_seguimiento' => $request->fecha_seguimiento,
                'proximo_seguimiento' => $request->proximo_seguimiento,
            ]);

            $seguimiento->load(['asesor', 'negocio.lead']);

            return response()->json([
                'success' => true,
                'message' => 'Seguimiento actualizado exitosamente',
                'data' => $seguimiento
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el seguimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un seguimiento
     * DELETE /api/seguimientos/{id}
     */
    public function destroy($id)
    {
        try {
            $seguimiento = Seguimiento::findOrFail($id);
            $seguimiento->delete();

            return response()->json([
                'success' => true,
                'message' => 'Seguimiento eliminado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el seguimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar seguimientos pendientes (con próximo seguimiento futuro)
     * GET /api/seguimientos/pendientes
     */
    public function pendientes(Request $request)
    {
        try {
            $asesorId = $request->get('asesor_id', null);
            
            $query = Seguimiento::with([
                'asesor',
                'negocio.lead',
                'negocio.terreno'
            ])->pendientes();

            // Filtrar por asesor si se proporciona
            if ($asesorId) {
                $query->where('asesor_id', $asesorId);
            }

            $seguimientos = $query->orderBy('proximo_seguimiento', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $seguimientos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener seguimientos pendientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de seguimiento disponibles
     * GET /api/seguimientos/tipos
     */
    public function tipos()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => Seguimiento::tipos()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de seguimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar recordatorio como enviado
     * PUT /api/seguimientos/{id}/recordatorio
     */
    public function marcarRecordatorio($id)
    {
        try {
            $seguimiento = Seguimiento::findOrFail($id);
            $seguimiento->recordatorio_enviado = true;
            $seguimiento->save();

            return response()->json([
                'success' => true,
                'message' => 'Recordatorio marcado como enviado',
                'data' => $seguimiento
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar el recordatorio',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}