<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappAutoReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'trigger_keyword',
        'reply_message',
        'is_active',
        'is_greeting',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_greeting' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Scope: Respuestas automáticas activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Respuestas de saludo
     */
    public function scopeGreetings($query)
    {
        return $query->where('is_greeting', true)->where('is_active', true);
    }

    /**
     * Scope: Respuestas con palabra clave
     */
    public function scopeWithKeyword($query)
    {
        return $query->whereNotNull('trigger_keyword')->where('is_active', true);
    }

    /**
     * Scope: Ordenar por prioridad
     */
    public function scopeOrderByPriority($query, $direction = 'desc')
    {
        return $query->orderBy('priority', $direction);
    }

    /**
     * Verificar si es un mensaje de saludo
     */
    public function isGreeting(): bool
    {
        return $this->is_greeting === true;
    }

    /**
     * Verificar si está activa
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Verificar si tiene palabra clave definida
     */
    public function hasKeyword(): bool
    {
        return !empty($this->trigger_keyword);
    }

    /**
     * Activar respuesta automática
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Desactivar respuesta automática
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Cambiar prioridad
     */
    public function changePriority(int $priority): bool
    {
        return $this->update(['priority' => $priority]);
    }

    /**
     * Buscar respuesta automática por mensaje entrante
     * 
     * @param string $incomingMessage
     * @return WhatsappAutoReply|null
     */
    public static function findMatchingReply(string $incomingMessage): ?WhatsappAutoReply
    {
        $incomingMessage = strtolower(trim($incomingMessage));
        
        // Buscar coincidencia exacta primero (ordenado por prioridad)
        $replies = self::active()
            ->withKeyword()
            ->orderByPriority('desc')
            ->get();
        
        foreach ($replies as $reply) {
            $keyword = strtolower(trim($reply->trigger_keyword));
            
            // Coincidencia exacta
            if ($incomingMessage === $keyword) {
                return $reply;
            }
            
            // Coincidencia parcial (el mensaje contiene la palabra clave)
            if (str_contains($incomingMessage, $keyword)) {
                return $reply;
            }
        }
        
        return null;
    }

    /**
     * Obtener mensaje de saludo predeterminado
     * 
     * @return WhatsappAutoReply|null
     */
    public static function getGreeting(): ?WhatsappAutoReply
    {
        return self::greetings()
            ->orderByPriority('desc')
            ->first();
    }

    /**
     * Obtener todas las palabras clave activas
     * 
     * @return array
     */
    public static function getAllKeywords(): array
    {
        return self::active()
            ->withKeyword()
            ->pluck('trigger_keyword')
            ->toArray();
    }

    /**
     * Verificar si un mensaje debe recibir respuesta automática
     * 
     * @param string $message
     * @return bool
     */
    public static function shouldAutoReply(string $message): bool
    {
        return self::findMatchingReply($message) !== null;
    }
}