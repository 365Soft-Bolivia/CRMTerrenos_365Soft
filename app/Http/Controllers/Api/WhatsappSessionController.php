<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WhatsappSessionController extends Controller
{
    /**
     * Listar todas las sesiones de WhatsApp
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $sessions = WhatsappSession::with('agent')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sessions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sesiones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear una nueva sesión de WhatsApp
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string|unique:whatsapp_sessions,session_id',
                'phone_number' => 'nullable|string|max:20',
                'agent_id' => 'nullable|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $session = WhatsappSession::create([
                'session_id' => $request->session_id,
                'phone_number' => $request->phone_number,
                'agent_id' => $request->agent_id ?? auth()->id(),
                'status' => WhatsappSession::STATUS_DISCONNECTED,
            ]);

            $session->load('agent');

            return response()->json([
                'success' => true,
                'message' => 'Sesión creada exitosamente',
                'data' => $session,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la sesión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar una sesión específica
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $session = WhatsappSession::with('agent')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $session,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no encontrada',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Actualizar una sesión
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $session = WhatsappSession::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'phone_number' => 'nullable|string|max:20',
                'agent_id' => 'nullable|exists:users,id',
                'status' => 'nullable|in:disconnected,connecting,connected,qr_ready',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $session->update($request->only([
                'phone_number',
                'agent_id',
                'status',
            ]));

            $session->load('agent');

            return response()->json([
                'success' => true,
                'message' => 'Sesión actualizada exitosamente',
                'data' => $session,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la sesión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar una sesión
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $session = WhatsappSession::findOrFail($id);
            $session->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión eliminada exitosamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la sesión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar QR Code de una sesión
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateQR(Request $request, int $id): JsonResponse
    {
        try {
            $session = WhatsappSession::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'qr_code' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $session->update([
                'qr_code' => $request->qr_code,
                'status' => WhatsappSession::STATUS_QR_READY,
                'last_activity' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'QR Code actualizado exitosamente',
                'data' => $session,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el QR Code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar estado de una sesión
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        try {
            $session = WhatsappSession::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:disconnected,connecting,connected,qr_ready',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $session->changeStatus($request->status);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => $session->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener sesiones activas
     * 
     * @return JsonResponse
     */
    public function active(): JsonResponse
    {
        try {
            $sessions = WhatsappSession::active()
                ->with('agent')
                ->orderBy('last_activity', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sessions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sesiones activas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener sesión con QR disponible
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getQR(int $id): JsonResponse
    {
        try {
            $session = WhatsappSession::findOrFail($id);

            if (!$session->hasQR()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay QR disponible para esta sesión',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => $session->qr_code,
                    'status' => $session->status,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el QR Code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}