<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import { useVentaStore } from '@/Stores/ventaStore.js';
import { debounce } from 'lodash';
import axios from 'axios'; 

const props = defineProps({
    clientes: Array,
    productos: Array,
    descuentos: Array,
    mediosPago: Array, 
    errors: Object,
});

const ventaStore = useVentaStore();

// --- MEDIOS DE PAGO ---
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
const buscandoCliente = ref(false);

const searchTermProducto = ref('');
const filteredProductos = ref([]);
const showProductoDropdown = ref(false);

const selectedProductToAdd = ref(null);
const quantityToAdd = ref(1);
const priceToAdd = ref(0);
const applyDiscountCode = ref('');

// --- FORMULARIO ---
const form = useForm({
    clienteID: null,
    medio_pago_id: '', 
    observaciones: '',
    items: [], 
    descuentos_globales: [],
});

// --- CÁLCULOS ---
const totales = computed(() => ventaStore.calcularTotales);

const esCuentaCorriente = computed(() => {
    const medio = props.mediosPago?.find(m => m.medioPagoID == ventaStore.metodoPago);
    return medio?.nombre.toLowerCase().includes('corriente');
});

const limiteExcedido = computed(() => {
    if (esCuentaCorriente.value && ventaStore.clienteSeleccionado?.cuenta_corriente) {
        const cc = ventaStore.clienteSeleccionado.cuenta_corriente;
        return (parseFloat(cc.saldo || 0) + totales.value.totalNeto) > parseFloat(cc.limiteCredito || 0);
    }
    return false;
});

const estadoCC = computed(() => {
    return ventaStore.clienteSeleccionado?.cuenta_corriente?.estado_cuenta_corriente?.nombreEstado || 'N/A';
});

// --- BÚSQUEDA CLIENTES ---
const buscarClientesAPI = debounce(async () => {
    if (searchTermCliente.value.length < 3) {
        filteredClientes.value = [];
        showClienteDropdown.value = false;
        return;
    }
    buscandoCliente.value = true;
    try {
        const response = await axios.get(route('api.clientes.buscar'), { params: { q: searchTermCliente.value } });
        filteredClientes.value = response.data;
        showClienteDropdown.value = true;
    } catch (error) {
        console.error(error);
    } finally {
        buscandoCliente.value = false;
    }
}, 300);

watch(searchTermCliente, () => {
    if (!ventaStore.clienteSeleccionado) buscarClientesAPI();
});

const selectCliente = (cliente) => {
    ventaStore.setCliente(cliente);
    searchTermCliente.value = `${cliente.nombre} ${cliente.apellido}`;
    showClienteDropdown.value = false;
};

const clearCliente = () => {
    ventaStore.clienteSeleccionado = null;
    searchTermCliente.value = '';
    filteredClientes.value = [];
};

// --- PRODUCTOS ---
const debouncedFilterProductos = debounce(() => {
    if (searchTermProducto.value.length >= 3) {
        filteredProductos.value = props.productos.filter(p =>
            p.nombre.toLowerCase().includes(searchTermProducto.value.toLowerCase()) ||
            p.codigo.toLowerCase().includes(searchTermProducto.value.toLowerCase())
        ).slice(0, 10);
        showProductoDropdown.value = true;
    } else {
        filteredProductos.value = [];
        showProductoDropdown.value = false;
    }
}, 300);

watch(searchTermProducto, debouncedFilterProductos);

const selectProductToAdd = (producto) => {
    selectedProductToAdd.value = producto;
    searchTermProducto.value = `${producto.nombre} (${producto.codigo})`;
    showProductoDropdown.value = false;
    
    if (producto.precios && producto.precios.length > 0) {
        const tipoClienteID = ventaStore.clienteSeleccionado?.tipoClienteID;
        const precio = producto.precios.find(p => p.tipoClienteID === tipoClienteID) 
                    || producto.precios[0]; 
        priceToAdd.value = parseFloat(precio.precio);
    } else {
        priceToAdd.value = 0;
    }
    quantityToAdd.value = 1;
};

const addProductToCart = () => {
    if (!ventaStore.clienteSeleccionado) return alert('Selecciona primero un cliente.');
    if (selectedProductToAdd.value && quantityToAdd.value > 0) {
        ventaStore.agregarItem(selectedProductToAdd.value, quantityToAdd.value, priceToAdd.value);
        selectedProductToAdd.value = null;
        searchTermProducto.value = '';
        quantityToAdd.value = 1;
        priceToAdd.value = 0;
    }
};

