<template>
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Auditoría del Sistema
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filtros -->
                <div class="bg-white shadow rounded-lg mb-6 p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700">Filtros</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="filtro-accion" class="block text-sm font-medium text-gray-700 mb-1">Acción</label>
                            <select 
                                id="filtro-accion"
                                name="accion"
                                v-model="filtros.accion" 
                                @change="aplicarFiltros" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Todas</option>
                                <option value="registrar_venta">Registrar Venta</option>
                                <option value="anular_venta">Anular Venta</option>
                                <option value="bloquear_cc">Bloquear CC</option>
                                <option value="desbloquear_cc">Desbloquear CC</option>
                                <option value="pendiente_aprobacion_cc">Poner en Revisión CC</option>
                                <option value="modificar_parametro_global">Modificar Configuración</option>
                            </select>
                        </div>
                        <div>
                            <label for="filtro-tabla" class="block text-sm font-medium text-gray-700 mb-1">Tabla</label>
                            <select 
                                id="filtro-tabla"
                                name="tabla"
                                v-model="filtros.tabla" 
                                @change="aplicarFiltros" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Todas</option>
                                <option value="venta">Ventas</option>
                                <option value="cuenta_corriente">Cuentas Corrientes</option>
                                <option value="configuracion">Configuración</option>
                                <option value="producto">Productos</option>
                                <option value="cliente">Clientes</option>
                            </select>
                        </div>
                        <div>
                            <label for="filtro-usuario" class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                            <input 
                                id="filtro-usuario"
                                name="usuario"
                                v-model="filtros.usuario" 
                                @input="aplicarFiltros"
                                type="text" 
                                placeholder="Nombre de usuario..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="flex items-end">
                            <button 
                                @click="limpiarFiltros"
                                type="button"
                                class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Auditoría -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha/Hora
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Usuario
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acción
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tabla
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Registro ID
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Detalle
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="registro in auditoriasFiltradas.data" :key="registro.auditoriaID" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatearFecha(registro.fechaHora) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span v-if="registro.usuario" class="text-gray-900">
                                            {{ registro.usuario.name }}
                                        </span>
                                        <span v-else class="text-gray-500 italic">Sistema automático</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getBadgeClass(registro.accion)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ formatearAccion(registro.accion) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ registro.tablaAfectada }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        #{{ registro.registroID }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                        {{ registro.descripcion || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button 
                                            @click="verDetalle(registro)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            Ver detalle
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="auditoriasFiltradas.data.length === 0">
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                        No hay registros de auditoría
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="auditoriasFiltradas.total > auditoriasFiltradas.per_page" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <a 
                                v-if="auditoriasFiltradas.prev_page_url" 
                                :href="auditoriasFiltradas.prev_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Anterior
                            </a>
                            <a 
                                v-if="auditoriasFiltradas.next_page_url"
                                :href="auditoriasFiltradas.next_page_url"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Siguiente
                            </a>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Mostrando 
                                    <span class="font-medium">{{ auditoriasFiltradas.from }}</span>
                                    a 
                                    <span class="font-medium">{{ auditoriasFiltradas.to }}</span>
                                    de 
                                    <span class="font-medium">{{ auditoriasFiltradas.total }}</span>
                                    registros
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <a 
                                        v-if="auditoriasFiltradas.prev_page_url"
                                        :href="auditoriasFiltradas.prev_page_url"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                    >
                                        <span class="sr-only">Anterior</span>
                                        ‹
                                    </a>
                                    <a 
                                        v-if="auditoriasFiltradas.next_page_url"
                                        :href="auditoriasFiltradas.next_page_url"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                    >
                                        <span class="sr-only">Siguiente</span>
                                        ›
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Detalle -->
        <div v-if="detalleVisible" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="detalleVisible = false"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Detalle de Auditoría #{{ registroSeleccionado?.auditoriaID }}
                                </h3>
                                <div class="mt-4 space-y-3">
                                    <div>
                                        <span class="font-semibold">Fecha:</span> 
                                        {{ formatearFecha(registroSeleccionado?.fechaHora) }}
                                    </div>
                                    <div>
                                        <span class="font-semibold">Usuario:</span> 
                                        {{ registroSeleccionado?.usuario?.name || 'Sistema automático' }}
                                    </div>
                                    <div>
                                        <span class="font-semibold">Acción:</span> 
                                        {{ formatearAccion(registroSeleccionado?.accion) }}
                                    </div>
                                    <div>
                                        <span class="font-semibold">Tabla:</span> 
                                        {{ registroSeleccionado?.tablaAfectada }}
                                    </div>
                                    <div>
                                        <span class="font-semibold">Registro ID:</span> 
                                        #{{ registroSeleccionado?.registroID }}
                                    </div>
                                    <div v-if="registroSeleccionado?.descripcion">
                                        <span class="font-semibold">Descripción:</span> 
                                        {{ registroSeleccionado.descripcion }}
                                    </div>
                                    <div v-if="registroSeleccionado?.motivo">
                                        <span class="font-semibold">Motivo:</span> 
                                        {{ registroSeleccionado.motivo }}
                                    </div>
                                    
                                    <!-- Datos Anteriores -->
                                    <div v-if="registroSeleccionado?.datosAnteriores" class="mt-4">
                                        <span class="font-semibold block mb-2">Datos Anteriores:</span>
                                        <pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto">{{ JSON.stringify(registroSeleccionado.datosAnteriores, null, 2) }}</pre>
                                    </div>
                                    
                                    <!-- Datos Nuevos -->
                                    <div v-if="registroSeleccionado?.datosNuevos" class="mt-4">
                                        <span class="font-semibold block mb-2">Datos Nuevos:</span>
                                        <pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto">{{ JSON.stringify(registroSeleccionado.datosNuevos, null, 2) }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            @click="detalleVisible = false"
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    auditorias: Object,
});

const filtros = ref({
    accion: new URLSearchParams(window.location.search).get('accion') || '',
    tabla: new URLSearchParams(window.location.search).get('tabla') || '',
    usuario: new URLSearchParams(window.location.search).get('usuario') || '',
});

const detalleVisible = ref(false);
const registroSeleccionado = ref(null);

const auditoriasFiltradas = computed(() => props.auditorias);

const aplicarFiltros = () => {
    router.get(route('auditorias.index'), filtros.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const limpiarFiltros = () => {
    filtros.value = { accion: '', tabla: '', usuario: '' };
    router.get(route('auditorias.index'));
};

const verDetalle = (registro) => {
    registroSeleccionado.value = registro;
    detalleVisible.value = true;
};

const formatearFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatearAccion = (accion) => {
    const nombres = {
        'registrar_venta': 'Registrar Venta',
        'anular_venta': 'Anular Venta',
        'bloquear_cc': 'Bloquear CC',
        'desbloquear_cc': 'Desbloquear CC',
        'pendiente_aprobacion_cc': 'Poner en Revisión CC',
        'modificar_parametro_global': 'Modificar Configuración',
    };
    return nombres[accion] || accion;
};

const getBadgeClass = (accion) => {
    const clases = {
        'registrar_venta': 'bg-green-100 text-green-800',
        'anular_venta': 'bg-red-100 text-red-800',
        'bloquear_cc': 'bg-red-100 text-red-800',
        'desbloquear_cc': 'bg-green-100 text-green-800',
        'pendiente_aprobacion_cc': 'bg-yellow-100 text-yellow-800',
        'modificar_parametro_global': 'bg-blue-100 text-blue-800',
    };
    return clases[accion] || 'bg-gray-100 text-gray-800';
};
</script>