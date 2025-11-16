<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue'; //sin color
import InputLabel from '@/Components/InputLabel.vue'; //sin color
import TextInput from '@/Components/TextInput.vue'; //sin color
import SelectInput from '@/Components/SelectInput.vue'; //sin color
import DangerButton from '@/Components/DangerButton.vue'; //sin color
import InputError from '@/Components/InputError.vue'; //sin color
import AlertMessage from '@/Components/AlertMessage.vue';
import { useVentaStore } from '@/Stores/ventaStore.js';
import { debounce } from 'lodash';


const props = defineProps({
    clientes: Array,
    productos: Array,
    descuentos: Array,
    errors: Object,
});
const ventaStore = useVentaStore();
const searchTermCliente = ref('');
const filteredClientes = ref([]);
const showClienteDropdown = ref(false);
const searchTermProducto = ref('');
const filteredProductos = ref([]);
const showProductoDropdown = ref(false);
const selectedProductToAdd = ref(null);
const quantityToAdd = ref(1);
const priceToAdd = ref(0);
const form = useForm({
    clienteID: computed(() => ventaStore.clienteSeleccionado?.clienteID || null),
    metodo_pago: computed(() => ventaStore.metodoPago), 
    observaciones: computed(() => ventaStore.observaciones),
    items: computed(() => ventaStore.items.map(item => ({
        productoID: item.productoID,
        cantidad: item.cantidad,
        precioUnitario: item.precioUnitario,
        precio_producto_id: item.precio_producto_id || null, 
        descuentos_item: item.descuentos_item || [] 
    }))),
    descuentos_globales: computed(() => ventaStore.descuentosGlobalesAplicados.map(d => {
        return { descuento_id: d.descuento_id }; 
    })),
    totalNeto: computed(() => ventaStore.calcularTotales.totalNeto), 
});
watch(selectedProductToAdd, (newProduct) => {
    if (newProduct && newProduct.precios && newProduct.precios.length > 0) {
        priceToAdd.value = newProduct.precios.reduce((max, p) => Math.max(max, parseFloat(p.precio)), 0);
    } else {
        priceToAdd.value = 0;
    }
});
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
    ventaStore.clienteSeleccionado = cliente;
    searchTermCliente.value = `${cliente.nombre} ${cliente.apellido} (${cliente.DNI})`;
    showClienteDropdown.value = false;
};
const clearCliente = () => {
    ventaStore.clienteSeleccionado = null;
    searchTermCliente.value = '';
};
const debouncedFilterProductos = debounce(() => {
    if (searchTermProducto.value.length >= 2) {
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
const errorMensaje = ref('');
const addProductToCart = () => {
    if (selectedProductToAdd.value && quantityToAdd.value > 0 && priceToAdd.value > 0) {
        ventaStore.agregarItem(
            selectedProductToAdd.value,
            quantityToAdd.value,
            priceToAdd.value
        );
        clearProductSelection();
        errorMensaje.value = ''; // Limpiar mensaje de error
    } else {
        errorMensaje.value = 'Por favor, selecciona un producto, cantidad y precio válidos.';
    }
};
const removeProductFromCart = (index) => {
    ventaStore.removerItem(index);
};
const updateItemQuantity = (index, event) => {
    const newQuantity = parseInt(event.target.value);
    if (!isNaN(newQuantity) && newQuantity >= 0) {
        ventaStore.actualizarCantidadItem(index, newQuantity);
    }
};
const errorSubmit = ref('');
const submit = () => {
    // Validaciones de cliente
    if (!ventaStore.clienteSeleccionado) {
        errorSubmit.value = 'Por favor, selecciona un cliente para la venta.';
        return;
    }
    // Validaciones de carrito
    if (ventaStore.items.length === 0) {
        errorSubmit.value = 'El carrito está vacío. Agrega productos para realizar la venta.';
        return;
    }
    
    errorSubmit.value = ''; // Limpiar error previo
    
    form.post(route('ventas.store'), {
        preserveScroll: true,
        onSuccess: () => {
            ventaStore.limpiarVenta();
            errorSubmit.value = '';
        },
        onError: (errors) => {
            console.error('Errores de validación:', errors);
            // Mostrar el primer error más relevante al usuario
            if (errors.clienteID) {
                errorSubmit.value = errors.clienteID;
            } else if (errors.items) {
                errorSubmit.value = errors.items;
            } else if (errors.metodo_pago) {
                errorSubmit.value = errors.metodo_pago;
            } else if (errors.message) {
                errorSubmit.value = errors.message;
            } else {
                errorSubmit.value = 'Ocurrió un error al registrar la venta. Por favor, verifica los datos.';
            }
        },
    });
};
const applyDiscountCode = ref('');
const errorDescuento = ref('');
const buscarYAplicarDescuento = () => {
    const descuentoEncontrado = props.descuentos.find(d => d.codigo.toLowerCase() === applyDiscountCode.value.toLowerCase());
    if (descuentoEncontrado) {
        ventaStore.aplicarDescuentoGlobal(descuentoEncontrado);
        applyDiscountCode.value = '';
        errorDescuento.value = ''; // Limpiar error
    } else {
        errorDescuento.value = 'Código de descuento no encontrado o no válido.';
    }
};
const removeGlobalDiscount = (descuentoId) => {
    ventaStore.removerDescuentoGlobal(descuentoId);
};
const totales = computed(() => ventaStore.calcularTotales);
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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar Nueva Venta</h2>
        </template>
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Error general de submit -->
                <AlertMessage 
                    v-if="errorSubmit"
                    :message="errorSubmit"
                    type="error"
                    @dismiss="errorSubmit = ''"
                />

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        
                        <!-- Sección: Cliente -->
                        <div>
                            <InputLabel value="Cliente *" />
                            <div class="cliente-search-container relative">
                                <TextInput
                                    v-model="searchTermCliente"
                                    type="text"
                                    placeholder="Buscar cliente por nombre, apellido o DNI..."
                                    class="mt-1 block w-full"
                                    @focus="showClienteDropdown = searchTermCliente.length >= 2"
                                />
                                <!-- Dropdown de clientes -->
                                <div v-if="showClienteDropdown && filteredClientes.length > 0"
                                     class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto">
                                    <div v-for="cliente in filteredClientes" 
                                         :key="cliente.clienteID"
                                         @click="selectCliente(cliente)"
                                         class="px-4 py-2 hover:bg-gray-100 cursor-pointer">
                                        {{ cliente.nombre }} {{ cliente.apellido }} ({{ cliente.DNI }})
                                    </div>
                                </div>
                                <!-- Cliente seleccionado -->
                                <div v-if="ventaStore.clienteSeleccionado" class="mt-2 p-3 bg-blue-50 rounded-md flex justify-between items-center">
                                    <span class="text-sm font-medium text-blue-800">
                                        {{ ventaStore.clienteSeleccionado.nombre }} {{ ventaStore.clienteSeleccionado.apellido }}
                                    </span>
                                    <button @click="clearCliente" type="button" class="text-red-600 hover:text-red-800">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <InputError :message="props.errors.clienteID" class="mt-2" />
                        </div>

                        <!-- Sección: Agregar Producto -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold mb-4">Agregar Productos</h3>
                            
                            <AlertMessage 
                                v-if="errorMensaje"
                                :message="errorMensaje"
                                type="warning"
                                @dismiss="errorMensaje = ''"
                            />

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-2">
                                    <InputLabel value="Producto" />
                                    <div class="producto-search-container relative">
                                        <TextInput
                                            v-model="searchTermProducto"
                                            type="text"
                                            placeholder="Buscar producto por nombre o código..."
                                            class="mt-1 block w-full"
                                            @focus="showProductoDropdown = searchTermProducto.length >= 2"
                                        />
                                        <div v-if="showProductoDropdown && filteredProductos.length > 0"
                                             class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto">
                                            <div v-for="producto in filteredProductos" 
                                                 :key="producto.productoID"
                                                 @click="selectProductToAdd(producto)"
                                                 class="px-4 py-2 hover:bg-gray-100 cursor-pointer">
                                                {{ producto.nombre }} ({{ producto.codigo }})
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <InputLabel value="Cantidad" />
                                    <TextInput
                                        v-model.number="quantityToAdd"
                                        type="number"
                                        min="1"
                                        class="mt-1 block w-full"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Precio" />
                                    <TextInput
                                        v-model.number="priceToAdd"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="mt-1 block w-full"
                                    />
                                </div>
                            </div>
                            <div class="mt-4">
                                <PrimaryButton @click="addProductToCart" type="button">
                                    Agregar al Carrito
                                </PrimaryButton>
                            </div>
                        </div>

                        <!-- Carrito -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold mb-4">Carrito de Compras</h3>
                            <div v-if="ventaStore.items.length === 0" class="text-gray-500 text-center py-8">
                                El carrito está vacío
                            </div>
                            <div v-else class="space-y-2">
                                <div v-for="(item, index) in ventaStore.items" :key="index"
                                     class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                    <div class="flex-1">
                                        <span class="font-medium">{{ item.nombre }}</span>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <input
                                            type="number"
                                            :value="item.cantidad"
                                            @input="updateItemQuantity(index, $event)"
                                            min="1"
                                            class="w-20 px-2 py-1 border rounded"
                                        />
                                        <span class="w-24 text-right">${{ (item.precioUnitario * item.cantidad).toFixed(2) }}</span>
                                        <button @click="removeProductFromCart(index)" type="button" class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Descuentos -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold mb-4">Descuentos</h3>
                            
                            <AlertMessage 
                                v-if="errorDescuento"
                                :message="errorDescuento"
                                type="warning"
                                @dismiss="errorDescuento = ''"
                            />

                            <div class="flex gap-2">
                                <TextInput
                                    v-model="applyDiscountCode"
                                    type="text"
                                    placeholder="Código de descuento"
                                    class="flex-1"
                                />
                                <PrimaryButton @click="buscarYAplicarDescuento" type="button">
                                    Aplicar
                                </PrimaryButton>
                            </div>
                            
                            <div v-if="ventaStore.descuentosGlobalesAplicados.length > 0" class="mt-4 space-y-2">
                                <div v-for="desc in ventaStore.descuentosGlobalesAplicados" :key="desc.descuento_id"
                                     class="flex justify-between items-center p-2 bg-green-50 rounded">
                                    <span class="text-sm">{{ desc.codigo }}</span>
                                    <button @click="removeGlobalDiscount(desc.descuento_id)" type="button" class="text-red-600">
                                        Quitar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Método de Pago -->
                        <div class="border-t pt-6">
                            <InputLabel value="Método de Pago *" />
                            <SelectInput
                                v-model="ventaStore.metodoPago"
                                class="mt-1 block w-full"
                            >
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="cuenta_corriente">Cuenta Corriente</option>
                            </SelectInput>
                            <InputError :message="props.errors.metodo_pago" class="mt-2" />
                        </div>

                        <!-- Observaciones -->
                        <div>
                            <InputLabel value="Observaciones" />
                            <textarea
                                v-model="ventaStore.observaciones"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                rows="3"
                            ></textarea>
                        </div>

                        <!-- Totales -->
                        <div class="border-t pt-6 bg-gray-50 p-4 rounded-md">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Subtotal:</span>
                                    <span class="font-medium">${{ totales.subtotal.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-green-600">
                                    <span>Descuentos:</span>
                                    <span class="font-medium">-${{ totales.descuentos.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold border-t pt-2">
                                    <span>Total:</span>
                                    <span>${{ totales.totalNeto.toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-end gap-4">
                            <Link :href="route('ventas.index')">
                                <DangerButton type="button">
                                    Cancelar
                                </DangerButton>
                            </Link>
                            <PrimaryButton @click="submit" type="button" :disabled="form.processing">
                                {{ form.processing ? 'Procesando...' : 'Finalizar Venta' }}
                            </PrimaryButton>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>