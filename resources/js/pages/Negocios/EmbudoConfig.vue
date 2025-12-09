<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Plus, Pencil, Trash2, GripVertical, Loader2, Palette, Settings2, ArrowUp, ArrowDown, Ban, CircleOff } from 'lucide-vue-next';
import axios from 'axios';
import { useNotification } from '@/composables/useNotification';
import { useConfirm } from 'primevue/useconfirm';

// Interfaces
interface Embudo {
  id: number;
  nombre: string;
  color: string;
  icono: string;
  orden: number;
  activo: boolean;
  descripcion: string | null;
  negocios_count?: number;
}

// Estado
const embudos = ref<Embudo[]>([]);
const loading = ref(true);
const saving = ref(false);
const showDialog = ref(false);
const editingEmbudo = ref<Embudo | null>(null);
const draggedIndex = ref<number | null>(null);
const dropTargetIndex = ref<number | null>(null);
const isDragging = ref(false);

// Formulario
const form = ref({
  nombre: '',
  color: '#3B82F6',
  icono: 'circle',
  descripcion: '',
  activo: true,
});

// Colores predefinidos
const coloresPredefinidos = ['#FBBF24', '#F97316', '#EF4444', '#EC4899', '#8B5CF6', '#3B82F6', '#06B6D4', '#10B981', '#22C55E', '#6B7280'];

const { showSuccess, showError } = useNotification();
const confirm = useConfirm();

// Computed
const isEditing = computed(() => editingEmbudo.value !== null);
const dialogTitle = computed(() => isEditing.value ? 'Editar Etapa' : 'Nueva Etapa');
const embudosActivos = computed(() => embudos.value.filter(e => e.activo));

// Cargar embudos
const fetchEmbudos = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/embudos');
    embudos.value = response.data.data || [];
  } catch (error) {
    console.error('Error al cargar embudos:', error);
    showError('Error', 'No se pudieron cargar las etapas del embudo');
  } finally {
    loading.value = false;
  }
};

// Abrir diálogo para crear
const openCreateDialog = () => {
  editingEmbudo.value = null;
  form.value = {
    nombre: '',
    color: '#3B82F6',
    icono: 'circle',
    descripcion: '',
    activo: true,
  };
  showDialog.value = true;
};

// Abrir diálogo para editar
const openEditDialog = (embudo: Embudo) => {
  editingEmbudo.value = embudo;
  form.value = {
    nombre: embudo.nombre,
    color: embudo.color,
    icono: embudo.icono || 'circle',
    descripcion: embudo.descripcion || '',
    activo: embudo.activo,
  };
  showDialog.value = true;
};

