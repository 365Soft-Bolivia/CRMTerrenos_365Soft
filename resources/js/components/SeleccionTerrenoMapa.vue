<script setup lang="ts">
import { ref, onMounted, watch, computed, onUnmounted } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { MapPin, X, Check, Loader2, XCircle } from 'lucide-vue-next';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

interface Terreno {
  id: number;
  codigo: string;
  ubicacion: string;
  categoria: string;
  categoria_color: string;
  superficie: number;
  precio_venta: number;
  cuota_inicial?: number;
  cuota_mensual?: number;
  barrio?: string;
  cuadra?: string;
}

interface Props {
  open: boolean;
  proyectoId: number | string;
}

interface Emits {
  (e: 'update:open', value: boolean): void;
  (e: 'terreno-seleccionado', terreno: Terreno): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Estado
const loading = ref(true);
const error = ref<string | null>(null);
const map = ref<any>(null);
const barriosLayer = ref<any>(null);
const cuadrasLayer = ref<any>(null);
const terrenosLayer = ref<any>(null);
const selectedTerreno = ref<Terreno | null>(null);
const highlightedLayer = ref<any>(null);
const showInfoPanel = ref(false);

// Niveles de zoom
const ZOOM_BARRIOS = 15;
const ZOOM_CUADRAS = 17;
const ZOOM_TERRENOS = 18;

// Computed
const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
});

// Limpiar mapa completamente
const limpiarMapa = () => {
  console.log('🧹 Limpiando mapa completamente');
  
  // Limpiar selección
  if (highlightedLayer.value && terrenosLayer.value) {
    try {
      terrenosLayer.value.resetStyle(highlightedLayer.value);
    } catch (e) {
      console.warn('Error al resetear estilo:', e);
    }
  }
  highlightedLayer.value = null;
  selectedTerreno.value = null;
  showInfoPanel.value = false;
  
  // Remover capas
  if (barriosLayer.value && map.value) {
    try {
      map.value.removeLayer(barriosLayer.value);
    } catch (e) {}
    barriosLayer.value = null;
  }
  
  if (cuadrasLayer.value && map.value) {
    try {
      map.value.removeLayer(cuadrasLayer.value);
    } catch (e) {}
    cuadrasLayer.value = null;
  }
  
  if (terrenosLayer.value && map.value) {
    try {
      map.value.removeLayer(terrenosLayer.value);
    } catch (e) {}
    terrenosLayer.value = null;
  }
  
  // Destruir mapa completamente
  if (map.value) {
    try {
      map.value.remove();
    } catch (e) {
      console.warn('Error al remover mapa:', e);
    }
    map.value = null;
  }
  
  console.log('✅ Mapa limpiado exitosamente');
};

// Inicializar mapa
const initMap = () => {
  if (map.value) {
    console.log('⚠️ Mapa ya existe, limpiando antes de crear uno nuevo');
    limpiarMapa();
  }

  const mapContainer = document.getElementById('mapa-seleccion');
  if (!mapContainer) {
    console.error('❌ Contenedor del mapa no encontrado');
    return;
  }

  console.log('🗺️ Inicializando nuevo mapa');

  // Crear mapa centrado en Bolivia - pantalla completa
  map.value = L.map(mapContainer, {
    center: [-16.5, -68.15],
    zoom: 13,
    zoomControl: true,
  });

  // Agregar capa base
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 20,
  }).addTo(map.value as L.Map);
  
  // Listener para cambios de zoom - controlar capas visibles
  map.value.on('zoomend', actualizarCapasPorZoom);
  
  // Listener para cerrar panel al hacer clic fuera
  map.value.on('click', (e: any) => {
    // Si el clic no fue en un feature, cerrar el panel
    if (!e.originalEvent.defaultPrevented) {
      cerrarPanelInfo();
    }
  });
  
  console.log('✅ Mapa inicializado correctamente');
};

