<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SelectInput from '@/Components/SelectInput.vue';
import AlertMessage from '@/Components/AlertMessage.vue';

const props = defineProps({
    reparacion: Object,
    mediosPago: Array,
});

const page = usePage();

const showDeleteModal = ref(false);
const showCobrarModal = ref(false);
const deleteForm = useForm({ motivo: '' });

const cobrarForm = useForm({
    medio_pago_id: '',
});

// --- Calcular total a cobrar ---
// Prioridad: total_final (si fue cargado manualmente) > repuestos + mano de obra
const totalACobrar = computed(() => {
    const totalFinal = Number(props.reparacion.total_final || 0);
    if (totalFinal > 0) {
        return totalFinal;
    }
    const repuestos = props.reparacion.repuestos || [];
    const totalRepuestos = repuestos.reduce((sum, item) => sum + Number(item.subtotal || 0), 0);
    const manoObra = Number(props.reparacion.costo_mano_obra || 0);
    return totalRepuestos + manoObra;
});

const yaFueCobrada = computed(() => {
    return ['pagado', 'cuenta_corriente'].includes(props.reparacion.estado_pago);
});

// Rol del usuario (para gatear acciones sensibles y no mostrar botones que darían 403).
const rol = computed(() => page.props.auth?.user?.role);
const puedeCobrarRol = computed(() => ['administrador', 'vendedor'].includes(rol.value));
const puedeAnular = computed(() => rol.value === 'administrador');

const puedeCobrar = computed(() => {
    const estado = props.reparacion.estado?.nombreEstado;
    return puedeCobrarRol.value
        && !props.reparacion.anulada
        && !yaFueCobrada.value
        && totalACobrar.value > 0
        && ['Reparado', 'Entregado'].includes(estado);
});

const estadoPagoLabel = computed(() => {
    const map = {
        'pendiente': 'Pendiente de Cobro',
        'pagado': 'Cobrado',
        'cuenta_corriente': 'Cta. Corriente',
    };
    return map[props.reparacion.estado_pago] || 'Pendiente de Cobro';
});

