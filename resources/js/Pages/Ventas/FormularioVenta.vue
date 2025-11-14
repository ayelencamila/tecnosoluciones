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
const addProductToCart = () => {
    if (selectedProductToAdd.value && quantityToAdd.value > 0 && priceToAdd.value > 0) {
        ventaStore.agregarItem(
            selectedProductToAdd.value,
            quantityToAdd.value,
            priceToAdd.value
        );
        clearProductSelection();
    } else {
        alert('Por favor, selecciona un producto, cantidad y precio válidos.');
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
const submit = () => {
    if (!ventaStore.clienteSeleccionado) {
        alert('Por favor, selecciona un cliente para la venta.'); 
        return;
    }
    if (ventaStore.items.length === 0) {
        alert('El carrito está vacío. Agrega productos para realizar la venta.');
        return;
    }
    form.post(route('ventas.store'), {
        preserveScroll: true,
        onSuccess: () => {
            ventaStore.limpiarVenta(); 
        },
        onError: (e) => {
            console.error('Errores de validación:', e);
        },
    });
};
const applyDiscountCode = ref('');
const buscarYAplicarDescuento = () => {
    const descuentoEncontrado = props.descuentos.find(d => d.codigo.toLowerCase() === applyDiscountCode.value.toLowerCase());
    if (descuentoEncontrado) {
        ventaStore.aplicarDescuentoGlobal(descuentoEncontrado);
        applyDiscountCode.value = ''; 
    } else {
        alert('Código de descuento no encontrado o no válido.');
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

    <!-- 
      AQUÍ ESTABA EL ERROR. 
      Decía <AuthenticatedLayout> pero tu import se llama 'AppLayout'.
      Lo corregí:
    -->
    <AppLayout> 
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar Nueva Venta</h2>
        </template>
        
        <!-- Tu HTML del formulario de ventas va aquí -->
        <!-- No lo incluyo aquí para no hacer ruido, pero es el resto de tu template -->
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- ... (tu código de formulario) ... -->
            </div>
        </div>

    </AppLayout> <!-- <-- Y aquí también -->
</template>