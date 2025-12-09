<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Embudo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmbudoController extends Controller
{
    /**
     * Listar todos los embudos
     * GET /api/embudos
     */
    public function index(Request $request)
    {
        try {
            $query = Embudo::query();

            // Filtrar solo activos si se solicita
            if ($request->has('solo_activos') && $request->solo_activos) {
                $query->activos();
            }

            // Ordenar por campo 'orden'
            $embudos = $query->ordenado()->get();

            return response()->json([
                'success' => true,
                'data' => $embudos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los embudos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un embudo específico
     * GET /api/embudos/{id}
     */
    public function show($id)
    {
        try {
            $embudo = Embudo::findOrFail($id);
            
            // Contar negocios que tienen esta etapa (por nombre)
            $negociosCount = \App\Models\Negocio::where('etapa', $embudo->nombre)->count();
            
            // Agregar el conteo al objeto
            $embudoData = $embudo->toArray();
            $embudoData['negocios_count'] = $negociosCount;

            return response()->json([
                'success' => true,
                'data' => $embudoData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Embudo no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Crear un nuevo embudo
     * POST /api/embudos
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:embudos,nombre',
                'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'icono' => 'nullable|string|max:50',
                'orden' => 'nullable|integer|min:0',
                'activo' => 'nullable|boolean',
                'descripcion' => 'nullable|string|max:500',
            ], [
                'nombre.required' => 'El nombre del embudo es requerido',
                'nombre.unique' => 'Ya existe un embudo con ese nombre',
                'color.required' => 'El color es requerido',
                'color.regex' => 'El color debe estar en formato hexadecimal (#RRGGBB)',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Si no se proporciona orden, asignar el siguiente disponible
            $data = $validator->validated();
            if (!isset($data['orden'])) {
                $data['orden'] = Embudo::max('orden') + 1;
            }

            $embudo = Embudo::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Embudo creado correctamente',
                'data' => $embudo
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el embudo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un embudo existente
     * PUT /api/embudos/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $embudo = Embudo::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nombre' => 'sometimes|required|string|max:255|unique:embudos,nombre,' . $id,
                'color' => 'sometimes|required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'icono' => 'nullable|string|max:50',
                'orden' => 'nullable|integer|min:0',
                'activo' => 'nullable|boolean',
                'descripcion' => 'nullable|string|max:500',
            ], [
                'nombre.unique' => 'Ya existe un embudo con ese nombre',
                'color.regex' => 'El color debe estar en formato hexadecimal (#RRGGBB)',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $embudo->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Embudo actualizado correctamente',
                'data' => $embudo->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el embudo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un embudo
     * DELETE /api/embudos/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            $embudo = Embudo::findOrFail($id);
            
            // Verificar si se fuerza la eliminación (query param o input)
            $forceDelete = $request->query('force') === 'true' || $request->input('force') === true;

            // Verificar si tiene negocios asociados (por nombre de etapa)
            $negociosCount = \App\Models\Negocio::where('etapa', $embudo->nombre)->count();
            
            // Si tiene negocios y no se fuerza la eliminación, retornar error
            if ($negociosCount > 0 && !$forceDelete) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede eliminar la etapa \"{$embudo->nombre}\" porque tiene {$negociosCount} negocio(s) en el tablero. Primero mueve los negocios a otra etapa.",
                    'negocios_count' => $negociosCount
                ], 400);
            }
            
            // Si se fuerza la eliminación, eliminar también los negocios de esta etapa
            if ($negociosCount > 0 && $forceDelete) {
                \App\Models\Negocio::where('etapa', $embudo->nombre)->delete();
            }

            $embudo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Embudo eliminado correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el embudo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reordenar embudos
     * POST /api/embudos/reordenar
     */
    public function reordenar(Request $request)
    {
        try {
            // Aceptar tanto 'orden' (array de IDs) como 'embudos' (array de objetos con id y orden)
            if ($request->has('embudos')) {
                $validator = Validator::make($request->all(), [
                    'embudos' => 'required|array',
                    'embudos.*.id' => 'required|integer|exists:embudos,id',
                    'embudos.*.orden' => 'required|integer|min:1',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error de validación',
                        'errors' => $validator->errors()
                    ], 422);
                }

                foreach ($request->embudos as $item) {
                    Embudo::where('id', $item['id'])->update(['orden' => $item['orden']]);
                }
            } else {
                $validator = Validator::make($request->all(), [
                    'orden' => 'required|array',
                    'orden.*' => 'integer|exists:embudos,id',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error de validación',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $ordenIds = $request->orden;

                foreach ($ordenIds as $index => $embudoId) {
                    Embudo::where('id', $embudoId)->update(['orden' => $index + 1]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Orden actualizado correctamente',
                'data' => Embudo::ordenado()->get()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reordenar los embudos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