// Actualizar capas según el nivel de zoom
const actualizarCapasPorZoom = () => {
  if (!map.value) return;
  
  const zoom = map.value.getZoom();
  console.log('🔍 Zoom actual:', zoom);
  
  // Mostrar/ocultar barrios
  if (barriosLayer.value) {
    if (zoom < ZOOM_CUADRAS) {
      if (!map.value.hasLayer(barriosLayer.value)) {
        barriosLayer.value.addTo(map.value);
      }
    } else {
      if (map.value.hasLayer(barriosLayer.value)) {
        map.value.removeLayer(barriosLayer.value);
      }
    }
  }
  
  // Mostrar/ocultar cuadras
  if (cuadrasLayer.value) {
    if (zoom >= ZOOM_CUADRAS && zoom < ZOOM_TERRENOS) {
      if (!map.value.hasLayer(cuadrasLayer.value)) {
        cuadrasLayer.value.addTo(map.value);
      }
    } else {
      if (map.value.hasLayer(cuadrasLayer.value)) {
        map.value.removeLayer(cuadrasLayer.value);
      }
    }
  }
  
  // Mostrar/ocultar terrenos
  if (terrenosLayer.value) {
    if (zoom >= ZOOM_TERRENOS) {
      if (!map.value.hasLayer(terrenosLayer.value)) {
        terrenosLayer.value.addTo(map.value);
      }
    } else {
      if (map.value.hasLayer(terrenosLayer.value)) {
        map.value.removeLayer(terrenosLayer.value);
      }
    }
  }
};

// Cargar todas las capas
const cargarCapas = async () => {
  if (!props.proyectoId) {
    console.log('⚠️ No hay proyecto seleccionado');
    return;
  }

  try {
    loading.value = true;
    error.value = null;

    console.log('📡 Cargando capas para proyecto:', props.proyectoId);
    
    if (!map.value) {
      console.log('🗺️ Mapa no existe, inicializando...');
      initMap();
      await new Promise(resolve => setTimeout(resolve, 100));
    }

    // Cargar barrios, cuadras y terrenos en paralelo
    const [barriosData, cuadrasData, terrenosData] = await Promise.all([
      axios.get(`/api/mapa/proyectos/${props.proyectoId}/barrios`).catch(err => {
        console.error('Error cargando barrios:', err);
        return { data: { features: [] } };
      }),
      axios.get(`/api/mapa/proyectos/${props.proyectoId}/cuadras`).catch(err => {
        console.error('Error cargando cuadras:', err);
        return { data: { features: [] } };
      }),
      axios.get(`/api/mapa/proyectos/${props.proyectoId}/terrenos-disponibles`).catch(err => {
        console.error('Error cargando terrenos:', err);
        return { data: { features: [] } };
      })
    ]);

    console.log('📦 Datos recibidos - Barrios:', barriosData.data.features?.length, 
                'Cuadras:', cuadrasData.data.features?.length,
                'Terrenos:', terrenosData.data.features?.length);

    // Limpiar capas anteriores
    if (barriosLayer.value) map.value.removeLayer(barriosLayer.value);
    if (cuadrasLayer.value) map.value.removeLayer(cuadrasLayer.value);
    if (terrenosLayer.value) map.value.removeLayer(terrenosLayer.value);

    // Crear capa de BARRIOS
    if (barriosData.data.features && barriosData.data.features.length > 0) {
      barriosLayer.value = L.geoJSON(barriosData.data, {
        style: (): L.PathOptions => ({
          fillColor: '#3b82f6',
          weight: 3,
          opacity: 1,
          color: '#1e40af',
          fillOpacity: 0.3,
        }),
        onEachFeature: (feature: any, layer: any) => {
          layer.bindTooltip(`<strong>${feature.properties.nombre}</strong>`, {
            permanent: false,
            direction: 'center'
          });
          
          layer.on('mouseover', () => {
            layer.setStyle({ fillOpacity: 0.5 });
          });
          
          layer.on('mouseout', () => {
            layer.setStyle({ fillOpacity: 0.3 });
          });
        },
      });
    }

    // Crear capa de CUADRAS
    if (cuadrasData.data.features && cuadrasData.data.features.length > 0) {
      cuadrasLayer.value = L.geoJSON(cuadrasData.data, {
        style: (): L.PathOptions => ({
          fillColor: '#10b981',
          weight: 2,
          opacity: 1,
          color: '#059669',
          fillOpacity: 0.4,
        }),
        onEachFeature: (feature: any, layer: any) => {
          const props = feature.properties;
          layer.bindTooltip(`
            <strong>${props.nombre}</strong><br>
            Barrio: ${props.barrio || 'N/A'}
          `);
          
          layer.on('mouseover', () => {
            layer.setStyle({ fillOpacity: 0.6 });
          });
          
          layer.on('mouseout', () => {
            layer.setStyle({ fillOpacity: 0.4 });
          });
        },
      });
    }

    // Crear capa de TERRENOS
    if (terrenosData.data.features && terrenosData.data.features.length > 0) {
      terrenosLayer.value = L.geoJSON(terrenosData.data, {
        style: (feature): L.PathOptions => ({
          fillColor: feature?.properties?.categoria_color || '#6b7280',
          weight: 2,
          opacity: 1,
          color: '#fff',
          fillOpacity: 0.6,
        }),
        onEachFeature: (feature: any, layer: any) => {
          const props = feature.properties;
          
          // Tooltip al hover
          layer.bindTooltip(`
            <strong>${props.ubicacion}</strong><br>
            Superficie: ${props.superficie} m²<br>
            Precio: $${props.precio_venta?.toLocaleString('es-AR') || 'N/A'}
          `);

          // Click para seleccionar
          layer.on('click', (e: any) => {
            L.DomEvent.stopPropagation(e); // Prevenir que el clic del mapa cierre el panel
            seleccionarTerreno(props, layer);
          });

          // Hover effects
          layer.on('mouseover', () => {
            if (selectedTerreno.value?.id !== props.id) {
              layer.setStyle({
                fillOpacity: 0.8,
                weight: 3,
              });
            }
          });

          layer.on('mouseout', () => {
            if (selectedTerreno.value?.id !== props.id) {
              layer.setStyle({
                fillOpacity: 0.6,
                weight: 2,
              });
            }
          });
        },
      });

      // Ajustar vista inicial a los barrios (zoom menor)
      let initialBounds;
      if (barriosLayer.value) {
        initialBounds = barriosLayer.value.getBounds();
      } else if (terrenosLayer.value) {
        initialBounds = terrenosLayer.value.getBounds();
      }

      if (initialBounds && initialBounds.isValid()) {
        (map.value as L.Map).fitBounds(initialBounds, { padding: [50, 50] });
      }
      
      // Actualizar capas según zoom inicial
      actualizarCapasPorZoom();
      
      console.log('✅ Capas cargadas exitosamente');
    } else {
      console.warn('⚠️ No se encontraron terrenos disponibles');
      error.value = 'No hay terrenos disponibles en este proyecto';
    }

  } catch (err: any) {
    console.error('❌ Error cargando capas:', err);
    console.error('Detalles:', err.response?.data);
    error.value = 'Error al cargar los datos del mapa';
  } finally {
    loading.value = false;
  }
};

