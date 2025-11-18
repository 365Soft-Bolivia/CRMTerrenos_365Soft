<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAutoReply;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WhatsappAutoReplyController extends Controller
{
    /**
     * Listar todas las respuestas automáticas
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = WhatsappAutoReply::query();

            // Filtro por estado activo
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filtro por tipo de saludo
            if ($request->has('is_greeting')) {
                $query->where('is_greeting', $request->boolean('is_greeting'));
            }

            // Ordenar por prioridad
            $autoReplies = $query->orderByPriority('desc')->get();

            return response()->json([
                'success' => true,
                'data' => $autoReplies,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las respuestas automáticas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear una nueva respuesta automática
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'trigger_keyword' => 'nullable|string|max:255',
                'reply_message' => 'required|string',
                'is_active' => 'nullable|boolean',
                'is_greeting' => 'nullable|boolean',
                'priority' => 'nullable|integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validar que si es saludo, no debe tener keyword
            if ($request->boolean('is_greeting') && !empty($request->trigger_keyword)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un mensaje de saludo no debe tener palabra clave',
                ], 422);
            }

            // Validar que si no es saludo, debe tener keyword
            if (!$request->boolean('is_greeting') && empty($request->trigger_keyword)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe especificar una palabra clave para respuestas automáticas',
                ], 422);
            }

            $autoReply = WhatsappAutoReply::create([
                'trigger_keyword' => $request->trigger_keyword,
                'reply_message' => $request->reply_message,
                'is_active' => $request->is_active ?? true,
                'is_greeting' => $request->is_greeting ?? false,
                'priority' => $request->priority ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Respuesta automática creada exitosamente',
                'data' => $autoReply,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la respuesta automática',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar una respuesta automática específica
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $autoReply = WhatsappAutoReply::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $autoReply,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Respuesta automática no encontrada',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Actualizar una respuesta automática
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $autoReply = WhatsappAutoReply::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'trigger_keyword' => 'nullable|string|max:255',
                'reply_message' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'is_greeting' => 'nullable|boolean',
                'priority' => 'nullable|integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validaciones de lógica de negocio
            $isGreeting = $request->has('is_greeting') ? $request->boolean('is_greeting') : $autoReply->is_greeting;
            $keyword = $request->has('trigger_keyword') ? $request->trigger_keyword : $autoReply->trigger_keyword;

            if ($isGreeting && !empty($keyword)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un mensaje de saludo no debe tener palabra clave',
                ], 422);
            }

            if (!$isGreeting && empty($keyword)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe especificar una palabra clave para respuestas automáticas',
                ], 422);
            }

            $autoReply->update($request->only([
                'trigger_keyword',
                'reply_message',
                'is_active',
                'is_greeting',
                'priority',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Respuesta automática actualizada exitosamente',
                'data' => $autoReply->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la respuesta automática',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar una respuesta automática
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $autoReply = WhatsappAutoReply::findOrFail($id);
            $autoReply->delete();

            return response()->json([
                'success' => true,
                'message' => 'Respuesta automática eliminada exitosamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la respuesta automática',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activar una respuesta automática
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $autoReply = WhatsappAutoReply::findOrFail($id);
            $autoReply->activate();

            return response()->json([
                'success' => true,
                'message' => 'Respuesta automática activada exitosamente',
                'data' => $autoReply->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al activar la respuesta automática',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Desactivar una respuesta automática
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $autoReply = WhatsappAutoReply::findOrFail($id);
            $autoReply->deactivate();

            return response()->json([
                'success' => true,
                'message' => 'Respuesta automática desactivada exitosamente',
                'data' => $autoReply->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar la respuesta automática',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar prioridad de una respuesta automática
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changePriority(Request $request, int $id): JsonResponse
    {
        try {
            $autoReply = WhatsappAutoReply::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'priority' => 'required|integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $autoReply->changePriority($request->priority);

            return response()->json([
                'success' => true,
                'message' => 'Prioridad actualizada exitosamente',
                'data' => $autoReply->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar la prioridad',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener respuestas automáticas activas
     * 
     * @return JsonResponse
     */
    public function active(): JsonResponse
    {
        try {
            $autoReplies = WhatsappAutoReply::active()
                ->orderByPriority('desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $autoReplies,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener respuestas activas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener mensaje de saludo
     * 
     * @return JsonResponse
     */
    public function greeting(): JsonResponse
    {
        try {
            $greeting = WhatsappAutoReply::getGreeting();

            if (!$greeting) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay mensaje de saludo configurado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $greeting,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el mensaje de saludo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buscar respuesta automática por mensaje
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function findMatch(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $autoReply = WhatsappAutoReply::findMatchingReply($request->message);

            if (!$autoReply) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró respuesta automática para este mensaje',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $autoReply,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar respuesta automática',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener todas las palabras clave activas
     * 
     * @return JsonResponse
     */
    public function keywords(): JsonResponse
    {
        try {
            $keywords = WhatsappAutoReply::getAllKeywords();

            return response()->json([
                'success' => true,
                'data' => $keywords,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las palabras clave',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activar/Desactivar múltiples respuestas automáticas
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkToggle(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:whatsapp_auto_replies,id',
                'is_active' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            WhatsappAutoReply::whereIn('id', $request->ids)
                ->update(['is_active' => $request->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Respuestas automáticas actualizadas exitosamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar respuestas automáticas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}