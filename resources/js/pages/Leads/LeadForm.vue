<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ArrowLeft, Save, Map, Search, XCircle } from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';
import axios from "axios";
import SeleccionTerrenoMapa from '@/components/SeleccionTerrenoMapa.vue';

// Props
interface Terreno {
  id: number;
  precio?: number;
  area?: number;
  codigo?: string;
  numero?: string;
  ubicacion?: string
  numero_terreno?: string
}

interface Proyecto {
  id: number;
  nombre: string;
}

interface Barrio {
  id: number;
  nombre: string;
}

interface Cuadra {
  id: number;
  nombre: string;
}

const props = defineProps<{
  lead?: any;
  terrenos?: Terreno[];
}>();

// Notificaciones
const { showSuccess, showError } = useNotification();

type LeadFormData = {
  nombre: string;
  carnet: string;
  numero_1: string;
  numero_2: string;
  direccion: string;
  crear_acuerdo: boolean;
  terreno_id: number | null;
  etapa: string;
  fecha_inicio: string;
  monto_estimado: string;
  notas: string;
};

// Estado del formulario (useForm de Inertia), inicializado con props.lead si existe
const form = useForm<LeadFormData>({
  nombre: props.lead?.nombre || '',
  carnet: props.lead?.carnet || '',
  numero_1: props.lead?.numero_1 || '',
  numero_2: props.lead?.numero_2 || '',
  direccion: props.lead?.direccion || '',
  crear_acuerdo: false,
  terreno_id: null,
  etapa: 'Interés Generado',
  fecha_inicio: new Date().toISOString().split('T')[0],
  monto_estimado: '',
  notas: '',
});

// Datos para dropdowns desde props
const terrenos = computed<Terreno[]>(() => props.terrenos ?? []);
const etapas = [
  'Interés Generado',
  'Contacto Inicial',
  'Visita Programada',
  'Propuesta / Oferta',
  'Negociación',
  'Cierre / Venta Concretada',
  'Perdido / No Concretado',
];

// Computed
const isEdit = computed(() => !!props.lead);
const pageTitle = computed(() => isEdit.value ? 'Editar Lead' : 'Nuevo Lead');

// Estado del mapa
const showMapa = ref(false);

// ===== NUEVOS ESTADOS PARA CONTROL DE MÉTODOS DE BÚSQUEDA =====
const metodoSeleccionado = ref<'mapa' | 'codigo' | 'cascada' | null>(null);

// Computed para deshabilitar métodos
const mapaDeshabilitado = computed(() => 
  metodoSeleccionado.value !== null && metodoSeleccionado.value !== 'mapa'
);

const codigoDeshabilitado = computed(() => 
  metodoSeleccionado.value !== null && metodoSeleccionado.value !== 'codigo'
);

const cascadaDeshabilitada = computed(() => 
  metodoSeleccionado.value !== null && metodoSeleccionado.value !== 'cascada'
);

// Guardar lead
const handleSubmit = async () => {
  // Preparar payload adicional solo cuando se crea y se activa "Crear Acuerdo"
  if (!isEdit.value && form.crear_acuerdo) {
    form.terreno_id = form.terreno_id ? parseInt(form.terreno_id as unknown as string) as any : ('' as any);
    form.monto_estimado = form.monto_estimado
      ? (parseFloat(form.monto_estimado as unknown as string) as any)
      : ('' as any);
  }

  const url = isEdit.value && props.lead ? `/api/leads/${props.lead.id}` : '/api/leads';

  form.clearErrors();

  if (isEdit.value) {
    form.put(url, {
      onSuccess: () => {
        showSuccess('Lead actualizado', `El lead ${form.nombre} ha sido actualizado correctamente.`);
        setTimeout(() => {
          router.visit('/leads');
        }, 2000);
      },
      onError: (errors) => {
        console.error('Errores de validación al actualizar lead:', errors);
        const first = errors ? (Object.values(errors)[0] as any) : null;
        const detail = Array.isArray(first) ? first[0] : first || 'Por favor, verifica los datos ingresados e intenta nuevamente.';
        showError('Error al guardar el lead', detail as string);
      },
    });
  } else {
    form.post(url, {
      onSuccess: () => {
        showSuccess('Lead creado', `El lead ${form.nombre} ha sido creado correctamente.`);
        setTimeout(() => {
          router.visit('/leads');
        }, 2000);
      },
      onError: (errors) => {
        console.error('Errores de validación al crear lead:', errors);
        const first = errors ? (Object.values(errors)[0] as any) : null;
        const detail = Array.isArray(first) ? first[0] : first || 'Por favor, verifica los datos ingresados e intenta nuevamente.';
        showError('Error al guardar el lead', detail as string);
      },
    });
  }
};

