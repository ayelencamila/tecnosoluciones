<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { useVentaStore } from '@/Stores/ventaStore.js';
import { debounce } from 'lodash';

const props = defineProps({
    clientes: Array,
    productos: Array,
    descuentos: Array,
    errors: Object,
});

const ventaStore = useVentaStore();

// --- ESTADO LOCAL ---
const searchTermCliente = ref('');
const filteredClientes = ref([]);
const showClienteDropdown = ref(false);

const searchTermProducto = ref('');
const filteredProductos = ref([]);
const showProductoDropdown = ref(false);

const selectedProductToAdd = ref(null);
const quantityToAdd = ref(1);
const priceToAdd = ref(0);

const applyDiscountCode = ref('');

// --- FORMULARIO INERTIA ---
const form = useForm({
    clienteID: computed(() => ventaStore.clienteSeleccionado?.clienteID || null),
    metodo_pago: computed(() => ventaStore.metodoPago), 
    observaciones: computed(() => ventaStore.observaciones),
    items: computed(() => ventaStore.items.map(item => ({
        productoID: item.productoID,
        cantidad: item.cantidad,
        precioUnitario: item.precioUnitario,
        precio_producto_id: item.precio_producto_id || null, 
        descuentos_item: item.descuentos_item.map(d => ({ descuento_id: d.descuento_id, monto_aplicado_item: d.montoAplicado }))
    }))),
    descuentos_globales: computed(() => ventaStore.descuentosGlobalesAplicados.map(d => {
        return { descuento_id: d.descuento_id }; 
    })),
});

// --- CÁLCULOS ---
const totales = computed(() => ventaStore.calcularTotales);

const limiteExcedido = computed(() => {
    if (form.metodo_pago === 'cuenta_corriente' && ventaStore.clienteSeleccionado?.cuenta_corriente) {
        const cc = ventaStore.clienteSeleccionado.cuenta_corriente;
        const saldoActual = parseFloat(cc.saldo || 0); 
        const limite = parseFloat(cc.limiteCredito || 0); 
        return (saldoActual + totales.value.totalNeto) > limite;
    }
    return false;
});

const estadoCC = computed(() => {
    return ventaStore.clienteSeleccionado?.cuenta_corriente?.estado_cuenta_corriente?.nombreEstado || 'N/A';
});

// --- CLIENTES ---
const debouncedFilterClientes = debounce(() => {
    if (searchTermCliente.value.length >= 3) {
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
    ventaStore.setCliente(cliente);
    searchTermCliente.value = `${cliente.nombre} ${cliente.apellido} (${cliente.DNI})`;
    showClienteDropdown.value = false;
};

const clearCliente = () => {
    ventaStore.clienteSeleccionado = null;
    searchTermCliente.value = '';
    form.clearErrors(); 
};

// --- PRODUCTOS ---
watch(selectedProductToAdd, (newProduct) => {
    if (newProduct) {
        if (newProduct.precios && newProduct.precios.length > 0) {
            const tipoClienteID = ventaStore.clienteSeleccionado?.tipoClienteID;
            const precioEncontrado = newProduct.precios.find(p => p.tipoClienteID === tipoClienteID);
            
            if (precioEncontrado) {
                priceToAdd.value = parseFloat(precioEncontrado.precio);
            } else {
                priceToAdd.value = newProduct.precios.reduce((max, p) => Math.max(max, parseFloat(p.precio)), 0);
            }
        } else {
            priceToAdd.value = 0;
        }
    } else {
        priceToAdd.value = 0;
    }
});

const debouncedFilterProductos = debounce(() => {
    if (searchTermProducto.value.length >= 3) {
        filteredProductos.value = props.productos.filter(producto =>
            producto.nombre.toLowerCase().includes(searchTermProducto.value.toLowerCase()) ||
            producto.codigo.toLowerCase().includes(searchTermProducto.value.toLowerCase())
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
    quantityToAdd.value = 1; 
};

const clearProductSelection = () => {
    selectedProductToAdd.value = null;
    searchTermProducto.value = '';
    quantityToAdd.value = 1;
    priceToAdd.value = 0;
};

const addProductToCart = () => {
    if (!ventaStore.clienteSeleccionado) {
        alert('Por favor, selecciona primero un cliente.');
        return;
    }

    if (selectedProductToAdd.value && quantityToAdd.value > 0 && priceToAdd.value > 0) {
        ventaStore.agregarItem(
            selectedProductToAdd.value,
            quantityToAdd.value,
            priceToAdd.value
        );
        clearProductSelection();
    } else {
        alert('Datos inválidos. Verifica producto, cantidad y precio.');
    }
};

const removeProductFromCart = (index) => {
    ventaStore.removerItem(index);
};

const updateItemQuantity = (index, event) => {
    const newQuantity = parseFloat(event.target.value);
    if (!isNaN(newQuantity) && newQuantity >= 0.01) {
        ventaStore.actualizarCantidadItem(index, newQuantity);
    }
};

// --- DESCUENTOS ---
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
    if (!ventaStore.clienteSeleccionado) {
        alert('Falta seleccionar el Cliente.'); 
        return;
    }
    if (ventaStore.items.length === 0) {
        alert('El carrito está vacío.');
        return;
    }
    if (form.metodo_pago === 'cuenta_corriente' && limiteExcedido.value) {
        alert('Límite de Crédito Excedido.');
        return;
    }

    form.post(route('ventas.store'), {
        preserveScroll: true,
        onSuccess: () => {
            ventaStore.limpiarVenta(); 
        },
        onError: (errors) => {
            if (errors.message) alert(errors.message);
        },
    });
};

onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.cliente-search-container')) {
            showClienteDropdown.value = false;
        }
        if (!e.target.closest('.producto-search-container')) {
            showProductoDropdown.value = false;
        }
    });
});
</script>

