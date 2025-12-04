<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import NegocioCard from './NegocioCard.vue';
import { BarChart3, TrendingUp, TrendingDown, DollarSign } from 'lucide-vue-next';
import axios from 'axios';
import { useConfirm } from 'primevue/useconfirm';
import { useNotification } from '@/composables/useNotification';

// Interfaces
interface Lead {
  id: number;
  nombre: string;
  numero_1: string;
}

interface Terreno {
  id: number;
  ubicacion: string;
  proyecto?: {
    nombre: string;
  };
}

interface Asesor {
  id: number;
  name: string;
}

interface Negocio {
  id: number;
  etapa: string;
  fecha_inicio: string;
  monto_estimado?: number;
  lead: Lead;
  terreno: Terreno;
  asesor: Asesor;
}

interface EtapaData {
  etapa: string;
  cantidad: number;
  negocios: Negocio[];
}

interface Estadisticas {
  total: number;
  activos: number;
  ganados: number;
  perdidos: number;
  monto_total_ganado: number;
  tasa_conversion: number;
}

// Estado
const tablero = ref<EtapaData[]>([]);
const estadisticas = ref<Estadisticas | null>(null);
const loading = ref(true);
const draggedNegocio = ref<Negocio | null>(null);

// Notificaciones y confirmación
const { showSuccess, showError } = useNotification();
const confirm = useConfirm();

// Cargar datos del tablero
const fetchTablero = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/negocios/tablero');
    tablero.value = response.data.data || [];
  } catch (error) {
    console.error('Error al cargar el tablero:', error);
  } finally {
    loading.value = false;
  }
};

// Cargar estadísticas
const fetchEstadisticas = async () => {
  try {
    const response = await axios.get('/api/negocios/estadisticas');
    estadisticas.value = response.data.data;
  } catch (error) {
    console.error('Error al cargar estadísticas:', error);
  }
};

// Drag & Drop handlers
const handleDragStart = (negocio: Negocio) => {
  draggedNegocio.value = negocio;
};

const handleDragOver = (event: DragEvent) => {
  event.preventDefault();
};

const actualizarEtapaNegocio = async (negocioId: number, etapa: string) => {
  try {
    await axios.put(`/api/negocios/${negocioId}/etapa`, { etapa });
    await fetchTablero();
    await fetchEstadisticas();
    showSuccess('Negocio actualizado', 'La etapa del negocio se actualizó correctamente.');
  } catch (error) {
    console.error('Error al actualizar etapa:', error);
    showError('Error al mover el negocio', 'Ocurrió un problema al intentar mover el negocio.');
  } finally {
    draggedNegocio.value = null;
  }
};

const handleDrop = async (etapa: string) => {
  if (!draggedNegocio.value) return;

  const negocio = draggedNegocio.value;
  const negocioId = negocio.id;
  const etapaAnterior = negocio.etapa;

  // No hacer nada si se suelta en la misma etapa
  if (etapaAnterior === etapa) {
    draggedNegocio.value = null;
    return;
  }

  const esCierre = etapa.includes('Cierre / Venta Concretada');

  if (esCierre) {
    // Confirmación antes de marcar como cierre/venta concretada
    confirm.require({
      message: `¿Confirmas mover este negocio a "Cierre / Venta Concretada"?`,
      header: 'Confirmar cierre de negocio',
      icon: 'pi pi-check-circle',
      acceptLabel: 'Confirmar',
      rejectLabel: 'Cancelar',
      acceptClass: 'p-button-success',
      accept: () => {
        actualizarEtapaNegocio(negocioId, etapa);
      },
      reject: () => {
        draggedNegocio.value = null;
      },
    });
  } else {
    await actualizarEtapaNegocio(negocioId, etapa);
  }
};

// Obtener color según la etapa
const getEtapaColor = (etapa: string) => {
  if (etapa.includes('🟡')) return 'bg-yellow-100 border-yellow-300';
  if (etapa.includes('🔵')) return 'bg-blue-100 border-blue-300';
  if (etapa.includes('🟢')) return 'bg-green-100 border-green-300';
  if (etapa.includes('🟠')) return 'bg-orange-100 border-orange-300';
  if (etapa.includes('🔴')) return 'bg-red-100 border-red-300';
  return 'bg-gray-100 border-gray-300';
};

// Formatear moneda
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
  }).format(amount);
};

// Cargar datos al montar
onMounted(() => {
  fetchTablero();
  fetchEstadisticas();
});
</script>

<template>
  <Head title="Tablero de Negocios" />

  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Tablero de Negocios</h1>
      </div>

      <!-- Estadísticas -->
      <div v-if="estadisticas" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Negocios</CardTitle>
            <BarChart3 class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ estadisticas.total }}</div>
            <p class="text-xs text-muted-foreground">
              {{ estadisticas.activos }} activos
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Ganados</CardTitle>
            <TrendingUp class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ estadisticas.ganados }}</div>
            <p class="text-xs text-muted-foreground">
              Tasa: {{ estadisticas.tasa_conversion }}%
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Perdidos</CardTitle>
            <TrendingDown class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ estadisticas.perdidos }}</div>
            <p class="text-xs text-muted-foreground">
              Negocios no concretados
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Monto Total</CardTitle>
            <DollarSign class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ formatCurrency(estadisticas.monto_total_ganado) }}
            </div>
            <p class="text-xs text-muted-foreground">
              De negocios ganados
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <p class="text-muted-foreground">Cargando tablero...</p>
      </div>

      <!-- Tablero Kanban -->
      <div v-else class="overflow-x-auto pb-4">
        <div class="inline-flex gap-4 min-w-full">
          <!-- Columna por cada etapa -->
          <div
            v-for="etapaData in tablero"
            :key="etapaData.etapa"
            class="flex-shrink-0 w-80"
          >
            <Card
              :class="['border-2', getEtapaColor(etapaData.etapa)]"
              @dragover="handleDragOver"
              @drop="handleDrop(etapaData.etapa)"
            >
              <CardHeader>
                <div class="flex items-center justify-between">
                  <CardTitle class="text-base">{{ etapaData.etapa }}</CardTitle>
                  <Badge variant="secondary">{{ etapaData.cantidad }}</Badge>
                </div>
              </CardHeader>
              <CardContent class="space-y-3 max-h-[600px] overflow-y-auto">
                <!-- Tarjetas de negocios -->
                <NegocioCard
                  v-for="negocio in etapaData.negocios"
                  :key="negocio.id"
                  :negocio="negocio"
                  @dragstart="handleDragStart(negocio)"
                />

                <!-- Estado vacío -->
                <div
                  v-if="etapaData.negocios.length === 0"
                  class="text-center py-8 text-sm text-muted-foreground"
                >
                  No hay negocios en esta etapa
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>

      <!-- Instrucciones -->
      <Card>
        <CardHeader>
          <CardTitle>💡 Cómo usar el tablero</CardTitle>
        </CardHeader>
        <CardContent>
          <ul class="list-disc list-inside space-y-1 text-sm text-muted-foreground">
            <li>Arrastra y suelta las tarjetas entre columnas para cambiar la etapa del negocio</li>
            <li>Haz clic en una tarjeta para ver los detalles completos y el historial de seguimientos</li>
            <li>Los colores indican el estado: amarillo (inicio), azul (contacto), verde (avance), naranja (negociación), rojo (perdido)</li>
          </ul>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>