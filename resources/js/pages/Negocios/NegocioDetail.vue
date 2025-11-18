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
  Edit,
  Trash2,
  Phone,
  MapPin,
  User,
  Calendar,
  DollarSign,
  Plus,
  MessageSquare,
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
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="icon" @click="router.visit('/negocios')">
            <ArrowLeft class="h-4 w-4" />
          </Button>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">
              {{ negocio?.lead.nombre || 'Detalle del Negocio' }}
            </h1>
            <p class="text-muted-foreground">
              Información completa del negocio y seguimientos
            </p>
          </div>
        </div>

        <div v-if="!loading && negocio" class="flex gap-2">
          <Button variant="destructive" @click="deleteNegocio">
            <Trash2 class="mr-2 h-4 w-4" />
            Eliminar
          </Button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-8">
        <p class="text-muted-foreground">Cargando información...</p>
      </div>

      <!-- Contenido -->
      <div v-else-if="negocio" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna principal -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Información del Negocio -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <CardTitle>Información del Negocio</CardTitle>
                <Badge>{{ negocio.etapa }}</Badge>
              </div>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-start gap-3">
                  <Calendar class="h-5 w-5 text-muted-foreground mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Fecha de Inicio</p>
                    <p class="text-base font-semibold">{{ formatDate(negocio.fecha_inicio) }}</p>
                  </div>
                </div>

                <div v-if="negocio.monto_estimado" class="flex items-start gap-3">
                  <DollarSign class="h-5 w-5 text-muted-foreground mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Monto Estimado</p>
                    <p class="text-base font-semibold text-green-600">
                      {{ formatCurrency(negocio.monto_estimado) }}
                    </p>
                  </div>
                </div>
              </div>

              <Separator />

              <!-- Información del Terreno -->
              <div>
                <h3 class="font-semibold mb-3">Terreno</h3>
                <div class="space-y-2">
                  <div class="flex items-start gap-3">
                    <MapPin class="h-5 w-5 text-muted-foreground mt-0.5" />
                    <div>
                      <p class="text-sm font-medium text-muted-foreground">Ubicación</p>
                      <p class="text-base">{{ negocio.terreno.ubicacion }}</p>
                      <p v-if="negocio.terreno.numero_terreno" class="text-sm text-muted-foreground">
                        Número: {{ negocio.terreno.numero_terreno }}
                      </p>
                    </div>
                  </div>

                  <div v-if="negocio.terreno.proyecto" class="text-sm">
                    <span class="text-muted-foreground">Proyecto:</span>
                    <span class="ml-2 font-medium">{{ negocio.terreno.proyecto.nombre }}</span>
                  </div>

                  <div v-if="negocio.terreno.categoria" class="text-sm">
                    <span class="text-muted-foreground">Categoría:</span>
                    <span class="ml-2 font-medium">{{ negocio.terreno.categoria.nombre }}</span>
                  </div>

                  <div v-if="negocio.terreno.cuadra?.barrio" class="text-sm">
                    <span class="text-muted-foreground">Barrio:</span>
                    <span class="ml-2 font-medium">{{ negocio.terreno.cuadra.barrio.nombre }}</span>
                  </div>
                </div>
              </div>

              <Separator v-if="negocio.notas" />

              <!-- Notas -->
              <div v-if="negocio.notas">
                <h3 class="font-semibold mb-2">Notas</h3>
                <p class="text-sm text-muted-foreground">{{ negocio.notas }}</p>
              </div>
            </CardContent>
          </Card>

          <!-- Información del Lead -->
          <Card>
            <CardHeader>
              <CardTitle>Información del Cliente</CardTitle>
              <CardDescription>
                <Link :href="`/leads/${negocio.lead.id}`" class="text-primary hover:underline">
                  Ver perfil completo del lead
                </Link>
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-start gap-3">
                  <User class="h-5 w-5 text-muted-foreground mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Nombre</p>
                    <p class="text-base font-semibold">{{ negocio.lead.nombre }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <User class="h-5 w-5 text-muted-foreground mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Carnet/CI</p>
                    <p class="text-base font-semibold">{{ negocio.lead.carnet }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <Phone class="h-5 w-5 text-muted-foreground mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Teléfono Principal</p>
                    <p class="text-base font-semibold">{{ negocio.lead.numero_1 }}</p>
                  </div>
                </div>

                <div v-if="negocio.lead.numero_2" class="flex items-start gap-3">
                  <Phone class="h-5 w-5 text-muted-foreground mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Teléfono Secundario</p>
                    <p class="text-base font-semibold">{{ negocio.lead.numero_2 }}</p>
                  </div>
                </div>

                <div v-if="negocio.lead.direccion" class="flex items-start gap-3 md:col-span-2">
                  <MapPin class="h-5 w-5 text-muted-foreground mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Dirección</p>
                    <p class="text-base">{{ negocio.lead.direccion }}</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Timeline de Seguimientos -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle>Historial de Seguimientos</CardTitle>
                  <CardDescription>
                    {{ seguimientos.length }} seguimiento(s) registrado(s)
                  </CardDescription>
                </div>
                <Dialog v-model:open="showSeguimientoDialog">
                  <DialogTrigger as-child>
                    <Button>
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
              <div v-if="seguimientos.length === 0" class="text-center py-8">
                <MessageSquare class="h-12 w-12 text-muted-foreground mx-auto mb-2" />
                <p class="text-muted-foreground">No hay seguimientos registrados</p>
                <p class="text-sm text-muted-foreground">
                  Agrega el primer seguimiento para comenzar el historial
                </p>
              </div>

              <!-- Timeline -->
              <div v-else class="space-y-4">
                <div
                  v-for="seguimiento in seguimientos"
                  :key="seguimiento.id"
                  class="flex gap-4 pb-4 border-b last:border-b-0"
                >
                  <div class="flex-shrink-0">
                    <Badge variant="outline">{{ seguimiento.tipo }}</Badge>
                  </div>
                  <div class="flex-1 space-y-1">
                    <p class="text-sm text-muted-foreground">
                      {{ formatDateTime(seguimiento.fecha_seguimiento) }}
                      • {{ seguimiento.asesor.name }}
                    </p>
                    <p class="text-sm">{{ seguimiento.descripcion }}</p>
                    <p v-if="seguimiento.proximo_seguimiento" class="text-xs text-muted-foreground">
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
          <Card>
            <CardHeader>
              <CardTitle>Asesor Asignado</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
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
          <Card>
            <CardHeader>
              <CardTitle>Acciones Rápidas</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
              <Button variant="outline" class="w-full justify-start" as-child>
                <Link :href="`/leads/${negocio.lead.id}`">
                  <User class="mr-2 h-4 w-4" />
                  Ver Perfil del Lead
                </Link>
              </Button>
              <Button variant="outline" class="w-full justify-start" as-child>
                <a :href="`tel:${negocio.lead.numero_1}`">
                  <Phone class="mr-2 h-4 w-4" />
                  Llamar al Cliente
                </a>
              </Button>
              <Button variant="outline" class="w-full justify-start" @click="showSeguimientoDialog = true">
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