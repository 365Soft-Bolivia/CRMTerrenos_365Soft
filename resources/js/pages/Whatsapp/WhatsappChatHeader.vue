<script setup>
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import axios from 'axios';

const props = defineProps({
    conversation: {
        type: Object,
        required: true
    }
});

// Obtener iniciales del nombre
const getInitials = computed(() => {
    const name = props.conversation.contact_name;
    if (!name) return '??';
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
});

// Cerrar conversación
const closeConversation = async () => {
    if (confirm('¿Estás seguro de cerrar esta conversación?')) {
        try {
            await axios.post(`/api/whatsapp/conversations/${props.conversation.id}/close`);
            window.location.reload();
        } catch (error) {
            console.error('Error al cerrar conversación:', error);
            alert('Error al cerrar la conversación');
        }
    }
};

// Archivar conversación
const archiveConversation = async () => {
    if (confirm('¿Deseas archivar esta conversación?')) {
        try {
            await axios.post(`/api/whatsapp/conversations/${props.conversation.id}/archive`);
            window.location.reload();
        } catch (error) {
            console.error('Error al archivar conversación:', error);
            alert('Error al archivar la conversación');
        }
    }
};

// Badge de estado
const statusBadge = computed(() => {
    switch (props.conversation.status) {
        case 'open':
            return { text: 'Abierta', variant: 'default', class: 'bg-green-600' };
        case 'closed':
            return { text: 'Cerrada', variant: 'secondary', class: 'bg-gray-600' };
        case 'archived':
            return { text: 'Archivada', variant: 'secondary', class: 'bg-yellow-600' };
        default:
            return { text: 'Desconocido', variant: 'secondary', class: 'bg-gray-600' };
    }
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
        <div class="flex items-center justify-between">
            <!-- Información del contacto -->
            <div class="flex items-center gap-3">
                <Avatar class="w-10 h-10">
                    <AvatarImage :src="conversation.contact_profile_pic" />
                    <AvatarFallback>{{ getInitials }}</AvatarFallback>
                </Avatar>
                
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ conversation.contact_name || conversation.contact_phone }}
                        </h3>
                        <Badge :class="statusBadge.class" class="text-xs">
                            {{ statusBadge.text }}
                        </Badge>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ conversation.contact_phone }}
                    </p>
                </div>
            </div>
            
            <!-- Acciones -->
            <div class="flex items-center gap-2">
                <!-- Ver lead asociado -->
                <Button
                    v-if="conversation.lead_id"
                    variant="outline"
                    size="sm"
                    @click="$inertia.visit(`/leads/${conversation.lead_id}`)"
                >
                    👤 Ver Lead
                </Button>
                
                <!-- Menú de opciones -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="sm">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"
                                />
                            </svg>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="$inertia.visit(`/whatsapp/conversation/${conversation.id}`)">
                            📋 Ver detalles
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="!conversation.lead_id" @click="$inertia.visit(`/leads/create?phone=${conversation.contact_phone}`)">
                            ➕ Crear Lead
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="closeConversation" v-if="conversation.status === 'open'">
                            ✅ Cerrar conversación
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="archiveConversation" v-if="conversation.status !== 'archived'">
                            📦 Archivar
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
        
        <!-- Información adicional -->
        <div v-if="conversation.assigned_agent" class="mt-2 text-xs text-gray-600 dark:text-gray-400">
            Asignado a: <span class="font-medium">{{ conversation.assigned_agent.name }}</span>
        </div>
    </div>
</template>