// =======================
// FILTROS
// =======================
const filtros = reactive({
  proyecto_id: '',
  barrio_id: '',
  cuadra_id: '',
  buscar_codigo: ''
});

onMounted(() => {
  cargarProyectos();
});

const cargarProyectos = async () => {
  try {
    const { data } = await axios.get("/api/terrenos/proyectos");
    if (data.success) {
      proyectos.value = data.data;
    }
  } catch (error) {
    console.error('Error cargando proyectos:', error);
  }
};

// ==============================
// CUANDO CAMBIA PROYECTO
// ==============================
watch(() => filtros.proyecto_id, async (id) => {
  barrios.value = [];
  cuadras.value = [];
  terrenosSelect.value = [];

  if (!id) return;

  const { data } = await axios.get("/api/terrenos/barrios", {
    params: { proyecto_id: id }
  });

  if (data.success) {
    barrios.value = data.data;
  }
});

// ==============================
// CUANDO CAMBIA BARRIO
// ==============================
watch(() => filtros.barrio_id, async (id) => {
  cuadras.value = [];
  terrenosSelect.value = [];

  // Activar método cascada cuando se selecciona barrio
  if (id && !metodoSeleccionado.value) {
    metodoSeleccionado.value = 'cascada';
  }

  if (!id) return;

  const { data } = await axios.get("/api/terrenos/cuadras", {
    params: { barrio_id: id }
  });

  if (data.success) {
    cuadras.value = data.data;
  }
});

// ==============================
// CUANDO CAMBIA CUADRA
// ==============================
watch(() => filtros.cuadra_id, async (id) => {
  terrenosSelect.value = [];

  if (!id) return;

  const { data } = await axios.get("/api/terrenos/por-cuadra", {
    params: { cuadra_id: id }
  });

  if (data.success) {
    terrenosSelect.value = data.data;
  }
});

// =======================
// DATA LISTS
// =======================
const proyectos = ref<{ id: number; nombre: string }[]>([]);
const barrios   = ref<{ id: number; nombre: string }[]>([]);
const cuadras   = ref<{ id: number; nombre: string }[]>([]);
const terrenosSelect  = ref<{ id: number; nombre: string }[]>([]);

// =======================
// Selected terreno
// =======================
const selectedTerreno = computed(() => {
  return terrenos.value.find(t => t.id == form.terreno_id) || null;
});

// =======================
// Estado Código encontrado
// =======================
const codigoValido = ref<boolean|null>(null);

// =======================
// FUNCIONES
// =======================
const buscarPorCodigo = async () => {
  try {
    if (!filtros.buscar_codigo || !filtros.proyecto_id) {
      codigoValido.value = false;
      return;
    }

    // Activar método código
    metodoSeleccionado.value = 'codigo';

    // Normalizar: quitar espacios y guiones
    const normalizar = (str: string) =>
      str.toLowerCase().replace(/[\s-]+/g, '').trim();

    const res = await axios.post('/api/leads/buscar-por-codigo', {
      codigo: normalizar(filtros.buscar_codigo),
      proyecto_id: filtros.proyecto_id
    });

    if (res.data?.success) {
      form.terreno_id = res.data.data.id;
      codigoValido.value = true;
      console.log("Terreno encontrado:", res.data.data);
    } else {
      codigoValido.value = false;
      console.log("Terreno no encontrado");
    }
  } catch (error: any) {
    console.error("Error buscando terreno:", error.response?.data ?? error);
    codigoValido.value = false;
  }
};

// Manejar selección desde el mapa
const handleTerrenoSeleccionado = (terreno: any) => {
  console.log('Terreno seleccionado desde mapa:', terreno);
  
  form.terreno_id = terreno.id;
  codigoValido.value = true;
  metodoSeleccionado.value = 'mapa';
  
  showSuccess('Terreno seleccionado', `Has seleccionado el terreno ${terreno.ubicacion}`);
};

