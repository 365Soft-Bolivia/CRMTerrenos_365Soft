<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import NegocioCard from './NegocioCard.vue';
import { BarChart3, TrendingUp, TrendingDown, DollarSign, Loader2, StickyNote } from 'lucide-vue-next';
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
// Nota: mantenemos loading para el primer render general,
// pero evitamos usarlo en los movimientos de tarjetas para no
// desmontar el DOM y perder la posición de scroll.
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

// Actualiza el tablero en memoria sin volver a llamar a la API.
// De esta forma, el movimiento de tarjetas es fluido y no se
// desmonta el tablero completo (se mantiene el scroll y el estado visual).
const moverNegocioEnEstadoLocal = (negocioId: number, nuevaEtapa: string) => {
  // Encontrar la columna origen y el negocio
  const origen = tablero.value.find((col) =>
    col.negocios.some((n) => n.id === negocioId)
  );

  if (!origen) return;

  const indiceNegocio = origen.negocios.findIndex((n) => n.id === negocioId);
  if (indiceNegocio === -1) return;

  const negocio = origen.negocios[indiceNegocio];

  // Si ya está en la etapa destino, no hacemos nada
  if (negocio.etapa === nuevaEtapa) return;

  // Actualizar etapa localmente
  negocio.etapa = nuevaEtapa;

  // Quitar de la columna origen y ajustar contador
  origen.negocios.splice(indiceNegocio, 1);
  origen.cantidad = origen.negocios.length;

  // Buscar columna destino
  const destino = tablero.value.find((col) => col.etapa === nuevaEtapa);
  if (!destino) return;

  // Agregar al inicio de la lista destino (para que parezca "recién movido")
  destino.negocios.unshift(negocio);
  destino.cantidad = destino.negocios.length;
};

const actualizarEtapaNegocio = async (
  negocioId: number,
  etapaNueva: string,
  etapaAnterior?: string,
) => {
  // 1) Mover primero en el frontend (optimista) para una UI más fluida
  moverNegocioEnEstadoLocal(negocioId, etapaNueva);

  try {
    // 2) Confirmar con el backend
    await axios.put(`/api/negocios/${negocioId}/etapa`, { etapa: etapaNueva });

    // 3) Actualizar estadísticas SIN bloquear la UI
    fetchEstadisticas(); // sin await

    showSuccess(
      'Negocio actualizado',
      'La etapa del negocio se actualizó correctamente.',
    );
  } catch (error) {
    console.error('Error al actualizar etapa:', error);

    // 4) Si falla, devolver el negocio a su etapa anterior (si la recibimos)
    if (etapaAnterior) {
      moverNegocioEnEstadoLocal(negocioId, etapaAnterior);
    }

    showError(
      'Error al mover el negocio',
      'Ocurrió un problema al intentar mover el negocio.',
    );
  } finally {
    draggedNegocio.value = null;
  }
};

// Mover negocio a la etapa anterior/siguiente desde los botones de la tarjeta
// Reutilizamos la misma lógica de confirmación que en el drag & drop
// cuando la etapa destino es "Cierre / Venta Concretada".
const moverNegocioAEtapaRelativa = (negocio: Negocio, direccion: 'left' | 'right') => {
  const indexActual = tablero.value.findIndex((col) => col.etapa === negocio.etapa);
  if (indexActual === -1) return;

  const nuevoIndex = direccion === 'left' ? indexActual - 1 : indexActual + 1;
  if (nuevoIndex < 0 || nuevoIndex >= tablero.value.length) return;

  const nuevaEtapa = tablero.value[nuevoIndex].etapa;
  const etapaAnterior = negocio.etapa;

  const esCierre = nuevaEtapa.includes('Cierre / Venta Concretada');

  if (esCierre) {
    confirm.require({
      message: `¿Confirmas mover este negocio a "Cierre / Venta Concretada"?`,
      header: 'Confirmar cierre de negocio',
      icon: 'pi pi-check-circle',
      acceptLabel: 'Confirmar',
      rejectLabel: 'Cancelar',
      acceptClass: 'p-button-success',
      accept: () => {
        actualizarEtapaNegocio(negocio.id, nuevaEtapa, etapaAnterior);
      },
    });
  } else {
    actualizarEtapaNegocio(negocio.id, nuevaEtapa, etapaAnterior);
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
        actualizarEtapaNegocio(negocioId, etapa, etapaAnterior);
      },
      reject: () => {
        draggedNegocio.value = null;
      },
    });
  } else {
    await actualizarEtapaNegocio(negocioId, etapa, etapaAnterior);
  }
};

