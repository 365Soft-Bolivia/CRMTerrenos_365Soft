<script setup>
import { computed } from 'vue';

const props = defineProps({
    message: {
        type: Object,
        required: true
    }
});

const isOutgoing = computed(() => {
    return props.message.from_me || props.message.direction === 'outgoing';
});

const formatTime = computed(() => {
    const date = new Date(props.message.sent_at);
    return date.toLocaleTimeString('es-ES', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
});

const statusIcon = computed(() => {
    if (!isOutgoing.value) return null;
    
    switch (props.message.status) {
        case 'sent':
            return '✓';
        case 'delivered':
            return '✓✓';
        case 'read':
            return '✓✓';
        case 'failed':
            return '⚠️';
        default:
            return '🕐';
    }
});

const statusColor = computed(() => {
    if (props.message.status === 'read') {
        return 'text-blue-500';
    }
    return 'text-gray-500';
});

const isAutoReply = computed(() => {
    return props.message.is_auto_reply;
});

// ✅ NUEVO: Construir URL completa del media
const mediaUrl = computed(() => {
    if (!props.message.media_url) return null;
    
    // Si ya tiene el prefijo /storage/, devolverlo tal cual
    if (props.message.media_url.startsWith('/storage/')) {
        return props.message.media_url;
    }
    
    // Si no, agregar el prefijo /storage/
    return `/storage/${props.message.media_url}`;
});
</script>

<template>
    <div :class="['flex', isOutgoing ? 'justify-end' : 'justify-start']">
        <div
            :class="[
                'max-w-md rounded-lg px-4 py-2 shadow-sm',
                isOutgoing
                    ? 'bg-green-500 text-white rounded-br-none'
                    : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-bl-none'
            ]"
        >
            <div v-if="isAutoReply" class="text-xs opacity-75 mb-1 flex items-center gap-1">
                <span>🤖 Respuesta automática</span>
            </div>
            
            <div
                v-if="!isOutgoing && message.sender_name"
                class="text-xs font-semibold mb-1 text-green-600 dark:text-green-400"
            >
                {{ message.sender_name }}
            </div>
            
            <div class="break-words whitespace-pre-wrap">
                <!-- ✅ MENSAJE DE TEXTO -->
                <p v-if="message.type === 'text'">{{ message.content }}</p>
                
                <!-- ✅ IMAGEN -->
                <div v-else-if="message.type === 'image'" class="space-y-2">
                    <img 
                        v-if="mediaUrl" 
                        :src="mediaUrl" 
                        :alt="message.content || 'Imagen'" 
                        class="rounded-lg max-w-full h-auto cursor-pointer hover:opacity-90 transition"
                        @error="(e) => e.target.src = '/images/no-image.png'"
                    />
                    <p v-if="message.content && message.content !== '📷 Imagen'" class="text-sm">
                        {{ message.content }}
                    </p>
                </div>
                
                <!-- ✅ VIDEO -->
                <div v-else-if="message.type === 'video'" class="space-y-2">
                    <video 
                        v-if="mediaUrl" 
                        :src="mediaUrl" 
                        controls 
                        class="rounded-lg max-w-full h-auto"
                        preload="metadata"
                    >
                        Tu navegador no soporta el elemento de video.
                    </video>
                    <p v-if="message.content && message.content !== '🎥 Video'" class="text-sm">
                        {{ message.content }}
                    </p>
                </div>
                
                <!-- ✅ AUDIO -->
                <div v-else-if="message.type === 'audio'" class="space-y-2">
                    <audio 
                        v-if="mediaUrl" 
                        :src="mediaUrl" 
                        controls 
                        class="w-full"
                        preload="metadata"
                    >
                        Tu navegador no soporta el elemento de audio.
                    </audio>
                    <p v-if="message.content && message.content !== '🎵 Audio'" class="text-sm">
                        {{ message.content }}
                    </p>
                </div>
                
                <!-- ✅ STICKER -->
                <div v-else-if="message.type === 'sticker'" class="space-y-2">
                    <img 
                        v-if="mediaUrl" 
                        :src="mediaUrl" 
                        alt="Sticker" 
                        class="w-32 h-32 object-contain"
                        @error="(e) => e.target.src = '/images/no-image.png'"
                    />
                    <p v-if="message.content && message.content !== '🎨 Sticker'" class="text-xs opacity-75">
                        {{ message.content }}
                    </p>
                </div>
                
                <!-- ✅ DOCUMENTO -->
                <div v-else-if="message.type === 'document'" class="flex items-center gap-2">
                    <span class="text-4xl">📄</span>
                    <div>
                        <p class="text-sm font-medium">{{ message.content || 'Documento' }}</p>
                        <a 
                            v-if="mediaUrl" 
                            :href="mediaUrl" 
                            target="_blank" 
                            download
                            class="text-xs underline hover:opacity-75"
                        >
                            Descargar
                        </a>
                    </div>
                </div>
                
                <!-- ✅ UBICACIÓN -->
                <div v-else-if="message.type === 'location'" class="flex items-center gap-2">
                    <span class="text-4xl">📍</span>
                    <div>
                        <p class="text-sm font-medium">Ubicación compartida</p>
                        <p class="text-xs">{{ message.content }}</p>
                    </div>
                </div>
                
                <!-- ✅ OTROS TIPOS -->
                <div v-else class="flex items-center gap-2">
                    <span class="text-2xl">📎</span>
                    <p class="text-sm">{{ message.content || `Mensaje tipo: ${message.type}` }}</p>
                </div>
            </div>
            
            <div
                :class="[
                    'flex items-center justify-end gap-1 mt-1 text-xs',
                    isOutgoing ? 'text-white opacity-75' : 'text-gray-500 dark:text-gray-400'
                ]"
            >
                <span>{{ formatTime }}</span>
                <span v-if="statusIcon" :class="statusColor" class="font-bold">{{ statusIcon }}</span>
            </div>
        </div>
    </div>
</template>