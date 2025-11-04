<template>
  <AppLayout>
    <!-- Header con navegación -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-4">
          <Link 
            href="/clientes"
            class="flex items-center text-gray-600 hover:text-gray-900 transition-colors"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver a Clientes
          </Link>
        </div>
        
        <div class="flex space-x-3">
          <Link 
            :href="`/clientes/${cliente.clienteID}/edit`"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Editar Cliente
          </Link>
          
          <button 
            @click="showDeleteModal = true"
            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Dar de Baja
          </button>
        </div>
      </div>

      <!-- Título principal -->
      <div>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ cliente.nombre }} {{ cliente.apellido }}
        </h1>
        <div class="flex items-center mt-2 space-x-4">
          <span 
            class="inline-flex px-3 py-1 text-sm font-semibold rounded-full"
            :class="getEstadoBadgeClass(cliente.estado_cliente?.nombreEstado)"
          >
            {{ cliente.estado_cliente?.nombreEstado || 'N/A' }}
          </span>
          <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
            {{ cliente.tipo_cliente?.nombreTipo || 'N/A' }}
          </span>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Columna principal: Información del cliente -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Datos Personales -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
              <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              Datos Personales
            </h2>
          </div>
          <div class="px-6 py-4">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">Nombre Completo</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ cliente.nombre }} {{ cliente.apellido }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">DNI</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ cliente.DNI }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Email</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <a v-if="cliente.mail" :href="`mailto:${cliente.mail}`" class="text-indigo-600 hover:text-indigo-800">
                    {{ cliente.mail }}
                  </a>
                  <span v-else class="text-gray-400">No registrado</span>
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Teléfono</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <a v-if="cliente.telefono" :href="`tel:${cliente.telefono}`" class="text-indigo-600 hover:text-indigo-800">
                    {{ cliente.telefono }}
                  </a>
                  <span v-else class="text-gray-400">No registrado</span>
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">WhatsApp</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <a v-if="cliente.whatsapp" :href="`https://wa.me/${cliente.whatsapp.replace(/[^0-9]/g, '')}`" target="_blank" class="text-green-600 hover:text-green-800 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.886 3.488"/>
                    </svg>
                    {{ cliente.whatsapp }}
                  </a>
                  <span v-else class="text-gray-400">No registrado</span>
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Dirección -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200" v-if="cliente.direccion">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
              <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              Dirección
            </h2>
          </div>
          <div class="px-6 py-4">
            <div class="text-sm text-gray-900">
              <p class="font-medium">{{ cliente.direccion.calle }} {{ cliente.direccion.altura }}</p>
              <p v-if="cliente.direccion.pisoDepto" class="text-gray-600">{{ cliente.direccion.pisoDepto }}</p>
              <p v-if="cliente.direccion.barrio" class="text-gray-600">Barrio: {{ cliente.direccion.barrio }}</p>
              <p class="text-gray-600">
                {{ cliente.direccion.localidad?.nombre }}, {{ cliente.direccion.localidad?.provincia?.nombre }}
              </p>
              <p v-if="cliente.direccion.codigoPostal" class="text-gray-600">CP: {{ cliente.direccion.codigoPostal }}</p>
            </div>
          </div>
        </div>

        <!-- Cuenta Corriente (si aplica) -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200" v-if="cliente.cuenta_corriente">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
              <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
              </svg>
              Cuenta Corriente
            </h2>
          </div>
          <div class="px-6 py-4">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">Estado</dt>
                <dd class="mt-1">
                  <span 
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="getEstadoCuentaBadgeClass(cliente.cuenta_corriente.estado_cuenta_corriente?.nombreEstado)"
                  >
                    {{ cliente.cuenta_corriente.estado_cuenta_corriente?.nombreEstado || 'N/A' }}
                  </span>
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Límite de Crédito</dt>
                <dd class="mt-1 text-sm text-gray-900 font-medium">
                  ${{ Number(cliente.cuenta_corriente.limiteCredito || 0).toLocaleString() }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Saldo Actual</dt>
                <dd class="mt-1 text-sm font-medium" :class="getSaldoColorClass(cliente.cuenta_corriente.saldo)">
                  ${{ Number(cliente.cuenta_corriente.saldo || 0).toLocaleString() }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Días de Gracia</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ cliente.cuenta_corriente.diasGracia || 0 }} días
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- Sidebar: Historial de Auditoría -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
              <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Historial de Operaciones
            </h2>
          </div>
          <div class="px-6 py-4 max-h-96 overflow-y-auto">
            <div v-if="historialAuditoria.length === 0" class="text-center text-gray-500 py-4">
              No hay registros de auditoría
            </div>
            <div v-else class="space-y-3">
              <div 
                v-for="auditoria in historialAuditoria" 
                :key="auditoria.id"
                class="border-l-4 border-blue-400 pl-4 pb-3"
              >
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">
                      {{ auditoria.tipoOperacion }}
                    </p>
                    <p class="text-xs text-gray-600 mt-1">
                      {{ auditoria.descripcion }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                      {{ formatDate(auditoria.created_at) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación de baja -->
    <div 
      v-if="showDeleteModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click="showDeleteModal = false"
    >
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
        <div class="mt-3 text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mt-2">Dar de baja cliente</h3>
          <div class="mt-2 px-7 py-3">
            <p class="text-sm text-gray-500">
              ¿Estás seguro de que quieres dar de baja al cliente 
              <strong>{{ cliente.nombre }} {{ cliente.apellido }}</strong>?
            </p>
            <div class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Motivo de la baja:
              </label>
              <textarea 
                v-model="motivoBaja"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                rows="3"
                placeholder="Ingrese el motivo de la baja..."
              ></textarea>
            </div>
          </div>
          <div class="flex justify-center space-x-3 mt-4">
            <button 
              @click="showDeleteModal = false"
              class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors"
            >
              Cancelar
            </button>
            <button 
              @click="darDeBaja"
              :disabled="!motivoBaja.trim()"
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
            >
              Dar de Baja
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

// Props del componente
const props = defineProps({
  cliente: Object,
  historialAuditoria: Array,
});

// Estados reactivos
const showDeleteModal = ref(false);
const motivoBaja = ref('');

// Métodos
const getEstadoBadgeClass = (estado) => {
  switch (estado?.toLowerCase()) {
    case 'activo':
      return 'bg-green-100 text-green-800';
    case 'inactivo':
      return 'bg-red-100 text-red-800';
    case 'suspendido':
      return 'bg-yellow-100 text-yellow-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const getEstadoCuentaBadgeClass = (estado) => {
  switch (estado?.toLowerCase()) {
    case 'activa':
      return 'bg-green-100 text-green-800';
    case 'suspendida':
      return 'bg-yellow-100 text-yellow-800';
    case 'bloqueada':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const getSaldoColorClass = (saldo) => {
  const valor = Number(saldo || 0);
  if (valor > 0) return 'text-green-600';
  if (valor < 0) return 'text-red-600';
  return 'text-gray-900';
};

const formatDate = (fecha) => {
  if (!fecha) return 'Fecha no disponible';
  return new Date(fecha).toLocaleString('es-AR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const darDeBaja = () => {
  if (!motivoBaja.value.trim()) return;
  
  router.post(`/clientes/${props.cliente.clienteID}/dar-de-baja`, {
    motivo: motivoBaja.value
  }, {
    onSuccess: () => {
      showDeleteModal.value = false;
      motivoBaja.value = '';
    },
  });
};
</script>