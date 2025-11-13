<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; //Sin color
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import { debounce } from 'lodash';

// Props pasadas desde PagoController@create
const props = defineProps({
    clientes: Array,
    errors: Object, // Errores de validación de Inertia
});

// --- Formulario de Inertia ---
// Define los campos que se enviarán al backend
const form = useForm({
    clienteID: null,
    monto: null,
    metodo_pago: 'efectivo', // Valor por defecto
    observaciones: '',
});

// --- Estado local para el buscador de Clientes ---
const searchTermCliente = ref('');
const filteredClientes = ref([]);
const showClienteDropdown = ref(false);
const clienteSeleccionado = ref(null);

// Filtrar clientes en tiempo real
const debouncedFilterClientes = debounce(() => {
    if (searchTermCliente.value.length < 2) {
        filteredClientes.value = [];
        showClienteDropdown.value = false;
        return;
    }
    filteredClientes.value = props.clientes.filter(cliente =>
        cliente.nombre.toLowerCase().includes(searchTermCliente.value.toLowerCase()) ||
        cliente.apellido.toLowerCase().includes(searchTermCliente.value.toLowerCase()) ||
        cliente.DNI.includes(searchTermCliente.value)
    ).slice(0, 10);
    showClienteDropdown.value = true;
}, 300);

watch(searchTermCliente, debouncedFilterClientes);

// Al seleccionar un cliente del dropdown
const selectCliente = (cliente) => {
    clienteSeleccionado.value = cliente;
    form.clienteID = cliente.clienteID; // Setea el ID en el formulario
    searchTermCliente.value = `${cliente.apellido}, ${cliente.nombre} (${cliente.DNI})`;
    showClienteDropdown.value = false;
};

// Limpiar la selección de cliente
const clearCliente = () => {
    clienteSeleccionado.value = null;
    form.clienteID = null;
    searchTermCliente.value = '';
    showClienteDropdown.value = false;
};

// Enviar el formulario (CU-10 Paso 9)
const submit = () => {
    form.post(route('pagos.store'), {
        onSuccess: () => {
            // No se necesita alert. Inertia redirigirá a pagos.show
            // y el backend enviará el mensaje 'success' (flash).
            form.reset();
        },
        onError: (errors) => {
            console.error('Error al registrar el pago:', errors);
        },
    });
};

// Opciones para el método de pago
const metodosPagoOptions = [
    { value: 'efectivo', label: 'Efectivo' },
    { value: 'transferencia', label: 'Transferencia Bancaria' },
    { value: 'tarjeta', label: 'Tarjeta (Débito/Crédito)' },
    { value: 'cheque', label: 'Cheque' },
];
</script>

<template>
    <Head title="Registrar Pago" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Registrar Nuevo Pago (CU-10)
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 md:p-8 space-y-6">

                        <!-- SELECCIÓN DE CLIENTE -->
                        <section>
                            <InputLabel for="clienteSearch" value="Buscar Cliente (Obligatorio)" class="text-lg font-medium" />
                            <p class="text-sm text-gray-500 mb-2">Selecciona el cliente que realiza el pago.</p>
                            
                            <div class="cliente-search-container relative">
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <TextInput
                                        id="clienteSearch"
                                        type="text"
                                        v-model="searchTermCliente"
                                        @focus="showClienteDropdown = true"
                                        autocomplete="off"
                                        placeholder="Buscar por Nombre, Apellido o DNI..."
                                        class="flex-1 block w-full rounded-none rounded-l-md"
                                        :class="{'rounded-r-md': !clienteSeleccionado}"
                                        :disabled="!!clienteSeleccionado"
                                    />
                                    <DangerButton v-if="clienteSeleccionado" type="button" @click="clearCliente" class="rounded-l-none">X</DangerButton>
                                </div>
                                
                                <!-- Dropdown de resultados -->
                                <ul v-if="showClienteDropdown && filteredClientes.length" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-auto">
                                    <li v-for="cliente in filteredClientes" :key="cliente.clienteID" @click="selectCliente(cliente)" class="p-3 cursor-pointer hover:bg-gray-100 text-sm">
                                        {{ cliente.apellido }}, {{ cliente.nombre }} (DNI: {{ cliente.DNI }})
                                    </li>
                                </ul>
                            </div>
                            <InputError class="mt-2" :message="form.errors.clienteID" />
                        </section>

                        <!-- DETALLES DEL PAGO -->
                        <section class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900">Detalles del Pago</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <!-- Monto -->
                                <div>
                                    <InputLabel for="monto" value="Monto a Pagar" />
                                    <TextInput
                                        id="monto"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        class="mt-1 block w-full"
                                        v-model="form.monto"
                                        required
                                        placeholder="$ 0.00"
                                    />
                                    <InputError class="mt-2" :message="form.errors.monto" />
                                </div>

                                <!-- Método de Pago -->
                                <div>
                                    <InputLabel for="metodo_pago" value="Método de Pago" />
                                    <SelectInput
                                        id="metodo_pago"
                                        class="mt-1 block w-full"
                                        v-model="form.metodo_pago"
                                        :options="metodosPagoOptions"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.metodo_pago" />
                                </div>
                            </div>
                        </section>

                        <!-- Observaciones -->
                        <section class="border-t pt-6">
                            <InputLabel for="observaciones" value="Observaciones (Opcional)" />
                            <textarea
                                id="observaciones"
                                v-model="form.observaciones"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                placeholder="Ej: Pago parcial, saldo pendiente, nro de cheque..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.observaciones" />
                        </section>

                        <!-- BOTONES -->
                        <div class="flex items-center justify-end space-x-4 border-t pt-6">
                            <Link :href="route('pagos.index')" class="text-sm text-gray-600 hover:text-gray-900">
                                Cancelar
                            </Link>
                            
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing || !form.clienteID || !form.monto">
                                <span v-if="form.processing">Registrando...</span>
                                <span v-else>Registrar Pago</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>