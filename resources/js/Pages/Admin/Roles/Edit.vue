<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { computed } from 'vue';

const props = defineProps({
    rol: Object,
    permisosDisponibles: Object,
});

const form = useForm({
    nombre: props.rol.nombre,
    descripcion: props.rol.descripcion || '',
    permisos: props.rol.permisos || [],
});

const submit = () => {
    form.put(route('admin.roles.update', props.rol.rol_id));
};

// Gestión de permisos
const togglePermiso = (permiso) => {
    const index = form.permisos.indexOf(permiso);
    if (index > -1) {
        form.permisos.splice(index, 1);
    } else {
        form.permisos.push(permiso);
    }
};

const toggleModulo = (modulo) => {
    const permisosModulo = Object.keys(props.permisosDisponibles[modulo]);
    const todosSeleccionados = permisosModulo.every(p => form.permisos.includes(p));
    
    if (todosSeleccionados) {
        permisosModulo.forEach(p => {
            const index = form.permisos.indexOf(p);
            if (index > -1) form.permisos.splice(index, 1);
        });
    } else {
        permisosModulo.forEach(p => {
            if (!form.permisos.includes(p)) form.permisos.push(p);
        });
    }
};

const moduloCompleto = (modulo) => {
    const permisosModulo = Object.keys(props.permisosDisponibles[modulo]);
    return permisosModulo.every(p => form.permisos.includes(p));
};

const moduloParcial = (modulo) => {
    const permisosModulo = Object.keys(props.permisosDisponibles[modulo]);
    const seleccionados = permisosModulo.filter(p => form.permisos.includes(p));
    return seleccionados.length > 0 && seleccionados.length < permisosModulo.length;
};

const seleccionarTodos = () => {
    form.permisos = Object.values(props.permisosDisponibles)
        .flatMap(modulo => Object.keys(modulo));
};

const deseleccionarTodos = () => {
    form.permisos = [];
};

const totalPermisos = computed(() => {
    return Object.values(props.permisosDisponibles)
        .reduce((total, modulo) => total + Object.keys(modulo).length, 0);
});

const esRolAdmin = computed(() => props.rol.nombre === 'administrador');

const getModuloIcono = (modulo) => {
    const iconos = {
        clientes: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        productos: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        ventas: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
        reparaciones: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        stock: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        proveedores: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        compras: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
        pagos: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        reportes: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        configuracion: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
        usuarios: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        roles: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        auditoria: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
    };
    return iconos[modulo] || 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2';
};
</script>

<template>
    <Head :title="`Editar Rol: ${rol.nombre}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('admin.roles.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Editar Rol: <span class="capitalize">{{ rol.nombre }}</span>
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Advertencia para admin -->
                <div v-if="esRolAdmin" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Atención:</strong> Este es el rol de Administrador. El nombre no puede ser modificado 
                                y algunos cambios en los permisos pueden afectar la gestión del sistema.
                            </p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Datos básicos -->
                    <div class="bg-white shadow-md rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Información del Rol</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="nombre" value="Nombre del Rol *" />
                                <TextInput
                                    id="nombre"
                                    v-model="form.nombre"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :class="{ 'bg-gray-100': esRolAdmin }"
                                    :disabled="esRolAdmin"
                                    required
                                />
                                <InputError :message="form.errors.nombre" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="descripcion" value="Descripción" />
                                <TextInput
                                    id="descripcion"
                                    v-model="form.descripcion"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Breve descripción del rol"
                                />
                                <InputError :message="form.errors.descripcion" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-4 text-sm text-gray-500">
                            <strong>Usuarios con este rol:</strong> {{ rol.users_count || 0 }}
                        </div>
                    </div>

                    <!-- Permisos -->
                    <div class="bg-white shadow-md rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Permisos</h3>
                                <p class="text-sm text-gray-500">
                                    Seleccionados: {{ form.permisos.length }} de {{ totalPermisos }}
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <SecondaryButton type="button" @click="seleccionarTodos" class="text-xs">
                                    Seleccionar todos
                                </SecondaryButton>
                                <SecondaryButton type="button" @click="deseleccionarTodos" class="text-xs">
                                    Deseleccionar todos
                                </SecondaryButton>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="(permisos, modulo) in permisosDisponibles" :key="modulo" 
                                class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center mb-3">
                                    <input 
                                        type="checkbox" 
                                        :checked="moduloCompleto(modulo)"
                                        :indeterminate="moduloParcial(modulo)"
                                        @change="toggleModulo(modulo)"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    >
                                    <svg class="w-5 h-5 ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getModuloIcono(modulo)"/>
                                    </svg>
                                    <span class="ml-2 font-medium text-gray-700 capitalize">{{ modulo }}</span>
                                </div>
                                
                                <div class="space-y-2 ml-7">
                                    <label v-for="(descripcion, permiso) in permisos" :key="permiso" 
                                        class="flex items-center text-sm">
                                        <input 
                                            type="checkbox" 
                                            :checked="form.permisos.includes(permiso)"
                                            @change="togglePermiso(permiso)"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        >
                                        <span class="ml-2 text-gray-600">{{ descripcion }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <InputError :message="form.errors.permisos" class="mt-4" />
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end space-x-3">
                        <Link :href="route('admin.roles.index')">
                            <SecondaryButton type="button">Cancelar</SecondaryButton>
                        </Link>
                        <PrimaryButton type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
