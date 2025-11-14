<template>
  <AppLayout>
    <!-- Header -->
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
          <span class="text-gray-300">|</span>
          <Link 
            :href="`/clientes/${cliente.clienteID}`"
            class="flex items-center text-gray-600 hover:text-gray-900 transition-colors"
          >
            Ver Detalles
          </Link>
        </div>
      </div>

      <div>
        <h1 class="text-3xl font-bold text-gray-900">Dar de Baja Cliente</h1>
        <p class="mt-1 text-sm text-gray-600">
          Confirme la baja del cliente <strong>{{ cliente.nombre }} {{ cliente.apellido }}</strong>
        </p>
      </div>
    </div>

    <!-- Información del Cliente -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
          <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          Información del Cliente
        </h2>
      </div>
      <div class="px-6 py-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
            <p class="text-gray-900 font-semibold">{{ cliente.nombre }} {{ cliente.apellido }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">DNI</label>
            <p class="text-gray-900">{{ cliente.DNI }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Tipo de Cliente</label>
            <p class="text-gray-900">{{ cliente.tipo_cliente?.nombreTipo || 'No disponible' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Estado Actual</label>
            <span 
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="getEstadoBadgeClass(cliente.estado_cliente?.nombreEstado)"
            >
              {{ cliente.estado_cliente?.nombreEstado || 'No disponible' }}
            </span>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <p class="text-gray-900">{{ cliente.mail || 'No disponible' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <p class="text-gray-900">{{ cliente.telefono || 'No disponible' }}</p>
          </div>
        </div>

        <!-- Dirección -->
        <div class="mt-6">
          <label class="block text-sm font-medium text-gray-700">Dirección</label>
          <p class="text-gray-900">
            {{ formatDireccion(cliente.direccion) }}
          </p>
        </div>

        <!-- Cuenta Corriente (si existe) -->
        <div v-if="cliente.cuenta_corriente" class="mt-6">
          <label class="block text-sm font-medium text-gray-700">Cuenta Corriente</label>
          <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mt-2">
            <div class="flex">
              <svg class="w-5 h-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
              </svg>
              <div class="ml-3">
                <p class="text-sm text-yellow-800">
                  <strong>¡Atención!</strong> Este cliente tiene una cuenta corriente asociada. Al darlo de baja, la cuenta también será desactivada.
                </p>
                <div class="mt-2 text-sm text-yellow-700">
                  <p>Saldo actual: <span class="font-semibold">${{ formatCurrency(cliente.cuenta_corriente.saldo || 0) }}</span></p>
                  <p>Límite de crédito: <span class="font-semibold">${{ formatCurrency(cliente.cuenta_corriente.limiteCredito || 0) }}</span></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Operaciones Pendientes (si las hay) -->
    <div v-if="operacionesPendientes && operacionesPendientes.length > 0" class="bg-white rounded-lg shadow-sm border border-red-200 mb-6">
      <div class="px-6 py-4 border-b border-red-200">
        <h2 class="text-xl font-semibold text-red-900 flex items-center">
          <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
          Operaciones Pendientes
        </h2>
      </div>
      <div class="px-6 py-4">
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
          <p class="text-sm text-red-800">
            <strong>No es posible dar de baja este cliente</strong> porque tiene las siguientes operaciones pendientes:
          </p>
          <ul class="mt-2 list-disc list-inside text-sm text-red-700">
            <li v-for="operacion in operacionesPendientes" :key="operacion.id">
              {{ operacion.descripcion }}
            </li>
          </ul>
          <p class="mt-2 text-sm text-red-800">
            Por favor, complete o cancele estas operaciones antes de dar de baja al cliente.
          </p>
        </div>
      </div>
    </div>

    <!-- Formulario de Confirmación -->
    <div v-if="!operacionesPendientes || operacionesPendientes.length === 0" class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
          <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          Confirmación de Baja
        </h2>
      </div>
      <div class="px-6 py-4">
        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- Advertencia -->
          <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
              <svg class="w-5 h-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
              </svg>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Importante</h3>
                <div class="mt-2 text-sm text-red-700">
                  <p>Al dar de baja este cliente:</p>
                  <ul class="mt-1 list-disc list-inside">
                    <li>Su estado cambiará a "Inactivo"</li>
                    <li>No aparecerá en los listados activos por defecto</li>
                    <li v-if="cliente.cuenta_corriente">Su cuenta corriente será desactivada</li>
                    <li>Esta acción quedará registrada en el historial de auditoría</li>
                    <li>Podrá ser reactivado posteriormente si es necesario</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Motivo de la Baja -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Motivo de la Baja <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="form.motivo"
              required
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
              :class="{ 'border-red-500': errors.motivo }"
              placeholder="Indique el motivo por el cual se da de baja este cliente..."
            ></textarea>
            <p v-if="errors.motivo" class="mt-1 text-sm text-red-600">{{ errors.motivo }}</p>
            <p class="mt-1 text-sm text-gray-500">
              Este motivo quedará registrado en el historial de auditoría y será visible para otros usuarios.
            </p>
          </div>

          <!-- Confirmación -->
          <div class="flex items-center">
            <input
              v-model="form.confirmacion"
              type="checkbox"
              id="confirmacion"
              required
              class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
            >
            <label for="confirmacion" class="ml-2 block text-sm text-gray-700">
              Confirmo que deseo dar de baja al cliente <strong>{{ cliente.nombre }} {{ cliente.apellido }}</strong>
            </label>
          </div>

          <!-- Botones de Acción -->
          <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
            <Link 
              :href="`/clientes/${cliente.clienteID}`"
              class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors"
            >
              Cancelar
            </Link>
            <button
              type="submit"
              :disabled="processing || !form.confirmacion"
              class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center"
            >
              <svg v-if="processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ processing ? 'Procesando...' : 'Confirmar Baja' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Mensaje si hay operaciones pendientes -->
    <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4">
        <div class="text-center">
          <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2">No es posible dar de baja este cliente</h3>
          <p class="text-gray-600 mb-6">
            Complete o cancele las operaciones pendientes antes de continuar.
          </p>
          <Link 
            :href="`/clientes/${cliente.clienteID}`"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"
          >
            Volver a Detalles del Cliente
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

// Props del componente
const props = defineProps({
  cliente: Object,
  operacionesPendientes: Array,
});

// Estados reactivos
const errors = ref({});
const processing = ref(false);

// Formulario reactivo
const form = useForm({
  motivo: '',
  confirmacion: false,
});

// Methods
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

const formatDireccion = (direccion) => {
  if (!direccion) return 'No disponible';
  
  let direccionCompleta = `${direccion.calle} ${direccion.altura}`;
  
  if (direccion.pisoDepto) {
    direccionCompleta += `, ${direccion.pisoDepto}`;
  }
  
  if (direccion.barrio) {
    direccionCompleta += `, ${direccion.barrio}`;
  }
  
  if (direccion.localidad?.nombre) {
    direccionCompleta += `, ${direccion.localidad.nombre}`;
  }
  
  if (direccion.localidad?.provincia?.nombre) {
    direccionCompleta += `, ${direccion.localidad.provincia.nombre}`;
  }
  
  if (direccion.codigoPostal) {
    direccionCompleta += ` (${direccion.codigoPostal})`;
  }
  
  return direccionCompleta;
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value);
};

const submitForm = () => {
  processing.value = true;
  errors.value = {};
  
  form.post(`/clientes/${props.cliente.clienteID}/dar-de-baja`, {
    onSuccess: () => {
      processing.value = false;
    },
    onError: (formErrors) => {
      processing.value = false;
      errors.value = formErrors;
    },
  });
};
</script>