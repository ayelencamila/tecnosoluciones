<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SelectInput from '@/Components/SelectInput.vue';
import ConfigurableSelect from '@/Components/ConfigurableSelect.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import AlertMessage from '@/Components/AlertMessage.vue'; 
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

const page = usePage();
const ventaStore = useVentaStore();

// --- MEDIOS DE PAGO ---
const mediosPagoList = ref([...props.mediosPago || []]);

const mediosOptions = computed(() => {
    if (!mediosPagoList.value) return [];
    return mediosPagoList.value.map(m => ({
        value: m.medioPagoID,
        label: `${m.nombre} ${parseFloat(m.recargo_porcentaje) > 0 ? '(+' + parseFloat(m.recargo_porcentaje) + '%)' : ''}`
    }));
});

const refreshMediosPago = async () => {
    try {
        const response = await axios.get('/api/medios-pago');
        mediosPagoList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar medios de pago:', error);
    }
};

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
const nombreListaPrecio = ref('');
const applyDiscountCode = ref('');

// --- FORMULARIO INERTIA ---
const form = useForm({
    clienteID: null,
    medio_pago_id: '', 
    observaciones: '',
    items: [], 
    descuentos_globales: [],
});

// --- CÁLCULOS Y VALIDACIONES ---
const totales = computed(() => ventaStore.calcularTotales);

const esCuentaCorriente = computed(() => {
    const medio = props.mediosPago?.find(m => m.medioPagoID == ventaStore.metodoPago);
    return medio?.nombre.toLowerCase().includes('corriente');
});

const limiteExcedido = computed(() => {
    if (esCuentaCorriente.value && ventaStore.clienteSeleccionado?.cuenta_corriente) {
        const cc = ventaStore.clienteSeleccionado.cuenta_corriente;
        const saldo = parseFloat(cc.saldo || 0);
        const limite = parseFloat(cc.limiteCredito || 0);
        return (saldo + totales.value.totalNeto) > limite;
    }
    return false;
});

const estadoCC = computed(() => {
    return ventaStore.clienteSeleccionado?.cuenta_corriente?.estado_cuenta_corriente?.nombreEstado || 'N/A';
});

// Validación Local de Stock
const stockInsuficienteLocal = computed(() => {
    if (!selectedProductToAdd.value) return false;
    if (selectedProductToAdd.value.unidad_medida?.nombre === 'Servicio') return false; 
    
    const enCarrito = ventaStore.items.filter(i => i.productoID === selectedProductToAdd.value.id)
                                      .reduce((acc, i) => acc + i.cantidad, 0);
                                      
    const totalNecesario = enCarrito + quantityToAdd.value;
    const stockReal = selectedProductToAdd.value.stock_total || 0;
    
    return totalNecesario > stockReal;
});

// --- FUNCIONES ---
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
    selectedProductToAdd.value = null;
    priceToAdd.value = 0;
    searchTermProducto.value = '';
};

const clearCliente = () => {
    ventaStore.clienteSeleccionado = null;
    searchTermCliente.value = '';
    filteredClientes.value = [];
};

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
        let precioObj = producto.precios.find(p => p.tipoClienteID === tipoClienteID);
        
        if (precioObj) {
            nombreListaPrecio.value = `Lista ${ventaStore.clienteSeleccionado?.tipo_cliente?.nombreTipo || 'Especial'}`;
        } else {
            precioObj = producto.precios[0];
            nombreListaPrecio.value = 'Precio Base / General';
        }

        priceToAdd.value = parseFloat(precioObj.precio);
    } else {
        priceToAdd.value = 0;
        nombreListaPrecio.value = 'Sin precio definido';
    }
    quantityToAdd.value = 1;
};

const addProductToCart = () => {
    if (!ventaStore.clienteSeleccionado) {
        alert('Por favor, selecciona un cliente para asignar el precio correcto.');
        return;
    }
    
    if (selectedProductToAdd.value && quantityToAdd.value > 0) {
        if (stockInsuficienteLocal.value) return;

        ventaStore.agregarItem(selectedProductToAdd.value, quantityToAdd.value, priceToAdd.value);
        
        selectedProductToAdd.value = null;
        searchTermProducto.value = '';
        quantityToAdd.value = 1;
        priceToAdd.value = 0;
        nombreListaPrecio.value = '';
        
        document.getElementById('search_producto_input')?.focus();
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
        alert('Código de descuento inválido.');
    }
};

const removeGlobalDiscount = (descuentoId) => {
    ventaStore.removerDescuentoGlobal(descuentoId);
};

// --- SUBMIT ---
const submit = () => {
    if (!ventaStore.clienteSeleccionado) return alert('Debes seleccionar un Cliente.');
    if (ventaStore.items.length === 0) return alert('El carrito está vacío.');
    if (esCuentaCorriente.value && limiteExcedido.value) return alert('Límite de crédito excedido.');
    if (!ventaStore.metodoPago) return alert('Selecciona un Medio de Pago.');

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
        onSuccess: () => {
            ventaStore.limpiarVenta();
        },
        onError: (e) => {
            console.error("Error validación:", e);
        }
    });
};