<template>
    <Head title="Registrar Venta" />

    <AppLayout> 
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Registrar Nueva Venta
            </h2>
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
                                        name="input_buscar_cliente"
                                        v-model="searchTermCliente"
                                        placeholder="Buscar por Nombre o DNI..."
                                        class="w-full"
                                        @focus="searchTermCliente.length >= 3 ? showClienteDropdown = true : null"
                                        autocomplete="off"
                                    />
                                    <button 
                                        type="button"
                                        @click="clearCliente"
                                        class="ml-2 text-red-500 hover:text-red-700"
                                        v-if="ventaStore.clienteSeleccionado"
                                        title="Quitar cliente"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-6 h-6"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                                    </button>
                                </div>

                                <ul v-if="showClienteDropdown && filteredClientes.length" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-40 overflow-y-auto">
                                    <li v-for="cliente in filteredClientes" :key="cliente.clienteID" @click="selectCliente(cliente)" class="px-4 py-2 cursor-pointer hover:bg-indigo-50 text-sm border-b">
                                        <span class="font-bold">{{ cliente.apellido }}, {{ cliente.nombre }}</span> ({{ cliente.DNI }})
                                    </li>
                                </ul>
                                <InputError :message="form.errors.clienteID" class="mt-2" />
                            </div>

                            <div class="flex flex-col justify-center p-3 bg-gray-50 rounded-md">
                                <p v-if="ventaStore.clienteSeleccionado" class="text-sm">
                                    <span class="font-medium text-gray-700">Tipo:</span> {{ ventaStore.clienteSeleccionado.tipo_cliente?.nombreTipo || 'Minorista' }}
                                </p>
                                <p v-if="ventaStore.clienteSeleccionado" class="text-sm mt-1">
                                    <span class="font-medium text-gray-700">CC:</span> 
                                    <span :class="{'text-green-600 font-bold': estadoCC === 'Activa', 'text-red-600 font-bold': estadoCC === 'Bloqueada'}">
                                        {{ estadoCC }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 border-t pt-4">
                            <InputLabel for="select_metodo_pago" value="Método de Pago" />
                            <SelectInput 
                                id="select_metodo_pago" 
                                v-model="ventaStore.metodoPago" 
                                :options="[
                                    { value: 'efectivo', label: 'Efectivo' },
                                    { value: 'tarjeta', label: 'Tarjeta' },
                                    { value: 'cuenta_corriente', label: 'Cuenta Corriente' },
                                ]" 
                                class="w-full md:w-1/3" 
                            />
                            <InputError :message="form.errors.metodo_pago" class="mt-2" />

                            <div v-if="form.metodo_pago === 'cuenta_corriente'" class="mt-2">
                                <p v-if="limiteExcedido" class="text-sm text-red-600 font-semibold bg-red-50 p-2 rounded border border-red-200">
                                    ⚠️ Límite de crédito excedido.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow-xl sm:rounded-lg p-6 mb-6 border-t-4 border-yellow-500">
                         <h3 class="text-xl font-semibold text-gray-800 mb-4">Detalle de Productos</h3>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end mb-6 border-b pb-4 bg-yellow-50 p-4 rounded-lg">
                            
                            <div class="md:col-span-5 relative producto-search-container">
                                <InputLabel for="input_add_producto" value="Buscar Producto" />
                                <TextInput
                                    id="input_add_producto"
                                    name="input_add_producto"
                                    v-model="searchTermProducto"
                                    placeholder="Cód. o Nombre..."
                                    @focus="searchTermProducto.length >= 3 ? showProductoDropdown = true : null"
                                    autocomplete="off"
                                    class="w-full"
                                />
                                <ul v-if="showProductoDropdown && filteredProductos.length" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto">
                                    <li v-for="prod in filteredProductos" :key="prod.id" @click="selectProductToAdd(prod)" class="px-4 py-2 cursor-pointer hover:bg-yellow-100 text-sm border-b">
                                        <div class="font-bold">{{ prod.nombre }}</div>
                                        <div class="text-xs text-gray-500 flex justify-between">
                                            <span>{{ prod.codigo }}</span>
                                            <span>Stock: {{ prod.stock_total || 0 }}</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="input_add_cantidad" value="Cant." />
                                <TextInput 
                                    id="input_add_cantidad" 
                                    name="input_add_cantidad"
                                    type="number" 
                                    v-model.number="quantityToAdd" 
                                    class="w-full text-center"
                                />
                            </div>

                            <div class="md:col-span-4">
                                <InputLabel for="input_add_precio" value="Precio Unitario (Congelado)" />
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <TextInput 
                                        id="input_add_precio" 
                                        name="input_add_precio"
                                        type="number" 
                                        v-model.number="priceToAdd" 
                                        class="w-full pl-7"
                                    />
                                </div>
                            </div>

                            <div class="md:col-span-1 flex justify-end">
                                <PrimaryButton 
                                    type="button" 
                                    @click="addProductToCart" 
                                    :disabled="!selectedProductToAdd || quantityToAdd <= 0 || priceToAdd <= 0"
                                    class="h-10 w-full flex justify-center items-center bg-yellow-600 hover:bg-yellow-700"
                                    title="Agregar"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-6 h-6"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                                </PrimaryButton>
                            </div>
                            
                            <div class="md:col-span-12">
                                <InputError :message="form.errors['items']" class="mt-1" />
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Producto</th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Cant.</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Precio U.</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Subtotal</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in ventaStore.items" :key="item.productoID">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ item.nombre }}</div>
                                            <div class="text-xs text-gray-500">{{ item.codigo }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <TextInput 
                                                :id="'item_qty_' + index"
                                                :name="'item_qty_' + index"
                                                type="number" 
                                                :model-value="item.cantidad" 
                                                @input="updateItemQuantity(index, $event)" 
                                                class="w-20 text-center text-sm" 
                                            />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            ${{ item.precioUnitario }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                            ${{ item.subtotalCalculado }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <DangerButton @click="removeProductFromCart(index)">
                                                &times;
                                            </DangerButton>
                                        </td>
                                    </tr>
                                    <tr v-if="ventaStore.items.length === 0">
                                        <td colspan="5" class="text-center py-8 text-gray-400 bg-gray-50">
                                            Carrito vacío.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2 bg-white shadow-xl sm:rounded-lg p-6 border-t-4 border-green-500">
                             <h3 class="text-xl font-semibold text-gray-800 mb-4">Observaciones</h3>

                            <InputLabel for="input_observaciones" value="Observaciones" class="mb-1" />
                            <TextInput id="input_observaciones" name="observaciones" v-model="ventaStore.observaciones" class="w-full mb-6" />
                            
                            <InputLabel for="input_cupon" value="Cupón de Descuento" class="mb-1" />
                            <div class="flex space-x-3">
                                <TextInput id="input_cupon" name="cupon" v-model="applyDiscountCode" placeholder="Código..." class="flex-1" />
                                <SecondaryButton type="button" @click="buscarYAplicarDescuento" :disabled="!applyDiscountCode">Aplicar</SecondaryButton>
                            </div>
                            
                            <div class="mt-4 space-y-2">
                                <div v-for="desc in ventaStore.descuentosGlobalesAplicados" :key="desc.descuento_id" class="flex justify-between items-center bg-green-50 p-2 rounded border border-green-200 text-sm text-green-800">
                                    <span>🏷️ {{ desc.descripcion }}</span>
                                    <button type="button" @click="removeGlobalDiscount(desc.descuento_id)" class="text-red-500 font-bold px-2">&times;</button>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white shadow-xl sm:rounded-lg p-6 border-t-4 border-indigo-700">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Resumen</h3>
                            
                            <div class="space-y-3">
                                <p class="flex justify-between text-gray-600">
                                    <span>Subtotal:</span>
                                    <span class="font-medium">${{ (parseFloat(totales?.subtotalBruto) || 0).toFixed(2) }}</span> 
                                </p>
                                <p class="flex justify-between text-green-600" v-if="(parseFloat(totales?.totalDescuentosItems) || 0) > 0">
                                    <span>Desc. Items:</span>
                                    <span class="font-medium">- ${{ (parseFloat(totales?.totalDescuentosItems) || 0).toFixed(2) }}</span>
                                </p>
                                <p class="flex justify-between text-green-600" v-if="(parseFloat(totales?.totalDescuentosGlobales) || 0) > 0">
                                    <span>Desc. Global:</span>
                                    <span class="font-medium">- ${{ (parseFloat(totales?.totalDescuentosGlobales) || 0).toFixed(2) }}</span>
                                </p>

                                <div class="border-t border-gray-200 pt-3 mt-2">
                                    <p class="flex justify-between text-xl font-bold text-gray-900">
                                        <span>TOTAL:</span>
                                        <span>${{ (parseFloat(totales?.totalNeto) || 0).toFixed(2) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 flex justify-end">
                        <PrimaryButton 
                            type="submit"
                            class="px-6 py-3 text-lg"
                            :class="{ 'opacity-25': form.processing }" 
                            :disabled="form.processing || ventaStore.items.length === 0"
                        >
                            Confirmar Venta
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>