<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
// Badge removed - not used in this component
import { User, MapPin, Calendar, DollarSign, ArrowLeft, ArrowRight } from 'lucide-vue-next';

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

// Props
const props = defineProps<{
  negocio: Negocio;
  // Índice de la columna actual y total de columnas, para saber si
  // debemos mostrar/ocultar las flechas de navegación.
  columnIndex?: number;
  totalColumns?: number;
}>();

// Emits
const emit = defineEmits<{
  dragstart: [negocio: Negocio];
  moveleft: [negocio: Negocio];
  moveright: [negocio: Negocio];
}>();

// Formatear fecha
const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
};

// Formatear moneda
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 0,
  }).format(amount);
};

// Handle drag start
const handleDragStart = (event: DragEvent) => {
  emit('dragstart', props.negocio);
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
  }
};

// Botones auxiliares para mover la tarjeta a columnas vecinas
const handleMoveLeft = (event: MouseEvent) => {
  // Evitar que el click dispare la navegación del Link
  event.preventDefault();
  event.stopPropagation();
  emit('moveleft', props.negocio);
};

const handleMoveRight = (event: MouseEvent) => {
  event.preventDefault();
  event.stopPropagation();
  emit('moveright', props.negocio);
};

const isFirstColumn = computed(() => props.columnIndex === 0);
const isLastColumn = computed(() =>
  props.totalColumns !== undefined &&
  props.columnIndex !== undefined &&
  props.columnIndex === props.totalColumns - 1,
);
</script>

<template>
  <a :href="`/leads/${negocio.lead.id}`">
    <Card
      draggable="true"
      @dragstart="handleDragStart"
      class="cursor-grab select-none bg-white dark:bg-slate-800 
             border-2 border-border/60 hover:border-primary/60
             rounded-xl shadow-sm hover:shadow-lg hover:shadow-primary/10
             transition-all duration-300 ease-out overflow-hidden
             active:cursor-grabbing active:shadow-md active:border-primary/50"
    >
      <CardHeader class="pb-3">
        <div class="flex items-center gap-2">
          <CardTitle class="flex-1 text-sm font-bold line-clamp-1 text-foreground group-hover:text-primary transition-colors">
            {{ negocio.lead.nombre }}
          </CardTitle>

          <!-- Controles de navegación horizontal dentro de la tarjeta -->
          <div class="flex items-center gap-1 text-sm opacity-60 hover:opacity-100 transition-opacity">
            <button
              v-if="!isFirstColumn"
              type="button"
              class="flex items-center justify-center w-7 h-7 rounded-lg border border-border bg-background text-muted-foreground
                     transition-all duration-200 hover:bg-primary hover:text-white hover:border-primary hover:scale-110 active:scale-95"
              @click="handleMoveLeft"
              title="Mover a la etapa anterior"
            >
              <ArrowLeft class="h-4 w-4" />
            </button>
            <button
              v-if="!isLastColumn"
              type="button"
              class="flex items-center justify-center w-7 h-7 rounded-lg border border-border bg-background text-muted-foreground
                     transition-all duration-200 hover:bg-primary hover:text-white hover:border-primary hover:scale-110 active:scale-95"
              @click="handleMoveRight"
              title="Mover a la siguiente etapa"
            >
              <ArrowRight class="h-4 w-4" />
            </button>
          </div>
        </div>
        <CardDescription class="text-xs flex items-center gap-1">
          <span class="inline-block w-2 h-2 rounded-full bg-primary/60"></span>
          {{ negocio.lead.numero_1 }}
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-2">
        <!-- Terreno -->
        <div class="flex items-start gap-2 text-xs py-1 px-1 -mx-1 rounded transition-colors">
          <div class="flex items-center justify-center w-5 h-5 rounded bg-blue-500/10 text-blue-500 shrink-0">
            <MapPin class="h-3 w-3" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-medium truncate text-foreground">{{ negocio.terreno.ubicacion }}</p>
            <p v-if="negocio.terreno.proyecto" class="text-muted-foreground truncate">
              {{ negocio.terreno.proyecto.nombre }}
            </p>
          </div>
        </div>

        <!-- Asesor -->
        <div class="flex items-center gap-2 text-xs py-1 px-1 -mx-1 rounded transition-colors">
          <div class="flex items-center justify-center w-5 h-5 rounded bg-violet-500/10 text-violet-500 shrink-0">
            <User class="h-3 w-3" />
          </div>
          <span class="text-muted-foreground truncate">{{ negocio.asesor.name }}</span>
        </div>

        <!-- Fecha de Inicio -->
        <div class="flex items-center gap-2 text-xs py-1 px-1 -mx-1 rounded transition-colors">
          <div class="flex items-center justify-center w-5 h-5 rounded bg-cyan-500/10 text-cyan-500 shrink-0">
            <Calendar class="h-3 w-3" />
          </div>
          <span class="text-muted-foreground">{{ formatDate(negocio.fecha_inicio) }}</span>
        </div>

        <!-- Monto Estimado -->
        <div v-if="negocio.monto_estimado" 
             class="inline-flex items-center gap-1 mt-2 px-2.5 py-1.5 rounded-lg
                    bg-gradient-to-r from-green-500/10 to-green-500/5 
                    border border-green-500/20 text-green-600 dark:text-green-400
                    text-xs font-semibold">
          <DollarSign class="h-3.5 w-3.5" />
          <span>{{ formatCurrency(negocio.monto_estimado) }}</span>
        </div>
      </CardContent>
    </Card>
  </a>
</template>