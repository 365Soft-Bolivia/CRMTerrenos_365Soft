<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
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
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  ArrowLeft,
  Trash2,
  Phone,
  MapPin,
  User,
  Calendar,
  DollarSign,
  Plus,
  MessageSquare,
  Loader2,
  Clock,
  Building,
  FileText,
} from 'lucide-vue-next';
import axios from 'axios';

// Props
const props = defineProps<{
  negocioId: number;
}>();

// Interfaces
interface Seguimiento {
  id: number;
  tipo: string;
  descripcion: string;
  fecha_seguimiento: string;
  proximo_seguimiento?: string;
  asesor: {
    name: string;
  };
}

interface Negocio {
  id: number;
  etapa: string;
  fecha_inicio: string;
  monto_estimado?: number;
  notas?: string;
  lead: {
    id: number;
    nombre: string;
    carnet: string;
    numero_1: string;
    numero_2?: string;
    direccion?: string;
  };
  terreno: {
    id: number;
    ubicacion: string;
    numero_terreno?: string;
    proyecto?: {
      nombre: string;
    };
    categoria?: {
      nombre: string;
    };
    cuadra?: {
      nombre?: string;
      barrio?: {
        nombre: string;
      };
    };
  };
  asesor: {
    id: number;
    name: string;
    email: string;
  };
  seguimientos?: Seguimiento[];
}

// Estado
const negocio = ref<Negocio | null>(null);
const seguimientos = ref<Seguimiento[]>([]);
const loading = ref(true);
const loadingSeguimientos = ref(false);
const showSeguimientoDialog = ref(false);
const tiposSeguimiento = ref<string[]>([]);

// Formulario de seguimiento
const seguimientoForm = ref({
  tipo: '',
  descripcion: '',
  fecha_seguimiento: new Date().toISOString().split('T')[0],
  proximo_seguimiento: '',
});

const seguimientoErrors = ref<Record<string, string>>({});

