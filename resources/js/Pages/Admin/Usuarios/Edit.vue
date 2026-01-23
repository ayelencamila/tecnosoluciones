<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    usuario: Object,
    roles: Array,
});

const form = useForm({
    name: props.usuario.name,
    telefono: props.usuario.telefono || '',
    rol_id: props.usuario.rol_id,
});

const submit = () => {
    form.put(route('admin.usuarios.update', props.usuario.id));
};
</script>

<template>
    <Head title="Modificar Usuario" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('admin.usuarios.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Modificar Usuario
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-md rounded-lg p-6">
                    <!-- Info del usuario -->
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="h-16 w-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold">
                                {{ usuario.name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">{{ usuario.name }}</h3>
                                <p class="text-sm text-gray-500">{{ usuario.email }}</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Registrado: {{ new Date(usuario.created_at).toLocaleDateString('es-AR') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Email (solo lectura) -->
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                :value="usuario.email"
                                type="email"
                                class="mt-1 block w-full bg-gray-100"
                                disabled
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                El email no puede ser modificado desde esta función.
                            </p>
                        </div>

                        <!-- Nombre Completo -->
                        <div>
                            <InputLabel for="name" value="Nombre Completo *" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <InputLabel for="telefono" value="Teléfono" />
                            <TextInput
                                id="telefono"
                                v-model="form.telefono"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Ej: +54 9 11 1234-5678"
                            />
                            <InputError :message="form.errors.telefono" class="mt-2" />
                        </div>

                        <!-- Rol -->
                        <div>
                            <InputLabel for="rol_id" value="Rol *" />
                            <select
                                id="rol_id"
                                v-model="form.rol_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option v-for="rol in roles" :key="rol.rol_id" :value="rol.rol_id">
                                    {{ rol.nombre }} - {{ rol.descripcion }}
                                </option>
                            </select>
                            <InputError :message="form.errors.rol_id" class="mt-2" />
                        </div>

                        <!-- Info sobre contraseña -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Para cambiar la contraseña del usuario, use la opción <strong>"Restablecer Contraseña"</strong> desde el listado de usuarios.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <Link :href="route('admin.usuarios.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>
                            <PrimaryButton type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
