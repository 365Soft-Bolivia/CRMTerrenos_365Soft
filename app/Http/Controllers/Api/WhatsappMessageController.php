<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAutoReply;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WhatsappMessageController extends Controller
{
    /**
     * Listar todos los mensajes
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = WhatsappMessage::with(['conversation', 'sentByAgent']);

            // Filtro por conversación
            if ($request->has('conversation_id')) {
                $query->where('conversation_id', $request->conversation_id);
            }

            // Filtro por dirección
            if ($request->has('direction')) {
                $query->where('direction', $request->direction);
            }

            // Filtro por tipo
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Ordenar por fecha de envío
            $messages = $query->orderBySent('asc')->get();

            return response()->json([
                'success' => true,
                'data' => $messages,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mensajes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo mensaje (enviar mensaje)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'conversation_id' => 'required|exists:whatsapp_conversations,id',
                'content' => 'required|string',
                'type' => 'nullable|in:text,image,video,audio,document,sticker,location,contact',
                'media_url' => 'nullable|string',
                'media_mime_type' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Obtener conversación
            $conversation = WhatsappConversation::findOrFail($request->conversation_id);

            // Crear mensaje
            $message = WhatsappMessage::create([
                'conversation_id' => $request->conversation_id,
                'message_id' => 'msg_' . Str::random(20),
                'type' => $request->type ?? WhatsappMessage::TYPE_TEXT,
                'content' => $request->content,
                'media_url' => $request->media_url,
                'media_mime_type' => $request->media_mime_type,
                'direction' => WhatsappMessage::DIRECTION_OUTGOING,
                'from_me' => true,
                'sender_phone' => $conversation->contact_phone,
                'sent_by_agent_id' => auth()->id(),
                'status' => WhatsappMessage::STATUS_PENDING,
                'sent_at' => now(),
            ]);

            // Actualizar conversación
            $conversation->updateLastMessageTime();
            $conversation->markAsRead();

            $message->load(['conversation', 'sentByAgent']);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado exitosamente',
                'data' => $message,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el mensaje',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recibir un nuevo mensaje (webhook desde whatsapp-web.js)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function receive(Request $request): JsonResponse
    {
        try {
            \Log::info('📩 Webhook recibido:', $request->all());

            // Determinar si viene con archivo o solo JSON
            $hasFile = $request->hasFile('file');

            if ($hasFile) {
                // ✅ MENSAJE CON MEDIA (FormData)
                $validator = Validator::make($request->all(), [
                    'message_id' => 'required|string',
                    'contact_phone' => 'required|string',
                    'contact_name' => 'nullable|string',
                    'content' => 'nullable|string',
                    'type' => 'required|in:text,image,video,audio,document,sticker,location,contact',
                    'sender_phone' => 'nullable|string',
                    'sender_name' => 'nullable|string',
                    'timestamp' => 'nullable|integer',
                    'file' => 'required|file|max:51200'  // Máximo 50MB
                ]);
            } else {
                // ✅ MENSAJE DE TEXTO (JSON)
                $validator = Validator::make($request->all(), [
                    'message_id' => 'required|string',
                    'contact_phone' => 'required|string',
                    'contact_name' => 'nullable|string',
                    'content' => 'required|string',
                    'type' => 'required|in:text,image,video,audio,document,sticker,location,contact',
                    'sender_phone' => 'nullable|string',
                    'sender_name' => 'nullable|string',
                    'timestamp' => 'nullable|integer'
                ]);
            }

            if ($validator->fails()) {
                \Log::error('❌ Validación fallida:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // Verificar si el mensaje ya existe
            $existingMessage = WhatsappMessage::where('message_id', $validated['message_id'])->first();
            if ($existingMessage) {
                \Log::info('⚠️ Mensaje duplicado ignorado:', $validated['message_id']);
                return response()->json([
                    'success' => true,
                    'message' => 'Mensaje ya registrado',
                    'data' => $existingMessage,
                ], 200);
            }

            // Buscar o crear conversación
            $conversation = WhatsappConversation::firstOrCreate(
                ['contact_phone' => $validated['contact_phone']],
                [
                    'contact_name' => $validated['contact_name'] ?? 'Sin nombre',
                    'status' => WhatsappConversation::STATUS_OPEN,
                    'unread' => true,
                    'unread_count' => 0,
                ]
            );

            $mediaUrl = null;
            $mediaMimeType = null;

            // ✅ Procesar archivo si existe
            if ($hasFile) {
                try {
                    $file = $request->file('file');
                    $mediaMimeType = $file->getMimeType();

                    // Guardar archivo en storage/app/public/whatsapp/YYYY/MM/
                    $path = $file->store('whatsapp/' . date('Y/m'), 'public');
                    $mediaUrl = $path;

                    \Log::info('✅ Archivo guardado:', [
                        'path' => $path,
                        'mime' => $mediaMimeType,
                        'size' => $file->getSize()
                    ]);
                } catch (\Exception $e) {
                    \Log::error('❌ Error al guardar archivo:', ['error' => $e->getMessage()]);
                    // Continuar sin el archivo
                }
            }

            // Crear mensaje entrante
            $message = WhatsappMessage::create([
                'conversation_id' => $conversation->id,
                'message_id' => $validated['message_id'],
                'type' => $validated['type'],
                'content' => $validated['content'] ?? null,
                'media_url' => $mediaUrl,
                'media_mime_type' => $mediaMimeType,
                'direction' => WhatsappMessage::DIRECTION_INCOMING,
                'from_me' => false,
                'sender_phone' => $validated['sender_phone'] ?? $validated['contact_phone'],
                'sender_name' => $validated['sender_name'] ?? $validated['contact_name'],
                'status' => WhatsappMessage::STATUS_DELIVERED,
                'sent_at' => now(),
            ]);

            \Log::info('✅ Mensaje creado:', [
                'id' => $message->id,
                'type' => $message->type,
                'has_content' => !empty($message->content),
                'has_media' => !empty($message->media_url)
            ]);

            // Actualizar conversación
            $conversation->incrementUnreadCount();
            $conversation->updateLastMessageTime();

            // ✅ Verificar si debe enviar respuesta automática (solo para mensajes de texto)
            if ($validated['type'] === WhatsappMessage::TYPE_TEXT && !empty($validated['content'])) {
                $autoReply = WhatsappAutoReply::findMatchingReply($validated['content']);

                if ($autoReply) {
                    \Log::info('🤖 Respuesta automática encontrada');
                    // Crear mensaje de respuesta automática
                    $autoReplyMessage = WhatsappMessage::create([
                        'conversation_id' => $conversation->id,
                        'message_id' => 'auto_' . Str::random(20),
                        'type' => WhatsappMessage::TYPE_TEXT,
                        'content' => $autoReply->reply_message,
                        'direction' => WhatsappMessage::DIRECTION_OUTGOING,
                        'from_me' => true,
                        'is_auto_reply' => true,
                        'status' => WhatsappMessage::STATUS_PENDING,
                        'sent_at' => now(),
                    ]);

                    $message->auto_reply = $autoReplyMessage;
                }
            }

            $message->load(['conversation', 'sentByAgent']);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje recibido exitosamente',
                'data' => $message,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('❌ Error al recibir mensaje:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al recibir el mensaje',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un mensaje específico
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $message = WhatsappMessage::with(['conversation', 'sentByAgent'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $message,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mensaje no encontrado',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Actualizar estado de un mensaje
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $message = WhatsappMessage::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,sent,delivered,read,failed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $message->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => $message->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un mensaje
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $message = WhatsappMessage::findOrFail($id);
            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mensaje eliminado exitosamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el mensaje',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener mensajes de una conversación
     *
     * @param int $conversationId
     * @return JsonResponse
     */
    public function byConversation(int $conversationId): JsonResponse
    {
        try {
            $messages = WhatsappMessage::forConversation($conversationId)
                ->with('sentByAgent')
                ->orderBySent('asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $messages,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mensajes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marcar mensajes como leídos
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsRead(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'message_ids' => 'required|array',
                'message_ids.*' => 'exists:whatsapp_messages,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            WhatsappMessage::whereIn('id', $request->message_ids)
                ->update(['status' => WhatsappMessage::STATUS_READ]);

            return response()->json([
                'success' => true,
                'message' => 'Mensajes marcados como leídos',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar mensajes como leídos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener mensajes con media
     *
     * @param int $conversationId
     * @return JsonResponse
     */
    public function media(int $conversationId): JsonResponse
    {
        try {
            $messages = WhatsappMessage::forConversation($conversationId)
                ->withMedia()
                ->orderBySent('desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $messages,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los mensajes con media',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
