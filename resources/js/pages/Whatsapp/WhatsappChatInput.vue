<script setup>
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

const emit = defineEmits(['send-message']);

const messageText = ref('');
const isSending = ref(false);

// Enviar mensaje
const sendMessage = async () => {
    const text = messageText.value.trim();
    
    if (!text || isSending.value) {
        return;
    }
    
    try {
        isSending.value = true;
        emit('send-message', text);
        messageText.value = '';
    } catch (error) {
        console.error('Error al enviar mensaje:', error);
    } finally {
        isSending.value = false;
    }
};

// Enviar mensaje con Enter (Shift+Enter para nueva línea)
const handleKeyDown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
        <div class="flex items-end gap-3">
            <!-- Botón de emoji (opcional) -->
            <Button
                variant="ghost"
                size="sm"
                class="shrink-0"
                type="button"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 text-gray-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
            </Button>
            
            <!-- Input de texto -->
            <Textarea
                v-model="messageText"
                placeholder="Escribe un mensaje..."
                class="flex-1 min-h-[44px] max-h-32 resize-none"
                rows="1"
                @keydown="handleKeyDown"
                :disabled="isSending"
            />
            
            <!-- Botón de adjuntar (opcional) -->
            <Button
                variant="ghost"
                size="sm"
                class="shrink-0"
                type="button"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 text-gray-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                    />
                </svg>
            </Button>
            
            <!-- Botón de enviar -->
            <Button
                @click="sendMessage"
                :disabled="!messageText.trim() || isSending"
                class="shrink-0 bg-green-600 hover:bg-green-700"
                size="sm"
            >
                <svg
                    v-if="!isSending"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"
                    />
                </svg>
                <svg
                    v-else
                    class="animate-spin h-5 w-5"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
            </Button>
        </div>
        
        <!-- Hint de teclado -->
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            Presiona <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">Enter</kbd> para enviar o 
            <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">Shift + Enter</kbd> para nueva línea
        </p>
    </div>
</template>