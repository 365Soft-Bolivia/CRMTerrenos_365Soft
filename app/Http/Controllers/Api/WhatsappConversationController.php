<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\WhatsappConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhatsappConversationController extends Controller
{
    /**
     * Listar todas las conversaciones
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = WhatsappConversation::with(['lead', 'assignedAgent', 'lastMessage']);

            // Filtros opcionales
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('assigned_agent_id')) {
                $query->where('assigned_agent_id', $request->assigned_agent_id);
            }

            if ($request->has('unread')) {
                $query->where('unread', true);
            }

            // Ordenar por actividad reciente
            $conversations = $query->recentActivity()->get();

            return response()->json([
                'success' => true,
                'data' => $conversations,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las conversaciones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear una nueva conversación
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_phone' => 'required|string|max:20',
                'contact_name' => 'nullable|string|max:255',
                'contact_profile_pic' => 'nullable|string',
                'lead_id' => 'nullable|exists:leads,id',
                'assigned_agent_id' => 'nullable|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Verificar si ya existe una conversación con este contacto
            $existingConversation = WhatsappConversation::where('contact_phone', $request->contact_phone)
                ->first();

            if ($existingConversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una conversación con este contacto',
                    'data' => $existingConversation->load(['lead', 'assignedAgent', 'lastMessage']),
                ], 409);
            }

            $conversation = WhatsappConversation::create([
                'contact_phone' => $request->contact_phone,
                'contact_name' => $request->contact_name,
                'contact_profile_pic' => $request->contact_profile_pic,
                'lead_id' => $request->lead_id,
                'assigned_agent_id' => $request->assigned_agent_id ?? auth()->id(),
                'status' => WhatsappConversation::STATUS_OPEN,
            ]);

            $conversation->load(['lead', 'assignedAgent', 'lastMessage']);

            return response()->json([
                'success' => true,
                'message' => 'Conversación creada exitosamente',
                'data' => $conversation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la conversación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar una conversación específica con sus mensajes
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $conversation = WhatsappConversation::with([
                'lead',
                'assignedAgent',
                'messages' => function ($query) {
                    $query->orderBy('sent_at', 'asc');
                },
                'messages.sentByAgent'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $conversation,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Conversación no encontrada',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Actualizar una conversación
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $conversation = WhatsappConversation::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'contact_name' => 'nullable|string|max:255',
                'contact_profile_pic' => 'nullable|string',
                'lead_id' => 'nullable|exists:leads,id',
                'assigned_agent_id' => 'nullable|exists:users,id',
                'status' => 'nullable|in:open,closed,archived',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $conversation->update($request->only([
                'contact_name',
                'contact_profile_pic',
                'lead_id',
                'assigned_agent_id',
                'status',
            ]));

            $conversation->load(['lead', 'assignedAgent', 'lastMessage']);

            return response()->json([
                'success' => true,
                'message' => 'Conversación actualizada exitosamente',
                'data' => $conversation,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la conversación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar una conversación
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $conversation = WhatsappConversation::findOrFail($id);
            $conversation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conversación eliminada exitosamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la conversación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marcar conversación como leída
     *
     * @param int $id
     * @return JsonResponse
     */
    public function markAsRead(int $id): JsonResponse
    {
        try {
            $conversation = WhatsappConversation::findOrFail($id);
            $conversation->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Conversación marcada como leída',
                'data' => $conversation->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como leída',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cerrar conversación
     *
     * @param int $id
     * @return JsonResponse
     */
    public function close(int $id): JsonResponse
    {
        try {
            $conversation = WhatsappConversation::findOrFail($id);
            $conversation->close();

            return response()->json([
                'success' => true,
                'message' => 'Conversación cerrada exitosamente',
                'data' => $conversation->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar la conversación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archivar conversación
     *
     * @param int $id
     * @return JsonResponse
     */
    public function archive(int $id): JsonResponse
    {
        try {
            $conversation = WhatsappConversation::findOrFail($id);
            $conversation->archive();

            return response()->json([
                'success' => true,
                'message' => 'Conversación archivada exitosamente',
                'data' => $conversation->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al archivar la conversación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reabrir conversación
     *
     * @param int $id
     * @return JsonResponse
     */
    public function reopen(int $id): JsonResponse
    {
        try {
            $conversation = WhatsappConversation::findOrFail($id);
            $conversation->reopen();

            return response()->json([
                'success' => true,
                'message' => 'Conversación reabierta exitosamente',
                'data' => $conversation->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reabrir la conversación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Asignar conversación a un agente
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        try {
            $conversation = WhatsappConversation::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'agent_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $conversation->assignTo($request->agent_id);

            return response()->json([
                'success' => true,
                'message' => 'Conversación asignada exitosamente',
                'data' => $conversation->fresh(['assignedAgent']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar la conversación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Asociar conversación con un lead
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function linkToLead(Request $request, int $id): JsonResponse
    {
        try {
            $conversation = WhatsappConversation::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'lead_id' => 'required|exists:leads,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $conversation->update(['lead_id' => $request->lead_id]);

            return response()->json([
                'success' => true,
                'message' => 'Conversación vinculada al lead exitosamente',
                'data' => $conversation->fresh(['lead']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al vincular con el lead',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener conversaciones sin leer
     *
     * @return JsonResponse
     */
    public function unread(): JsonResponse
    {
        try {
            $conversations = WhatsappConversation::unread()
                ->with(['lead', 'assignedAgent', 'lastMessage'])
                ->recentActivity()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $conversations,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener conversaciones sin leer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buscar conversación por número de teléfono
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchByPhone(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $conversation = WhatsappConversation::where('contact_phone', $request->phone)
                ->with(['lead', 'assignedAgent', 'lastMessage'])
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró conversación con ese número',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $conversation,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar la conversación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sincronizar chats existentes desde WhatsApp
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncChats(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_phone' => 'required|string|min:8|max:20',
                'contact_name' => 'required|string|max:255',
                'last_message_at' => 'nullable|date',
                'unread_count' => 'nullable|integer|min:0',
                'status' => 'nullable|in:active,archived,blocked'
            ]);

            if ($validator->fails()) {
                // No loguear todos los errores de validación, solo contarlos
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // ✅ Limpiar y validar el número de teléfono
            $cleanPhone = preg_replace('/[^0-9]/', '', $validated['contact_phone']);

            if (strlen($cleanPhone) < 8 || strlen($cleanPhone) > 20) {
                return response()->json([
                    'success' => false,
                    'message' => 'Número de teléfono inválido',
                ], 422);
            }

            // Buscar o crear conversación
            $conversation = WhatsappConversation::updateOrCreate(
                ['contact_phone' => $validated['contact_phone']],
                [
                    'contact_name' => $validated['contact_name'],
                    'last_message_at' => $validated['last_message_at'] ?? now(),
                    'unread_count' => $validated['unread_count'] ?? 0,
                    'status' => $validated['status'] ?? WhatsappConversation::STATUS_OPEN,
                    'unread' => ($validated['unread_count'] ?? 0) > 0
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Chat sincronizado exitosamente',
                'data' => $conversation,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('❌ Error al sincronizar chat:', [
                'message' => $e->getMessage(),
                'phone' => $request->input('contact_phone')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar el chat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Limpiar todos los chats y mensajes al desconectar WhatsApp
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearChats(Request $request): JsonResponse
    {
        try {
            \Log::info('🧹 Limpiando chats al desconectar...');

            // Contar antes de eliminar
            $conversationsCount = WhatsappConversation::count();
            $messagesCount = WhatsappMessage::count();

            // Eliminar todos los mensajes
            WhatsappMessage::query()->delete();

            // Eliminar todas las conversaciones
            WhatsappConversation::query()->delete();

            \Log::info('✅ Chats limpiados:', [
                'conversations_deleted' => $conversationsCount,
                'messages_deleted' => $messagesCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chats limpiados exitosamente',
                'data' => [
                    'conversations_deleted' => $conversationsCount,
                    'messages_deleted' => $messagesCount
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('❌ Error al limpiar chats:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar los chats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
