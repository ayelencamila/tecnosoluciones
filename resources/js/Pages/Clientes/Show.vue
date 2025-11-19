<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    cliente: Object,
    historialAuditoria: Array,
});

// Helper para formato de moneda
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};
</script>

<template>
    <Head title="Detalle de Cliente" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Ficha de Cliente: {{ cliente.nombre }} {{ cliente.apellido }}
                </h2>
                <div class="space-x-2">
                    <Link :href="route('clientes.index')">
                        <SecondaryButton>Volver</SecondaryButton>
                    </Link>
                    <Link :href="route('clientes.edit', cliente.clienteID)">
                        <PrimaryButton>Modificar</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Información Personal</h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">Datos de contacto y ubicación.</p>
                        </div>
                        <span 
                            class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full"
                            :class="cliente.estado_cliente?.nombreEstado === 'Activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                        >
                            {{ cliente.estado_cliente?.nombreEstado }}
                        </span>
                    </div>
                    <div class="border-t border-gray-200">
                        <dl>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">DNI</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ cliente.DNI }}</dd>
                            </div>
                            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ cliente.mail || '-' }}</dd>
                            </div>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Teléfono / WhatsApp</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ cliente.telefono || '-' }} / {{ cliente.whatsapp || '-' }}
                                </dd>
                            </div>
                            
                            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Dirección</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    <span v-if="cliente.direccion">
                                        {{ cliente.direccion.calle }} {{ cliente.direccion.altura }}
                                        <span v-if="cliente.direccion.pisoDepto">, {{ cliente.direccion.pisoDepto }}</span>
                                        <br>
                                        {{ cliente.direccion.localidad?.nombre }}, {{ cliente.direccion.localidad?.provincia?.nombre }}
                                        <span v-if="cliente.direccion.codigoPostal"> (CP: {{ cliente.direccion.codigoPostal }})</span>
                                    </span>
                                    <span v-else class="text-gray-400 italic">
                                        Sin dirección cargada
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div v-if="cliente.cuenta_corriente" class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-indigo-700">Cuenta Corriente (Mayorista)</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-6 py-4">
                        <div class="p-4 bg-indigo-50 rounded-lg text-center">
                            <p class="text-sm text-gray-500">Saldo Actual</p>
                            <p class="text-2xl font-bold" :class="cliente.saldo < 0 ? 'text-red-600' : 'text-green-600'">
                                {{ formatCurrency(cliente.saldo) }}
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg text-center">
                            <p class="text-sm text-gray-500">Límite de Crédito</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ formatCurrency(cliente.cuenta_corriente.limiteCredito) }}
                            </p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg text-center">
                            <p class="text-sm text-gray-500">Crédito Disponible</p>
                            <p class="text-2xl font-bold text-indigo-600">
                                {{ formatCurrency(cliente.credito_disponible) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="cliente.estado_cliente?.nombreEstado === 'Activo'" class="flex justify-end">
                    <Link :href="route('clientes.confirmDelete', cliente.clienteID)">
                        <DangerButton>
                            Dar de Baja Cliente
                        </DangerButton>
                    </Link>
                </div>

            </div>
        </div>
    </AppLayout>
</template>