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
        </div>
      </div>

      <div>
        <h1 class="text-3xl font-bold text-gray-900">Registrar Nuevo Cliente</h1>
        <p class="mt-1 text-sm text-gray-600">
          Complete los datos del cliente para registrarlo en el sistema
        </p>
      </div>
    </div>

    <!-- Formulario -->
    <form @submit.prevent="submitForm" class="space-y-6">
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
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombre -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Nombre <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.nombre"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.nombre }"
                placeholder="Ingrese el nombre"
              >
              <p v-if="errors.nombre" class="mt-1 text-sm text-red-600">{{ errors.nombre }}</p>
            </div>

            <!-- Apellido -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Apellido <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.apellido"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.apellido }"
                placeholder="Ingrese el apellido"
              >
              <p v-if="errors.apellido" class="mt-1 text-sm text-red-600">{{ errors.apellido }}</p>
            </div>

            <!-- DNI -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                DNI <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.DNI"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.DNI }"
                placeholder="Ej: 12345678"
              >
              <p v-if="errors.DNI" class="mt-1 text-sm text-red-600">{{ errors.DNI }}</p>
            </div>

            <!-- Email -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Email
              </label>
              <input
                v-model="form.mail"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.mail }"
                placeholder="cliente@email.com"
              >
              <p v-if="errors.mail" class="mt-1 text-sm text-red-600">{{ errors.mail }}</p>
            </div>

            <!-- Teléfono -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Teléfono
              </label>
              <input
                v-model="form.telefono"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.telefono }"
                placeholder="Ej: 011-1234-5678"
              >
              <p v-if="errors.telefono" class="mt-1 text-sm text-red-600">{{ errors.telefono }}</p>
            </div>

            <!-- WhatsApp -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                WhatsApp
              </label>
              <input
                v-model="form.whatsapp"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.whatsapp }"
                placeholder="Ej: +54911234567"
              >
              <p v-if="errors.whatsapp" class="mt-1 text-sm text-red-600">{{ errors.whatsapp }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Dirección -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
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
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Calle -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Calle <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.calle"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.calle }"
                placeholder="Nombre de la calle"
              >
              <p v-if="errors.calle" class="mt-1 text-sm text-red-600">{{ errors.calle }}</p>
            </div>

            <!-- Altura -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Altura <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.altura"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.altura }"
                placeholder="1234"
              >
              <p v-if="errors.altura" class="mt-1 text-sm text-red-600">{{ errors.altura }}</p>
            </div>

            <!-- Piso/Depto -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Piso/Departamento
              </label>
              <input
                v-model="form.pisoDepto"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.pisoDepto }"
                placeholder="Ej: 3° A"
              >
              <p v-if="errors.pisoDepto" class="mt-1 text-sm text-red-600">{{ errors.pisoDepto }}</p>
            </div>

            <!-- Barrio -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Barrio
              </label>
              <input
                v-model="form.barrio"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.barrio }"
                placeholder="Nombre del barrio"
              >
              <p v-if="errors.barrio" class="mt-1 text-sm text-red-600">{{ errors.barrio }}</p>
            </div>

            <!-- Código Postal -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Código Postal <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.codigoPostal"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.codigoPostal }"
                placeholder="1234"
              >
              <p v-if="errors.codigoPostal" class="mt-1 text-sm text-red-600">{{ errors.codigoPostal }}</p>
            </div>

            <!-- Provincia -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Provincia <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.provincia_id"
                @change="onProvinciaChange"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.provincia_id }"
              >
                <option value="">Seleccione una provincia</option>
                <option v-for="provincia in provincias" :key="provincia.provinciaID" :value="provincia.provinciaID">
                  {{ provincia.nombre }}
                </option>
              </select>
              <p v-if="errors.provincia_id" class="mt-1 text-sm text-red-600">{{ errors.provincia_id }}</p>
            </div>

            <!-- Localidad -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Localidad <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.localidad_id"
                required
                :disabled="!form.provincia_id || loadingLocalidades"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100"
                :class="{ 'border-red-500': errors.localidad_id }"
              >
                <option value="">{{ loadingLocalidades ? 'Cargando...' : 'Seleccione una localidad' }}</option>
                <option v-for="localidad in localidades" :key="localidad.localidadID" :value="localidad.localidadID">
                  {{ localidad.nombre }}
                </option>
              </select>
              <p v-if="errors.localidad_id" class="mt-1 text-sm text-red-600">{{ errors.localidad_id }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tipo de Cliente -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            Configuración Comercial
          </h2>
        </div>
        <div class="px-6 py-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Tipo de Cliente -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Tipo de Cliente <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.tipo_cliente_id"
                @change="onTipoClienteChange"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.tipo_cliente_id }"
              >
                <option value="">Seleccione un tipo</option>
                <option v-for="tipo in tiposCliente" :key="tipo.tipoClienteID" :value="tipo.tipoClienteID">
                  {{ tipo.nombreTipo }}
                </option>
              </select>
              <p v-if="errors.tipo_cliente_id" class="mt-1 text-sm text-red-600">{{ errors.tipo_cliente_id }}</p>
            </div>

            <!-- Estado del Cliente -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Estado <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.estado_cliente_id"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.estado_cliente_id }"
              >
                <option value="">Seleccione un estado</option>
                <option v-for="estado in estadosCliente" :key="estado.estadoClienteID" :value="estado.estadoClienteID">
                  {{ estado.nombreEstado }}
                </option>
              </select>
              <p v-if="errors.estado_cliente_id" class="mt-1 text-sm text-red-600">{{ errors.estado_cliente_id }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Configuración Cuenta Corriente (solo para Mayoristas) -->
      <div v-if="esMayorista" class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            Configuración de Cuenta Corriente
          </h2>
        </div>
        <div class="px-6 py-4">
          <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
            <div class="flex">
              <svg class="w-5 h-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
              </svg>
              <div class="ml-3">
                <p class="text-sm text-blue-800">
                  Configure los términos de la cuenta corriente para este cliente mayorista.
                </p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Límite de Crédito -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Límite de Crédito
              </label>
              <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500">$</span>
                <input
                  v-model="form.limiteCredito"
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{ 'border-red-500': errors.limiteCredito }"
                  placeholder="0.00"
                >
              </div>
              <p v-if="errors.limiteCredito" class="mt-1 text-sm text-red-600">{{ errors.limiteCredito }}</p>
            </div>

            <!-- Días de Gracia -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Días de Gracia
              </label>
              <input
                v-model="form.diasGracia"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.diasGracia }"
                placeholder="0"
              >
              <p v-if="errors.diasGracia" class="mt-1 text-sm text-red-600">{{ errors.diasGracia }}</p>
            </div>

            <!-- Estado Cuenta Corriente -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Estado de Cuenta
              </label>
              <select
                v-model="form.estado_cuenta_corriente_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.estado_cuenta_corriente_id }"
              >
                <option value="">Seleccione un estado</option>
                <option v-for="estado in estadosCuentaCorriente" :key="estado.estadoCuentaCorrienteID" :value="estado.estadoCuentaCorrienteID">
                  {{ estado.nombreEstado }}
                </option>
              </select>
              <p v-if="errors.estado_cuenta_corriente_id" class="mt-1 text-sm text-red-600">{{ errors.estado_cuenta_corriente_id }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Botones de Acción -->
      <div class="flex justify-end space-x-4 pt-6">
        <Link 
          href="/clientes"
          class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors"
        >
          Cancelar
        </Link>
        <button
          type="submit"
          :disabled="processing"
          class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center"
        >
          <svg v-if="processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ processing ? 'Registrando...' : 'Registrar Cliente' }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

// Props del componente
const props = defineProps({
  provincias: Array,
  tiposCliente: Array,
  estadosCliente: Array,
  estadosCuentaCorriente: Array,
});

// Estados reactivos
const localidades = ref([]);
const loadingLocalidades = ref(false);
const errors = ref({});
const processing = ref(false);

// Formulario reactivo
const form = useForm({
  nombre: '',
  apellido: '',
  DNI: '',
  mail: '',
  telefono: '',
  whatsapp: '',
  calle: '',
  altura: '',
  pisoDepto: '',
  barrio: '',
  codigoPostal: '',
  provincia_id: '',
  localidad_id: '',
  tipo_cliente_id: '',
  estado_cliente_id: '',
  limiteCredito: '',
  diasGracia: '',
  estado_cuenta_corriente_id: '',
});

// Computed
const esMayorista = computed(() => {
  const tipoSeleccionado = props.tiposCliente.find(t => t.tipoClienteID == form.tipo_cliente_id);
  return tipoSeleccionado?.nombreTipo?.toLowerCase() === 'mayorista';
});

// Methods
const onProvinciaChange = async () => {
  form.localidad_id = '';
  localidades.value = [];
  
  if (!form.provincia_id) return;
  
  loadingLocalidades.value = true;
  
  try {
    const response = await fetch(`/api/provincias/${form.provincia_id}/localidades`);
    const data = await response.json();
    localidades.value = data;
  } catch (error) {
    console.error('Error al cargar localidades:', error);
  } finally {
    loadingLocalidades.value = false;
  }
};

const onTipoClienteChange = () => {
  // Limpiar campos de cuenta corriente si no es mayorista
  if (!esMayorista.value) {
    form.limiteCredito = '';
    form.diasGracia = '';
    form.estado_cuenta_corriente_id = '';
  }
};

const submitForm = () => {
  processing.value = true;
  errors.value = {};
  
  form.post('/clientes', {
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