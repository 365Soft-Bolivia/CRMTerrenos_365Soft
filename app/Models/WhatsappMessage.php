<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'message_id',
        'type',
        'content',
        'media_url',
        'media_mime_type',
        'direction',
        'from_me',
        'sender_phone',
        'sender_name',
        'sent_by_agent_id',
        'status',
        'is_auto_reply',
        'sent_at',
    ];

    protected $casts = [
        'from_me' => 'boolean',
        'is_auto_reply' => 'boolean',
        'sent_at' => 'datetime',
    ];

    /**
     * Tipos de mensaje
     */
    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    const TYPE_DOCUMENT = 'document';
    const TYPE_STICKER = 'sticker';
    const TYPE_LOCATION = 'location';
    const TYPE_CONTACT = 'contact';

    /**
     * Direcciones del mensaje
     */
    const DIRECTION_INCOMING = 'incoming';
    const DIRECTION_OUTGOING = 'outgoing';

    /**
     * Estados del mensaje
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';
    const STATUS_FAILED = 'failed';

    /**
     * Relación: Un mensaje pertenece a una conversación
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }

    /**
     * Relación: Un mensaje puede ser enviado por un agente
     */
    public function sentByAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_agent_id');
    }

    /**
     * Scope: Mensajes entrantes
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', self::DIRECTION_INCOMING);
    }

    /**
     * Scope: Mensajes salientes
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direction', self::DIRECTION_OUTGOING);
    }

    /**
     * Scope: Mensajes de texto
     */
    public function scopeTextOnly($query)
    {
        return $query->where('type', self::TYPE_TEXT);
    }

    /**
     * Scope: Mensajes con medios (imágenes, videos, etc.)
     */
    public function scopeWithMedia($query)
    {
        return $query->whereIn('type', [
            self::TYPE_IMAGE,
            self::TYPE_VIDEO,
            self::TYPE_AUDIO,
            self::TYPE_DOCUMENT,
        ]);
    }

    /**
     * Scope: Respuestas automáticas
     */
    public function scopeAutoReplies($query)
    {
        return $query->where('is_auto_reply', true);
    }

    /**
     * Scope: Mensajes enviados por agentes
     */
    public function scopeSentByAgents($query)
    {
        return $query->whereNotNull('sent_by_agent_id');
    }

    /**
     * Scope: Ordenar por fecha de envío
     */
    public function scopeOrderBySent($query, $direction = 'asc')
    {
        return $query->orderBy('sent_at', $direction);
    }

    /**
     * Scope: Mensajes de una conversación específica
     */
    public function scopeForConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * Verificar si el mensaje es entrante
     */
    public function isIncoming(): bool
    {
        return $this->direction === self::DIRECTION_INCOMING;
    }

    /**
     * Verificar si el mensaje es saliente
     */
    public function isOutgoing(): bool
    {
        return $this->direction === self::DIRECTION_OUTGOING;
    }

    /**
     * Verificar si el mensaje contiene media
     */
    public function hasMedia(): bool
    {
        return in_array($this->type, [
            self::TYPE_IMAGE,
            self::TYPE_VIDEO,
            self::TYPE_AUDIO,
            self::TYPE_DOCUMENT,
        ]) && !empty($this->media_url);
    }

    /**
     * Verificar si es respuesta automática
     */
    public function isAutoReply(): bool
    {
        return $this->is_auto_reply === true;
    }

    /**
     * Verificar si fue enviado por un agente
     */
    public function wasSentByAgent(): bool
    {
        return $this->sent_by_agent_id !== null;
    }

    /**
     * Marcar mensaje como enviado
     */
    public function markAsSent(): bool
    {
        return $this->update(['status' => self::STATUS_SENT]);
    }

    /**
     * Marcar mensaje como entregado
     */
    public function markAsDelivered(): bool
    {
        return $this->update(['status' => self::STATUS_DELIVERED]);
    }

    /**
     * Marcar mensaje como leído
     */
    public function markAsRead(): bool
    {
        return $this->update(['status' => self::STATUS_READ]);
    }

    /**
     * Marcar mensaje como fallido
     */
    public function markAsFailed(): bool
    {
        return $this->update(['status' => self::STATUS_FAILED]);
    }

   /**
     * Obtener ícono según el tipo de mensaje
     */
    public function getTypeIcon(): string
    {
        switch ($this->type) {
            case self::TYPE_IMAGE:
                return '🖼️';
            case self::TYPE_VIDEO:
                return '🎥';
            case self::TYPE_AUDIO:
                return '🎵';
            case self::TYPE_DOCUMENT:
                return '📄';
            case self::TYPE_STICKER:
                return '😊';
            case self::TYPE_LOCATION:
                return '📍';
            case self::TYPE_CONTACT:
                return '👤';
            default:
                return '💬';
        }
    }
}