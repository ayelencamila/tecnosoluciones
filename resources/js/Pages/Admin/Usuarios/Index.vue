<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    usuarios: Object,
    roles: Array,
    filtros: Object,
});

const search = ref(props.filtros?.search || '');
const rolFiltro = ref(props.filtros?.rol_id || '');
const activoFiltro = ref(props.filtros?.activo ?? '');

// Modal de confirmación
const showModal = ref(false);
const modalAction = ref(null);
const selectedUser = ref(null);
const modalTitle = ref('');
const modalMessage = ref('');

// Modal de reset password
const showPasswordModal = ref(false);
const passwordUser = ref(null);
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
    generar_temporal: false,
});

// Búsqueda con debounce
const buscar = debounce(() => {
    router.get(route('admin.usuarios.index'), {
        search: search.value,
        rol_id: rolFiltro.value,
        activo: activoFiltro.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, rolFiltro, activoFiltro], buscar);

const limpiarFiltros = () => {
    search.value = '';
    rolFiltro.value = '';
    activoFiltro.value = '';
    buscar();
};

// Acciones de usuario
const confirmarAccion = (usuario, accion) => {
    selectedUser.value = usuario;
    modalAction.value = accion;
    
    switch(accion) {
        case 'toggleActivo':
            modalTitle.value = usuario.activo ? 'Desactivar Usuario' : 'Activar Usuario';
            modalMessage.value = usuario.activo 
                ? `¿Está seguro que desea desactivar al usuario "${usuario.name}"? No podrá acceder al sistema.`
                : `¿Está seguro que desea activar al usuario "${usuario.name}"?`;
            break;
        case 'toggleBloqueo':
            const estaBloqueado = usuario.bloqueado_hasta && new Date(usuario.bloqueado_hasta) > new Date();
            modalTitle.value = estaBloqueado ? 'Desbloquear Usuario' : 'Bloquear Usuario';
            modalMessage.value = estaBloqueado
                ? `¿Está seguro que desea desbloquear al usuario "${usuario.name}"?`
                : `¿Está seguro que desea bloquear al usuario "${usuario.name}"? No podrá iniciar sesión.`;
            break;
    }
    
    showModal.value = true;
};

const ejecutarAccion = () => {
    if (!selectedUser.value || !modalAction.value) return;
    
    const routeName = modalAction.value === 'toggleActivo' 
        ? 'admin.usuarios.toggle-activo'
        : 'admin.usuarios.toggle-bloqueo';
    
    router.patch(route(routeName, selectedUser.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            selectedUser.value = null;
            modalAction.value = null;
        },
    });
};

// Reset Password
const abrirModalPassword = (usuario) => {
    passwordUser.value = usuario;
    passwordForm.reset();
    showPasswordModal.value = true;
};

const resetPassword = () => {
    passwordForm.post(route('admin.usuarios.reset-password', passwordUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false;
            passwordUser.value = null;
            passwordForm.reset();
        },
    });
};

// Helpers
const getRolNombre = (usuario) => {
    return usuario.rol?.nombre || 'Sin rol';
};

const getEstadoClass = (usuario) => {
    if (!usuario.activo) return 'bg-gray-100 text-gray-600';
    const estaBloqueado = usuario.bloqueado_hasta && new Date(usuario.bloqueado_hasta) > new Date();
    if (estaBloqueado) return 'bg-red-100 text-red-700';
    return 'bg-green-100 text-green-700';
};