// Seleccionar terreno
const seleccionarTerreno = (terreno: Terreno, layer: any) => {
  console.log('🎯 Seleccionando terreno:', terreno.ubicacion);
  
  // Limpiar selección anterior
  if (highlightedLayer.value && terrenosLayer.value) {
    try {
      terrenosLayer.value.resetStyle(highlightedLayer.value);
    } catch (e) {
      console.warn('Error al resetear estilo anterior:', e);
    }
  }

  // Aplicar estilo de selección al nuevo terreno
  layer.setStyle({
    fillOpacity: 0.9,
    weight: 4,
    color: '#10b981',
  });

  highlightedLayer.value = layer;
  selectedTerreno.value = terreno;
  showInfoPanel.value = true;
  
  console.log('✅ Terreno seleccionado correctamente');
};

// Cerrar panel de información
const cerrarPanelInfo = () => {
  console.log('❌ Cerrando panel de información');
  
  if (highlightedLayer.value && terrenosLayer.value) {
    try {
      terrenosLayer.value.resetStyle(highlightedLayer.value);
    } catch (e) {
      console.warn('Error al resetear estilo:', e);
    }
  }
  highlightedLayer.value = null;
  selectedTerreno.value = null;
  showInfoPanel.value = false;
  
  console.log('✅ Panel cerrado exitosamente');
};

// Confirmar selección
const confirmarSeleccion = () => {
  if (selectedTerreno.value) {
    console.log('✅ Confirmando selección:', selectedTerreno.value.ubicacion);
    emit('terreno-seleccionado', selectedTerreno.value);
    cerrarDialog();
  }
};