// Obtener color según la etapa - Diseño mejorado con paleta más amplia
const getEtapaColor = (etapa: string) => {
  // Mapeo de emojis a colores específicos (Colores sólidos sin gradientes)
  if (etapa.includes('🟡')) return 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-700/50';
  if (etapa.includes('🔵')) return 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-700/50';
  if (etapa.includes('🟢')) return 'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-700/50';
  if (etapa.includes('🟠')) return 'bg-orange-50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-700/50';
  if (etapa.includes('🔴')) return 'bg-rose-50 border-rose-200 dark:bg-rose-900/20 dark:border-rose-700/50';
  if (etapa.includes('🟣')) return 'bg-purple-50 border-purple-200 dark:bg-purple-900/20 dark:border-purple-700/50';
  if (etapa.includes('🟤')) return 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-700/50';
  
  // Colores por defecto basados en palabras clave si no hay emoji
  const lowerEtapa = etapa.toLowerCase();
  if (lowerEtapa.includes('nuevo') || lowerEtapa.includes('prospecto')) return 'bg-sky-50 border-sky-200 dark:bg-sky-900/20 dark:border-sky-700/50';
  if (lowerEtapa.includes('contacto') || lowerEtapa.includes('llamada')) return 'bg-indigo-50 border-indigo-200 dark:bg-indigo-900/20 dark:border-indigo-700/50';
  if (lowerEtapa.includes('visita') || lowerEtapa.includes('cita')) return 'bg-violet-50 border-violet-200 dark:bg-violet-900/20 dark:border-violet-700/50';
  if (lowerEtapa.includes('negociacion') || lowerEtapa.includes('propuesta')) return 'bg-fuchsia-50 border-fuchsia-200 dark:bg-fuchsia-900/20 dark:border-fuchsia-700/50';
  if (lowerEtapa.includes('cierre') || lowerEtapa.includes('venta')) return 'bg-teal-50 border-teal-200 dark:bg-teal-900/20 dark:border-teal-700/50';
  if (lowerEtapa.includes('perdido') || lowerEtapa.includes('cancelado')) return 'bg-slate-50 border-slate-200 dark:bg-slate-900/20 dark:border-slate-700/50';

  // Fallback genérico elegante
  return 'bg-gradient-to-b from-gray-50 to-gray-100/50 border-gray-200 shadow-sm shadow-gray-100/50 dark:from-gray-800 dark:to-gray-900 dark:border-gray-700';
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
    <div class="space-y-6 animate-fade-in">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight bg-gradient-to-r from-primary to-blue-600 bg-clip-text text-transparent">
            Tablero de Negocios
          </h1>
          <p class="text-muted-foreground mt-1">Gestiona tus oportunidades de venta</p>
        </div>
      </div>

      <!-- Estadísticas -->
      <div v-if="estadisticas" class="flex flex-wrap gap-5">
        <!-- Total -->
        <Card class="border py-0 gap-0">
          <CardContent class="p-5 flex items-center gap-5">
            <div class="p-2.5 bg-primary/10 rounded-xl shrink-0">
              <BarChart3 class="h-6 w-6 text-primary" />
            </div>
            <div class="flex flex-col">
              <span class="text-base font-medium text-muted-foreground">Total</span>
              <p class="text-sm text-muted-foreground">
                <span class="text-primary font-medium">{{ estadisticas.activos }}</span> activos
              </p>
            </div>
            <div class="ml-4 text-3xl font-bold text-primary leading-none">{{ estadisticas.total }}</div>
          </CardContent>
        </Card>

        <!-- Ganados -->
        <Card class="border py-0 gap-0">
          <CardContent class="p-5 flex items-center gap-5">
            <div class="p-2.5 bg-emerald-500/10 rounded-xl shrink-0">
              <TrendingUp class="h-6 w-6 text-emerald-500" />
            </div>
            <div class="flex flex-col">
              <span class="text-base font-medium text-muted-foreground">Ganados</span>
              <p class="text-sm text-muted-foreground">
                Tasa: <span class="text-emerald-500 font-medium">{{ estadisticas.tasa_conversion }}%</span>
              </p>
            </div>
            <div class="ml-4 text-3xl font-bold text-emerald-500 leading-none">{{ estadisticas.ganados }}</div>
          </CardContent>
        </Card>

        <!-- Perdidos -->
        <Card class="border py-0 gap-0">
          <CardContent class="p-5 flex items-center gap-5">
            <div class="p-2.5 bg-rose-500/10 rounded-xl shrink-0">
              <TrendingDown class="h-6 w-6 text-rose-500" />
            </div>
            <div class="flex flex-col">
              <span class="text-base font-medium text-muted-foreground">Perdidos</span>
              <p class="text-sm text-muted-foreground">
                No concretados
              </p>
            </div>
            <div class="ml-4 text-3xl font-bold text-rose-500 leading-none">{{ estadisticas.perdidos }}</div>
          </CardContent>
        </Card>

        <!-- Monto -->
        <Card class="border py-0 gap-0">
          <CardContent class="p-5 flex items-center gap-5">
            <div class="p-2.5 bg-amber-500/10 rounded-xl shrink-0">
              <DollarSign class="h-6 w-6 text-amber-500" />
            </div>
            <div class="flex flex-col min-w-0">
              <span class="text-base font-medium text-muted-foreground">Monto</span>
              <p class="text-sm text-muted-foreground">
                De ganados
              </p>
            </div>
            <div class="ml-4 text-2xl font-bold text-amber-600 dark:text-amber-400 truncate leading-none" :title="formatCurrency(estadisticas.monto_total_ganado)">
              {{ formatCurrency(estadisticas.monto_total_ganado) }}
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Tablero Kanban -->
       <div class="overflow-x-auto pb-4">
        <div class="inline-flex gap-4 min-w-full px-1">
          <!-- Columna por cada etapa -->
          <div
            v-for="(etapaData, index) in tablero"
            :key="etapaData.etapa"
            class="flex-shrink-0 w-80"
          >
            <!-- Card con fondo de color según etapa -->
            <Card
              :class="['transition-all duration-300 hover:shadow-lg rounded-xl', getEtapaColor(etapaData.etapa)]"
              @dragover="handleDragOver"
              @drop="handleDrop(etapaData.etapa)"
            >
              <CardHeader class="pb-3">
                <div class="flex items-center justify-between">
                  <CardTitle class="text-base font-semibold">{{ etapaData.etapa }}</CardTitle>
                  <Badge class="bg-primary text-white font-semibold min-w-7 text-center">{{ etapaData.cantidad }}</Badge>
                </div>
              </CardHeader>
              <CardContent class="space-y-3 h-[calc(100vh-280px)] min-h-[500px] overflow-y-auto scrollbar-thin scrollbar-thumb-border scrollbar-track-transparent px-3">
                <!-- Tarjetas de negocios con transición suave de reordenamiento -->
                <TransitionGroup name="card-list" tag="div" class="space-y-3 relative">
                  <NegocioCard
                    v-for="negocio in etapaData.negocios"
                    :key="negocio.id"
                    :negocio="negocio"
                    :column-index="tablero.indexOf(etapaData)"
                    :total-columns="tablero.length"
                    @dragstart="handleDragStart(negocio)"
                    @moveleft="moverNegocioAEtapaRelativa(negocio, 'left')"
                    @moveright="moverNegocioAEtapaRelativa(negocio, 'right')"
                  />
                </TransitionGroup>

                <!-- Estado vacío -->
                <div
                  v-if="etapaData.negocios.length === 0"
                  class="text-center py-10 text-sm text-muted-foreground rounded-lg border-2 border-dashed border-border bg-muted/30"
                >
                  <div class="mb-2 opacity-50">
                    <StickyNote class="mx-auto h-8 w-8" />
                  </div>
                  <p>No hay negocios en esta etapa</p>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Solo animamos el movimiento de reordenamiento, no la entrada/salida para evitar parpadeos */
.card-list-move {
  transition: transform 0.3s ease;
}
</style>