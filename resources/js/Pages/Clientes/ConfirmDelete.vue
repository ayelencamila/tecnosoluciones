<script setup>
import { ref } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; 
import InputError from '@/Components/InputError.vue'; 
import InputLabel from '@/Components/InputLabel.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue'; 
import DangerButton from '@/Components/DangerButton.vue'; 
import SecondaryButton from '@/Components/SecondaryButton.vue'; 

// Props del componente
const props = defineProps({
    cliente: Object,
    operacionesPendientes: Array, // Viene del controlador (vacío por ahora)
});

// Estados reactivos
// Formulario reactivo de Inertia (Elimina la gestión manual de errors/processing)
const form = useForm({
    motivo: '',
    confirmacion: false,
});

// Methods de UX
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
    let dir = `${direccion.calle} ${direccion.altura}`;
    if (direccion.localidad?.nombre) {
        dir += `, ${direccion.localidad.nombre}`;
    }
    if (direccion.localidad?.provincia?.nombre) {
        dir += `, ${direccion.localidad.provincia.nombre}`;
    }
    return dir;
};

const formatCurrency = (value) => new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);

// Método que llama al ClienteController@darDeBaja (CU-04)
const submitForm = () => {
    // LLama a la ruta POST /clientes/{id}/dar-de-baja
    form.post(route('clientes.dar-de-baja', props.cliente.clienteID), {
        preserveScroll: true,
        onError: () => {
            // El InputError mostrará los mensajes del DarDeBajaClienteRequest
        }
    });
};
</script>

<template>
    <Head title="Dar de Baja Cliente" />
    <AppLayout>
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-4">
                    <Link 
                        :href="route('clientes.index')"
                        class="flex items-center text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Volver a Clientes
                    </Link>
                    <span class="text-gray-300">|</span>
                    <Link 
                        :href="route('clientes.show', cliente.clienteID)"
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
                        <InputLabel value="Nombre Completo" />
                        <p class="text-gray-900 font-semibold">{{ cliente.nombre }} {{ cliente.apellido }}</p>
                    </div>
                    <div>
                        <InputLabel value="DNI" />
                        <p class="text-gray-900">{{ cliente.DNI }}</p>
                    </div>
                    <div>
                        <InputLabel value="Tipo de Cliente" />
                        <p class="text-gray-900">{{ cliente.tipo_cliente?.nombreTipo || 'No disponible' }}</p>
                    </div>
                    <div>
                        <InputLabel value="Estado Actual" />
                        <span 
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="getEstadoBadgeClass(cliente.estado_cliente?.nombreEstado)"
                        >
                            {{ cliente.estado_cliente?.nombreEstado || 'No disponible' }}
                        </span>
                    </div>
                    <div>
                        <InputLabel value="Email" />
                        <p class="text-gray-900">{{ cliente.mail || 'No disponible' }}</p>
                    </div>
                    <div>
                        <InputLabel value="Teléfono" />
                        <p class="text-gray-900">{{ cliente.telefono || 'No disponible' }}</p>
                    </div>
                </div>

                <!-- Advertencia de Cuenta Corriente -->
                <div v-if="cliente.cuentaCorriente" class="mt-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <p class="text-sm text-yellow-800">
                            <strong>¡Atención!</strong> Este cliente tiene una cuenta corriente asociada. Al darlo de baja, la cuenta también será **desactivada**.
                        </p>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Saldo actual: <span class="font-semibold">${{ formatCurrency(cliente.cuentaCorriente.saldo || 0) }}</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Confirmación -->
        <div v-if="!operacionesPendientes || operacionesPendientes.length === 0" class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">Confirmación de Baja</h2>
            </div>
            <div class="px-6 py-4">
                <form @submit.prevent="submitForm" class="space-y-6">
                    
                    <!-- Motivo de la Baja -->
                    <div>
                        <InputLabel for="motivo" value="Motivo de la Baja *" />
                        <textarea
                            id="motivo"
                            v-model="form.motivo"
                            required
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md mt-1 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            :class="{ 'border-red-500': form.errors.motivo }"
                            placeholder="Indique el motivo por el cual se da de baja este cliente..."
                        ></textarea>
                        <!-- KENDALL: Feedback de error -->
                        <InputError :message="form.errors.motivo" class="mt-1" />
                        <p class="mt-1 text-sm text-gray-500">
                            Este motivo quedará registrado en el historial de auditoría (CU-04).
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
                        <Link :href="route('clientes.show', cliente.clienteID)" class="px-6 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </Link>
                        <PrimaryButton 
                            :disabled="form.processing || !form.confirmacion || !form.motivo" 
                            :class="{ 'opacity-25': form.processing }"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>{{ form.processing ? 'Procesando...' : 'Confirmar Baja' }}</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>

        <!-- Mensaje si hay operaciones pendientes (Mantenemos tu lógica) -->
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
                        :href="route('clientes.show', cliente.clienteID)"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                    >
                        Volver a Detalles del Cliente
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>