// Cerrar dialog
const cerrarDialog = () => {
  console.log('🚪 Cerrando diálogo del mapa');
  
  // Limpiar selección
  selectedTerreno.value = null;
  showInfoPanel.value = false;
  if (highlightedLayer.value && terrenosLayer.value) {
    try {
      terrenosLayer.value.resetStyle(highlightedLayer.value);
    } catch (e) {
      console.warn('Error al resetear estilo:', e);
    }
  }
  highlightedLayer.value = null;
  
  // Limpiar mapa completamente
  limpiarMapa();
  
  // Cerrar el diálogo
  isOpen.value = false;
  
  console.log('✅ Diálogo cerrado');
};

// Watchers
watch(() => props.open, (newVal) => {
  console.log('👁️ Dialog abierto:', newVal);
  
  if (newVal) {
    // Resetear estado al abrir
    loading.value = true;
    error.value = null;
    selectedTerreno.value = null;
    highlightedLayer.value = null;
    showInfoPanel.value = false;
    
    // Esperar a que el DOM se actualice
    setTimeout(() => {
      console.log('🔄 Inicializando mapa para proyecto:', props.proyectoId);
      
      // Siempre inicializar un mapa nuevo cuando se abre
      initMap();
      
      // Esperar un poco más para asegurar que el mapa está listo
      setTimeout(() => {
        if (map.value) {
          map.value.invalidateSize();
          cargarCapas();
        }
      }, 100);
    }, 100);
  }
});

watch(() => props.proyectoId, (newVal, oldVal) => {
  console.log('🔄 Proyecto cambió de', oldVal, 'a', newVal);
  
  if (newVal && props.open) {
    selectedTerreno.value = null;
    highlightedLayer.value = null;
    showInfoPanel.value = false;
    cargarCapas();
  }
});

// Limpiar al desmontar el componente
onUnmounted(() => {
  console.log('💀 Componente desmontándose, limpiando recursos');
  limpiarMapa();
});

