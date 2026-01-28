<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref } from 'vue';

const props = defineProps({
    roles: Array,
});

// Modal de confirmación
const showModal = ref(false);
const modalAction = ref(null);
const selectedRol = ref(null);
const modalTitle = ref('');
const modalMessage = ref('');

// Modal de eliminar
const showDeleteModal = ref(false);
const deleteRol = ref(null);
const deleteForm = ref({
    accion_usuarios: 'reasignar',
    rol_destino_id: '',
});

const confirmarAccion = (rol, accion) => {
    selectedRol.value = rol;
    modalAction.value = accion;
    
    if (accion === 'toggleActivo') {
        modalTitle.value = rol.activo ? 'Desactivar Rol' : 'Activar Rol';
        modalMessage.value = rol.activo 
            ? `¿Está seguro que desea desactivar el rol "${rol.nombre}"?`
            : `¿Está seguro que desea activar el rol "${rol.nombre}"?`;
        showModal.value = true;
    }
};

const ejecutarAccion = () => {
    if (!selectedRol.value || modalAction.value !== 'toggleActivo') return;
    
    router.patch(route('admin.roles.toggle-activo', selectedRol.value.rol_id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            selectedRol.value = null;
            modalAction.value = null;
        },
    });
};

const abrirModalEliminar = (rol) => {
    deleteRol.value = rol;
    deleteForm.value = {
        accion_usuarios: 'reasignar',
        rol_destino_id: props.roles.find(r => r.nombre === 'vendedor')?.rol_id || '',
    };
    showDeleteModal.value = true;
};

const eliminarRol = () => {
    const data = deleteRol.value.users_count > 0 ? deleteForm.value : {};
    
    router.delete(route('admin.roles.destroy', deleteRol.value.rol_id), {
        data,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deleteRol.value = null;
        },
    });
};

const getRolColor = (nombre) => {
    const colores = {
        'administrador': 'bg-red-100 text-red-800 border-red-200',
        'vendedor': 'bg-blue-100 text-blue-800 border-blue-200',
        'tecnico': 'bg-green-100 text-green-800 border-green-200',
    };
    return colores[nombre] || 'bg-gray-100 text-gray-800 border-gray-200';
};
</script>

<template>
    <Head title="Gestión de Roles" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestión de Roles y Permisos
                </h2>
                <Link :href="route('admin.roles.create')">
                    <PrimaryButton>+ Crear Nuevo Rol</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Los roles determinan qué funcionalidades puede acceder cada usuario. 
                                El rol <strong>admin</strong> tiene acceso completo al sistema y no puede ser eliminado.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Grid de roles -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="rol in roles" :key="rol.rol_id" 
                        :class="['bg-white rounded-lg shadow-md overflow-hidden border-l-4', getRolColor(rol.nombre)]">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 capitalize">{{ rol.nombre }}</h3>
                                    <p class="text-sm text-gray-500 mt-1">{{ rol.descripcion || 'Sin descripción' }}</p>
                                </div>
                                <span v-if="!rol.activo" class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">
                                    Inactivo
                                </span>
                            </div>

                            <div class="mt-4 flex items-center text-sm text-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                {{ rol.users_count }} usuario(s) asignado(s)
                            </div>

                            <div class="mt-4 flex items-center text-sm text-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                {{ rol.permisos?.length || 0 }} permiso(s)
                            </div>
                        </div>

                        <div class="px-6 py-3 bg-gray-50 border-t flex justify-between items-center">
                            <div class="flex space-x-2">
                                <Link :href="route('admin.roles.edit', rol.rol_id)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    Editar
                                </Link>
                                <button v-if="rol.nombre !== 'administrador'" 
                                    @click="confirmarAccion(rol, 'toggleActivo')"
                                    :class="rol.activo ? 'text-gray-600 hover:text-gray-900' : 'text-green-600 hover:text-green-900'"
                                    class="text-sm font-medium">
                                    {{ rol.activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </div>
                            <button v-if="rol.nombre !== 'administrador'" 
                                @click="abrirModalEliminar(rol)"
                                class="text-red-600 hover:text-red-900 text-sm font-medium">
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="roles.length === 0" class="bg-white rounded-lg shadow-md p-8 text-center text-gray-500">
                    No hay roles configurados en el sistema.
                </div>
            </div>
        </div>

        <!-- Modal de confirmación genérico -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false"></div>
                    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ modalTitle }}</h3>
                        <p class="text-sm text-gray-500 mb-6">{{ modalMessage }}</p>
                        <div class="flex justify-end space-x-3">
                            <SecondaryButton @click="showModal = false">Cancelar</SecondaryButton>
                            <PrimaryButton @click="ejecutarAccion">Confirmar</PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal de eliminar rol -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDeleteModal = false"></div>
                    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Eliminar Rol</h3>
                        
                        <p class="text-sm text-gray-500 mb-4">
                            ¿Está seguro que desea eliminar el rol <strong>"{{ deleteRol?.nombre }}"</strong>?
                        </p>

                        <div v-if="deleteRol?.users_count > 0" class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-4">
                            <p class="text-sm text-yellow-700 mb-3">
                                <strong>Atención:</strong> Este rol tiene {{ deleteRol.users_count }} usuario(s) asignado(s).
                                ¿Qué desea hacer con ellos?
                            </p>
                            
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" v-model="deleteForm.accion_usuarios" value="reasignar" class="mr-2">
                                    <span class="text-sm">Reasignar a otro rol</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" v-model="deleteForm.accion_usuarios" value="deshabilitar" class="mr-2">
                                    <span class="text-sm">Deshabilitar usuarios</span>
                                </label>
                            </div>

                            <div v-if="deleteForm.accion_usuarios === 'reasignar'" class="mt-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rol destino:</label>
                                <select v-model="deleteForm.rol_destino_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    <option value="">Seleccione...</option>
                                    <option v-for="r in roles.filter(r => r.rol_id !== deleteRol?.rol_id)" 
                                        :key="r.rol_id" :value="r.rol_id">
                                        {{ r.nombre }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <SecondaryButton @click="showDeleteModal = false">Cancelar</SecondaryButton>
                            <DangerButton @click="eliminarRol" 
                                :disabled="deleteRol?.users_count > 0 && deleteForm.accion_usuarios === 'reasignar' && !deleteForm.rol_destino_id">
                                Eliminar Rol
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