// Guardar embudo
const saveEmbudo = async () => {
  if (!form.value.nombre.trim()) {
    showError('Error', 'El nombre es requerido');
    return;
  }

  if (!form.value.color.match(/^#[0-9A-Fa-f]{6}$/)) {
    showError('Error', 'El color debe ser un código hexadecimal válido');
    return;
  }

  try {
    saving.value = true;

    if (isEditing.value && editingEmbudo.value) {
      // Actualizar
      await axios.put(`/api/embudos/${editingEmbudo.value.id}`, form.value);
      showSuccess('Éxito', 'Etapa actualizada correctamente');
    } else {
      // Crear
      await axios.post('/api/embudos', form.value);
      showSuccess('Éxito', 'Etapa creada correctamente');
    }

    showDialog.value = false;
    await fetchEmbudos();
  } catch (error: any) {
    console.error('Error al guardar embudo:', error);
    const message = error.response?.data?.message || 'Error al guardar la etapa';
    showError('Error', message);
  } finally {
    saving.value = false;
  }
};

// Eliminar embudo
const deleteEmbudo = async (embudo: Embudo) => {
  try {
    // Primero verificar si tiene negocios asociados
    const response = await axios.get(`/api/embudos/${embudo.id}`);
    const negociosCount = response.data.data.negocios_count || 0;
    
    if (negociosCount > 0) {
      // No permitir eliminar - mostrar modal informativo (solo botón Entendido)
      confirm.require({
        message: `Esta etapa contiene <span class="text-red-500 font-bold">${negociosCount} lead${negociosCount > 1 ? 's' : ''}</span>.<span class="text-red-500 font-bold"> Para eliminar esta etapa, primero mueve los leads a otra etapa.</span>`,
        header: 'No se puede eliminar',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Entendido',
        rejectLabel: ' ',
        rejectClass: '!hidden !w-0 !p-0 !m-0 !border-0 !opacity-0 !invisible',
        acceptClass: 'p-button-danger',
      });
      return;
    }
    
    // Si no tiene negocios, confirmar eliminación (doble confirmación)
    confirm.require({
      message: `¿Estás seguro de eliminar la etapa "${embudo.nombre}"? Esta acción no se puede deshacer.`,
      header: 'Confirmar eliminación',
      icon: 'pi pi-exclamation-triangle',
      acceptLabel: 'Eliminar',
      rejectLabel: 'Cancelar',
      acceptClass: 'p-button-danger',
      accept: () => {
        // Pequeño delay para que el primer modal se cierre antes de abrir el segundo
        setTimeout(() => {
          confirm.require({
            message: `¿Estás <strong>COMPLETAMENTE SEGURO</strong> de eliminar "${embudo.nombre}"?`,
            header: 'Ultima confirmación',
            icon: 'pi pi-exclamation-circle',
            acceptLabel: 'Eliminar definitivamente',
            rejectLabel: 'Cancelar',
            acceptClass: 'p-button-danger',
            accept: async () => {
              await ejecutarEliminacion(embudo);
            },
          });
        }, 100);
      },
    });
  } catch (error) {
    console.error('Error:', error);
    showError('Error', 'No se pudo verificar la etapa');
  }
};

// Función auxiliar para ejecutar la eliminación (optimista - sin recargar página)
const ejecutarEliminacion = async (embudo: Embudo) => {
  // Guardar índice por si hay error
  const index = embudos.value.findIndex(e => e.id === embudo.id);
  
  // Eliminar de la lista local inmediatamente (optimista)
  embudos.value = embudos.value.filter(e => e.id !== embudo.id);
  
  try {
    await axios.delete(`/api/embudos/${embudo.id}?force=true`);
    showSuccess('Éxito', 'Etapa eliminada correctamente');
  } catch (error: any) {
    console.error('Error al eliminar embudo:', error);
    // Revertir si hay error (volver a agregar en su posición)
    if (index !== -1) {
      embudos.value.splice(index, 0, embudo);
    }
    const message = error.response?.data?.message || 'Error al eliminar la etapa';
    showError('Error', message);
  }
};

// Mover embudo arriba/abajo (optimista - sin recargar)
const moverEmbudo = (embudo: Embudo, direccion: 'up' | 'down') => {
  const index = embudos.value.findIndex(e => e.id === embudo.id);
  if (index === -1) return;

  const newIndex = direccion === 'up' ? index - 1 : index + 1;
  if (newIndex < 0 || newIndex >= embudos.value.length) return;

  // Intercambiar en el array local (UI instantánea)
  [embudos.value[index], embudos.value[newIndex]] = [embudos.value[newIndex], embudos.value[index]];
  
  // Guardar en background
  guardarOrden();
};

// Drag & Drop handlers
const onDragStart = (e: DragEvent, index: number) => {
  draggedIndex.value = index;
  isDragging.value = true;
  
  // Crear imagen fantasma personalizada
  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move';
    // Hacer la imagen fantasma semi-transparente
    const target = e.target as HTMLElement;
    setTimeout(() => {
      target.style.opacity = '0.4';
    }, 0);
  }
};

const onDragOver = (e: DragEvent, index: number) => {
  e.preventDefault();
  if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
  
  if (draggedIndex.value === null || draggedIndex.value === index) {
    dropTargetIndex.value = null;
    return;
  }
  
  dropTargetIndex.value = index;
};

const onDragEnter = (e: DragEvent, index: number) => {
  e.preventDefault();
  if (draggedIndex.value !== null && draggedIndex.value !== index) {
    dropTargetIndex.value = index;
  }
};

const onDragLeave = () => {
  // Solo limpiar si salimos del área de drop
};

const onDrop = (e: DragEvent, index: number) => {
  e.preventDefault();
  if (draggedIndex.value === null || draggedIndex.value === index) return;
  
  // Mover el item
  const item = embudos.value.splice(draggedIndex.value, 1)[0];
  embudos.value.splice(index, 0, item);
  
  guardarOrden();
};