onMounted(() => {
    if (props.mediosPago && props.mediosPago.length > 0 && !ventaStore.metodoPago) {
        ventaStore.metodoPago = props.mediosPago[0].medioPagoID;
    }

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
                    <SecondaryButton> &larr; Volver al Listado </SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <AlertMessage 
                    v-if="Object.keys(form.errors).length > 0" 
                    type="error"
                    :message="'Se encontraron errores de validación. Por favor revisa los campos.'"
                    class="mb-4"
                />

                <AlertMessage 
                    v-if="$page.props.flash?.success" 
                    type="success"
                    :message="$page.props.flash.success"
                    class="mb-4"
                />

                <form @submit.prevent="submit">
                    <div class="bg-white shadow-xl sm:rounded-lg p-6 mb-6 border-t-4 border-indigo-500">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            Datos de la Transacción
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            
                            <div class="md:col-span-2 relative cliente-search-container">
                                <InputLabel for="cliente_input" value="Cliente" />
                                <div class="flex items-center relative">
                                    <TextInput 
                                        id="cliente_input"
                                        name="cliente_search" 
                                        v-model="searchTermCliente" 
                                        placeholder="Buscar por Nombre o DNI..." 
                                        class="w-full pr-10" 
                                        autocomplete="off" 
                                        @focus="searchTermCliente.length>=3 && (showClienteDropdown=true)" 
                                    />
                                    <div v-if="buscandoCliente" class="absolute right-10 top-3">
                                        <svg class="animate-spin h-5 w-5 text-indigo-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </div>
                                    <button type="button" @click="clearCliente" class="ml-2 text-gray-400 hover:text-red-500 transition" v-if="ventaStore.clienteSeleccionado">
                                        &times;
                                    </button>
                                </div>
                                <ul v-if="showClienteDropdown && filteredClientes.length" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-40 overflow-y-auto">
                                    <li v-for="cli in filteredClientes" :key="cli.clienteID" @click="selectCliente(cli)" class="px-4 py-2 cursor-pointer hover:bg-indigo-50 text-sm border-b">
                                        <span class="font-bold text-indigo-700">{{ cli.apellido }}, {{ cli.nombre }}</span> 
                                        <span class="text-gray-500 ml-2 text-xs">DNI: {{ cli.dni }}</span>
                                    </li>
                                </ul>
                                <InputError :message="form.errors.clienteID" class="mt-2" />
                            </div>

                            <div class="flex flex-col justify-center p-4 bg-gray-50 rounded-md border border-gray-200">
                                <template v-if="ventaStore.clienteSeleccionado">
                                    <p class="text-sm text-gray-600"><span class="font-bold">Tipo:</span> {{ ventaStore.clienteSeleccionado.tipo_cliente?.nombreTipo }}</p>
                                    <p class="text-sm mt-2 flex justify-between">
                                        <span class="font-bold">Cta. Corriente:</span> 
                                        <span :class="{'bg-green-100 text-green-800': estadoCC === 'Activa', 'bg-red-100 text-red-800': estadoCC !== 'Activa'}" class="px-2 py-1 rounded text-xs font-bold">
                                            {{ estadoCC }}
                                        </span>
                                    </p>
                                </template>
                                <p v-else class="text-sm text-gray-400 italic text-center">Selecciona un cliente</p>
                            </div>
                        </div>

                        <div class="mt-6 border-t pt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <ConfigurableSelect
                                    id="medio_pago_select"
                                    v-model="ventaStore.metodoPago"
                                    label="Método de Pago"
                                    :options="mediosOptions"
                                    placeholder="Seleccione medio de pago..."
                                    :error="form.errors.medio_pago_id"
                                    api-endpoint="/api/medios-pago"
                                    name-field="nombre"
                                    @refresh="refreshMediosPago"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow-xl sm:rounded-lg p-6 mb-6 border-t-4 border-yellow-500">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Carrito de Compras</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end mb-6 bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                            
                            <div class="md:col-span-6 relative producto-search-container">
                                <InputLabel for="search_producto_input" value="Producto" />
                                <TextInput 
                                    id="search_producto_input"
                                    name="producto_search"
                                    v-model="searchTermProducto" 
                                    placeholder="Nombre o código..." 
                                    class="w-full" 
                                    autocomplete="off" 
                                    @focus="searchTermProducto.length>=3 && (showProductoDropdown=true)" 
                                />
                                <ul v-if="showProductoDropdown && filteredProductos.length" class="absolute z-20 w-full bg-white border border-gray-300 rounded-md shadow-xl mt-1 max-h-60 overflow-y-auto">
                                    <li v-for="prod in filteredProductos" :key="prod.id" @click="selectProductToAdd(prod)" class="px-4 py-2 cursor-pointer hover:bg-yellow-100 text-sm border-b">
                                        <div class="font-bold text-gray-800">{{ prod.nombre }}</div>
                                        <div class="text-xs text-gray-500 flex justify-between mt-1">
                                            <span>COD: {{ prod.codigo }}</span>
                                            <span :class="{'text-green-600': prod.stock_total > 0, 'text-red-600': prod.stock_total <= 0}" class="font-bold">
                                                Stock: {{ prod.stock_total || 0 }}
                                            </span>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="cantidad_input" value="Cant." />
                                <TextInput 
                                    id="cantidad_input" 
                                    name="cantidad_item"
                                    type="number" 
                                    min="1" 
                                    v-model.number="quantityToAdd" 
                                    class="w-full text-center" 
                                />
                            </div>

                            <div class="md:col-span-3">
                                <InputLabel for="precio_input" value="Precio Unit." />
                                <TextInput 
                                    id="precio_input" 
                                    name="precio_item"
                                    type="number" 
                                    step="0.01" 
                                    v-model.number="priceToAdd" 
                                    class="w-full" 
                                />
                                <p class="text-xs text-gray-500 mt-1 truncate">{{ nombreListaPrecio }}</p>
                            </div>

                            <div class="md:col-span-1">
                                <PrimaryButton 
                                    type="button" 
                                    @click="addProductToCart" 
                                    class="w-full justify-center bg-yellow-600 hover:bg-yellow-700 disabled:opacity-50"
                                    :disabled="!selectedProductToAdd || stockInsuficienteLocal"
                                >
                                    <span v-if="!stockInsuficienteLocal">+</span>
                                    <span v-else class="text-xs">Sin Stock</span>
                                </PrimaryButton>
                            </div>
                        </div>

                        <div class="overflow-x-auto border rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Cant.</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio U.</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in ventaStore.items" :key="item.productoID" class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ item.nombre }}</td>
                                        <td class="px-4 py-3 text-sm text-center">{{ item.cantidad }}</td>
                                        <td class="px-4 py-3 text-sm text-right">${{ parseFloat(item.precioUnitario).toFixed(2) }}</td>
                                        <td class="px-4 py-3 text-sm text-right font-bold">${{ parseFloat(item.subtotalCalculado).toFixed(2) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <button @click="ventaStore.removerItem(index)" class="text-red-500 hover:text-red-700 font-bold px-2">&times;</button>
                                        </td>
                                    </tr>
                                    <tr v-if="ventaStore.items.length===0">
                                        <td colspan="5" class="text-center py-8 text-gray-400 italic">No hay productos en el carrito.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <InputError :message="form.errors.items" class="mt-2" />
                    </div>

                    <div class="bg-white shadow-xl sm:rounded-lg p-6 border-t-4 border-indigo-700">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                            <div class="w-full md:w-1/2 mb-4 md:mb-0 pr-0 md:pr-6">
                                <InputLabel for="cupon_input" value="Código de Descuento (Opcional)" />
                                <div class="flex gap-2">
                                    <TextInput 
                                        id="cupon_input" 
                                        name="cupon_codigo"
                                        v-model="applyDiscountCode" 
                                        placeholder="Ingresa código..." 
                                        class="w-full md:w-48" 
                                    />
                                    <SecondaryButton type="button" @click="buscarYAplicarDescuento">Aplicar</SecondaryButton>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span v-for="desc in ventaStore.descuentosGlobalesAplicados" :key="desc.descuento_id" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ desc.descripcion }}
                                        <button type="button" @click="removeGlobalDiscount(desc.descuento_id)" class="ml-1 text-green-600 font-bold">&times;</button>
                                    </span>
                                </div>
                            </div>

                            <div class="w-full md:w-1/2 text-right space-y-2">
                                <div class="text-gray-600 flex justify-between md:justify-end gap-4">
                                    <span>Subtotal:</span>
                                    <span>${{ (parseFloat(totales?.subtotal)||0).toFixed(2) }}</span>
                                </div>
                                <div v-if="totales?.totalDescuentos > 0" class="text-green-600 flex justify-between md:justify-end gap-4">
                                    <span>Descuentos:</span>
                                    <span>- ${{ (parseFloat(totales?.totalDescuentos)||0).toFixed(2) }}</span>
                                </div>
                                <div class="text-3xl font-extrabold text-gray-900 border-t pt-2 flex justify-between md:justify-end gap-4">
                                    <span>TOTAL:</span>
                                    <span>${{ (parseFloat(totales?.totalNeto)||0).toFixed(2) }}</span>
                                </div>
                                
                                <div class="mt-6">
                                    <PrimaryButton 
                                        type="submit" 
                                        class="w-full md:w-auto px-8 py-3 text-lg shadow-lg" 
                                        :class="{ 'opacity-50 cursor-not-allowed': form.processing || limiteExcedido }"
                                        :disabled="form.processing || limiteExcedido"
                                    >
                                        {{ form.processing ? 'Procesando...' : 'Confirmar Venta' }}
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>