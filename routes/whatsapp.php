<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsappSessionController;
use App\Http\Controllers\Api\WhatsappConversationController;
use App\Http\Controllers\Api\WhatsappMessageController;
use App\Http\Controllers\Api\WhatsappAutoReplyController;

/*
|--------------------------------------------------------------------------
| WhatsApp Routes
|--------------------------------------------------------------------------
|
| Rutas para el módulo de WhatsApp Web
|
*/

Route::middleware(['web', 'auth'])->prefix('whatsapp')->group(function () {

    // ==================== SESIONES ====================
    Route::prefix('sessions')->group(function () {
        Route::get('/', [WhatsappSessionController::class, 'index'])->name('whatsapp.sessions.index');
        Route::post('/', [WhatsappSessionController::class, 'store'])->name('whatsapp.sessions.store');
        Route::get('/active', [WhatsappSessionController::class, 'active'])->name('whatsapp.sessions.active');
        Route::get('/{id}', [WhatsappSessionController::class, 'show'])->name('whatsapp.sessions.show');
        Route::put('/{id}', [WhatsappSessionController::class, 'update'])->name('whatsapp.sessions.update');
        Route::delete('/{id}', [WhatsappSessionController::class, 'destroy'])->name('whatsapp.sessions.destroy');
        
        // Rutas especiales de sesiones
        Route::post('/{id}/qr', [WhatsappSessionController::class, 'updateQR'])->name('whatsapp.sessions.updateQR');
        Route::get('/{id}/qr', [WhatsappSessionController::class, 'getQR'])->name('whatsapp.sessions.getQR');
        Route::post('/{id}/status', [WhatsappSessionController::class, 'changeStatus'])->name('whatsapp.sessions.changeStatus');
    });

    // ==================== CONVERSACIONES ====================
    Route::prefix('conversations')->group(function () {
        Route::get('/', [WhatsappConversationController::class, 'index'])->name('whatsapp.conversations.index');
        Route::post('/', [WhatsappConversationController::class, 'store'])->name('whatsapp.conversations.store');
        Route::get('/unread', [WhatsappConversationController::class, 'unread'])->name('whatsapp.conversations.unread');
        Route::post('/search', [WhatsappConversationController::class, 'searchByPhone'])->name('whatsapp.conversations.search');
        Route::get('/{id}', [WhatsappConversationController::class, 'show'])->name('whatsapp.conversations.show');
        Route::put('/{id}', [WhatsappConversationController::class, 'update'])->name('whatsapp.conversations.update');
        Route::delete('/{id}', [WhatsappConversationController::class, 'destroy'])->name('whatsapp.conversations.destroy');
        
        // Rutas especiales de conversaciones
        Route::post('/{id}/mark-read', [WhatsappConversationController::class, 'markAsRead'])->name('whatsapp.conversations.markAsRead');
        Route::post('/{id}/close', [WhatsappConversationController::class, 'close'])->name('whatsapp.conversations.close');
        Route::post('/{id}/archive', [WhatsappConversationController::class, 'archive'])->name('whatsapp.conversations.archive');
        Route::post('/{id}/reopen', [WhatsappConversationController::class, 'reopen'])->name('whatsapp.conversations.reopen');
        Route::post('/{id}/assign', [WhatsappConversationController::class, 'assign'])->name('whatsapp.conversations.assign');
        Route::post('/{id}/link-lead', [WhatsappConversationController::class, 'linkToLead'])->name('whatsapp.conversations.linkLead');
    });

    // ==================== MENSAJES ====================
    Route::prefix('messages')->group(function () {
        Route::get('/', [WhatsappMessageController::class, 'index'])->name('whatsapp.messages.index');
        Route::post('/', [WhatsappMessageController::class, 'store'])->name('whatsapp.messages.store');
        Route::get('/{id}', [WhatsappMessageController::class, 'show'])->name('whatsapp.messages.show');
        Route::delete('/{id}', [WhatsappMessageController::class, 'destroy'])->name('whatsapp.messages.destroy');
        
        // Rutas especiales de mensajes
        Route::post('/{id}/status', [WhatsappMessageController::class, 'updateStatus'])->name('whatsapp.messages.updateStatus');
        Route::post('/mark-read', [WhatsappMessageController::class, 'markAsRead'])->name('whatsapp.messages.markAsRead');
        Route::get('/conversation/{conversationId}', [WhatsappMessageController::class, 'byConversation'])->name('whatsapp.messages.byConversation');
        Route::get('/conversation/{conversationId}/media', [WhatsappMessageController::class, 'media'])->name('whatsapp.messages.media');
    });

    // ==================== RESPUESTAS AUTOMÁTICAS ====================
    Route::prefix('auto-replies')->group(function () {
        Route::get('/', [WhatsappAutoReplyController::class, 'index'])->name('whatsapp.autoReplies.index');
        Route::post('/', [WhatsappAutoReplyController::class, 'store'])->name('whatsapp.autoReplies.store');
        Route::get('/active', [WhatsappAutoReplyController::class, 'active'])->name('whatsapp.autoReplies.active');
        Route::get('/greeting', [WhatsappAutoReplyController::class, 'greeting'])->name('whatsapp.autoReplies.greeting');
        Route::get('/keywords', [WhatsappAutoReplyController::class, 'keywords'])->name('whatsapp.autoReplies.keywords');
        Route::post('/find-match', [WhatsappAutoReplyController::class, 'findMatch'])->name('whatsapp.autoReplies.findMatch');
        Route::post('/bulk-toggle', [WhatsappAutoReplyController::class, 'bulkToggle'])->name('whatsapp.autoReplies.bulkToggle');
        Route::get('/{id}', [WhatsappAutoReplyController::class, 'show'])->name('whatsapp.autoReplies.show');
        Route::put('/{id}', [WhatsappAutoReplyController::class, 'update'])->name('whatsapp.autoReplies.update');
        Route::delete('/{id}', [WhatsappAutoReplyController::class, 'destroy'])->name('whatsapp.autoReplies.destroy');
        
        // Rutas especiales de respuestas automáticas
        Route::post('/{id}/activate', [WhatsappAutoReplyController::class, 'activate'])->name('whatsapp.autoReplies.activate');
        Route::post('/{id}/deactivate', [WhatsappAutoReplyController::class, 'deactivate'])->name('whatsapp.autoReplies.deactivate');
        Route::post('/{id}/priority', [WhatsappAutoReplyController::class, 'changePriority'])->name('whatsapp.autoReplies.changePriority');
    });
});

// ==================== WEBHOOK (SIN AUTENTICACIÓN) ====================
// Ruta para recibir mensajes desde whatsapp-web.js (Node.js)
Route::post('whatsapp/webhook/message', [WhatsappMessageController::class, 'receive'])
    ->name('whatsapp.webhook.message');

// Ruta para sincronizar chats existentes desde WhatsApp
Route::post('whatsapp/sync-chats', [WhatsappConversationController::class, 'syncChats'])
    ->name('whatsapp.webhook.syncChats');

// Ruta para limpiar chats al desconectar
Route::post('whatsapp/clear-chats', [WhatsappConversationController::class, 'clearChats'])
    ->name('whatsapp.webhook.clearChats');