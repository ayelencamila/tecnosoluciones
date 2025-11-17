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
const quantityToAdd = ref('1');
const priceToAdd = ref('0');
const skuInput = ref('');
const skuInputField = ref(null);
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
        // Buscar precio específico para el tipo de cliente seleccionado
        if (ventaStore.clienteSeleccionado) {
            const precioCliente = newProduct.precios.find(p => 
                p.tipoClienteID === ventaStore.clienteSeleccionado.tipoClienteID
            );
            priceToAdd.value = String(precioCliente ? parseFloat(precioCliente.precio) : parseFloat(newProduct.precios[0].precio));
        } else {
            // Si no hay cliente, usar el primer precio disponible
            priceToAdd.value = String(parseFloat(newProduct.precios[0].precio));
        }
    } else {
        priceToAdd.value = '0';
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
    quantityToAdd.value = '1'; 
};
const clearProductSelection = () => {
    selectedProductToAdd.value = null;
    searchTermProducto.value = '';
    quantityToAdd.value = '1';
    priceToAdd.value = '0';
};
const agregarPorSKU = () => {
    if (!skuInput.value.trim()) return;
    
    let codigo = skuInput.value.trim();
    let cantidad = 1;
    
    // Parsear formato CODIGO*CANTIDAD (ej: ABC123*5)
    if (codigo.includes('*')) {
        const parts = codigo.split('*');
        codigo = parts[0].trim();
        cantidad = parseInt(parts[1]) || 1;
    }
    
    // Buscar producto por código exacto (case-insensitive)
    const producto = props.productos.find(p => 
        p.codigo.toLowerCase() === codigo.toLowerCase()
    );
    
    if (!producto) {
        errorMensaje.value = `Producto con código "${codigo}" no encontrado.`;
        skuInput.value = '';
        return;
    }
    
    // Validar stock disponible
    if (producto.stockActual < cantidad) {
        errorMensaje.value = `Stock insuficiente para ${producto.nombre}. Disponible: ${producto.stockActual}, Solicitado: ${cantidad}`;
        skuInput.value = '';
        return;
    }
    
    // Validar que haya cliente seleccionado
    if (!ventaStore.clienteSeleccionado) {
        errorMensaje.value = 'Por favor, selecciona un cliente antes de agregar productos.';
        skuInput.value = '';
        return;
    }
    
    // Obtener precio para el tipo de cliente
    let precio = 0;
    let precioProductoId = null;
    if (producto.precios && producto.precios.length > 0) {
        const precioCliente = producto.precios.find(p => 
            p.tipoClienteID === ventaStore.clienteSeleccionado.tipoClienteID
        );
        if (precioCliente) {
            precio = parseFloat(precioCliente.precio);
            precioProductoId = precioCliente.id;
        } else if (producto.precios.length > 0) {
            // Fallback al primer precio disponible
            precio = parseFloat(producto.precios[0].precio);
            precioProductoId = producto.precios[0].id;
        }
    }
    
    if (precio === 0) {
        errorMensaje.value = `No se encontró precio válido para ${producto.nombre}.`;
        skuInput.value = '';
        return;
    }
    
    // Agregar precio_producto_id al objeto producto temporalmente
    producto.precio_producto_id = precioProductoId;
    
    // Agregar al carrito
    ventaStore.agregarItem(producto, cantidad, precio);
    
    // Limpiar input y mantener foco para siguiente producto
    skuInput.value = '';
    errorMensaje.value = '';
    
    // Mantener foco en el campo SKU para escaneos rápidos
    setTimeout(() => {
        skuInputField.value?.$el?.focus();
    }, 50);
};
const errorMensaje = ref('');
const addProductToCart = () => {
    // Validar que haya cliente seleccionado
    if (!ventaStore.clienteSeleccionado) {
        errorMensaje.value = 'Por favor, selecciona un cliente antes de agregar productos.';
        return;
    }
    
    const cantidad = Number(quantityToAdd.value);
    const precio = Number(priceToAdd.value);
    
    if (selectedProductToAdd.value && cantidad > 0 && precio > 0) {
        // Validar stock disponible
        if (selectedProductToAdd.value.stockActual < cantidad) {
            errorMensaje.value = `Stock insuficiente para ${selectedProductToAdd.value.nombre}. Disponible: ${selectedProductToAdd.value.stockActual}, Solicitado: ${cantidad}`;
            return;
        }
        
        // Obtener precio_producto_id del precio seleccionado
        let precioProductoId = null;
        if (selectedProductToAdd.value.precios && ventaStore.clienteSeleccionado) {
            const precioCliente = selectedProductToAdd.value.precios.find(p => 
                p.tipoClienteID === ventaStore.clienteSeleccionado.tipoClienteID
            );
            precioProductoId = precioCliente?.id || (selectedProductToAdd.value.precios[0]?.id || null);
        }
        
        selectedProductToAdd.value.precio_producto_id = precioProductoId;
        
        ventaStore.agregarItem(
            selectedProductToAdd.value,
            cantidad,
            precio
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
    // Validación de cuenta corriente si es necesario
    if (ventaStore.metodoPago === 'cuenta_corriente') {
        if (!ventaStore.clienteSeleccionado.cuentaCorriente) {
            errorSubmit.value = 'El cliente seleccionado no tiene una cuenta corriente activa. Selecciona otra forma de venta o elige un cliente diferente.';
            return;
        }
        
        // Validar estado de la cuenta corriente
        const estadoCC = ventaStore.clienteSeleccionado.cuentaCorriente.estado_cuenta_corriente?.nombre;
        if (estadoCC === 'Bloqueada') {
            errorSubmit.value = 'La cuenta corriente del cliente está BLOQUEADA. Solo se permiten ventas al contado.';
            return;
        }
        if (estadoCC === 'Pendiente de Aprobación') {
            errorSubmit.value = 'La cuenta corriente del cliente está PENDIENTE DE APROBACIÓN. No se permiten ventas a crédito hasta su revisión.';
            return;
        }
    }
    
    errorSubmit.value = ''; // Limpiar error previo
    
    form.post(route('ventas.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Comprueba si la página recargada (el redirect) trajo un error flash
            if (page.props.flash?.error) {
                errorSubmit.value = page.props.flash.error;
            } else {
                ventaStore.limpiarVenta();
                errorSubmit.value = '';
            }
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
    
    // Auto-focus en campo SKU para iniciar escaneos rápidamente
    setTimeout(() => {
        skuInputField.value?.$el?.focus();
    }, 300);
});
</script>

<template>
    <Head title="Registrar Venta" />
    <AppLayout> 
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva Venta</h2>
        </template>
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Flash messages del servidor -->
                <AlertMessage 
                    v-if="$page.props.flash?.error"
                    :message="$page.props.flash.error"
                    type="error"
                    @dismiss="() => $page.props.flash.error = null"
                />
                <AlertMessage 
                    v-if="$page.props.flash?.success"
                    :message="$page.props.flash.success"
                    type="success"
                    @dismiss="() => $page.props.flash.success = null"
                />

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
                            <div class="flex justify-between items-center">
                                <InputLabel value="Cliente *" />
                                <Link :href="route('clientes.create')" class="text-sm text-indigo-600 hover:text-indigo-900">
                                    + Nuevo cliente
                                </Link>
                            </div>
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

                            <!-- Campo SKU prioritario para escáner -->
                            <div class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <InputLabel value="Código SKU (presiona Enter o escanea código de barras)" class="font-semibold" />
                                <TextInput
                                    ref="skuInputField"
                                    v-model="skuInput"
                                    type="text"
                                    placeholder="Ej: ABC123 o ABC123*5 para 5 unidades"
                                    class="mt-2 block w-full font-mono text-lg"
                                    @keydown.enter.prevent="agregarPorSKU"
                                />
                                <p class="mt-2 text-xs text-blue-600">
                                    💡 <strong>Tip:</strong> Escanea el código de barras o escribe CODIGO*CANTIDAD (ej: ABC123*5)
                                </p>
                            </div>

                            <!-- Separador -->
                            <div class="relative my-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white text-gray-500">O busca por nombre/código</span>
                                </div>
                            </div>

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
                                                <div class="flex justify-between items-center">
                                                    <span>{{ producto.nombre }} ({{ producto.codigo }})</span>
                                                    <span class="text-sm" :class="producto.stockActual > 10 ? 'text-green-600' : producto.stockActual > 0 ? 'text-yellow-600' : 'text-red-600'">
                                                        Stock: {{ producto.stockActual }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <InputLabel value="Cantidad" />
                                    <TextInput
                                        v-model="quantityToAdd"
                                        type="number"
                                        min="1"
                                        class="mt-1 block w-full"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Precio" />
                                    <TextInput
                                        v-model="priceToAdd"
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

                        <!-- Forma de Venta -->
                        <div class="border-t pt-6">
                            <InputLabel value="Forma de Venta *" />
                            <SelectInput
                                v-model="ventaStore.metodoPago"
                                :options="[
                                    { value: 'efectivo', label: 'Al Contado - Efectivo' },
                                    { value: 'tarjeta', label: 'Al Contado - Tarjeta' },
                                    { value: 'cuenta_corriente', label: 'A Crédito (Cuenta Corriente)' }
                                ]"
                                class="mt-1 block w-full"
                            />
                            <p class="mt-1 text-sm text-gray-500">
                                <span v-if="ventaStore.metodoPago === 'cuenta_corriente'">El monto se registrará como deuda en la cuenta corriente del cliente.</span>
                                <span v-else>El pago se considera efectuado al momento de la venta.</span>
                            </p>
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
                                    <span class="font-medium">${{ totales.subtotalBruto }}</span>
                                </div>
                                <div class="flex justify-between text-green-600">
                                    <span>Descuentos:</span>
                                    <span class="font-medium">-${{ totales.totalDescuentos }}</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold border-t pt-2">
                                    <span>Total:</span>
                                    <span>${{ totales.totalNeto }}</span>
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