const onDragEnd = (e: DragEvent) => {
  const target = e.target as HTMLElement;
  target.style.opacity = '1';
  
  draggedIndex.value = null;
  dropTargetIndex.value = null;
  isDragging.value = false;
};

// Guardar orden en el servidor (debounced)
let saveTimeout: ReturnType<typeof setTimeout> | null = null;
const guardarOrden = () => {
  if (saveTimeout) clearTimeout(saveTimeout);
  saveTimeout = setTimeout(async () => {
    const nuevoOrden = embudos.value.map((e, i) => ({ id: e.id, orden: i + 1 }));
    try {
      await axios.post('/api/embudos/reordenar', { embudos: nuevoOrden });
    } catch (error) {
      console.error('Error al reordenar:', error);
      showError('Error', 'No se pudo actualizar el orden');
      fetchEmbudos();
    }
  }, 300);
};

// Toggle activo (optimista)
const toggleActivo = (embudo: Embudo) => {
  embudo.activo = !embudo.activo;
  axios.put(`/api/embudos/${embudo.id}`, { ...embudo }).catch(error => {
    console.error('Error al cambiar estado:', error);
    embudo.activo = !embudo.activo; // Revertir
    showError('Error', 'No se pudo cambiar el estado');
  });
};

// Obtener estilo del color
const getColorStyle = (color: string) => ({
  backgroundColor: color + '20',
  borderColor: color,
  color: color,
});

// Montar
onMounted(() => {
  fetchEmbudos();
});
</script>

