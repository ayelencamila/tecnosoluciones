<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    roles: Array,
});

const form = useForm({
    name: '',
    email: '',
    telefono: '',
    password: '',
    password_confirmation: '',
    rol_id: '',
});

const submit = () => {
    form.post(route('admin.usuarios.store'));
};
</script>

<template>
    <Head title="Registrar Usuario" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('admin.usuarios.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Registrar Nuevo Usuario
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-md rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Nombre Completo -->
                        <div>
                            <InputLabel for="name" value="Nombre Completo *" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                                placeholder="Ej: Juan Pérez"
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div>
                            <InputLabel for="email" value="Email *" />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full"
                                required
                                placeholder="usuario@tecnosoluciones.com"
                            />
                            <InputError :message="form.errors.email" class="mt-2" />
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
                                <option value="">Seleccione un rol...</option>
                                <option v-for="rol in roles" :key="rol.rol_id" :value="rol.rol_id">
                                    {{ rol.nombre }} - {{ rol.descripcion }}
                                </option>
                            </select>
                            <InputError :message="form.errors.rol_id" class="mt-2" />
                        </div>

                        <!-- Contraseña -->
                        <div>
                            <InputLabel for="password" value="Contraseña *" />
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full"
                                required
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Mínimo 8 caracteres, incluir mayúsculas, minúsculas, números y símbolos.
                            </p>
                            <InputError :message="form.errors.password" class="mt-2" />
                        </div>

                        <!-- Confirmar Contraseña -->
                        <div>
                            <InputLabel for="password_confirmation" value="Confirmar Contraseña *" />
                            <TextInput
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.password_confirmation" class="mt-2" />
                        </div>

                        <!-- Info -->
                        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        El usuario será creado con estado <strong>Activo</strong> y podrá iniciar sesión inmediatamente.
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
                                {{ form.processing ? 'Registrando...' : 'Registrar Usuario' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
