<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputError from '@/Components/InputError.vue';
import { debounce } from 'lodash';

const props = defineProps({
    clientes: Array, 
    mediosPago: Array, 
});

// --- LÓGICA MEDIOS DE PAGO ---
const mediosOptions = computed(() => {
    if (!props.mediosPago) return [];
    return props.mediosPago.map(m => ({
        value: m.medioPagoID,
        label: `${m.nombre} ${parseFloat(m.recargo_porcentaje) > 0 ? '(+' + parseFloat(m.recargo_porcentaje) + '%)' : ''}`
    }));
});

// --- ESTADO LOCAL ---
const searchTermCliente = ref('');
const filteredClientes = ref([]);
const showClienteDropdown = ref(false);
const clienteSeleccionado = ref(null);

const form = useForm({
    clienteID: '',
    monto: '',
    medioPagoID: '', 
    observaciones: '',
});

// --- FORMATEADORES ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

// --- LÓGICA CLIENTES ---
const debouncedFilterClientes = debounce(() => {
    if (searchTermCliente.value.length >= 2) {
        filteredClientes.value = props.clientes.filter(cliente =>
            cliente.nombre.toLowerCase().includes(searchTermCliente.value.toLowerCase()) ||
            cliente.apellido.toLowerCase().includes(searchTermCliente.value.toLowerCase()) ||
            cliente.DNI.includes(searchTermCliente.value)
        ).slice(0, 10);
        showClienteDropdown.value = true;
    } else {
        filteredClientes.value = [];
        showClienteDropdown.value = false;
    }
}, 300);

watch(searchTermCliente, debouncedFilterClientes);

const selectCliente = (cliente) => {
    clienteSeleccionado.value = cliente;
    form.clienteID = cliente.clienteID;
    searchTermCliente.value = `${cliente.apellido}, ${cliente.nombre}`;
    showClienteDropdown.value = false;
};

const clearCliente = () => {
    clienteSeleccionado.value = null;
    form.clienteID = '';
    searchTermCliente.value = '';
    form.clearErrors();
};

// --- PROPIEDADES UI ---
const infoCuentaCorriente = computed(() => {
    return clienteSeleccionado.value?.cuenta_corriente || null;
});

const submit = () => {
    if (!form.clienteID) {
        alert('Debe seleccionar un cliente.');
        return;
    }
    form.post(route('pagos.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.cliente-search-container')) {
            showClienteDropdown.value = false;
        }
    });
});
</script>

<template>
    <Head title="Registrar Pago" />
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar Nuevo Pago</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <div class="bg-white shadow-xl sm:rounded-lg overflow-hidden">
                        <div class="bg-indigo-50 px-6 py-4 border-b border-indigo-100">
                            <h3 class="text-lg font-medium text-indigo-800">Detalles del Pago</h3>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="md:col-span-2 relative cliente-search-container">
                                <InputLabel for="cliente_search" value="Cliente (Buscar por Nombre o DNI)" />
                                <div class="flex mt-1">
                                    <TextInput id="cliente_search" v-model="searchTermCliente" placeholder="Escriba para buscar..." class="w-full" autocomplete="off" @focus="searchTermCliente.length >= 2 ? showClienteDropdown = true : null" />
                                    <button type="button" v-if="clienteSeleccionado" @click="clearCliente" class="ml-2 text-gray-400 hover:text-red-500" title="Limpiar"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                                <ul v-if="showClienteDropdown && filteredClientes.length" class="absolute z-20 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto">
                                    <li v-for="cli in filteredClientes" :key="cli.clienteID" @click="selectCliente(cli)" class="px-4 py-3 hover:bg-indigo-50 cursor-pointer border-b last:border-b-0">
                                        <div class="font-bold text-gray-800">{{ cli.apellido }}, {{ cli.nombre }}</div>
                                        <div class="text-xs text-gray-500">DNI: {{ cli.DNI }} <span v-if="cli.cuenta_corriente" :class="cli.cuenta_corriente.saldo > 0 ? 'text-red-600' : 'text-green-600'">| Deuda: {{ formatCurrency(cli.cuenta_corriente.saldo) }}</span></div>
                                    </li>
                                </ul>
                                <InputError :message="form.errors.clienteID" class="mt-2" />
                            </div>

                            <div v-if="clienteSeleccionado && infoCuentaCorriente" class="md:col-span-2 bg-gray-50 rounded-lg p-4 border border-gray-200 flex justify-between items-center">
                                <div><span class="text-xs font-bold text-gray-400 uppercase">Saldo Actual</span><span class="text-2xl font-bold" :class="infoCuentaCorriente.saldo > 0 ? 'text-red-600' : 'text-green-600'">{{ formatCurrency(infoCuentaCorriente.saldo) }}</span></div>
                            </div>

                            <div>
                                <InputLabel for="monto" value="Monto a Pagar" />
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-gray-500 sm:text-sm">$</span></div>
                                    <TextInput id="monto" type="number" v-model="form.monto" class="w-full pl-7 font-bold text-lg" placeholder="0.00" min="0.01" step="0.01" />
                                </div>
                                <InputError :message="form.errors.monto" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="medioPagoID" value="Método de Pago" />
                                <SelectInput
                                    id="medioPagoID"
                                    v-model="form.medioPagoID"
                                    class="w-full mt-1"
                                    :options="mediosOptions"
                                />
                                <InputError :message="form.errors.medioPagoID" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="observaciones" value="Observaciones (Opcional)" />
                                <TextInput id="observaciones" v-model="form.observaciones" class="w-full mt-1" />
                                <InputError :message="form.errors.observaciones" class="mt-2" />
                            </div>
                        </div>

                        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="px-6 py-3">Registrar Pago</PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>