// Cancelar selección de terreno
const cancelarSeleccionTerreno = () => {
  console.log('Cancelando selección de terreno');
  
  form.terreno_id = null;
  codigoValido.value = null;
  metodoSeleccionado.value = null;
  
  // Limpiar filtros
  filtros.buscar_codigo = '';
  filtros.barrio_id = '';
  filtros.cuadra_id = '';
  terrenosSelect.value = [];
  
  console.log('Selección cancelada y filtros limpiados');
};

// Abrir mapa
const abrirMapa = () => {
  console.log('Abriendo mapa. Proyecto ID:', filtros.proyecto_id);
  
  if (!filtros.proyecto_id) {
    console.warn('No hay proyecto seleccionado');
    showError('Selecciona un proyecto', 'Debes seleccionar un proyecto antes de abrir el mapa');
    return;
  }
  
  showMapa.value = true;
  metodoSeleccionado.value = 'mapa';
  
  console.log('Estado del mapa actualizado:', { showMapa: showMapa.value, metodo: metodoSeleccionado.value });
};
</script>

<template>
  <Head :title="pageTitle" />

  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Button variant="ghost" size="icon" @click="router.visit('/leads')">
          <ArrowLeft class="h-4 w-4" />
        </Button>
        <div>
          <h1 class="text-3xl font-bold tracking-tight">{{ pageTitle }}</h1>
          <p class="text-muted-foreground">
            {{ isEdit ? 'Actualiza la información del lead' : 'Registra un nuevo cliente potencial' }}
          </p>
        </div>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Información del Lead -->
        <Card>
          <CardHeader>
            <CardTitle>Información del Lead</CardTitle>
            <CardDescription>
              Datos de contacto del cliente potencial
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- Nombre -->
            <div class="space-y-2">
              <Label for="nombre">
                Nombre <span class="text-destructive">*</span>
              </Label>
              <Input
                id="nombre"
                v-model="form.nombre"
                placeholder="Nombre completo del cliente"
                :class="{ 'border-destructive': form.errors.nombre }"
              />
              <p v-if="form.errors.nombre" class="text-sm text-destructive">
                {{ form.errors.nombre }}
              </p>
            </div>

            <!-- Carnet/CI -->
            <div class="space-y-2">
              <Label for="carnet">
                Carnet/CI <span class="text-destructive">*</span>
              </Label>
              <Input
                id="carnet"
                v-model="form.carnet"
                placeholder="Número de carnet o cédula de identidad"
                :class="{ 'border-destructive': form.errors.carnet }"
              />
              <p v-if="form.errors.carnet" class="text-sm text-destructive">
                {{ form.errors.carnet }}
              </p>
            </div>

            <!-- Teléfonos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="numero_1">
                  Teléfono Principal <span class="text-destructive">*</span>
                </Label>
                <Input
                  id="numero_1"
                  v-model="form.numero_1"
                  placeholder="Número de teléfono"
                  :class="{ 'border-destructive': form.errors.numero_1 }"
                />
                <p v-if="form.errors.numero_1" class="text-sm text-destructive">
                  {{ form.errors.numero_1 }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="numero_2">Teléfono Secundario</Label>
                <Input
                  id="numero_2"
                  v-model="form.numero_2"
                  placeholder="Número alternativo (opcional)"
                />
              </div>
            </div>

            <!-- Dirección -->
            <div class="space-y-2">
              <Label for="direccion">Dirección</Label>
              <Textarea
                id="direccion"
                v-model="form.direccion"
                placeholder="Dirección del cliente (opcional)"
                :rows="3"
              />
            </div>
          </CardContent>
        </Card>

        <!-- Opciones de Negocio -->
        <Card v-if="!isEdit">
          <CardHeader>
            <CardTitle>Opciones de Negocio</CardTitle>
            <CardDescription>
              Configura si deseas crear un negocio asociado a este lead
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- Checkbox: Crear Acuerdo -->
            <div class="flex items-center space-x-2">
              <input
                id="crear_acuerdo"
                type="checkbox"
                v-model="form.crear_acuerdo"
              />
              <Label for="crear_acuerdo" class="cursor-pointer">
                ✅ Crear Acuerdo
              </Label>
            </div>

            <!-- Campos adicionales si crear_acuerdo está activo -->
            <div v-if="form.crear_acuerdo" class="space-y-4 pt-4 border-t">
              <!-- Selección de Proyecto -->
              <div class="space-y-2">
                <Label for="proyecto_id">
                  Proyecto <span class="text-destructive">*</span>
                </Label>
                <select
                  id="proyecto_id"
                  v-model="filtros.proyecto_id"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                  <option value="">Selecciona un proyecto</option>
                  <option
                    v-for="proyecto in proyectos"
                    :key="proyecto.id"
                    :value="proyecto.id"
                  >
                    {{ proyecto.nombre }}
                  </option>
                </select>
              </div>

              <!-- Preview del terreno seleccionado -->
              <div v-if="selectedTerreno" class="bg-green-50 p-4 rounded-lg border-2 border-green-300 relative">
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  @click="cancelarSeleccionTerreno"
                  class="absolute top-2 right-2 h-8 w-8 text-muted-foreground hover:text-destructive"
                  title="Cancelar selección"
                >
                  <XCircle class="h-5 w-5" />
                </Button>
                
                <p class="font-semibold text-green-900 mb-3 flex items-center gap-2">
                  <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                  Terreno Seleccionado
                </p>
                <div class="grid grid-cols-2 gap-3 text-green-800 text-sm">
                  <div>
                    <span class="font-medium">Código:</span> {{ selectedTerreno.codigo }}
                  </div>
                  <div>
                    <span class="font-medium">Número:</span> {{ selectedTerreno.numero || 'N/A' }}
                  </div>
                  <div>
                    <span class="font-medium">Precio:</span> ${{ selectedTerreno.precio?.toLocaleString('es-AR') || 'N/A' }}
                  </div>
                  <div>
                    <span class="font-medium">Área:</span> {{ selectedTerreno.area }} m²
                  </div>
                </div>
              </div>

              <!-- Métodos de selección -->
              <div v-else class="space-y-4">
                <!-- Botón para abrir mapa -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <p class="text-sm text-blue-900 mb-3 font-medium">
                    Selecciona un terreno de las siguientes formas:
                  </p>
                  <div class="space-y-2">
                    <Button 
                      type="button" 
                      @click="abrirMapa" 
                      :disabled="!filtros.proyecto_id || mapaDeshabilitado"
                      class="w-full"
                      variant="default"
                    >
                      <Map class="mr-2 h-4 w-4" />
                      {{ mapaDeshabilitado ? '🔒 Mapa bloqueado' : 'Elegir terreno en el mapa' }}
                    </Button>
                    <p class="text-xs text-center text-muted-foreground">o busca manualmente:</p>
                  </div>
                </div>

                <!-- Búsqueda por código -->
                <div class="space-y-2">
                  <Label :class="{ 'opacity-50': codigoDeshabilitado }">
                    Búsqueda rápida por código
                  </Label>
                  <div class="flex gap-2">
                    <Input
                      v-model="filtros.buscar_codigo"
                      placeholder="Ej: UV001-MZ001-1"
                      @keyup.enter="buscarPorCodigo"
                      :disabled="!filtros.proyecto_id || codigoDeshabilitado"
                    />
                    <Button
                      type="button"
                      variant="outline"
                      size="icon"
                      @click="buscarPorCodigo"
                      :disabled="!filtros.proyecto_id || codigoDeshabilitado"
                    >
                      <Search class="h-4 w-4" />
                    </Button>
                  </div>
                  <div v-if="codigoValido !== null && metodoSeleccionado === 'codigo'" class="mt-2">
                    <span v-if="codigoValido" class="text-sm text-green-600 font-medium">
                      ✔ Código válido
                    </span>
                    <span v-else class="text-sm text-red-600 font-medium">
                      ✘ No existe
                    </span>
                  </div>
                </div>

              <!-- Reemplaza la sección de "Filtros jerárquicos" en tu LeadForm.vue -->
              <!-- Filtros jerárquicos -->
              <div class="p-4 rounded-lg bg-muted/50" :class="{ 'opacity-50': cascadaDeshabilitada }">
                <h3 class="font-semibold text-sm mb-4 flex items-center gap-2">
                  Selección Manual
                  <span v-if="cascadaDeshabilitada" class="text-xs text-muted-foreground font-normal">(bloqueado)</span>
                </h3>
                <div class="space-y-4">
                  <!-- Barrio -->
                  <div class="space-y-2">
                    <Label for="barrio_id">Barrio</Label>
                    <select
                      id="barrio_id"
                      v-model="filtros.barrio_id"
                      :disabled="!filtros.proyecto_id || cascadaDeshabilitada"
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      <option value="" class="text-foreground">Selecciona un barrio</option>
                      <option
                        v-for="barrio in barrios"
                        :key="barrio.id"
                        :value="barrio.id"
                        class="text-foreground"
                      >
                        {{ barrio.nombre }}
                      </option>
                    </select>
                  </div>

                  <!-- Cuadra -->
                  <div class="space-y-2">
                    <Label for="cuadra_id">Cuadra</Label>
                    <select
                      id="cuadra_id"
                      v-model="filtros.cuadra_id"
                      :disabled="!filtros.barrio_id || cascadaDeshabilitada"
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      <option value="" class="text-foreground">Selecciona una cuadra</option>
                      <option
                        v-for="cuadra in cuadras"
                        :key="cuadra.id"
                        :value="cuadra.id"
                        class="text-foreground"
                      >
                        {{ cuadra.nombre }}
                      </option>
                    </select>
                  </div>

                  <!-- Terreno -->
                  <div class="space-y-2">
                    <Label for="terreno_id">Terreno</Label>
                    <select
                      id="terreno_id"
                      v-model="form.terreno_id"
                      :disabled="!filtros.cuadra_id || cascadaDeshabilitada"
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      <option value="" class="text-foreground">Selecciona un terreno</option>
                      <option
                        v-for="terreno in terrenosSelect"
                        :key="terreno.id"
                        :value="terreno.id"
                        class="text-foreground"
                      >
                        {{ terreno.nombre }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

              <!-- Etapa -->
              <div class="space-y-2">
                <Label for="etapa">
                  Etapa del Acuerdo <span class="text-destructive">*</span>
                </Label>
                <select
                  id="etapa"
                  v-model="form.etapa"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  <option v-for="etapa in etapas" :key="etapa" :value="etapa">
                    {{ etapa }}
                  </option>
                </select>
              </div>

              <!-- Fecha de Inicio -->
              <div class="space-y-2">
                <Label for="fecha_inicio">
                  Fecha de Inicio <span class="text-destructive">*</span>
                </Label>
                <Input
                  id="fecha_inicio"
                  v-model="form.fecha_inicio"
                  type="date"
                  :class="{ 'border-destructive': form.errors.fecha_inicio }"
                />
                <p v-if="form.errors.fecha_inicio" class="text-sm text-destructive">
                  {{ form.errors.fecha_inicio }}
                </p>
              </div>

              <!-- Monto Estimado -->
              <div class="space-y-2">
                <Label for="monto_estimado">Monto Estimado</Label>
                <Input
                  id="monto_estimado"
                  v-model="form.monto_estimado"
                  type="number"
                  step="0.01"
                  placeholder="Monto estimado del negocio"
                />
              </div>

              <!-- Notas -->
              <div class="space-y-2">
                <Label for="notas">Notas</Label>
                <Textarea
                  id="notas"
                  v-model="form.notas"
                  placeholder="Notas adicionales sobre el negocio"
                  :rows="3"
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Botones de acción -->
        <div class="flex justify-end gap-4">
          <Button
            type="button"
            variant="outline"
            @click="router.visit('/leads')"
            :disabled="form.processing"
          >
            Cancelar
          </Button>
          <Button type="submit" :disabled="form.processing">
            <Save class="mr-2 h-4 w-4" />
            {{ form.processing ? 'Guardando...' : 'Guardar Lead' }}
          </Button>
        </div>
      </form>
    </div>

    <!-- Dialog del Mapa -->
    <SeleccionTerrenoMapa
      v-model:open="showMapa"
      :proyecto-id="filtros.proyecto_id"
      @terreno-seleccionado="handleTerrenoSeleccionado"
    />
  </AppLayout>
</template>