<script setup>
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

const props = defineProps({
    conversations: {
        type: Array,
        default: () => []
    },
    selectedConversation: {
        type: Object,
        default: null
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['select-conversation', 'refresh']);

const searchQuery = ref('');

// Filtrar conversaciones por búsqueda
const filteredConversations = computed(() => {
    if (!searchQuery.value) {
        return props.conversations;
    }
    
    const query = searchQuery.value.toLowerCase();
    return props.conversations.filter(conv => 
        conv.contact_name?.toLowerCase().includes(query) ||
        conv.contact_phone?.toLowerCase().includes(query)
    );
});

// Formatear fecha/hora del último mensaje
const formatLastMessageTime = (timestamp) => {
    if (!timestamp) return '';
    
    const date = new Date(timestamp);
    const now = new Date();
    const diffInHours = (now - date) / (1000 * 60 * 60);
    
    if (diffInHours < 24) {
        return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    } else if (diffInHours < 48) {
        return 'Ayer';
    } else {
        return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
    }
};

// Obtener iniciales del nombre
const getInitials = (name) => {
    if (!name) return '??';
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

// Seleccionar conversación
const selectConversation = (conversation) => {
    emit('select-conversation', conversation);
};

// Refrescar lista
const refresh = () => {
    emit('refresh');
};
</script>

<template>
    <div class="w-96 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
        <!-- Header del sidebar -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Conversaciones
                </h2>
                <Button
                    variant="ghost"
                    size="sm"
                    @click="refresh"
                    :disabled="loading"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        :class="{ 'animate-spin': loading }"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </Button>
            </div>
            
            <!-- Buscador -->
            <Input
                v-model="searchQuery"
                placeholder="Buscar conversación..."
                class="w-full"
            />
        </div>
        
        <!-- Lista de conversaciones -->
        <div class="flex-1 overflow-y-auto">
            <!-- Loading state -->
            <div v-if="loading && conversations.length === 0" class="p-4 space-y-3">
                <div v-for="i in 5" :key="i" class="animate-pulse flex items-center gap-3 p-3">
                    <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                    <div class="flex-1">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                    </div>
                </div>
            </div>
            
            <!-- Lista de conversaciones -->
            <div v-else-if="filteredConversations.length > 0">
                <div
                    v-for="conversation in filteredConversations"
                    :key="conversation.id"
                    @click="selectConversation(conversation)"
                    :class="[
                        'flex items-center gap-3 p-4 cursor-pointer transition-colors border-b border-gray-100 dark:border-gray-700',
                        selectedConversation?.id === conversation.id
                            ? 'bg-gray-100 dark:bg-gray-700'
                            : 'hover:bg-gray-50 dark:hover:bg-gray-750'
                    ]"
                >
                    <!-- Avatar -->
                    <Avatar class="w-12 h-12">
                        <AvatarImage :src="conversation.contact_profile_pic" />
                        <AvatarFallback>{{ getInitials(conversation.contact_name) }}</AvatarFallback>
                    </Avatar>
                    
                    <!-- Información de la conversación -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ conversation.contact_name || conversation.contact_phone }}
                            </h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-2 shrink-0">
                                {{ formatLastMessageTime(conversation.last_message_at) }}
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                {{ conversation.last_message?.content || 'Sin mensajes' }}
                            </p>
                            
                            <!-- Badge de mensajes sin leer -->
                            <Badge
                                v-if="conversation.unread && conversation.unread_count > 0"
                                variant="default"
                                class="ml-2 bg-green-600 hover:bg-green-700 shrink-0"
                            >
                                {{ conversation.unread_count }}
                            </Badge>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estado vacío -->
            <div v-else class="flex flex-col items-center justify-center h-full p-8 text-center">
                <div class="text-5xl mb-3">📭</div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    {{ searchQuery ? 'No se encontraron conversaciones' : 'No hay conversaciones' }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ searchQuery ? 'Intenta con otra búsqueda' : 'Las conversaciones aparecerán aquí' }}
                </p>
            </div>
        </div>
    </div>
</template>