// Cargar negocio
const fetchNegocio = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/negocios/${props.negocioId}`);
    negocio.value = response.data.data;
    seguimientos.value = negocio.value?.seguimientos || [];
  } catch (error) {
    console.error('Error al cargar negocio:', error);
    alert('Error al cargar los datos del negocio');
    router.visit('/negocios');
  } finally {
    loading.value = false;
  }
};

// Cargar tipos de seguimiento
const fetchTiposSeguimiento = async () => {
  try {
    const response = await axios.get('/api/seguimientos/tipos');
    tiposSeguimiento.value = response.data.data || [];
    if (tiposSeguimiento.value.length > 0) {
      seguimientoForm.value.tipo = tiposSeguimiento.value[0];
    }
  } catch (error) {
    console.error('Error al cargar tipos de seguimiento:', error);
  }
};

// Guardar seguimiento
const handleSaveSeguimiento = async () => {
  seguimientoErrors.value = {};

  if (!seguimientoForm.value.tipo) {
    seguimientoErrors.value.tipo = 'El tipo es obligatorio';
  }
  if (!seguimientoForm.value.descripcion.trim()) {
    seguimientoErrors.value.descripcion = 'La descripción es obligatoria';
  }

  if (Object.keys(seguimientoErrors.value).length > 0) {
    return;
  }

  try {
    loadingSeguimientos.value = true;
    await axios.post('/api/seguimientos', {
      negocio_id: props.negocioId,
      tipo: seguimientoForm.value.tipo,
      descripcion: seguimientoForm.value.descripcion,
      fecha_seguimiento: seguimientoForm.value.fecha_seguimiento,
      proximo_seguimiento: seguimientoForm.value.proximo_seguimiento || null,
    });

    // Resetear formulario
    seguimientoForm.value = {
      tipo: tiposSeguimiento.value[0] || '',
      descripcion: '',
      fecha_seguimiento: new Date().toISOString().split('T')[0],
      proximo_seguimiento: '',
    };

    // Recargar seguimientos
    await fetchNegocio();
    showSeguimientoDialog.value = false;
  } catch (error: any) {
    console.error('Error al guardar seguimiento:', error);
    if (error.response?.data?.errors) {
      seguimientoErrors.value = error.response.data.errors;
    } else {
      alert('Error al guardar el seguimiento');
    }
  } finally {
    loadingSeguimientos.value = false;
  }
};

// Eliminar negocio
const deleteNegocio = async () => {
  if (!confirm('¿Estás seguro de eliminar este negocio? Esta acción no se puede deshacer.')) return;

  try {
    await axios.delete(`/api/negocios/${props.negocioId}`);
    router.visit('/negocios');
  } catch (error) {
    console.error('Error al eliminar negocio:', error);
    alert('Error al eliminar el negocio');
  }
};

// Formatear fecha
const formatDate = (date: string) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const formatDateTime = (date: string) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
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
  fetchNegocio();
  fetchTiposSeguimiento();
});
</script>

<template>
  <Head title="Detalle del Negocio" />

  <AppLayout>
    <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="icon" 
                  class="transition-all duration-200 hover:-translate-x-1 hover:bg-primary/10" 
                  @click="router.visit('/negocios')">
            <ArrowLeft class="h-4 w-4" />
          </Button>
          <div>
            <h1 class="text-3xl font-bold tracking-tight bg-gradient-to-r from-primary to-blue-500 bg-clip-text text-transparent">
              {{ negocio?.lead.nombre || 'Detalle del Negocio' }}
            </h1>
            <p class="text-muted-foreground">
              Información completa del negocio y seguimientos
            </p>
          </div>
        </div>

        <div v-if="!loading && negocio" class="flex gap-2">
          <Button variant="destructive" class="transition-all duration-200 hover:scale-[1.02]" @click="deleteNegocio">
            <Trash2 class="mr-2 h-4 w-4" />
            Eliminar
          </Button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-16 gap-4">
        <div class="flex items-center justify-center w-16 h-16 bg-card rounded-2xl shadow-lg">
          <Loader2 class="h-8 w-8 text-primary animate-spin" />
        </div>
        <p class="text-muted-foreground animate-pulse">Cargando información...</p>
      </div>

      <!-- Contenido -->
      <div v-else-if="negocio" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna principal -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Información del Negocio -->
          <Card class="transition-all duration-300 overflow-hidden hover:shadow-lg">
            <CardHeader class="border-b bg-muted/30">
              <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2">
                  <FileText class="h-5 w-5 text-primary" />
                  Información del Negocio
                </CardTitle>
                <Badge class="bg-gradient-to-r from-primary to-primary/80 text-white font-semibold px-3 py-1">
                  {{ negocio.etapa }}
                </Badge>
              </div>
            </CardHeader>
            <CardContent class="space-y-4 pt-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-start gap-3 p-2 rounded-lg transition-colors hover:bg-muted/50">
                  <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-500/10 shrink-0">
                    <Calendar class="h-5 w-5 text-blue-500" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Fecha de Inicio</p>
                    <p class="text-base font-semibold">{{ formatDate(negocio.fecha_inicio) }}</p>
                  </div>
                </div>

                <div v-if="negocio.monto_estimado" class="flex items-start gap-3 p-2 rounded-lg transition-colors hover:bg-muted/50">
                  <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-500/10 shrink-0">
                    <DollarSign class="h-5 w-5 text-emerald-500" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Monto Estimado</p>
                    <p class="text-base font-semibold text-emerald-600 dark:text-emerald-400">
                      {{ formatCurrency(negocio.monto_estimado) }}
                    </p>
                  </div>
                </div>
              </div>

              <Separator />

              <!-- Información del Terreno -->
              <div>
                <h3 class="font-semibold mb-3 flex items-center gap-2">
                  <Building class="h-4 w-4 text-primary" />
                  Terreno
                </h3>
                <div class="space-y-3 pl-6">
                  <div class="flex items-start gap-3 p-2 rounded-lg transition-colors hover:bg-muted/50">
                    <div class="flex items-center justify-center w-7 h-7 rounded-md bg-indigo-500/10 shrink-0">
                      <MapPin class="h-4 w-4 text-indigo-500" />
                    </div>
                    <div>
                      <p class="text-sm font-medium text-muted-foreground">Ubicación</p>
                      <p class="text-base">{{ negocio.terreno.ubicacion }}</p>
                      <p v-if="negocio.terreno.numero_terreno" class="text-sm text-muted-foreground">
                        Número: {{ negocio.terreno.numero_terreno }}
                      </p>
                    </div>
                  </div>

                  <div v-if="negocio.terreno.proyecto" class="text-sm flex items-center gap-2">
                    <span class="text-muted-foreground">Proyecto:</span>
                    <Badge variant="outline">{{ negocio.terreno.proyecto.nombre }}</Badge>
                  </div>

                  <div v-if="negocio.terreno.categoria" class="text-sm flex items-center gap-2">
                    <span class="text-muted-foreground">Categoría:</span>
                    <Badge variant="outline">{{ negocio.terreno.categoria.nombre }}</Badge>
                  </div>

                  <div v-if="negocio.terreno.cuadra?.barrio" class="text-sm flex items-center gap-2">
                    <span class="text-muted-foreground">Barrio:</span>
                    <Badge variant="outline">{{ negocio.terreno.cuadra.barrio.nombre }}</Badge>
                  </div>
                </div>
              </div>

              <Separator v-if="negocio.notas" />

              <!-- Notas -->
              <div v-if="negocio.notas">
                <h3 class="font-semibold mb-2">📝 Notas</h3>
                <p class="text-sm text-muted-foreground bg-muted/30 p-3 rounded-lg">{{ negocio.notas }}</p>
              </div>
            </CardContent>
          </Card>

          <!-- Información del Lead -->
          <Card class="transition-all duration-300 overflow-hidden hover:shadow-lg">
            <CardHeader class="border-b bg-muted/30">
              <CardTitle class="flex items-center gap-2">
                <User class="h-5 w-5 text-primary" />
                Información del Cliente
              </CardTitle>
              <CardDescription>
                <Link :href="`/leads/${negocio.lead.id}`" class="text-primary hover:underline inline-flex items-center gap-1">
                  Ver perfil completo del lead
                  <ArrowLeft class="h-3 w-3 rotate-180" />
                </Link>
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4 pt-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-start gap-3 p-2 rounded-lg transition-colors hover:bg-muted/50">
                  <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-violet-500/10 shrink-0">
                    <User class="h-5 w-5 text-violet-500" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Nombre</p>
                    <p class="text-base font-semibold">{{ negocio.lead.nombre }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3 p-2 rounded-lg transition-colors hover:bg-muted/50">
                  <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-500/10 shrink-0">
                    <User class="h-5 w-5 text-slate-500" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Carnet/CI</p>
                    <p class="text-base font-semibold">{{ negocio.lead.carnet }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3 p-2 rounded-lg transition-colors hover:bg-muted/50">
                  <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-green-500/10 shrink-0">
                    <Phone class="h-5 w-5 text-green-500" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Teléfono Principal</p>
                    <p class="text-base font-semibold">{{ negocio.lead.numero_1 }}</p>
                  </div>
                </div>

                <div v-if="negocio.lead.numero_2" class="flex items-start gap-3 p-2 rounded-lg transition-colors hover:bg-muted/50">
                  <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-green-500/10 shrink-0">
                    <Phone class="h-5 w-5 text-green-500" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Teléfono Secundario</p>
                    <p class="text-base font-semibold">{{ negocio.lead.numero_2 }}</p>
                  </div>
                </div>

                <div v-if="negocio.lead.direccion" class="flex items-start gap-3 p-2 rounded-lg transition-colors hover:bg-muted/50 md:col-span-2">
                  <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-amber-500/10 shrink-0">
                    <MapPin class="h-5 w-5 text-amber-500" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Dirección</p>
                    <p class="text-base">{{ negocio.lead.direccion }}</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Timeline de Seguimientos -->
          <Card class="transition-all duration-300 overflow-hidden hover:shadow-lg">
            <CardHeader class="border-b bg-muted/30">
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle class="flex items-center gap-2">
                    <MessageSquare class="h-5 w-5 text-primary" />
                    Historial de Seguimientos
                  </CardTitle>
                  <CardDescription>
                    {{ seguimientos.length }} seguimiento(s) registrado(s)
                  </CardDescription>
                </div>
                <Dialog v-model:open="showSeguimientoDialog">
                  <DialogTrigger as-child>
                    <Button class="bg-gradient-to-r from-primary to-blue-600 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-primary/30">
                      <Plus class="mr-2 h-4 w-4" />
                      Nuevo Seguimiento
                    </Button>
                  </DialogTrigger>
                  <DialogContent class="max-w-2xl">
                    <DialogHeader>
                      <DialogTitle>Agregar Seguimiento</DialogTitle>
                      <DialogDescription>
                        Registra una nueva interacción con el cliente
                      </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="handleSaveSeguimiento" class="space-y-4">
                      <!-- Tipo -->
                      <div class="space-y-2">
                        <Label for="tipo">
                          Tipo de Seguimiento <span class="text-destructive">*</span>
                        </Label>
                        <select
                          id="tipo"
                          v-model="seguimientoForm.tipo"
                          class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                          :class="{ 'border-destructive': seguimientoErrors.tipo }"
                        >
                          <option v-for="tipo in tiposSeguimiento" :key="tipo" :value="tipo">
                            {{ tipo }}
                          </option>
                        </select>
                        <p v-if="seguimientoErrors.tipo" class="text-sm text-destructive">
                          {{ seguimientoErrors.tipo }}
                        </p>
                      </div>

                      <!-- Fecha -->
                      <div class="space-y-2">
                        <Label for="fecha_seguimiento">Fecha y Hora</Label>
                        <Input
                          id="fecha_seguimiento"
                          v-model="seguimientoForm.fecha_seguimiento"
                          type="date"
                        />
                      </div>

                      <!-- Descripción -->
                      <div class="space-y-2">
                        <Label for="descripcion">
                          Descripción <span class="text-destructive">*</span>
                        </Label>
                        <Textarea
                          id="descripcion"
                          v-model="seguimientoForm.descripcion"
                          placeholder="Describe la interacción con el cliente..."
                          :rows="4"
                          :class="{ 'border-destructive': seguimientoErrors.descripcion }"
                        />
                        <p v-if="seguimientoErrors.descripcion" class="text-sm text-destructive">
                          {{ seguimientoErrors.descripcion }}
                        </p>
                      </div>

                      <!-- Próximo Seguimiento -->
                      <div class="space-y-2">
                        <Label for="proximo_seguimiento">Próximo Seguimiento (Opcional)</Label>
                        <Input
                          id="proximo_seguimiento"
                          v-model="seguimientoForm.proximo_seguimiento"
                          type="date"
                        />
                      </div>

                      <DialogFooter>
                        <Button type="button" variant="outline" @click="showSeguimientoDialog = false">
                          Cancelar
                        </Button>
                        <Button type="submit" :disabled="loadingSeguimientos">
                          {{ loadingSeguimientos ? 'Guardando...' : 'Guardar Seguimiento' }}
                        </Button>
                      </DialogFooter>
                    </form>
                  </DialogContent>
                </Dialog>
              </div>
            </CardHeader>
            <CardContent>
              <div v-if="seguimientos.length === 0" class="text-center py-12 bg-gradient-to-b from-muted/20 to-transparent rounded-xl">
                <div class="mb-4 animate-bounce">
                  <MessageSquare class="h-16 w-16 text-muted-foreground/30 mx-auto" />
                </div>
                <p class="text-muted-foreground font-medium">No hay seguimientos registrados</p>
                <p class="text-sm text-muted-foreground mt-1">
                  Agrega el primer seguimiento para comenzar el historial
                </p>
              </div>

              <!-- Timeline -->
              <div v-else class="relative pl-6 space-y-0 before:absolute before:left-2 before:top-0 before:bottom-0 before:w-0.5 before:bg-gradient-to-b before:from-primary before:to-border">
                <div
                  v-for="seguimiento in seguimientos"
                  :key="seguimiento.id"
                  class="relative pb-5 pl-5 before:absolute before:-left-[14px] before:top-1 before:w-2.5 before:h-2.5 before:bg-primary before:border-2 before:border-background before:rounded-full before:shadow-[0_0_0_3px_rgba(var(--primary),0.2)]"
                >
                  <div class="bg-muted/30 p-3 rounded-lg border-l-[3px] border-primary/30 transition-all duration-200 hover:bg-muted/50 hover:border-primary">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                      <Badge class="bg-primary/10 text-primary border border-primary/20">{{ seguimiento.tipo }}</Badge>
                      <Clock class="h-3 w-3" />
                      {{ formatDateTime(seguimiento.fecha_seguimiento) }}
                      <span class="text-primary">•</span>
                      {{ seguimiento.asesor.name }}
                    </div>
                    <p class="text-sm">{{ seguimiento.descripcion }}</p>
                    <p v-if="seguimiento.proximo_seguimiento" class="text-xs text-muted-foreground mt-2 flex items-center gap-1">
                      <Calendar class="h-3 w-3" />
                      Próximo seguimiento: {{ formatDate(seguimiento.proximo_seguimiento) }}
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Columna lateral -->
        <div class="space-y-6">
          <!-- Asesor Asignado -->
          <Card class="transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
            <CardHeader>
              <CardTitle class="text-base">Asesor Asignado</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary/10 to-primary/20 flex items-center justify-center border-2 border-primary/20">
                  <User class="h-5 w-5 text-primary" />
                </div>
                <div>
                  <p class="font-semibold">{{ negocio.asesor.name }}</p>
                  <p class="text-sm text-muted-foreground">{{ negocio.asesor.email }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Acciones Rápidas -->
          <Card class="transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
            <CardHeader>
              <CardTitle class="text-base">Acciones Rápidas</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
              <Button variant="outline" class="w-full justify-start transition-all duration-200 hover:border-primary hover:bg-primary/5 hover:translate-x-1" as-child>
                <Link :href="`/leads/${negocio.lead.id}`">
                  <User class="mr-2 h-4 w-4" />
                  Ver Perfil del Lead
                </Link>
              </Button>
              <Button variant="outline" class="w-full justify-start transition-all duration-200 hover:border-primary hover:bg-primary/5 hover:translate-x-1" as-child>
                <a :href="`tel:${negocio.lead.numero_1}`">
                  <Phone class="mr-2 h-4 w-4" />
                  Llamar al Cliente
                </a>
              </Button>
              <Button variant="outline" class="w-full justify-start transition-all duration-200 hover:border-primary hover:bg-primary/5 hover:translate-x-1" @click="showSeguimientoDialog = true">
                <Plus class="mr-2 h-4 w-4" />
                Agregar Seguimiento
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>