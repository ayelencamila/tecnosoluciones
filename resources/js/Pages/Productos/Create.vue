<script setup>
import { ref, computed } from 'vue'; 
import { Link, useForm, Head } from '@inertiajs/vue3'; 
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ConfigurableSelect from '@/Components/ConfigurableSelect.vue';
import axios from 'axios'; 

const props = defineProps({
  categorias: Array,
  estados: Array,
  tiposCliente: Array,
  proveedores: Array, 
  marcas: Array, 
  unidades: Array, 
  errors: Object, 
});

// Estados reactivos para listas configurables
const marcasList = ref([...props.marcas]);
const unidadesList = ref([...props.unidades]);
const categoriasList = ref([...props.categorias]);
const estadosList = ref([...props.estados]);

const form = useForm({
  codigo: '',
  nombre: '',
  descripcion: '',
  marca_id: '', 
  unidad_medida_id: '', 
  categoriaProductoID: '',
  estadoProductoID: props.estados.find(e => e.nombre === 'Activo')?.id || '',
  proveedor_habitual_id: '',
  
  // STOCK
  stock_minimo: 0,
  cantidad_inicial: 0, // <--- NUEVO CAMPO
  
  precios: {}, 
});

// Transformar datos para ConfigurableSelect
const marcasOptions = computed(() => {
    return marcasList.value.map(m => ({
        value: m.id,
        label: m.nombre
    }));
});

const unidadesOptions = computed(() => {
    return unidadesList.value.map(u => ({
        value: u.id,
        label: `${u.nombre} (${u.abreviatura})`
    }));
});

const categoriasOptions = computed(() => {
    return categoriasList.value.map(c => ({
        value: c.id,
        label: c.nombre
    }));
});

const estadosOptions = computed(() => {
    return estadosList.value.map(e => ({
        value: e.id,
        label: e.nombre
    }));
});

const precioError = computed(() => {
    if (props.errors.precios) return props.errors.precios;
    if (props.errors['precios.*.precio']) return props.errors['precios.*.precio'];
    return null;
});

// Funciones refresh para ConfigurableSelect
const refreshMarcas = async () => {
    try {
        const response = await axios.get('/api/marcas');
        marcasList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar marcas:', error);
    }
};

const refreshUnidades = async () => {
    try {
        const response = await axios.get('/api/unidades-medida');
        unidadesList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar unidades:', error);
    }
};

const refreshCategorias = async () => {
    try {
        const response = await axios.get('/api/categorias-producto');
        categoriasList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar categorías:', error);
    }
};

const refreshEstados = async () => {
    try {
        const response = await axios.get('/api/estados-producto');
        estadosList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar estados:', error);
    }
};

const submitForm = () => {
  const preciosArray = Object.entries(form.precios)
    .filter(([_, precio]) => precio && precio > 0)
    .map(([tipoClienteID, precio]) => ({
      tipoClienteID: parseInt(tipoClienteID),
      precio: parseFloat(precio)
    }));
  
  form.transform((data) => ({
    ...data,
    precios: preciosArray
  }))
  .post(route('productos.store'));
};
</script>

