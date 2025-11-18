<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Plus, Search, Eye, Edit, Trash2 } from 'lucide-vue-next';
import axios from 'axios';

// Interfaces
interface Asesor {
  id: number;
  name: string;
  email: string;
}

interface Negocio {
  id: number;
  etapa: string;
}

interface Lead {
  id: number;
  nombre: string;
  carnet: string;
  numero_1: string;
  numero_2?: string;
  direccion?: string;
  asesor?: Asesor;
  negocio?: Negocio;
  created_at: string;
  updated_at: string;
}

// Estado
const leads = ref<Lead[]>([]);
const loading = ref(true);
const searchTerm = ref('');

// Función para cargar leads
const fetchLeads = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/leads', {
      params: {
        buscar: searchTerm.value || undefined,
        per_page: 50
      }
    });
    leads.value = response.data.data.data || [];
  } catch (error) {
    console.error('Error al cargar leads:', error);
  } finally {
    loading.value = false;
  }
};

// Función para eliminar lead
const deleteLead = async (id: number) => {
  if (!confirm('¿Estás seguro de eliminar este lead?')) return;
  
  try {
    await axios.delete(`/api/leads/${id}`);
    await fetchLeads();
  } catch (error) {
    console.error('Error al eliminar lead:', error);
    alert('Error al eliminar el lead');
  }
};

// Buscar leads
const handleSearch = () => {
  fetchLeads();
};

// Cargar leads al montar el componente
onMounted(() => {
  fetchLeads();
});
</script>

<template>
  <Head title="Contacto de Leads" />

  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Contacto de Leads</h1>
          <p class="text-muted-foreground">
            Gestiona tus clientes potenciales y crea nuevos negocios
          </p>
        </div>
        <Button as-child>
          <Link href="/leads/crear">
            <Plus class="mr-2 h-4 w-4" />
            Nuevo Lead
          </Link>
        </Button>
      </div>

      <!-- Filtros y búsqueda -->
      <Card>
        <CardHeader>
          <CardTitle>Buscar Leads</CardTitle>
          <CardDescription>
            Busca por nombre, carnet o número de teléfono
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex gap-4">
            <div class="flex-1">
              <Input
                v-model="searchTerm"
                placeholder="Buscar..."
                @keyup.enter="handleSearch"
              />
            </div>
            <Button @click="handleSearch">
              <Search class="mr-2 h-4 w-4" />
              Buscar
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Tabla de leads -->
      <Card>
        <CardHeader>
          <CardTitle>Lista de Leads</CardTitle>
          <CardDescription>
            Total de leads: {{ leads.length }}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="loading" class="flex justify-center py-8">
            <p class="text-muted-foreground">Cargando leads...</p>
          </div>

          <div v-else-if="leads.length === 0" class="text-center py-8">
            <p class="text-muted-foreground">No hay leads registrados</p>
          </div>

          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead>Nombre</TableHead>
                <TableHead>Carnet/CI</TableHead>
                <TableHead>Teléfono</TableHead>
                <TableHead>Asesor</TableHead>
                <TableHead>Estado</TableHead>
                <TableHead class="text-right">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="lead in leads" :key="lead.id">
                <TableCell class="font-medium">{{ lead.nombre }}</TableCell>
                <TableCell>{{ lead.carnet }}</TableCell>
                <TableCell>{{ lead.numero_1 }}</TableCell>
                <TableCell>{{ lead.asesor?.name || 'N/A' }}</TableCell>
                <TableCell>
                  <Badge v-if="lead.negocio" variant="default">
                    Con Negocio
                  </Badge>
                  <Badge v-else variant="secondary">
                    Sin Negocio
                  </Badge>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="icon" as-child>
                      <Link :href="`/leads/${lead.id}`">
                        <Eye class="h-4 w-4" />
                      </Link>
                    </Button>
                    <Button variant="ghost" size="icon" as-child>
                      <Link :href="`/leads/${lead.id}/editar`">
                        <Edit class="h-4 w-4" />
                      </Link>
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      @click="deleteLead(lead.id)"
                    >
                      <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>