onMounted(() => {
  if (props.open) {
    initMap();
    cargarCapas();
  }
});
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="!max-w-none !w-screen !h-screen p-0 gap-0 !max-h-screen border-0">
      <DialogHeader class="absolute top-4 right-4 z-[1000] bg-white/95 backdrop-blur-sm rounded-lg shadow-lg p-4 max-w-md">
        <DialogTitle class="flex items-center gap-2 text-xl">
          <MapPin class="h-6 w-6" />
          Seleccionar Terreno en el Mapa
        </DialogTitle>
        <DialogDescription class="text-sm mt-1">
          Navega por el mapa: Barrios → Cuadras → Terrenos. Haz clic en un terreno para seleccionarlo.
        </DialogDescription>
        <Button 
          variant="outline" 
          size="sm" 
          @click="cerrarDialog"
          class="mt-3 w-full"
        >
          <X class="mr-2 h-4 w-4" />
          Cerrar
        </Button>
      </DialogHeader>

      <!-- Mapa a pantalla completa -->
      <div class="relative w-full h-full">
        <!-- Loading overlay -->
        <div v-if="loading" class="absolute inset-0 bg-white/80 z-[1000] flex items-center justify-center">
          <div class="text-center">
            <Loader2 class="h-8 w-8 animate-spin text-primary mx-auto mb-2" />
            <p class="text-sm text-muted-foreground">Cargando mapa...</p>
          </div>
        </div>

        <!-- Error -->
        <div v-if="error" class="absolute inset-0 z-[1000] flex items-center justify-center p-4">
          <Card class="w-full max-w-md">
            <CardContent class="pt-6">
              <div class="text-center">
                <X class="h-12 w-12 text-destructive mx-auto mb-2" />
                <p class="text-destructive">{{ error }}</p>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Contenedor del mapa -->
        <div id="mapa-seleccion" class="w-full h-full"></div>

        <!-- Panel flotante de información del terreno -->
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 translate-y-4"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 translate-y-4"
        >
          <div 
            v-if="showInfoPanel && selectedTerreno" 
            class="absolute bottom-4 right-4 z-[1000] w-96 max-h-[calc(100vh-8rem)] overflow-y-auto"
          >
            <Card class="shadow-2xl border-2 border-primary">
              <CardContent class="p-6">
                <div class="bg-primary/10 rounded-lg p-5 border-2 border-primary">
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                      <Badge variant="default" class="mb-2">Seleccionado</Badge>
                      <h3 class="font-bold text-xl">{{ selectedTerreno.ubicacion }}</h3>
                    </div>
                    <Button
                      variant="ghost"
                      size="icon"
                      @click.stop="cerrarPanelInfo"
                      class="h-8 w-8 text-muted-foreground hover:text-destructive"
                      title="Cerrar"
                    >
                      <XCircle class="h-5 w-5" />
                    </Button>
                  </div>
                  
                  <div class="space-y-3 text-sm mt-4">
                    <div class="flex justify-between py-2 border-b border-primary/20">
                      <span class="text-muted-foreground font-medium">Código:</span>
                      <span class="font-semibold">{{ selectedTerreno.codigo || 'N/A' }}</span>
                    </div>
                    
                    <div class="flex justify-between py-2 border-b border-primary/20">
                      <span class="text-muted-foreground font-medium">Categoría:</span>
                      <Badge :style="{ backgroundColor: selectedTerreno.categoria_color }" class="text-xs">
                        {{ selectedTerreno.categoria }}
                      </Badge>
                    </div>

                    <div v-if="selectedTerreno.barrio" class="flex justify-between py-2 border-b border-primary/20">
                      <span class="text-muted-foreground font-medium">Barrio:</span>
                      <span class="font-semibold">{{ selectedTerreno.barrio }}</span>
                    </div>

                    <div v-if="selectedTerreno.cuadra" class="flex justify-between py-2 border-b border-primary/20">
                      <span class="text-muted-foreground font-medium">Cuadra:</span>
                      <span class="font-semibold">{{ selectedTerreno.cuadra }}</span>
                    </div>

                    <div class="pt-3">
                      <div class="flex justify-between py-2">
                        <span class="text-muted-foreground font-medium">Superficie:</span>
                        <span class="font-semibold text-base">{{ selectedTerreno.superficie }} m²</span>
                      </div>
                    </div>

                    <div class="bg-primary/5 rounded-lg p-3 mt-3">
                      <div class="flex justify-between items-baseline">
                        <span class="text-muted-foreground font-medium">Precio Total:</span>
                        <span class="font-bold text-2xl text-primary">
                          ${{ selectedTerreno.precio_venta?.toLocaleString('es-AR') || 'N/A' }}
                        </span>
                      </div>
                    </div>

                    <div v-if="selectedTerreno.cuota_inicial" class="flex justify-between py-1 text-sm">
                      <span class="text-muted-foreground">Cuota inicial:</span>
                      <span class="font-medium">${{ selectedTerreno.cuota_inicial?.toLocaleString('es-AR') }}</span>
                    </div>

                    <div v-if="selectedTerreno.cuota_mensual" class="flex justify-between py-1 text-sm">
                      <span class="text-muted-foreground">Cuota mensual:</span>
                      <span class="font-medium">${{ selectedTerreno.cuota_mensual?.toLocaleString('es-AR') }}</span>
                    </div>
                  </div>
                </div>

                <Button 
                  @click="confirmarSeleccion" 
                  class="w-full mt-4"
                  size="lg"
                >
                  <Check class="mr-2 h-5 w-5" />
                  Confirmar Selección
                </Button>
              </CardContent>
            </Card>
          </div>
        </Transition>
      </div>
    </DialogContent>
  </Dialog>
</template>

<style scoped>
#mapa-seleccion {
  min-height: 100vh;
  width: 100%;
}

:deep(.leaflet-container) {
  font-family: inherit;
  height: 100vh !important;
  width: 100% !important;
}

:deep(.leaflet-popup-content-wrapper) {
  border-radius: 8px;
}

/* Forzar diálogo a pantalla completa */
:deep([role="dialog"]) {
  max-width: 100vw !important;
  width: 100vw !important;
  height: 100vh !important;
  max-height: 100vh !important;
  margin: 0 !important;
  border-radius: 0 !important;
}

:deep(.fixed.inset-0) {
  padding: 0 !important;
}

/* Remover cualquier overlay de fondo oscuro del diálogo */
:deep([data-radix-dialog-overlay]) {
  background-color: transparent !important;
}
</style>