<template>
  <AppLayout>
    <Head title="Configurar Embudos" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">Configurar Embudos</h1>
          <p class="text-muted-foreground">
            Administra las etapas del tablero Kanban de negocios
          </p>
        </div>
        <Button @click="openCreateDialog" class="gap-2">
          <Plus class="h-4 w-4" />
          Nueva Etapa
        </Button>
      </div>

      <!-- Preview del tablero -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="text-base flex items-center gap-2">
            <Palette class="h-4 w-4" />
            Vista previa del tablero
          </CardTitle>
          <CardDescription>
            Así se verán las columnas en el tablero Kanban
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex gap-2 overflow-x-auto pb-2">
            <div
              v-for="embudo in embudosActivos"
              :key="embudo.id"
              class="flex-shrink-0 rounded-lg border-2 px-4 py-2 text-sm font-medium"
              :style="getColorStyle(embudo.color)"
            >
              {{ embudo.nombre }}
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Lista de embudos -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Settings2 class="h-5 w-5" />
            Etapas del Embudo
          </CardTitle>
          <CardDescription>
            Arrastra para reordenar o usa los botones de flecha
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="loading" class="flex items-center justify-center py-12">
            <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
          </div>

          <div v-else-if="embudos.length === 0" class="text-center py-12">
            <p class="text-muted-foreground">No hay etapas configuradas</p>
            <Button @click="openCreateDialog" variant="outline" class="mt-4">
              Crear primera etapa
            </Button>
          </div>

          <TransitionGroup v-else name="list" tag="div" class="space-y-1">
            <div
              v-for="(embudo, index) in embudos"
              :key="embudo.id"
              draggable="true"
              @dragstart="onDragStart($event, index)"
              @dragover="onDragOver($event, index)"
              @dragenter="onDragEnter($event, index)"
              @dragleave="onDragLeave"
              @drop="onDrop($event, index)"
              @dragend="onDragEnd"
              class="flex items-center gap-3 rounded-lg border p-3 cursor-grab active:cursor-grabbing transition-all duration-200 ease-out hover:bg-muted/50"
              :class="{ 
                'opacity-50': !embudo.activo, 
                'scale-[1.02] shadow-lg ring-2 ring-primary/50': draggedIndex === index,
                'border-primary border-dashed bg-primary/5': dropTargetIndex === index && draggedIndex !== index
              }"
            >
              <!-- Grip para drag -->
              <GripVertical class="h-5 w-5 text-muted-foreground" />

              <!-- Color indicator -->
              <div
                class="h-8 w-8 rounded-full flex-shrink-0"
                :style="{ backgroundColor: embudo.color }"
              />

              <!-- Info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-medium truncate">{{ embudo.nombre }}</span>
                  <Badge v-if="!embudo.activo" variant="secondary" class="text-xs">Inactivo</Badge>
                </div>
                <p v-if="embudo.descripcion" class="text-sm text-muted-foreground truncate">{{ embudo.descripcion }}</p>
              </div>

              <!-- Orden -->
              <div class="flex items-center gap-1">
                <Button variant="ghost" size="icon" class="h-8 w-8" :disabled="index === 0" @click="moverEmbudo(embudo, 'up')">
                  <ArrowUp class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" class="h-8 w-8" :disabled="index === embudos.length - 1" @click="moverEmbudo(embudo, 'down')">
                  <ArrowDown class="h-4 w-4" />
                </Button>
              </div>

              <!-- Toggle activo -->
              <Button variant="ghost" size="icon" class="h-8 w-8" @click="toggleActivo(embudo)" :title="embudo.activo ? 'Desactivar' : 'Activar'">
                <Ban v-if="embudo.activo" class="h-4 w-4" />
                <CircleOff v-else class="h-4 w-4" />
              </Button>

              <!-- Acciones -->
              <Button variant="ghost" size="icon" class="h-8 w-8" @click="openEditDialog(embudo)">
                <Pencil class="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive hover:text-destructive" @click="deleteEmbudo(embudo)">
                <Trash2 class="h-4 w-4" />
              </Button>
            </div>
          </TransitionGroup>
        </CardContent>
      </Card>
    </div>

    <!-- Dialog para crear/editar -->
    <Dialog v-model:open="showDialog">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{{ dialogTitle }}</DialogTitle>
          <DialogDescription>
            Configura los detalles de la etapa del embudo
          </DialogDescription>
        </DialogHeader>

        <div class="grid gap-4 py-4">
          <!-- Nombre -->
          <div class="grid gap-2">
            <Label for="nombre">Nombre *</Label>
            <Input
              id="nombre"
              v-model="form.nombre"
              placeholder="Ej: Contacto Inicial"
            />
          </div>

          <!-- Color -->
          <div class="grid gap-2">
            <Label>Color *</Label>
            <div class="flex items-center gap-3">
              <!-- Selector de color nativo (cuentagotas) -->
              <label
                class="h-10 w-10 rounded-lg border-2 cursor-pointer overflow-hidden flex-shrink-0 relative"
                :style="{ backgroundColor: form.color, borderColor: form.color }"
                title="Haz clic para abrir el selector de color"
              >
                <input
                  type="color"
                  v-model="form.color"
                  class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                />
              </label>
              <Input
                v-model="form.color"
                placeholder="#3B82F6"
                class="flex-1 font-mono uppercase"
                maxlength="7"
              />
            </div>
            <p class="text-xs text-muted-foreground">Haz clic en el cuadro de color para abrir el selector</p>
            <!-- Colores predefinidos -->
            <div class="flex flex-wrap gap-2 mt-2">
              <button
                v-for="color in coloresPredefinidos"
                :key="color"
                type="button"
                class="h-6 w-6 rounded-full border-2 transition-transform hover:scale-110"
                :class="{ 'ring-2 ring-offset-2 ring-primary': form.color === color }"
                :style="{ backgroundColor: color, borderColor: color }"
                @click="form.color = color"
              />
            </div>
          </div>

          <!-- Descripción -->
          <div class="grid gap-2">
            <Label for="descripcion">Descripción</Label>
            <Textarea
              id="descripcion"
              v-model="form.descripcion"
              placeholder="Descripción opcional de esta etapa..."
              rows="2"
            />
          </div>

          <!-- Activo -->
          <div class="flex items-center justify-between">
            <Label for="activo">Visible en el tablero</Label>
            <Switch id="activo" v-model:checked="form.activo" />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showDialog = false">
            Cancelar
          </Button>
          <Button @click="saveEmbudo" :disabled="saving">
            <Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" />
            {{ isEditing ? 'Guardar cambios' : 'Crear etapa' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<style scoped>
/* Animaciones suaves para TransitionGroup */
.list-move,
.list-enter-active,
.list-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.list-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.list-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

/* Asegurar que los items que se mueven no tengan layout issues */
.list-leave-active {
  position: absolute;
  width: calc(100% - 2rem);
}
</style>