<script setup lang="ts">
import { computed } from 'vue';
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
import { ArrowLeft, Save } from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

// Props
interface Terreno {
  id: number;
  label: string;
  precio?: number;
}

const props = defineProps<{
  lead?: any;
  terrenos?: Terreno[];
}>();

// Notificaciones
const { showSuccess, showError } = useNotification();

// Estado del formulario (useForm de Inertia), inicializado con props.lead si existe
const form = useForm({
  nombre: props.lead?.nombre || '',
  carnet: props.lead?.carnet || '',
  numero_1: props.lead?.numero_1 || '',
  numero_2: props.lead?.numero_2 || '',
  direccion: props.lead?.direccion || '',
  crear_acuerdo: false,
  terreno_id: '' as string,
  etapa: '🟡 Interés Generado',
  fecha_inicio: new Date().toISOString().split('T')[0],
  monto_estimado: '' as string,
  notas: '',
});

// Datos para dropdowns desde props
const terrenos = computed(() => props.terrenos || []);
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
const isEdit = computed(() => !!props.lead);
const pageTitle = computed(() => isEdit.value ? 'Editar Lead' : 'Nuevo Lead');

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
        // Esperar un poco antes de navegar para que el toast sea visible
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
        // Esperar un poco antes de navegar para que el toast sea visible
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
                  :class="{ 'border-destructive': form.errors.terreno_id }"
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
                <p v-if="form.errors.terreno_id" class="text-sm text-destructive">
                  {{ form.errors.terreno_id }}
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
  </AppLayout>
</template>