const buscarYAplicarDescuento = () => {
    const descuentoEncontrado = props.descuentos.find(d => 
        d.codigo?.toLowerCase() === applyDiscountCode.value.toLowerCase() && d.aplicabilidad !== 'item'
    );
    if (descuentoEncontrado) {
        ventaStore.aplicarDescuentoGlobal(descuentoEncontrado);
        applyDiscountCode.value = ''; 
    } else {
        alert('Código inválido.');
    }
};

const removeGlobalDiscount = (descuentoId) => {
    ventaStore.removerDescuentoGlobal(descuentoId);
};

// --- SUBMIT ---
const submit = () => {
    if (!ventaStore.clienteSeleccionado) return alert('Falta Cliente.');
    if (ventaStore.items.length === 0) return alert('Carrito vacío.');
    if (esCuentaCorriente.value && limiteExcedido.value) return alert('Límite Excedido.');

    form.clienteID = ventaStore.clienteSeleccionado.clienteID;
    form.medio_pago_id = ventaStore.metodoPago;
    form.observaciones = ventaStore.observaciones;
    
    form.items = ventaStore.items.map(item => ({
        producto_id: item.productoID, 
        cantidad: item.cantidad,
        precio_producto_id: item.precio_producto_id || null, 
        descuentos_item: item.descuentos_item.map(d => ({ 
            descuento_id: d.descuento_id, 
            monto_aplicado_item: d.montoAplicado 
        }))
    }));

    form.descuentos_globales = ventaStore.descuentosGlobalesAplicados.map(d => ({ 
        descuento_id: d.descuento_id 
    }));

    form.post(route('ventas.store'), {
        preserveScroll: true,
        onSuccess: () => ventaStore.limpiarVenta(),
        onError: (e) => {
            console.error("Errores de validación:", e);
            if (e.items) alert("Error en productos: " + e.items); 
            if (e.medio_pago_id) alert("Error en medio de pago: " + e.medio_pago_id);
        }
    });
};

onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.cliente-search-container')) showClienteDropdown.value = false;
        if (!e.target.closest('.producto-search-container')) showProductoDropdown.value = false;
    });
});
</script>

