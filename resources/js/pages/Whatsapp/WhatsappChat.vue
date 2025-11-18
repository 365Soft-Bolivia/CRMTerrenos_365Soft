<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import WhatsappChatHeader from './WhatsappChatHeader.vue';
import WhatsappMessage from './WhatsappMessage.vue';
import WhatsappChatInput from './WhatsappChatInput.vue';
import axios from 'axios';

const props = defineProps({
    conversation: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['message-sent']);

const messages = ref([]);
const loading = ref(false);
const messagesContainer = ref(null);
let intervalId = null;

// Cargar mensajes de la conversación
const loadMessages = async () => {
    try {
        loading.value = true;
        const response = await axios.get(`/api/whatsapp/messages/conversation/${props.conversation.id}`);
        messages.value = response.data.data;
        
        // Marcar conversación como leída
        await axios.post(`/api/whatsapp/conversations/${props.conversation.id}/mark-read`);
        
        // Scroll al final
        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error('Error al cargar mensajes:', error);
    } finally {
        loading.value = false;
    }
};

// Enviar mensaje
const sendMessage = async (messageText) => {
    try {
        const response = await axios.post('/api/whatsapp/messages', {
            conversation_id: props.conversation.id,
            content: messageText,
            type: 'text'
        });
        
        // Agregar mensaje a la lista
        messages.value.push(response.data.data);
        
        // Scroll al final
        await nextTick();
        scrollToBottom();
        
        // Emitir evento
        emit('message-sent');
        
        // Enviar mensaje a través de Node.js
        await axios.post('http://localhost:3000/api/whatsapp/send-message', {
            phone: props.conversation.contact_phone,
            message: messageText
        });
        
    } catch (error) {
        console.error('Error al enviar mensaje:', error);
        alert('Error al enviar el mensaje. Por favor, intenta nuevamente.');
    }
};

// Scroll al final del chat
const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// Observar cambios en la conversación seleccionada
watch(() => props.conversation.id, async () => {
    await loadMessages();
}, { immediate: true });

// Actualizar mensajes cada 5 segundos
onMounted(() => {
    intervalId = setInterval(loadMessages, 5000);
});

// Limpiar intervalo al desmontar
onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});
</script>   

<template>
    <div class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900">
        <!-- Header del chat -->
        <WhatsappChatHeader :conversation="conversation" />
        
        <!-- Mensajes -->
        <div
            ref="messagesContainer"
            class="flex-1 overflow-y-auto p-6 space-y-4"
            style="background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48cGF0dGVybiBpZD0iYSIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBwYXR0ZXJuVHJhbnNmb3JtPSJyb3RhdGUoNDUpIj48cGF0aCBmaWxsPSJub25lIiBzdHJva2U9IiNlZWVlZWUiIHN0cm9rZS13aWR0aD0iMSIgZD0iTTAgMGg0MHY0MEgweiIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNhKSIvPjwvc3ZnPg==');"
        >
            <!-- Loading state -->
            <div v-if="loading && messages.length === 0" class="flex justify-center items-center h-full">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-green-600"></div>
            </div>
            
            <!-- Mensajes -->
            <template v-else>
                <WhatsappMessage
                    v-for="message in messages"
                    :key="message.id"
                    :message="message"
                />
            </template>
        </div>
        
        <!-- Input para escribir mensaje -->
        <WhatsappChatInput @send-message="sendMessage" />
    </div>
</template>