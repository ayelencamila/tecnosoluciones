<template>
    <AppLayout>
        <template #header>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Auditoría del Sistema
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Bitácora inmutable de operaciones: quién hizo qué, cuándo y desde dónde.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Filtros -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-700">Filtros</h3>
                        <button
                            v-if="hayFiltrosActivos"
                            @click="limpiarFiltros"
                            type="button"
                            class="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            Limpiar filtros
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label for="f-desde" class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                            <input id="f-desde" v-model="filtros.fecha_desde" @change="aplicarFiltros" type="date"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        </div>
                        <div>
                            <label for="f-hasta" class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                            <input id="f-hasta" v-model="filtros.fecha_hasta" @change="aplicarFiltros" type="date"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        </div>
                        <div>
                            <label for="f-accion" class="block text-sm font-medium text-gray-700 mb-1">Acción</label>
                            <select id="f-accion" v-model="filtros.accion" @change="aplicarFiltros"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Todas</option>
                                <option v-for="a in accionesDisponibles" :key="a" :value="a">{{ formatearAccion(a) }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="f-tabla" class="block text-sm font-medium text-gray-700 mb-1">Módulo / Tabla</label>
                            <select id="f-tabla" v-model="filtros.tabla" @change="aplicarFiltros"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Todas</option>
                                <option v-for="t in tablasDisponibles" :key="t" :value="t">{{ t }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="f-usuario" class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                            <input id="f-usuario" v-model="filtros.usuario" @input="aplicarFiltrosDebounced" type="text"
                                placeholder="Nombre..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        </div>
                    </div>
                </div>

                <!-- Tabla de Auditoría -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-6 py-3 border-b border-gray-100 text-sm text-gray-500">
                        {{ auditorias.total }} registro{{ auditorias.total === 1 ? '' : 's' }}
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha / Hora</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Módulo</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registro</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detalle</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="registro in auditorias.data" :key="registro.auditoriaID" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatearFecha(registro.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <template v-if="registro.usuario">
                                            <div class="text-gray-900">{{ registro.usuario.name }}</div>
                                            <div v-if="registro.usuario.rol" class="text-xs text-gray-400 capitalize">{{ registro.usuario.rol.nombre }}</div>
                                        </template>
                                        <span v-else class="text-gray-400 italic">Sistema</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getBadgeClass(registro.accion)" class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ formatearAccion(registro.accion) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ registro.tabla_afectada || '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ registro.registro_id ? '#' + registro.registro_id : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                        {{ registro.detalles || registro.motivo || '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="verDetalle(registro)" class="text-indigo-600 hover:text-indigo-900">
                                            Ver detalle
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="auditorias.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        No hay registros de auditoría que coincidan con los filtros.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="auditorias.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <p class="text-sm text-gray-700">
                            Mostrando <span class="font-medium">{{ auditorias.from }}</span>
                            a <span class="font-medium">{{ auditorias.to }}</span>
                            de <span class="font-medium">{{ auditorias.total }}</span>
                        </p>
                        <div class="flex gap-1">
                            <component :is="link.url ? 'button' : 'span'"
                                v-for="(link, i) in auditorias.links" :key="i"
                                @click="link.url && irA(link.url)"
                                :disabled="!link.url"
                                :class="[
                                    'px-3 py-1.5 text-sm rounded-md border',
                                    link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300',
                                    link.url ? 'hover:bg-gray-50' : 'text-gray-300 cursor-default',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Detalle -->
        <div v-if="detalleVisible" class="fixed z-10 inset-0 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="detalleVisible = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    <div class="bg-white px-6 pt-5 pb-4">
                        <div class="flex items-start justify-between">
                            <h3 id="modal-title" class="text-lg font-medium text-gray-900">
                                Registro de auditoría #{{ sel?.auditoriaID }}
                            </h3>
                            <span :class="getBadgeClass(sel?.accion)" class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full">
                                {{ formatearAccion(sel?.accion) }}
                            </span>
                        </div>

                        <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <dt class="text-gray-400">Fecha / Hora</dt>
                                <dd class="text-gray-900">{{ formatearFecha(sel?.created_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400">Usuario</dt>
                                <dd class="text-gray-900">
                                    {{ sel?.usuario?.name || 'Sistema automático' }}
                                    <span v-if="sel?.usuario?.rol" class="text-gray-400 capitalize">— {{ sel.usuario.rol.nombre }}</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-400">Módulo / Tabla</dt>
                                <dd class="text-gray-900">{{ sel?.tabla_afectada || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400">Registro afectado</dt>
                                <dd class="text-gray-900">{{ sel?.registro_id ? '#' + sel.registro_id : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400">Dirección IP</dt>
                                <dd class="text-gray-900 font-mono">{{ sel?.ip || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400">Navegador / Dispositivo</dt>
                                <dd class="text-gray-900 text-xs break-words">{{ sel?.user_agent || '—' }}</dd>
                            </div>
                            <div v-if="sel?.motivo" class="sm:col-span-2">
                                <dt class="text-gray-400">Motivo</dt>
                                <dd class="text-gray-900">{{ sel.motivo }}</dd>
                            </div>
                            <div v-if="sel?.detalles" class="sm:col-span-2">
                                <dt class="text-gray-400">Detalle</dt>
                                <dd class="text-gray-900">{{ sel.detalles }}</dd>
                            </div>
                        </dl>

                        <!-- Comparación Antes / Después -->
                        <div v-if="sel?.datos_anteriores || sel?.datos_nuevos" class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-if="sel?.datos_anteriores">
                                <span class="block mb-1 text-xs font-semibold text-gray-500 uppercase">Antes</span>
                                <pre class="bg-red-50 text-gray-700 p-3 rounded text-xs overflow-x-auto max-h-64">{{ formatearDatos(sel.datos_anteriores) }}</pre>
                            </div>
                            <div v-if="sel?.datos_nuevos">
                                <span class="block mb-1 text-xs font-semibold text-gray-500 uppercase">Después</span>
                                <pre class="bg-green-50 text-gray-700 p-3 rounded text-xs overflow-x-auto max-h-64">{{ formatearDatos(sel.datos_nuevos) }}</pre>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end">
                        <button @click="detalleVisible = false" type="button"
                            class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
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
    filtros: { type: Object, default: () => ({}) },
    accionesDisponibles: { type: Array, default: () => [] },
    tablasDisponibles: { type: Array, default: () => [] },
});

const filtros = ref({
    fecha_desde: props.filtros.fecha_desde || '',
    fecha_hasta: props.filtros.fecha_hasta || '',
    accion: props.filtros.accion || '',
    tabla: props.filtros.tabla || '',
    usuario: props.filtros.usuario || '',
});

const hayFiltrosActivos = computed(() => Object.values(filtros.value).some((v) => v !== '' && v !== null));

const detalleVisible = ref(false);
const sel = ref(null);

const aplicarFiltros = () => {
    const params = Object.fromEntries(Object.entries(filtros.value).filter(([, v]) => v !== '' && v !== null));
    router.get(route('auditorias.index'), params, { preserveState: true, preserveScroll: true, replace: true });
};

let debounceTimer = null;
const aplicarFiltrosDebounced = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(aplicarFiltros, 350);
};

const limpiarFiltros = () => {
    filtros.value = { fecha_desde: '', fecha_hasta: '', accion: '', tabla: '', usuario: '' };
    router.get(route('auditorias.index'), {}, { preserveState: true, preserveScroll: true });
};

const irA = (url) => router.get(url, {}, { preserveState: true, preserveScroll: true });

const verDetalle = (registro) => {
    sel.value = registro;
    detalleVisible.value = true;
};

const formatearFecha = (fecha) => {
    if (!fecha) return '—';
    return new Date(fecha).toLocaleString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

// Convierte 'CREAR_VENTA' / 'crear' / 'Registrar Oferta' en una etiqueta legible.
const formatearAccion = (accion) => {
    if (!accion) return '';
    return accion
        .replace(/_/g, ' ')
        .toLowerCase()
        .replace(/^\w/, (c) => c.toUpperCase());
};

// Categoriza la acción para dar color CON significado (no decorativo).
const getBadgeClass = (accion) => {
    const a = (accion || '').toUpperCase();
    if (/(CREAR|CREACION|ALTA|REGISTRAR|GENERAR|EMITIR|RECIBIR)/.test(a)) return 'bg-green-100 text-green-800';
    if (/(MODIFICAR|MODIFICACION|ACTUALIZAR|CAMBIAR|EDITAR|RESTAURAR|REEMITIR)/.test(a)) return 'bg-blue-100 text-blue-800';
    if (/(BAJA|ELIMINAR|ELIMINACION|ANULAR|ANULACION|CANCELAR|DESHABILITAR|DESACTIVAR|BLOQUEAR|DENEGADO)/.test(a)) return 'bg-red-100 text-red-800';
    if (/(LOGIN|LOGOUT|ACCESO)/.test(a)) return 'bg-indigo-100 text-indigo-800';
    if (/(CONSULTA|EXPORTACION|VER|DESCARGAR)/.test(a)) return 'bg-amber-100 text-amber-800';
    return 'bg-gray-100 text-gray-800';
};

// Enmascara campos sensibles antes de mostrarlos (defensa en profundidad en la salida).
const SENSIBLES = /(password|contrase|token|secret|clave|remember_token|api_key)/i;
const enmascarar = (obj) => {
    if (Array.isArray(obj)) return obj.map(enmascarar);
    if (obj && typeof obj === 'object') {
        return Object.fromEntries(Object.entries(obj).map(([k, v]) => [k, SENSIBLES.test(k) ? '••••••' : enmascarar(v)]));
    }
    return obj;
};

const formatearDatos = (datos) => JSON.stringify(enmascarar(datos), null, 2);
</script>
