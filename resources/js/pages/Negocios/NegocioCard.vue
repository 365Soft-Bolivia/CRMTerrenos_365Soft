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
import { Badge } from '@/components/ui/badge';
import { User, MapPin, Calendar, DollarSign } from 'lucide-vue-next';

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
}>();

// Emits
const emit = defineEmits<{
  dragstart: [negocio: Negocio];
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
</script>

<template>
  <Link :href="`/negocios/${negocio.id}`">
    <Card
      draggable="true"
      @dragstart="handleDragStart"
      class="cursor-move hover:shadow-md transition-shadow border-l-4 border-l-primary"
    >
      <CardHeader class="pb-3">
        <CardTitle class="text-sm font-semibold line-clamp-1">
          {{ negocio.lead.nombre }}
        </CardTitle>
        <CardDescription class="text-xs">
          {{ negocio.lead.numero_1 }}
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-2">
        <!-- Terreno -->
        <div class="flex items-start gap-2 text-xs">
          <MapPin class="h-3 w-3 text-muted-foreground mt-0.5 flex-shrink-0" />
          <div class="flex-1 min-w-0">
            <p class="font-medium truncate">{{ negocio.terreno.ubicacion }}</p>
            <p v-if="negocio.terreno.proyecto" class="text-muted-foreground truncate">
              {{ negocio.terreno.proyecto.nombre }}
            </p>
          </div>
        </div>

        <!-- Asesor -->
        <div class="flex items-center gap-2 text-xs">
          <User class="h-3 w-3 text-muted-foreground flex-shrink-0" />
          <span class="text-muted-foreground truncate">{{ negocio.asesor.name }}</span>
        </div>

        <!-- Fecha de Inicio -->
        <div class="flex items-center gap-2 text-xs">
          <Calendar class="h-3 w-3 text-muted-foreground flex-shrink-0" />
          <span class="text-muted-foreground">{{ formatDate(negocio.fecha_inicio) }}</span>
        </div>

        <!-- Monto Estimado -->
        <div v-if="negocio.monto_estimado" class="flex items-center gap-2 text-xs pt-2 border-t">
          <DollarSign class="h-3 w-3 text-green-600 flex-shrink-0" />
          <span class="font-semibold text-green-600">
            {{ formatCurrency(negocio.monto_estimado) }}
          </span>
        </div>
      </CardContent>
    </Card>
  </Link>
</template>

<style scoped>
/* Efecto visual durante el arrastre */
[draggable="true"] {
  user-select: none;
}

[draggable="true"]:active {
  opacity: 0.5;
  cursor: grabbing;
}
</style>