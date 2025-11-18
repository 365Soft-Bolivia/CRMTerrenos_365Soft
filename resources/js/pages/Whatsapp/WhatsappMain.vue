<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import WhatsappSidebar from './WhatsappSidebar.vue';
import WhatsappChat from './WhatsappChat.vue';
import axios from 'axios';

// Estado
const conversations = ref([]);
const selectedConversation = ref(null);
const loading = ref(true);
const disconnecting = ref(false);
const nodeServiceStatus = ref({
    isReady: false,
    hasQR: false
});

// Cargar conversaciones
const loadConversations = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/whatsapp/conversations');
        conversations.value = response.data.data;
    } catch (error) {
        console.error('Error al cargar conversaciones:', error);
    } finally {
        loading.value = false;
    }
};

// Verificar estado del servicio Node.js
const checkNodeServiceStatus = async () => {
    try {
        const response = await axios.get('http://localhost:3000/api/whatsapp/status');
        nodeServiceStatus.value = response.data.data;
    } catch (error) {
        console.error('Error al verificar servicio Node.js:', error);
        nodeServiceStatus.value = {
            isReady: false,
            hasQR: false
        };
    }
};

// ✅ NUEVO: Desconectar WhatsApp
const disconnectWhatsApp = async () => {
    if (!confirm('¿Estás seguro de cerrar la sesión de WhatsApp?')) {
        return;
    }

    try {
        disconnecting.value = true;
        
        // Llamar al servicio Node.js para desconectar
        await axios.post('http://localhost:3000/api/whatsapp/disconnect');
        
        // Limpiar conversaciones localmente
        conversations.value = [];
        selectedConversation.value = null;
        
        // Actualizar estado
        await checkNodeServiceStatus();
        
        alert('✅ Sesión cerrada correctamente. Escanea el nuevo código QR para reconectar.');
        
    } catch (error) {
        console.error('Error al desconectar:', error);
        alert('❌ Error al cerrar sesión. Intenta de nuevo.');
    } finally {
        disconnecting.value = false;
    }
};

// ✅ NUEVO: Watch para limpiar conversaciones cuando se desconecta
watch(() => nodeServiceStatus.value.isReady, (isReady, wasReady) => {
    // Si cambió de conectado a desconectado
    if (wasReady && !isReady) {
        console.log('🧹 WhatsApp desconectado, limpiando conversaciones...');
        conversations.value = [];
        selectedConversation.value = null;
    }
});

// Seleccionar conversación
const selectConversation = (conversation) => {
    selectedConversation.value = conversation;
};

// Actualizar conversación después de enviar mensaje
const handleMessageSent = async () => {
    await loadConversations();
};

// Inicializar
onMounted(async () => {
    await checkNodeServiceStatus();
    
    // Solo cargar conversaciones si está conectado
    if (nodeServiceStatus.value.isReady) {
        await loadConversations();
    }
    
    // Verificar estado cada 5 segundos
    setInterval(checkNodeServiceStatus, 5000);
    
    // Actualizar conversaciones cada 10 segundos (solo si está conectado)
    setInterval(() => {
        if (nodeServiceStatus.value.isReady) {
            loadConversations();
        }
    }, 10000);
});
</script>

<template>
    <Head title="WhatsApp" />
    
    <AppLayout>
        <div class="h-[calc(100vh-4rem)] flex flex-col">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            💬 WhatsApp
                        </h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Gestiona tus conversaciones de WhatsApp
                        </p>
                    </div>
                    
                    <!-- Estado del servicio -->
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <div 
                                :class="[
                                    'w-3 h-3 rounded-full',
                                    nodeServiceStatus.isReady ? 'bg-green-500' : 'bg-red-500'
                                ]"
                            ></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ nodeServiceStatus.isReady ? 'Conectado' : 'Desconectado' }}
                            </span>
                        </div>
                        
                        <!-- ✅ Botón de desconectar (si está conectado) -->
                        <button
                            v-if="nodeServiceStatus.isReady"
                            @click="disconnectWhatsApp"
                            :disabled="disconnecting"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium transition"
                        >
                            {{ disconnecting ? '⏳ Desconectando...' : '🔌 Cerrar sesión' }}
                        </button>
                        
                        <!-- ✅ Botón para ver QR (si no está conectado pero hay QR) -->
                        <button
                            v-if="!nodeServiceStatus.isReady && nodeServiceStatus.hasQR"
                            @click="$inertia.visit('/whatsapp/qr')"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium transition"
                        >
                            📱 Ver código QR
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <div class="flex-1 flex overflow-hidden">
                <!-- Sidebar - Lista de conversaciones -->
                <WhatsappSidebar
                    :conversations="conversations"
                    :selected-conversation="selectedConversation"
                    :loading="loading"
                    @select-conversation="selectConversation"
                    @refresh="loadConversations"
                />
                
                <!-- Chat principal -->
                <WhatsappChat
                    v-if="selectedConversation"
                    :conversation="selectedConversation"
                    @message-sent="handleMessageSent"
                />
                
                <!-- Estado vacío -->
                <div
                    v-else
                    class="flex-1 flex items-center justify-center bg-gray-50 dark:bg-gray-900"
                >
                    <div class="text-center">
                        <div class="text-6xl mb-4">💬</div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            WhatsApp Web
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            {{ nodeServiceStatus.isReady 
                                ? 'Selecciona una conversación para comenzar' 
                                : 'Conecta WhatsApp para ver tus conversaciones' 
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>