<template>
    <Head title="Registrar Venta" />
    <AppLayout> 
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar Nueva Venta</h2>
                <Link :href="route('ventas.index')">
                    <SecondaryButton>
                        &larr; Volver al Listado
                    </SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <div class="bg-white shadow-xl sm:rounded-lg p-6 mb-6 border-t-4 border-indigo-500">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Datos de la Transacción</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2 relative cliente-search-container">
                                <InputLabel for="input_buscar_cliente" value="Cliente" />
                                <div class="flex items-center">
                                    <TextInput 
                                        id="input_buscar_cliente" 
                                        name="cliente_search"
                                        v-model="searchTermCliente" 
                                        placeholder="Buscar por Nombre o DNI..." 
                                        class="w-full" 
                                        autocomplete="off" 
                                        @focus="searchTermCliente.length>=3 && (showClienteDropdown=true)" 
                                    />
                                    <button type="button" @click="clearCliente" class="ml-2 text-red-500 hover:text-red-700" v-if="ventaStore.clienteSeleccionado">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-6 h-6"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                                    </button>
                                </div>
                                <ul v-if="showClienteDropdown && filteredClientes.length" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-40 overflow-y-auto">
                                    <li v-for="cli in filteredClientes" :key="cli.clienteID" @click="selectCliente(cli)" class="px-4 py-2 cursor-pointer hover:bg-indigo-50 text-sm border-b">
                                        <span class="font-bold">{{ cli.apellido }}, {{ cli.nombre }}</span> ({{ cli.dni || 'Sin DNI' }})
                                    </li>
                                </ul>
                                <div v-if="buscandoCliente" class="absolute right-10 top-8"><span class="text-xs text-gray-500">Buscando...</span></div>
                                <InputError :message="form.errors.clienteID" class="mt-2" />
                            </div>

                            <div class="flex flex-col justify-center p-3 bg-gray-50 rounded-md">
                                <p v-if="ventaStore.clienteSeleccionado" class="text-sm"><span class="font-medium">Tipo:</span> {{ ventaStore.clienteSeleccionado.tipo_cliente?.nombreTipo }}</p>
                                <p v-if="ventaStore.clienteSeleccionado" class="text-sm mt-1"><span class="font-medium">CC:</span> <span :class="estadoCC === 'Activa' ? 'text-green-600 font-bold' : 'text-red-600'">{{ estadoCC }}</span></p>
                            </div>
                        </div>

                        <div class="mt-6 border-t pt-4">
                            <InputLabel for="select_medio_pago" value="Método de Pago" />
                            <SelectInput 
                                id="select_medio_pago"
                                name="medio_pago"
                                v-model="ventaStore.metodoPago" 
                                :options="mediosOptions" 
                                class="w-full md:w-1/3" 
                            />
                            <InputError :message="form.errors.medio_pago_id" class="mt-2" />
                            <div v-if="limiteExcedido" class="mt-2 text-sm text-red-600 font-bold bg-red-50 p-2 rounded">⚠️ Límite de crédito excedido.</div>
                        </div>
                    </div>

                    <div class="bg-white shadow-xl sm:rounded-lg p-6 mb-6 border-t-4 border-yellow-500">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Productos</h3>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end mb-6 bg-yellow-50 p-4 rounded-lg">
                            <div class="md:col-span-6 relative producto-search-container">
                                <InputLabel for="input_add_producto" value="Buscar Producto" />
                                <TextInput
                                    id="input_add_producto" 
                                    name="add_producto_search"
                                    v-model="searchTermProducto" 
                                    placeholder="Nombre o Código..." 
                                    class="w-full" 
                                    autocomplete="off" 
                                    @focus="searchTermProducto.length>=3 && (showProductoDropdown=true)" 
                                />
                                <ul v-if="showProductoDropdown && filteredProductos.length" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto">
                                    <li v-for="prod in filteredProductos" :key="prod.id" @click="selectProductToAdd(prod)" class="px-4 py-2 cursor-pointer hover:bg-yellow-100 text-sm border-b">
                                        <div class="font-bold">{{ prod.nombre }}</div>
                                        <div class="text-xs flex justify-between"><span>{{ prod.codigo }}</span><span>Stock: {{ prod.stock_total || 0 }}</span></div>
                                    </li>
                                </ul>
                            </div>
                            <div class="md:col-span-2">
                                <InputLabel for="input_add_cantidad" value="Cant." />
                                <TextInput id="input_add_cantidad" name="add_cantidad" type="number" v-model.number="quantityToAdd" class="w-full text-center" />
                            </div>
                            <div class="md:col-span-3">
                                <InputLabel for="input_add_precio" value="Precio" />
                                <TextInput id="input_add_precio" name="add_precio" type="number" v-model.number="priceToAdd" class="w-full" />
                            </div>
                            <div class="md:col-span-1"><PrimaryButton type="button" @click="addProductToCart" class="w-full justify-center bg-yellow-600 hover:bg-yellow-700">+</PrimaryButton></div>
                        </div>

                        <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left">Producto</th><th class="px-4 py-2 text-center">Cant.</th><th class="px-4 py-2 text-right">Subtotal</th><th class="px-4 py-2"></th></tr></thead>
                            <tbody>
                                <tr v-for="(item, index) in ventaStore.items" :key="item.productoID">
                                    <td class="px-4 py-2">{{ item.nombre }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <TextInput 
                                            :id="'qty_' + index" 
                                            :name="'qty_' + index"
                                            type="number" 
                                            :model-value="item.cantidad" 
                                            @input="updateItemQuantity(index, $event)" 
                                            class="w-20 text-center text-sm" 
                                        />
                                    </td>
                                    <td class="px-4 py-2 text-right font-bold">${{ item.subtotalCalculado }}</td>
                                    <td class="px-4 py-2 text-right"><DangerButton @click="ventaStore.removerItem(index)">&times;</DangerButton></td>
                                </tr>
                                <tr v-if="ventaStore.items.length===0"><td colspan="4" class="text-center py-4 text-gray-400">Carrito vacío</td></tr>
                            </tbody>
                        </table>
                        <InputError :message="form.errors['items']" class="mt-2" />
                    </div>

                    <div class="bg-white shadow-xl sm:rounded-lg p-6 border-t-4 border-indigo-700 text-right">
                        <div class="space-y-1 mb-4 text-sm text-gray-600" v-if="applyDiscountCode || ventaStore.descuentosGlobalesAplicados.length">
                            <div class="flex space-x-3 justify-end items-center mb-2">
                                <TextInput id="input_cupon" name="cupon_code" v-model="applyDiscountCode" placeholder="Cupón..." class="w-32" />
                                <SecondaryButton type="button" @click="buscarYAplicarDescuento">Aplicar</SecondaryButton>
                            </div>
                            <div v-for="desc in ventaStore.descuentosGlobalesAplicados" :key="desc.descuento_id" class="flex justify-end items-center">
                                <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded mr-2">{{ desc.descripcion }}</span>
                                <button type="button" @click="removeGlobalDiscount(desc.descuento_id)" class="text-red-500 font-bold">&times;</button>
                            </div>
                        </div>

                        <p class="text-2xl font-bold text-gray-900">TOTAL: ${{ (parseFloat(totales?.totalNeto)||0).toFixed(2) }}</p>
                        <div class="mt-4"><PrimaryButton type="submit" class="px-6 py-3 text-lg" :disabled="form.processing">Confirmar Venta</PrimaryButton></div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>