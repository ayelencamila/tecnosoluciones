<script setup>
import { ref, watch, computed } from 'vue'; 
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; 
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { debounce } from 'lodash';

const props = defineProps({
    pagos: Object,
    filters: Object,
    clientes_filtro: Array,
});

const form = ref({
    search: props.filters.search || '',
    cliente_id: props.filters.cliente_id || '',
    fecha_desde: props.filters.fecha_desde || '',
    fecha_hasta: props.filters.fecha_hasta || '',
});

// --- CORRECCIÓN: Formato para tu SelectInput (Kendall) ---
const clientesOptions = computed(() => [
    { value: '', label: 'Todos los Clientes' },
    ...props.clientes_filtro.map(c => ({
        value: c.clienteID,
        label: `${c.apellido}, ${c.nombre}`
    }))
]);

// ... (el resto de tu script setup está perfecto) ...
watch(form, debounce(() => {
    router.get(route('pagos.index'), form.value, {
        preserveState: true,
        replace: true,
    });
}, 300), { deep: true });
const resetFilters = () => {
    form.value = { search: '', cliente_id: '', fecha_desde: '', fecha_hasta: '' };
};
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('es-AR', {
        year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute:'2-digit'
    });
};
</script>

<template>
    <Head title="Listado de Pagos" />

    <AppLayout> <!-- <-- CORREGIDO -->
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Cobranzas</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
                        <div class="flex-1 w-full">
                            <TextInput v-model="form.search" placeholder="Buscar N° Recibo..." class="w-full" />
                        </div>
                        <Link :href="route('pagos.create')">
                            <PrimaryButton>+ Registrar Pago</PrimaryButton>
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        
                        <!-- CORRECCIÓN: Usando SelectInput con :options -->
                        <SelectInput 
                            v-model="form.cliente_id" 
                            class="w-full"
                            :options="clientesOptions"
                        />
                        
                        <TextInput type="date" v-model="form.fecha_desde" class="w-full" placeholder="Desde" />
                        <TextInput type="date" v-model="form.fecha_hasta" class="w-full" placeholder="Hasta" />
                        <button @click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 underline">
                            Limpiar Filtros
                        </button>
                    </div>
                </div>

                <!-- ... (El resto de tu tabla y paginación está perfecto) ... -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- ... (Tabla) ... -->
                    <!-- ... (Paginación) ... -->
                </div>
            </div>
        </div>
    </AppLayout> <!-- <-- CORREGIDO -->
</template>