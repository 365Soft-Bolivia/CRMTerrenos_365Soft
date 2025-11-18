<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_phone',
        'contact_name',
        'contact_profile_pic',
        'lead_id',
        'assigned_agent_id',
        'status',
        'unread',
        'unread_count',
        'last_message_at',
    ];

    protected $casts = [
        'unread' => 'boolean',
        'unread_count' => 'integer',
        'last_message_at' => 'datetime',
    ];

    /**
     * Estados posibles de una conversación
     */
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Relación: Una conversación pertenece a un lead
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Relación: Una conversación está asignada a un agente (usuario)
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    /**
     * Relación: Una conversación tiene muchos mensajes
     */
    public function messages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class, 'conversation_id');
    }

    /**
     * Relación: Último mensaje de la conversación
     */
    public function lastMessage()
    {
        return $this->hasOne(WhatsappMessage::class, 'conversation_id')
            ->latestOfMany('sent_at');
    }

    /**
     * Scope: Conversaciones abiertas
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope: Conversaciones cerradas
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope: Conversaciones archivadas
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Scope: Conversaciones con mensajes sin leer
     */
    public function scopeUnread($query)
    {
        return $query->where('unread', true)->where('unread_count', '>', 0);
    }

    /**
     * Scope: Conversaciones asignadas a un agente específico
     */
    public function scopeAssignedTo($query, $agentId)
    {
        return $query->where('assigned_agent_id', $agentId);
    }

    /**
     * Scope: Ordenar por actividad reciente
     */
    public function scopeRecentActivity($query)
    {
        return $query->orderBy('last_message_at', 'desc');
    }

    /**
     * Marcar conversación como leída
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'unread' => false,
            'unread_count' => 0,
        ]);
    }

    /**
     * Incrementar contador de mensajes sin leer
     */
    public function incrementUnreadCount(): bool
    {
        return $this->update([
            'unread' => true,
            'unread_count' => $this->unread_count + 1,
        ]);
    }

    /**
     * Actualizar timestamp del último mensaje
     */
    public function updateLastMessageTime(): bool
    {
        return $this->update(['last_message_at' => now()]);
    }

    /**
     * Cerrar conversación
     */
    public function close(): bool
    {
        return $this->update(['status' => self::STATUS_CLOSED]);
    }

    /**
     * Archivar conversación
     */
    public function archive(): bool
    {
        return $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * Reabrir conversación
     */
    public function reopen(): bool
    {
        return $this->update(['status' => self::STATUS_OPEN]);
    }

    /**
     * Asignar conversación a un agente
     */
    public function assignTo(int $agentId): bool
    {
        return $this->update(['assigned_agent_id' => $agentId]);
    }

    /**
     * Verificar si la conversación está abierta
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Verificar si tiene mensajes sin leer
     */
    public function hasUnreadMessages(): bool
    {
        return $this->unread && $this->unread_count > 0;
    }
}