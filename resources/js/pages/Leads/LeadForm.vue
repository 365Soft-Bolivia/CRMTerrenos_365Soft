<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
import { ArrowLeft, Save } from 'lucide-vue-next';
import axios from 'axios';

// Props
const props = defineProps<{
  leadId?: number;
}>();

// Interfaces
interface Terreno {
  id: number;
  label: string;
  precio?: number;
}

// Estado del formulario
const form = ref({
  nombre: '',
  carnet: '',
  numero_1: '',
  numero_2: '',
  direccion: '',
  crear_acuerdo: false,
  terreno_id: '' as string,
  etapa: '🟡 Interés Generado',
  fecha_inicio: new Date().toISOString().split('T')[0],
  monto_estimado: '' as string,
  notas: '',
});

const errors = ref<Record<string, string>>({});
const loading = ref(false);
const loadingData = ref(false);

// Datos para dropdowns
const terrenos = ref<Terreno[]>([]);
const etapas = [
  '🟡 Interés Generado',
  '🔵 Contacto Inicial',
  '🟢 Visita Programada',
  '🟢 Propuesta / Oferta',
  '🟠 Negociación',
  '🟢 Cierre / Venta Concretada',
  '🔴 Perdido / No Concretado',
];

// Computed
const isEdit = computed(() => !!props.leadId);
const pageTitle = computed(() => isEdit.value ? 'Editar Lead' : 'Nuevo Lead');

// Cargar terrenos disponibles
const fetchTerrenos = async () => {
  try {
    const response = await axios.get('/api/terrenos/dropdown', {
      params: { solo_disponibles: true }
    });
    terrenos.value = response.data.data || [];
  } catch (error) {
    console.error('Error al cargar terrenos:', error);
  }
};

// Cargar datos del lead si es edición
const fetchLead = async () => {
  if (!props.leadId) return;

  try {
    loadingData.value = true;
    const response = await axios.get(`/api/leads/${props.leadId}`);
    const lead = response.data.data;

    form.value.nombre = lead.nombre;
    form.value.carnet = lead.carnet;
    form.value.numero_1 = lead.numero_1;
    form.value.numero_2 = lead.numero_2 || '';
    form.value.direccion = lead.direccion || '';
  } catch (error) {
    console.error('Error al cargar lead:', error);
    alert('Error al cargar los datos del lead');
    router.visit('/leads');
  } finally {
    loadingData.value = false;
  }
};

// Validar formulario
const validateForm = () => {
  errors.value = {};

  if (!form.value.nombre.trim()) {
    errors.value.nombre = 'El nombre es obligatorio';
  }

  if (!form.value.carnet.trim()) {
    errors.value.carnet = 'El carnet/CI es obligatorio';
  }

  if (!form.value.numero_1.trim()) {
    errors.value.numero_1 = 'El número de teléfono es obligatorio';
  }

  if (form.value.crear_acuerdo) {
    if (!form.value.terreno_id) {
      errors.value.terreno_id = 'Debe seleccionar un terreno';
    }
    if (!form.value.fecha_inicio) {
      errors.value.fecha_inicio = 'La fecha de inicio es obligatoria';
    }
  }

  return Object.keys(errors.value).length === 0;
};

// Guardar lead
const handleSubmit = async () => {
  if (!validateForm()) {
    alert('Por favor, completa todos los campos obligatorios');
    return;
  }

  try {
    loading.value = true;

    const payload: any = {
      nombre: form.value.nombre,
      carnet: form.value.carnet,
      numero_1: form.value.numero_1,
      numero_2: form.value.numero_2,
      direccion: form.value.direccion,
    };

    // Si no es edición, incluir datos del negocio
    if (!isEdit.value) {
      payload.crear_acuerdo = form.value.crear_acuerdo;
      
      if (form.value.crear_acuerdo) {
        payload.terreno_id = parseInt(form.value.terreno_id);
        payload.etapa = form.value.etapa;
        payload.fecha_inicio = form.value.fecha_inicio;
        payload.monto_estimado = form.value.monto_estimado ? parseFloat(form.value.monto_estimado) : null;
        payload.notas = form.value.notas;
      }
    }

    if (isEdit.value) {
      await axios.put(`/api/leads/${props.leadId}`, payload);
    } else {
      await axios.post('/api/leads', payload);
    }

    router.visit('/leads');
  } catch (error: any) {
    console.error('Error al guardar lead:', error);
    
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      alert(error.response?.data?.message || 'Error al guardar el lead');
    }
  } finally {
    loading.value = false;
  }
};

// Cargar datos al montar
onMounted(() => {
  fetchTerrenos();
  if (isEdit.value) {
    fetchLead();
  }
});
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

      <div v-if="loadingData" class="flex justify-center py-8">
        <p class="text-muted-foreground">Cargando datos...</p>
      </div>

      <form v-else @submit.prevent="handleSubmit" class="space-y-6">
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
                :class="{ 'border-destructive': errors.nombre }"
              />
              <p v-if="errors.nombre" class="text-sm text-destructive">
                {{ errors.nombre }}
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
                :class="{ 'border-destructive': errors.carnet }"
              />
              <p v-if="errors.carnet" class="text-sm text-destructive">
                {{ errors.carnet }}
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
                  :class="{ 'border-destructive': errors.numero_1 }"
                />
                <p v-if="errors.numero_1" class="text-sm text-destructive">
                  {{ errors.numero_1 }}
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

        <!-- Crear Acuerdo (solo en modo creación) -->
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
                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary"
              />
              <Label for="crear_acuerdo" class="cursor-pointer">
                ✅ Crear Acuerdo
              </Label>
            </div>

            <!-- Campos adicionales si crear_acuerdo está activo -->
            <div v-if="form.crear_acuerdo" class="space-y-4 pt-4 border-t">
              <!-- Terreno -->
              <div class="space-y-2">
                <Label for="terreno_id">
                  Terreno <span class="text-destructive">*</span>
                </Label>
                <select
                  id="terreno_id"
                  v-model="form.terreno_id"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                  :class="{ 'border-destructive': errors.terreno_id }"
                >
                  <option value="">Selecciona un terreno</option>
                  <option
                    v-for="terreno in terrenos"
                    :key="terreno.id"
                    :value="terreno.id"
                  >
                    {{ terreno.label }}
                  </option>
                </select>
                <p v-if="errors.terreno_id" class="text-sm text-destructive">
                  {{ errors.terreno_id }}
                </p>
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
                  :class="{ 'border-destructive': errors.fecha_inicio }"
                />
                <p v-if="errors.fecha_inicio" class="text-sm text-destructive">
                  {{ errors.fecha_inicio }}
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
            :disabled="loading"
          >
            Cancelar
          </Button>
          <Button type="submit" :disabled="loading">
            <Save class="mr-2 h-4 w-4" />
            {{ loading ? 'Guardando...' : 'Guardar Lead' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>