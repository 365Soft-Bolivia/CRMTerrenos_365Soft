<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'phone_number',
        'qr_code',
        'status',
        'agent_id',
        'last_activity',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
    ];

    /**
     * Estados posibles de una sesión
     */
    const STATUS_DISCONNECTED = 'disconnected';
    const STATUS_CONNECTING = 'connecting';
    const STATUS_CONNECTED = 'connected';
    const STATUS_QR_READY = 'qr_ready';

    /**
     * Relación: Una sesión pertenece a un agente (usuario)
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Scope: Sesiones activas (conectadas)
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_CONNECTED);
    }

    /**
     * Scope: Sesiones desconectadas
     */
    public function scopeDisconnected($query)
    {
        return $query->where('status', self::STATUS_DISCONNECTED);
    }

    /**
     * Scope: Sesiones esperando escaneo de QR
     */
    public function scopeWaitingQR($query)
    {
        return $query->where('status', self::STATUS_QR_READY);
    }

    /**
     * Verificar si la sesión está conectada
     */
    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED;
    }

    /**
     * Verificar si tiene QR disponible
     */
    public function hasQR(): bool
    {
        return $this->status === self::STATUS_QR_READY && !empty($this->qr_code);
    }

    /**
     * Actualizar última actividad
     */
    public function updateActivity(): bool
    {
        return $this->update(['last_activity' => now()]);
    }

    /**
     * Cambiar estado de la sesión
     */
    public function changeStatus(string $status): bool
    {
        if (in_array($status, [
            self::STATUS_DISCONNECTED,
            self::STATUS_CONNECTING,
            self::STATUS_CONNECTED,
            self::STATUS_QR_READY
        ])) {
            return $this->update([
                'status' => $status,
                'last_activity' => now()
            ]);
        }
        
        return false;
    }
}