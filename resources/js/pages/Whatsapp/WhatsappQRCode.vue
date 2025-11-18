<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import axios from 'axios';

const qrCode = ref(null);
const status = ref({
    isReady: false,
    hasQR: false
});
const loading = ref(true);
const error = ref(null);

let intervalId = null;

// Obtener QR Code y estado
const fetchQRCode = async () => {
    try {
        loading.value = true;
        error.value = null;

        // Obtener estado del servicio
        const statusResponse = await axios.get('http://localhost:3000/api/whatsapp/status');
        status.value = statusResponse.data.data;

        // Si ya está conectado, no necesitamos QR
        if (status.value.isReady) {
            qrCode.value = null;
            return;
        }

        // Si hay QR disponible, obtenerlo
        if (status.value.hasQR) {
            const qrResponse = await axios.get('http://localhost:3000/api/whatsapp/qr');
            qrCode.value = qrResponse.data.data.qr_code;
        }
    } catch (err) {
        error.value = 'No se pudo conectar con el servicio de WhatsApp. Asegúrate de que el servidor Node.js esté corriendo.';
        console.error('Error al obtener QR:', err);
    } finally {
        loading.value = false;
    }
};

// Volver a WhatsApp principal
const goToWhatsApp = () => {
    window.location.href = '/whatsapp';
};

onMounted(() => {
    fetchQRCode();
    
    // Actualizar cada 3 segundos
    intervalId = setInterval(fetchQRCode, 3000);
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});
</script>

<template>
    <Head title="Código QR WhatsApp" />
    
    <AppLayout>
        <div class="min-h-screen flex items-center justify-center p-6 bg-gray-50 dark:bg-gray-900">
            <Card class="w-full max-w-md">
                <CardHeader class="text-center">
                    <div class="mx-auto mb-4 text-6xl">
                        💬
                    </div>
                    <CardTitle class="text-2xl">Conectar WhatsApp</CardTitle>
                    <CardDescription>
                        Escanea el código QR con tu teléfono
                    </CardDescription>
                </CardHeader>
                
                <CardContent class="space-y-6">
                    <!-- Loading State -->
                    <div v-if="loading && !qrCode && !status.isReady" class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            Cargando...
                        </p>
                    </div>

                    <!-- Error State -->
                    <Alert v-if="error" variant="destructive">
                        <AlertDescription>
                            {{ error }}
                        </AlertDescription>
                    </Alert>

                    <!-- Connected State -->
                    <div v-if="status.isReady" class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full mb-4">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-8 w-8 text-green-600 dark:text-green-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            ¡Conectado exitosamente!
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            Tu WhatsApp está conectado y listo para usar
                        </p>
                        <Button @click="goToWhatsApp" class="w-full bg-green-600 hover:bg-green-700">
                            Ir a WhatsApp
                        </Button>
                    </div>

                    <!-- QR Code Display -->
                    <div v-else-if="qrCode" class="space-y-4">
                        <div class="bg-white p-4 rounded-lg">
                            <img
                                :src="`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrCode)}`"
                                alt="QR Code"
                                class="w-full h-auto"
                            />
                        </div>
                        
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <p class="font-medium">Pasos para conectar:</p>
                            <ol class="list-decimal list-inside space-y-1">
                                <li>Abre WhatsApp en tu teléfono</li>
                                <li>Ve a Configuración → Dispositivos vinculados</li>
                                <li>Toca en "Vincular un dispositivo"</li>
                                <li>Escanea este código QR</li>
                            </ol>
                        </div>

                        <Alert>
                            <AlertDescription>
                                El código QR se actualiza automáticamente cada 3 segundos
                            </AlertDescription>
                        </Alert>
                    </div>

                    <!-- Waiting for QR -->
                    <div v-else-if="!loading" class="text-center py-8">
                        <div class="text-5xl mb-4">⏳</div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Esperando código QR del servidor...
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                            Asegúrate de que el servicio Node.js esté corriendo
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            @click="goToWhatsApp"
                            class="flex-1"
                        >
                            ← Volver
                        </Button>
                        <Button
                            variant="outline"
                            @click="fetchQRCode"
                            :disabled="loading"
                            class="flex-1"
                        >
                            🔄 Actualizar
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>