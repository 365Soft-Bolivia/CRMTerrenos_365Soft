<script setup lang="ts">
import { computed } from 'vue';
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
import { ArrowLeft, Pencil, Ban, Phone, Mail, MapPin, User, Calendar } from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';
import { useConfirm } from 'primevue/useconfirm';

// Props
const props = defineProps<{
  lead: any;
}>();

// Notificaciones
const { showSuccess, showError } = useNotification();
// Confirmación (PrimeVue)
const confirm = useConfirm();

// Estado derivado de props
const lead = computed(() => props.lead);
const loading = computed(() => !props.lead);

// Desactivar lead con modal de confirmación
const confirmDeactivateLead = () => {
  if (!lead.value) return;

  confirm.require({
    message: `¿Estás seguro de desactivar al lead ${lead.value.nombre}?`,
    header: 'Desactivar Lead',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Desactivar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(`/leads/${lead.value.id}`, {
        onSuccess: () => {
          showSuccess('Lead desactivado', 'El lead ha sido desactivado correctamente.');
          router.visit('/leads');
        },
        onError: () => {
          showError('Error al desactivar el lead', 'Ocurrió un problema al intentar desactivar el lead.');
        },
      });
    },
  });
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

</script>

<template>
  <Head title="Detalle del Lead" />

  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="icon" @click="router.visit('/leads')">
            <ArrowLeft class="h-4 w-4" />
          </Button>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">
              {{ lead?.nombre || 'Detalle del Lead' }}
            </h1>
            <p class="text-muted-foreground">
              Información completa del cliente potencial
            </p>
          </div>
        </div>

        <div v-if="!loading && lead" class="flex gap-2">
          <Button variant="outline" as-child>
            <Link :href="`/leads/${lead.id}/editar`">
              <Pencil class="mr-2 h-4 w-4" />
              Editar
            </Link>
          </Button>
          <Button variant="destructive" @click="confirmDeactivateLead">
            <Ban class="mr-2 h-4 w-4" />
            Desactivar
          </Button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-8">
        <p class="text-muted-foreground">Cargando información...</p>
      </div>

      <!-- Contenido -->
      <div v-else-if="lead" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna principal -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Información Personal -->
          <Card>
            <CardHeader>
              <CardTitle>Información Personal</CardTitle>
              <CardDescription>Datos de contacto del lead</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-start gap-3">
                  <User class="h-5 w-5 text-muted-foreground mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Nombre</p>
                    <p class="text-base font-semibold">{{ lead.nombre }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <User class="h-5 w-5 text-muted-foreground mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Carnet/CI</p>
                    <p class="text-base font-semibold">{{ lead.carnet }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <Phone class="h-5 w-5 text-muted-foreground mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Teléfono Principal</p>
                    <p class="text-base font-semibold">{{ lead.numero_1 }}</p>
                  </div>
                </div>

                <div v-if="lead.numero_2" class="flex items-start gap-3">
                  <Phone class="h-5 w-5 text-muted-foreground mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Teléfono Secundario</p>
                    <p class="text-base font-semibold">{{ lead.numero_2 }}</p>
                  </div>
                </div>

                <div v-if="lead.direccion" class="flex items-start gap-3 md:col-span-2">
                  <MapPin class="h-5 w-5 text-muted-foreground mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Dirección</p>
                    <p class="text-base">{{ lead.direccion }}</p>
                  </div>
                </div>
              </div>

              <Separator />

              <div class="flex items-start gap-3">
                <Calendar class="h-5 w-5 text-muted-foreground mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Fecha de Registro</p>
                  <p class="text-base">{{ formatDate(lead.created_at) }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Información del Negocio -->
          <Card v-if="lead.negocio">
            <CardHeader>
              <CardTitle>Negocio Asociado</CardTitle>
              <CardDescription>
                Información del negocio relacionado con este lead
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Etapa Actual</p>
                  <Badge class="mt-1">{{ lead.negocio.etapa }}</Badge>
                </div>

                <div>
                  <p class="text-sm font-medium text-muted-foreground">Fecha de Inicio</p>
                  <p class="text-base">{{ formatDate(lead.negocio.fecha_inicio) }}</p>
                </div>

                <div v-if="lead.negocio.terreno">
                  <p class="text-sm font-medium text-muted-foreground">Terreno</p>
                  <p class="text-base font-semibold">
                    {{ lead.negocio.terreno.ubicacion || 'N/A' }}
                  </p>
                  <p v-if="lead.negocio.terreno.proyecto" class="text-sm text-muted-foreground">
                    Proyecto: {{ lead.negocio.terreno.proyecto.nombre }}
                  </p>
                </div>

                <div v-if="lead.negocio.monto_estimado">
                  <p class="text-sm font-medium text-muted-foreground">Monto Estimado</p>
                  <p class="text-base font-semibold">
                    Bs. {{ parseFloat(lead.negocio.monto_estimado).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}
                  </p>
                </div>
              </div>

              <div v-if="lead.negocio.notas">
                <Separator class="my-4" />
                <p class="text-sm font-medium text-muted-foreground">Notas</p>
                <p class="text-base mt-2">{{ lead.negocio.notas }}</p>
              </div>

              <Separator />

              <Button variant="outline" as-child class="w-full">
                <Link :href="`/negocios/${lead.negocio.id}`">
                  Ver Detalle del Negocio
                </Link>
              </Button>
            </CardContent>
          </Card>

          <!-- Sin Negocio -->
          <Card v-else>
            <CardHeader>
              <CardTitle>Sin Negocio Asociado</CardTitle>
              <CardDescription>
                Este lead aún no tiene un negocio creado
              </CardDescription>
            </CardHeader>
            <CardContent>
              <p class="text-muted-foreground mb-4">
                Puedes crear un negocio editando este lead y activando la opción "Crear Acuerdo".
              </p>
              <Button variant="outline" as-child>
                <Link :href="`/leads/${lead.id}/editar`">
                  <Edit class="mr-2 h-4 w-4" />
                  Editar Lead
                </Link>
              </Button>
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
                  <p class="font-semibold">{{ lead.asesor?.name || 'Sin asignar' }}</p>
                  <p class="text-sm text-muted-foreground">{{ lead.asesor?.email || '' }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Estado -->
          <Card>
            <CardHeader>
              <CardTitle>Estado del Lead</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">Estado</span>
                  <Badge v-if="lead.negocio" variant="default">Activo</Badge>
                  <Badge v-else variant="secondary">Pendiente</Badge>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">Tiene Negocio</span>
                  <span class="text-sm font-medium">{{ lead.negocio ? 'Sí' : 'No' }}</span>
                </div>
                <div v-if="lead.negocio" class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">Cliente Convertido</span>
                  <span class="text-sm font-medium">
                    {{ lead.negocio.convertido_cliente ? 'Sí' : 'No' }}
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Historial de Seguimientos -->
          <Card v-if="lead.negocio?.seguimientos && lead.negocio.seguimientos.length > 0">
            <CardHeader>
              <CardTitle>Seguimientos Recientes</CardTitle>
              <CardDescription>
                Últimos {{ Math.min(3, lead.negocio.seguimientos.length) }} seguimientos
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div
                  v-for="seguimiento in lead.negocio.seguimientos.slice(0, 3)"
                  :key="seguimiento.id"
                  class="text-sm"
                >
                  <div class="flex items-center gap-2 mb-1">
                    <Badge variant="outline" class="text-xs">{{ seguimiento.tipo }}</Badge>
                    <span class="text-xs text-muted-foreground">
                      {{ formatDate(seguimiento.fecha_seguimiento) }}
                    </span>
                  </div>
                  <p class="text-muted-foreground">{{ seguimiento.descripcion }}</p>
                </div>
              </div>

              <Separator class="my-4" />

              <Button variant="outline" as-child class="w-full" size="sm">
                <Link :href="`/negocios/${lead.negocio.id}`">
                  Ver Todos los Seguimientos
                </Link>
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Error -->
      <div v-else class="text-center py-8">
        <p class="text-muted-foreground">No se pudo cargar la información del lead</p>
      </div>
    </div>
  </AppLayout>
</template>