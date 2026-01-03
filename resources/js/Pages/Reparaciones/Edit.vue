<script setup>
import { ref, watch, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AlertMessage from '@/Components/AlertMessage.vue';
import axios from 'axios';

const props = defineProps({
    reparacion: Object,
    estados: Array,
    productos: Array,
    marcas: Array, 
});

const form = useForm({
    // --- DATOS DE GESTIÓN (ESTADO Y TÉCNICO) ---
    estado_reparacion_id: props.reparacion.estado_reparacion_id,
    diagnostico_tecnico: props.reparacion.diagnostico_tecnico || '',
    observaciones: props.reparacion.observaciones || '',
    costo_mano_obra: props.reparacion.costo_mano_obra || 0,
    total_final: props.reparacion.total_final || 0,
    
    // --- DATOS DEL EQUIPO (EDITABLES) ---
    marca_id: props.reparacion.modelo?.marca_id || '', 
    modelo_id: props.reparacion.modelo_id || '',
    numero_serie_imei: props.reparacion.numero_serie_imei || '',
    clave_bloqueo: props.reparacion.clave_bloqueo || '',
    accesorios_dejados: props.reparacion.accesorios_dejados || '',
    falla_declarada: props.reparacion.falla_declarada || '',
    fecha_promesa: props.reparacion.fecha_promesa ? props.reparacion.fecha_promesa.substring(0, 16) : '', 

    // --- REPUESTOS ---
    repuestos: [], 
});

// --- LÓGICA MARCAS Y MODELOS (CASCADA) ---
const modelosDisponibles = ref([]);
const cargandoModelos = ref(false);

const cargarModelos = async (marcaId) => {
    if (!marcaId) return;
    cargandoModelos.value = true;
    try {
        const response = await axios.get(route('api.modelos.por-marca', marcaId));
        modelosDisponibles.value = response.data;
    } catch (error) {
        console.error("Error cargando modelos:", error);
    } finally {
        cargandoModelos.value = false;
    }
};

// Watch para cambio manual de marca
watch(() => form.marca_id, (newVal, oldVal) => {
    if (newVal !== oldVal && oldVal !== undefined) { 
        cargarModelos(newVal);
        form.modelo_id = ''; 
    }
});

// Precarga al iniciar
onMounted(() => {
    if (form.marca_id) {
        // Cargamos los modelos de la marca original sin borrar el modelo_id seleccionado
        cargarModelos(form.marca_id).then(() => {
            // Aseguramos que el modelo original siga seleccionado
            form.modelo_id = props.reparacion.modelo_id;
        });
    }
});

// --- LÓGICA REPUESTOS ---
const repuestoSeleccionado = ref('');
const cantidadRepuesto = ref(1);

const agregarRepuesto = () => {
    if (!repuestoSeleccionado.value || cantidadRepuesto.value < 1) return;
    const producto = props.productos.find(p => p.id === repuestoSeleccionado.value);
    
    if (producto) {
        form.repuestos.push({
            producto_id: producto.id,
            nombre: producto.nombre,
            cantidad: cantidadRepuesto.value
        });
        repuestoSeleccionado.value = '';
        cantidadRepuesto.value = 1;
    }
};

const quitarRepuesto = (index) => {
    form.repuestos.splice(index, 1);
};

const submit = () => {
    form.put(route('reparaciones.update', props.reparacion.reparacionID), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Actualizar Reparación" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Actualizar Reparación <span class="text-indigo-600 font-mono">#{{ reparacion.codigo_reparacion }}</span>
                </h2>
                <Link :href="route('reparaciones.show', reparacion.reparacionID)">
                    <SecondaryButton>Volver al Detalle</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <AlertMessage 
                    v-if="Object.keys(form.errors).length > 0" 
                    type="error"
                    :message="'Por favor revisa los errores.'"
                    class="mb-4"
                />

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 sm:p-8 grid grid-cols-1 gap-8">
                        
                        <div class="border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                Editar Datos del Equipo (Corrección)
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                                
                                <div>
                                    <InputLabel for="marca_id" value="Marca" />
                                    <select id="marca_id" v-model="form.marca_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="" disabled>Selecciona marca</option>
                                        <option v-for="marca in marcas" :key="marca.id" :value="marca.id">{{ marca.nombre }}</option>
                                    </select>
                                    <InputError :message="form.errors.marca_id" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="modelo_id" value="Modelo" />
                                    <select id="modelo_id" v-model="form.modelo_id" :disabled="!form.marca_id || cargandoModelos" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm disabled:bg-gray-100">
                                        <option value="" disabled>{{ cargandoModelos ? 'Cargando...' : 'Selecciona modelo' }}</option>
                                        <option v-for="modelo in modelosDisponibles" :key="modelo.id" :value="modelo.id">{{ modelo.nombre }}</option>
                                    </select>
                                    <InputError :message="form.errors.modelo_id" class="mt-2" />
                                </div>

                                <div><InputLabel for="imei" value="Serie / IMEI" /><TextInput id="imei" v-model="form.numero_serie_imei" class="mt-1 block w-full" /></div>
                                <div><InputLabel for="clave" value="Clave / Patrón" /><TextInput id="clave" v-model="form.clave_bloqueo" class="mt-1 block w-full" /></div>
                                <div class="md:col-span-2"><InputLabel for="accesorios" value="Accesorios" /><TextInput id="accesorios" v-model="form.accesorios_dejados" class="mt-1 block w-full" /></div>
                                <div class="md:col-span-3">
                                    <InputLabel for="falla" value="Falla Declarada (Cliente)" />
                                    <textarea id="falla" v-model="form.falla_declarada" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Gestión Técnica</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                <div>
                                    <InputLabel for="estado" value="Estado Actual *" />
                                    <select id="estado" v-model="form.estado_reparacion_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option v-for="estado in estados" :key="estado.estadoReparacionID" :value="estado.estadoReparacionID">
                                            {{ estado.nombreEstado }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="fecha_promesa" value="Actualizar Fecha Promesa" />
                                    <TextInput id="fecha_promesa" type="datetime-local" v-model="form.fecha_promesa" class="mt-1 block w-full" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <InputLabel for="diagnostico" value="Informe Técnico / Trabajo Realizado" />
                                    <textarea id="diagnostico" v-model="form.diagnostico_tecnico" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Detalla el diagnóstico o la solución aplicada..."></textarea>
                                </div>
                                <div>
                                    <InputLabel for="obs" value="Observaciones Internas (Solo Taller)" />
                                    <textarea id="obs" v-model="form.observaciones" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-yellow-50"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Costos y Repuestos</h3>
                            
                            <div class="flex flex-col md:flex-row gap-4 items-end bg-gray-50 p-4 rounded-lg mb-4 border border-gray-200">
                                <div class="flex-1 w-full">
                                    <InputLabel value="Agregar Repuesto / Insumo" />
                                    <select v-model="repuestoSeleccionado" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                        <option value="" disabled>Seleccionar...</option>
                                        <option v-for="prod in productos" :key="prod.id" :value="prod.id">
                                            {{ prod.nombre }} (Stock: {{ prod.stock_total }})
                                        </option>
                                    </select>
                                </div>
                                <div class="w-24">
                                    <InputLabel value="Cant." />
                                    <TextInput type="number" v-model="cantidadRepuesto" min="1" class="w-full text-center" />
                                </div>
                                <SecondaryButton @click="agregarRepuesto" type="button">+ Agregar</SecondaryButton>
                            </div>

                            <div v-if="form.repuestos.length > 0" class="mb-4">
                                <p class="text-xs font-bold text-gray-500 mb-1 uppercase">Repuestos a Agregar:</p>
                                <ul class="border rounded-md divide-y bg-white">
                                    <li v-for="(item, index) in form.repuestos" :key="index" class="px-4 py-2 flex justify-between items-center text-sm">
                                        <span>{{ item.nombre }} (x{{ item.cantidad }})</span>
                                        <button type="button" @click="quitarRepuesto(index)" class="text-red-500 font-bold hover:underline">Eliminar</button>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="reparacion.repuestos && reparacion.repuestos.length > 0" class="mb-6 opacity-75">
                                <p class="text-xs font-bold text-gray-500 mb-1 uppercase">Repuestos Ya Cargados (Histórico):</p>
                                <ul class="list-disc list-inside text-sm text-gray-600 pl-2">
                                    <li v-for="old in reparacion.repuestos" :key="old.id">
                                        {{ old.producto?.nombre }} (x{{ old.cantidad }}) - ${{ Number(old.subtotal).toFixed(2) }}
                                    </li>
                                </ul>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                                <div>
                                    <InputLabel for="mano_obra" value="Costo Mano de Obra ($)" />
                                    <TextInput id="mano_obra" type="number" step="0.01" v-model="form.costo_mano_obra" class="mt-1 block w-full font-bold text-right" />
                                </div>
                                <div>
                                    <InputLabel for="total" value="Total Final a Cobrar ($)" />
                                    <TextInput id="total" type="number" step="0.01" v-model="form.total_final" class="mt-1 block w-full font-bold text-right text-indigo-700" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('reparaciones.show', reparacion.reparacionID)"><SecondaryButton>Cancelar</SecondaryButton></Link>
                            <PrimaryButton :disabled="form.processing">Guardar Cambios</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>