const estadoPagoColor = computed(() => {
    const map = {
        'pendiente': 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'pagado': 'bg-green-100 text-green-800 border-green-200',
        'cuenta_corriente': 'bg-blue-100 text-blue-800 border-blue-200',
    };
    return map[props.reparacion.estado_pago] || 'bg-yellow-100 text-yellow-800 border-yellow-200';
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

// --- Cobrar ---
const confirmarCobro = () => {
    cobrarForm.post(route('reparaciones.cobrar', props.reparacion.reparacionID), {
        preserveScroll: true,
        onSuccess: () => {
            showCobrarModal.value = false;
            cobrarForm.reset();
        },
    });
};

// Filtrar medios de pago: Cuenta Corriente solo para clientes con CC (mayoristas)
const mediosPagoFiltrados = computed(() => {
    const tieneCC = !!props.reparacion.cliente?.cuenta_corriente || !!props.reparacion.cliente?.cuentaCorrienteID;
    if (tieneCC) return props.mediosPago || [];
    return (props.mediosPago || []).filter(mp => !mp.nombre.toLowerCase().includes('corriente'));
});

const abrirModalCobro = () => {
    cobrarForm.clearErrors();
    cobrarForm.reset();
    if (mediosPagoFiltrados.value.length > 0) {
        cobrarForm.medio_pago_id = mediosPagoFiltrados.value[0].medioPagoID;
    }
    showCobrarModal.value = true;
};

const anularReparacion = () => {
    deleteForm.delete(route('reparaciones.destroy', props.reparacion.reparacionID), {
        preserveScroll: true,
        onSuccess: () => showDeleteModal.value = false,
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const getEstadoColor = (nombre) => {
    const n = nombre?.toLowerCase() || '';
    if (n.includes('cancel') || n.includes('anul')) return 'text-red-800 bg-red-100 border border-red-200';
    if (n.includes('listo')) return 'text-green-800 bg-green-100 border border-green-200';
    if (n.includes('entregado')) return 'text-gray-800 bg-gray-100 border border-gray-200';
    if (n.includes('diagn')) return 'text-purple-800 bg-purple-100 border border-purple-200';
    return 'text-blue-800 bg-blue-100 border border-blue-200';
};

const imprimirComprobanteIngreso = () => {
    window.open(route('reparaciones.imprimir-ingreso', props.reparacion.reparacionID), '_blank');
};

const imprimirComprobanteEntrega = () => {
    window.open(route('reparaciones.imprimir-entrega', props.reparacion.reparacionID), '_blank');
};
</script>

<template>
    <Head :title="`Reparación ${reparacion.codigo_reparacion}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Detalle de Reparación <span class="font-mono text-indigo-600">{{ reparacion.codigo_reparacion }}</span>
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        <span class="font-medium">Ingreso:</span> {{ formatDate(reparacion.fecha_ingreso) }}
                        <span v-if="reparacion.tecnico" class="ml-4"><span class="font-medium">Técnico:</span> {{ reparacion.tecnico.name }}</span>
                    </p>
                </div>
                <span class="px-4 py-1 rounded-full text-sm font-bold uppercase tracking-wide border" :class="getEstadoColor(reparacion.estado?.nombreEstado)">
                    {{ reparacion.estado?.nombreEstado }}
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <AlertMessage 
                    v-if="cobrarForm.errors.cobro" 
                    type="error"
                    :message="cobrarForm.errors.cobro"
                    class="mb-0"
                />
                
                <!-- Barra de acciones reorganizada -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Navegación -->
                        <Link :href="route('reparaciones.index')">
                            <SecondaryButton class="w-full sm:w-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Volver al Listado
                            </SecondaryButton>
                        </Link>
                        
                        <!-- Acciones agrupadas -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <!-- Grupo: Comprobantes -->
                            <div class="flex items-center gap-2">
                                <span class="hidden sm:inline text-xs text-gray-400 uppercase tracking-wide">Comprobantes:</span>
                                <div class="flex gap-2">
                                    <button 
                                        @click="imprimirComprobanteIngreso" 
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-blue-300 bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors"
                                        title="Imprimir comprobante de ingreso"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        <span class="hidden sm:inline">Ingreso</span>
                                    </button>
                                    <button 
                                        v-if="['Reparado', 'Entregado'].includes(reparacion.estado?.nombreEstado)" 
                                        @click="imprimirComprobanteEntrega" 
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-green-300 bg-green-50 text-green-700 hover:bg-green-100 transition-colors"
                                        title="Imprimir comprobante de entrega"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="hidden sm:inline">Entrega</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Separador visual -->
                            <div class="hidden sm:block w-px bg-gray-200"></div>
                            
                            <!-- Grupo: Cobro -->
                            <div class="flex items-center gap-2">
                                <button 
                                    v-if="puedeCobrar"
                                    @click="abrirModalCobro" 
                                    class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-md border-2 border-emerald-500 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors shadow-sm"
                                    title="Registrar cobro de esta reparación"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Cobrar
                                </button>
                                <span v-if="yaFueCobrada" class="inline-flex items-center px-3 py-2 text-sm font-bold rounded-md border" :class="estadoPagoColor">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ estadoPagoLabel }}
                                </span>
                            </div>

                            <!-- Separador visual -->
                            <div class="hidden sm:block w-px bg-gray-200"></div>
                            
                            <!-- Grupo: Acciones principales -->
                            <div class="flex gap-2">
                                <DangerButton
                                    v-if="puedeAnular && !['Cancelado', 'Anulado', 'Entregado'].includes(reparacion.estado?.nombreEstado)"
                                    @click="showDeleteModal = true"
                                    class="!px-3"
                                    title="Anular reparación"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span class="hidden sm:inline">Anular</span>
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 space-y-6">
                        
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Información del Dispositivo</h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-500">Equipo</dt>
                                    <dd class="mt-1 text-gray-900 font-semibold">
                                        {{ reparacion.marca?.nombre }} {{ reparacion.modelo?.nombre }}
                                    </dd>
                                </div>
                                <div><dt class="font-medium text-gray-500">Serie / IMEI</dt><dd class="mt-1 text-gray-900 font-mono">{{ reparacion.numero_serie_imei || 'No registrado' }}</dd></div>
                                <div class="sm:col-span-2 bg-red-50 p-3 rounded border border-red-100"><dt class="font-bold text-red-700">Falla Declarada (Cliente)</dt><dd class="mt-1 text-gray-800">{{ reparacion.falla_declarada }}</dd></div>
                                <div class="sm:col-span-2"><dt class="font-medium text-gray-500">Accesorios</dt><dd class="mt-1 text-gray-900">{{ reparacion.accesorios_dejados || 'Ninguno' }}</dd></div>
                            </dl>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4 flex justify-between items-center">
                                Informe Técnico
                                <span class="text-xs font-normal bg-gray-100 px-2 py-1 rounded text-gray-600">Técnico: {{ reparacion.tecnico?.name || 'Sin Asignar' }}</span>
                            </h3>
                            <div v-if="!reparacion.diagnostico_tecnico" class="text-gray-400 italic text-center py-4">Pendiente de diagnóstico.</div>
                            <div v-else class="space-y-4 text-sm">
                                <div><h4 class="font-bold text-gray-700">Diagnóstico / Trabajo Realizado:</h4><p class="text-gray-800 mt-1 whitespace-pre-line">{{ reparacion.diagnostico_tecnico }}</p></div>
                                <div v-if="reparacion.observaciones" class="pt-2 border-t border-gray-100"><h4 class="font-bold text-gray-600">Notas Internas:</h4><p class="text-gray-600 mt-1">{{ reparacion.observaciones }}</p></div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Repuestos y Servicios Utilizados</h3>
                            <div v-if="reparacion.repuestos && reparacion.repuestos.length > 0">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left font-medium text-gray-500">Ítem</th><th class="px-3 py-2 text-right font-medium text-gray-500">Cant.</th><th class="px-3 py-2 text-right font-medium text-gray-500">Unitario</th><th class="px-3 py-2 text-right font-medium text-gray-500">Subtotal</th></tr></thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <tr v-for="item in reparacion.repuestos" :key="item.id">
                                                <td class="px-3 py-2 text-gray-900">{{ item.producto?.nombre }}</td><td class="px-3 py-2 text-gray-900 text-right">{{ item.cantidad }}</td><td class="px-3 py-2 text-gray-500 text-right">${{ Number(item.precio_unitario).toFixed(2) }}</td><td class="px-3 py-2 font-bold text-gray-900 text-right">${{ Number(item.subtotal).toFixed(2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Gran Total destacado según Kendall: alineación derecha, visualmente prominente -->
                                <div class="mt-4 rounded-lg p-4" :class="yaFueCobrada ? 'bg-green-50 border-2 border-green-200' : 'bg-indigo-50 border-2 border-indigo-200'">
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-700">Subtotal Repuestos:</span>
                                            <span class="font-semibold text-gray-900">${{ reparacion.repuestos.reduce((sum, item) => sum + Number(item.subtotal), 0).toFixed(2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-700">Mano de Obra:</span>
                                            <span class="font-semibold text-gray-900">${{ Number(reparacion.costo_mano_obra || 0).toFixed(2) }}</span>
                                        </div>
                                        <div class="flex justify-between pt-2 border-t-2" :class="yaFueCobrada ? 'border-green-300' : 'border-indigo-300'">
                                            <span class="text-lg font-bold" :class="yaFueCobrada ? 'text-green-900' : 'text-indigo-900'">{{ yaFueCobrada ? 'TOTAL COBRADO:' : 'TOTAL A COBRAR:' }}</span>
                                            <span class="text-xl font-bold" :class="yaFueCobrada ? 'text-green-900' : 'text-indigo-900'">${{ totalACobrar.toFixed(2) }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Info del cobro si ya fue cobrada -->
                                    <div v-if="yaFueCobrada" class="mt-3 pt-3 border-t border-green-200 text-xs text-green-700 space-y-1">
                                        <p><span class="font-semibold">Medio de pago:</span> {{ reparacion.medio_pago?.nombre || '-' }}</p>
                                        <p><span class="font-semibold">Cobrado por:</span> {{ reparacion.cobrador?.name || '-' }}</p>
                                        <p v-if="reparacion.fecha_cobro"><span class="font-semibold">Fecha:</span> {{ formatDate(reparacion.fecha_cobro) }}</p>
                                    </div>
                                    
                                    <!-- Botón cobrar inline -->
                                    <div v-if="puedeCobrar" class="mt-3 pt-3 border-t border-indigo-200">
                                        <button @click="abrirModalCobro" class="w-full inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-md transition-colors shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Registrar Cobro
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500 italic text-center py-2">No se han cargado repuestos.</div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Cliente</h3>
                            <div class="text-sm">
                                <p class="font-bold text-gray-900 text-lg">{{ reparacion.cliente.apellido }}, {{ reparacion.cliente.nombre }}</p>
                                <p class="text-gray-500 mt-1">DNI: {{ reparacion.cliente.DNI }}</p>
                                <div class="mt-2 flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500"><span class="font-semibold">Tel/WP:</span> {{ reparacion.cliente.whatsapp || reparacion.cliente.telefono || '-' }}</p>
                                    </div>
                                    <a 
                                        v-if="reparacion.cliente.whatsapp || reparacion.cliente.telefono"
                                        :href="`https://wa.me/${(reparacion.cliente.whatsapp || reparacion.cliente.telefono).replace(/[^0-9]/g, '')}`"
                                        target="_blank"
                                        class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold rounded-md transition">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        WhatsApp
                                    </a>
                                </div>
                                <p class="text-gray-500 mt-1"><span class="font-semibold">Email:</span> {{ reparacion.cliente.mail || '-' }}</p>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Fechas</h3>
                            <ul class="space-y-3 text-sm">
                                <li class="flex justify-between"><span class="text-gray-500">Ingreso:</span><span class="font-medium">{{ formatDate(reparacion.fecha_ingreso) }}</span></li>
                                <li class="flex justify-between"><span class="text-gray-500">Promesa:</span><span class="font-medium text-indigo-600">{{ formatDate(reparacion.fecha_promesa) }}</span></li>
                                <li v-if="reparacion.fecha_entrega_real" class="flex justify-between pt-2 border-t"><span class="text-gray-500">Entregado:</span><span class="font-bold text-green-600">{{ formatDate(reparacion.fecha_entrega_real) }}</span></li>
                            </ul>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Evidencia (Fotos)</h3>
                            <div v-if="reparacion.imagenes && reparacion.imagenes.length > 0" class="grid grid-cols-2 gap-2">
                                <div v-for="img in reparacion.imagenes" :key="img.id">
                                    <a :href="'/storage/' + img.ruta_archivo" target="_blank"><img :src="'/storage/' + img.ruta_archivo" class="h-24 w-full object-cover rounded border hover:opacity-75 transition cursor-zoom-in" /></a>
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-400 italic text-center">Sin imágenes.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showCobrarModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 px-4">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform transition-all">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Registrar Cobro de Reparación</h3>
                    <p class="text-sm text-gray-500 mb-4">{{ reparacion.codigo_reparacion }} &mdash; {{ reparacion.cliente?.apellido }}, {{ reparacion.cliente?.nombre }}</p>
                    
                    <div class="bg-gray-50 rounded-lg p-4 mb-4 border">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Repuestos:</span>
                            <span class="font-medium">${{ reparacion.repuestos?.reduce((s, i) => s + Number(i.subtotal), 0).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm mt-1">
                            <span class="text-gray-600">Mano de Obra:</span>
                            <span class="font-medium">${{ Number(reparacion.costo_mano_obra || 0).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold mt-2 pt-2 border-t">
                            <span>TOTAL:</span>
                            <span class="text-emerald-700">{{ formatCurrency(totalACobrar) }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Medio de Pago" class="mb-1" />
                        <select v-model="cobrarForm.medio_pago_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="" disabled>Seleccione...</option>
                            <option v-for="mp in mediosPagoFiltrados" :key="mp.medioPagoID" :value="mp.medioPagoID">
                                {{ mp.nombre }} {{ parseFloat(mp.recargo_porcentaje) > 0 ? `(+${parseFloat(mp.recargo_porcentaje)}%)` : '' }}
                            </option>
                        </select>
                        <InputError :message="cobrarForm.errors.medio_pago_id" class="mt-1" />
                        <InputError :message="cobrarForm.errors.cobro" class="mt-1" />
                    </div>

                    <div class="flex justify-end space-x-3">
                        <SecondaryButton @click="showCobrarModal = false"> Cancelar </SecondaryButton>
                        <button 
                            @click="confirmarCobro" 
                            :disabled="cobrarForm.processing || !cobrarForm.medio_pago_id"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-sm rounded-md transition-colors"
                        >
                            {{ cobrarForm.processing ? 'Procesando...' : 'Confirmar Cobro' }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 px-4">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform transition-all">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Anular Reparación</h3>
                    <p class="text-sm text-gray-500 mb-4">¿Está seguro? <br><span v-if="reparacion.repuestos && reparacion.repuestos.length > 0" class="font-bold text-red-600 flex items-center gap-1 mt-2 bg-red-50 p-2 rounded border border-red-100"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg> Se devolverá el stock de repuestos.</span></p>
                    <div class="mb-4">
                        <InputLabel value="Motivo (Requerido)" class="mb-1" />
                        <textarea v-model="deleteForm.motivo" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="Ej: Cliente cancela..."></textarea>
                        <InputError :message="deleteForm.errors.motivo" class="mt-1" />
                    </div>
                    <div class="flex justify-end space-x-3">
                        <SecondaryButton @click="showDeleteModal = false"> Cancelar </SecondaryButton>
                        <DangerButton @click="anularReparacion" :disabled="deleteForm.processing"> Confirmar Anulación </DangerButton>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>