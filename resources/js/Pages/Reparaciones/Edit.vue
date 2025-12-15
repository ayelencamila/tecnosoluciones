<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    reparacion: Object,
    estados: Array,
    productos: Array, // <--- OJO: Este array YA viene filtrado (solo repuestos) desde el controller
});

const form = useForm({
    estado_reparacion_id: props.reparacion.estado_reparacion_id,
    diagnostico_tecnico: props.reparacion.diagnostico_tecnico || '',
    observaciones: props.reparacion.observaciones || '',
    costo_mano_obra: props.reparacion.costo_mano_obra || 0,
    total_final: props.reparacion.total_final || 0,
    repuestos: [], 
});

// --- Lógica para agregar Repuestos ---
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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Actualizar Reparación #{{ reparacion.codigo_reparacion }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    
                    <form @submit.prevent="submit" class="p-6 sm:p-8 grid grid-cols-1 gap-8">
                        
                        <div class="border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Estado y Técnico</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="estado" value="Estado Actual *" />
                                    <select 
                                        id="estado" 
                                        name="estado_reparacion_id"
                                        v-model="form.estado_reparacion_id" 
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option v-for="estado in estados" :key="estado.estadoReparacionID" :value="estado.estadoReparacionID">
                                            {{ estado.nombreEstado }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.estado_reparacion_id" class="mt-2" />
                                    <p class="text-xs text-gray-500 mt-2">Si cambias a "Listo" o "Entregado", completa el diagnóstico.</p>
                                </div>
                            </div>
                        </div>

                        <div class="border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Informe Técnico</h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <InputLabel for="diagnostico" value="Diagnóstico Técnico" />
                                    <textarea 
                                        id="diagnostico" 
                                        name="diagnostico_tecnico"
                                        v-model="form.diagnostico_tecnico" 
                                        rows="4" 
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                        placeholder="Detalle del trabajo...">
                                    </textarea>
                                    <InputError :message="form.errors.diagnostico_tecnico" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="obs" value="Observaciones Internas" />
                                    <textarea 
                                        id="obs" 
                                        name="observaciones"
                                        v-model="form.observaciones" 
                                        rows="2" 
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    </textarea>
                                </div>
                            </div>
                        </div>

                        <div class="border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Costos de la Reparación</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="costo_mano_obra" value="Costo Mano de Obra" />
                                    <TextInput 
                                        id="costo_mano_obra" 
                                        name="costo_mano_obra"
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        v-model="form.costo_mano_obra" 
                                        class="mt-1 block w-full"
                                        placeholder="0.00"
                                    />
                                    <InputError :message="form.errors.costo_mano_obra" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="total_final" value="Total Final" />
                                    <TextInput 
                                        id="total_final" 
                                        name="total_final"
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        v-model="form.total_final" 
                                        class="mt-1 block w-full"
                                        placeholder="0.00"
                                    />
                                    <InputError :message="form.errors.total_final" class="mt-2" />
                                    <p class="text-xs text-gray-500 mt-2">Incluye mano de obra + repuestos</p>
                                </div>
                            </div>
                        </div>

                        <div class="border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Cargar Repuestos Utilizados</h3>
                            <div class="flex flex-col md:flex-row gap-4 items-end bg-gray-50 p-4 rounded-lg mb-4">
                                <div class="flex-1 w-full">
                                    <InputLabel for="repuesto_select" value="Repuesto (Filtrado por categoría 'Repuestos')" />
                                    <select 
                                        id="repuesto_select" 
                                        name="repuesto_select"
                                        v-model="repuestoSeleccionado" 
                                        class="w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="" disabled>Seleccionar repuesto...</option>
                                        <option v-for="prod in productos" :key="prod.id" :value="prod.id">
                                            {{ prod.nombre }} (Stock: {{ prod.stock_total ?? 'N/A' }})
                                        </option>
                                    </select>
                                </div>
                                <div class="w-24">
                                    <InputLabel for="cantidad_repuesto" value="Cant." />
                                    <TextInput 
                                        id="cantidad_repuesto"
                                        name="cantidad_repuesto"
                                        type="number" 
                                        v-model="cantidadRepuesto" 
                                        min="1" 
                                        class="w-full" 
                                    />
                                </div>
                                <SecondaryButton @click="agregarRepuesto" type="button">+ Agregar</SecondaryButton>
                            </div>

                            <div v-if="form.repuestos.length > 0" class="bg-white border rounded-md overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Repuesto</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th><th class="px-4 py-2"></th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="(item, index) in form.repuestos" :key="index">
                                            <td class="px-4 py-2">{{ item.nombre }}</td><td class="px-4 py-2 text-right">{{ item.cantidad }}</td><td class="px-4 py-2 text-right"><button type="button" @click="quitarRepuesto(index)" class="text-red-600 hover:text-red-800 font-bold">X</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div v-if="reparacion.repuestos.length > 0" class="mt-4 opacity-75">
                                <p class="text-sm font-bold text-gray-500 mb-2">Repuestos ya cargados:</p>
                                <ul class="list-disc list-inside text-sm text-gray-600">
                                    <li v-for="old in reparacion.repuestos" :key="old.id">{{ old.producto?.nombre }} (x{{ old.cantidad }})</li>
                                </ul>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('reparaciones.show', reparacion.reparacionID)"><SecondaryButton>Cancelar</SecondaryButton></Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">Guardar Cambios</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>