<template>
    <Head title="Registrar Producto" /> 
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar Nuevo Producto</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-6">
                    <Link :href="route('productos.index')" class="flex items-center text-gray-600 hover:text-gray-900 transition">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Volver al Catálogo
                    </Link>
                </div>

                <form @submit.prevent="submitForm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <div class="md:col-span-2 space-y-6">
                            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                    <h2 class="text-lg font-medium text-gray-900">Información Básica</h2>
                                </div>
                                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    
                                    <div>
                                        <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">Código / SKU <span class="text-red-500">*</span></label>
                                        <input id="codigo" name="codigo" v-model="form.codigo" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :class="{ 'border-red-500': form.errors.codigo }">
                                        <InputError :message="form.errors.codigo" class="mt-1" />
                                    </div>

                                    <div>
                                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto <span class="text-red-500">*</span></label>
                                        <input id="nombre" name="nombre" v-model="form.nombre" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :class="{ 'border-red-500': form.errors.nombre }">
                                        <InputError :message="form.errors.nombre" class="mt-1" />
                                    </div>

                                    <div>
                                        <ConfigurableSelect
                                            id="marca_id"
                                            v-model="form.marca_id"
                                            label="Marca"
                                            :options="marcasOptions"
                                            placeholder="Seleccione marca..."
                                            :error="form.errors.marca_id"
                                            api-endpoint="/api/marcas"
                                            name-field="nombre"
                                            @refresh="refreshMarcas"
                                        />
                                    </div>

                                    <div>
                                        <ConfigurableSelect
                                            id="unidad_medida_id"
                                            v-model="form.unidad_medida_id"
                                            label="Unidad de Medida"
                                            :options="unidadesOptions"
                                            placeholder="Seleccione unidad..."
                                            :error="form.errors.unidad_medida_id"
                                            api-endpoint="/api/unidades-medida"
                                            name-field="nombre"
                                            @refresh="refreshUnidades"
                                        />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                        <textarea id="descripcion" name="descripcion" v-model="form.descripcion" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                        <InputError :message="form.errors.descripcion" class="mt-1" />
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                                    <h2 class="text-lg font-medium text-gray-900">Lista de Precios</h2>
                                    <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded">Obligatorio</span>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div v-for="tipoCliente in tiposCliente" :key="tipoCliente.id" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                            <label :for="`precio_${tipoCliente.id}`" class="block text-sm font-bold text-gray-700 mb-2">{{ tipoCliente.nombre }}</label>
                                            <div class="relative rounded-md shadow-sm">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <span class="text-gray-500 sm:text-sm">$</span>
                                                </div>
                                                <input :id="`precio_${tipoCliente.id}`" :name="`precio_${tipoCliente.id}`" v-model.number="form.precios[tipoCliente.id]" type="number" step="0.01" min="0" class="block w-full rounded-md border-gray-300 pl-7 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                    <InputError :message="precioError" class="mt-3 text-center font-bold text-red-600" />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                    <h2 class="text-lg font-medium text-gray-900">Clasificación</h2>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div>
                                        <ConfigurableSelect
                                            id="categoriaProductoID"
                                            v-model="form.categoriaProductoID"
                                            label="Categoría"
                                            :options="categoriasOptions"
                                            placeholder="Seleccione categoría..."
                                            :error="form.errors.categoriaProductoID"
                                            api-endpoint="/api/categorias-producto"
                                            name-field="nombre"
                                            @refresh="refreshCategorias"
                                        />
                                    </div>

                                    <div>
                                        <ConfigurableSelect
                                            id="estadoProductoID"
                                            v-model="form.estadoProductoID"
                                            label="Estado"
                                            :options="estadosOptions"
                                            placeholder="Seleccione estado..."
                                            :error="form.errors.estadoProductoID"
                                            api-endpoint="/api/estados-producto"
                                            name-field="nombre"
                                            @refresh="refreshEstados"
                                        />
                                    </div>

                                    <div>
                                        <label for="proveedor_habitual_id" class="block text-sm font-medium text-gray-700 mb-1">Proveedor Habitual</label>
                                        <select id="proveedor_habitual_id" name="proveedor_habitual_id" v-model="form.proveedor_habitual_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">-- Sin asignar --</option>
                                            <option v-for="prov in proveedores" :key="prov.id" :value="prov.id">{{ prov.razon_social }}</option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">Proveedor por defecto para compras.</p>
                                        <InputError :message="form.errors.proveedor_habitual_id" class="mt-1" />
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 pt-4 border-t">
                                        <div>
                                            <label for="cantidad_inicial" class="block text-sm font-medium text-gray-700 mb-1">Stock Inicial</label>
                                            <input id="cantidad_inicial" name="cantidad_inicial" v-model="form.cantidad_inicial" type="number" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <p class="text-xs text-gray-500 mt-1">Depósito Principal</p>
                                        </div>
                                        <div>
                                            <label for="stock_minimo" class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo</label>
                                            <input id="stock_minimo" name="stock_minimo" v-model="form.stock_minimo" type="number" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3">
                                <PrimaryButton 
                                    class="w-full justify-center py-3" 
                                    :class="{ 'opacity-25': form.processing }" 
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'Guardando...' : 'Guardar Producto' }}
                                </PrimaryButton>

                                <Link :href="route('productos.index')" class="w-full text-center py-3 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition shadow-sm">
                                    Cancelar
                                </Link>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>