const getEstadoTexto = (usuario) => {
    if (!usuario.activo) return 'Inactivo';
    const estaBloqueado = usuario.bloqueado_hasta && new Date(usuario.bloqueado_hasta) > new Date();
    if (estaBloqueado) return 'Bloqueado';
    return 'Activo';
};
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestión de Usuarios
                </h2>
                <Link :href="route('admin.usuarios.create')">
                    <PrimaryButton>+ Registrar Usuario</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="bg-white p-4 rounded-lg shadow mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                            <TextInput 
                                v-model="search" 
                                type="text" 
                                placeholder="Nombre, email o teléfono..." 
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                            <select v-model="rolFiltro" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Todos los roles</option>
                                <option v-for="rol in roles" :key="rol.rol_id" :value="rol.rol_id">
                                    {{ rol.nombre }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select v-model="activoFiltro" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Todos</option>
                                <option value="1">Activos</option>
                                <option value="0">Inactivos</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <SecondaryButton @click="limpiarFiltros" class="w-full justify-center">
                                Limpiar filtros
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Tabla de usuarios -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="usuario in usuarios.data" :key="usuario.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold">
                                            {{ usuario.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ usuario.name }}</div>
                                            <div class="text-sm text-gray-500">{{ usuario.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800 capitalize">
                                        {{ getRolNombre(usuario) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ usuario.telefono || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs rounded-full', getEstadoClass(usuario)]">
                                        {{ getEstadoTexto(usuario) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <Link :href="route('admin.usuarios.show', usuario.id)" class="text-gray-600 hover:text-gray-900" title="Ver detalle">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </Link>
                                        <Link :href="route('admin.usuarios.edit', usuario.id)" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </Link>
                                        <button @click="abrirModalPassword(usuario)" class="text-yellow-600 hover:text-yellow-900" title="Restablecer contraseña">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                            </svg>
                                        </button>
                                        <button @click="confirmarAccion(usuario, 'toggleActivo')" 
                                            :class="usuario.activo ? 'text-gray-600 hover:text-gray-900' : 'text-green-600 hover:text-green-900'"
                                            :title="usuario.activo ? 'Desactivar' : 'Activar'">
                                            <svg v-if="usuario.activo" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                        <button @click="confirmarAccion(usuario, 'toggleBloqueo')" 
                                            :class="(usuario.bloqueado_hasta && new Date(usuario.bloqueado_hasta) > new Date()) ? 'text-green-600 hover:text-green-900' : 'text-red-600 hover:text-red-900'"
                                            :title="(usuario.bloqueado_hasta && new Date(usuario.bloqueado_hasta) > new Date()) ? 'Desbloquear' : 'Bloquear'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="usuarios.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    No se encontraron usuarios con los filtros aplicados.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <div v-if="usuarios.links && usuarios.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <p class="text-sm text-gray-700">
                                Mostrando {{ usuarios.from }} a {{ usuarios.to }} de {{ usuarios.total }} usuarios
                            </p>
                            <nav class="flex space-x-1">
                                <Link v-for="link in usuarios.links" :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-1 text-sm rounded',
                                        link.active ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                    v-html="link.label"
                                    preserve-scroll
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación -->
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

        <!-- Modal de reset password -->
        <Teleport to="body">
            <div v-if="showPasswordModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showPasswordModal = false"></div>
                    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Restablecer Contraseña
                        </h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Usuario: <strong>{{ passwordUser?.name }}</strong>
                        </p>
                        
                        <form @submit.prevent="resetPassword">
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="passwordForm.generar_temporal" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600">Generar contraseña temporal automática</span>
                                </label>
                            </div>

                            <div v-if="!passwordForm.generar_temporal" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Contraseña</label>
                                    <TextInput v-model="passwordForm.password" type="password" class="w-full" />
                                    <p v-if="passwordForm.errors.password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.password }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña</label>
                                    <TextInput v-model="passwordForm.password_confirmation" type="password" class="w-full" />
                                </div>
                                <p class="text-xs text-gray-500">
                                    La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas, números y símbolos.
                                </p>
                            </div>

                            <div class="flex justify-end space-x-3 mt-6">
                                <SecondaryButton type="button" @click="showPasswordModal = false">Cancelar</SecondaryButton>
                                <PrimaryButton type="submit" :disabled="passwordForm.processing">
                                    {{ passwordForm.processing ? 'Procesando...' : 'Restablecer' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
