<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PlataformaLayout from '@/Layouts/PlataformaLayout.vue';

const props = defineProps({
    empresa: Object,
    usuarios: Array,
});

const entrar = () => {
    router.post(route('super.empresas.entrar', props.empresa.id));
};
</script>

<template>
    <Head :title="empresa.nombre" />

    <PlataformaLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <Link
                        :href="route('super.empresas.index')"
                        class="text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        ← Volver a empresas
                    </Link>
                    <h1 class="mt-1 text-xl font-semibold text-gray-900">{{ empresa.nombre }}</h1>
                </div>
                <button
                    type="button"
                    class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="entrar"
                >
                    Entrar a su sistema
                </button>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Datos de la empresa -->
            <div class="rounded-lg bg-white p-6 shadow lg:col-span-1">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Datos</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Identificador</dt>
                        <dd class="font-mono text-gray-900">{{ empresa.slug }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">CUIT</dt>
                        <dd class="text-gray-900">{{ empresa.cuit || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Teléfono</dt>
                        <dd class="text-gray-900">{{ empresa.telefono || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Email</dt>
                        <dd class="text-gray-900">{{ empresa.email || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Dirección</dt>
                        <dd class="text-gray-900">{{ empresa.direccion || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Alta</dt>
                        <dd class="text-gray-900">{{ empresa.creada }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Acceso</dt>
                        <dd>
                            <span
                                :class="[
                                    'rounded-full px-2 py-1 text-xs font-medium',
                                    empresa.activa ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800',
                                ]"
                            >
                                {{ empresa.activa ? 'Habilitado' : 'Suspendido' }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Usuarios de la empresa -->
            <div class="rounded-lg bg-white shadow lg:col-span-2">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Usuarios ({{ usuarios.length }})
                    </h2>
                    <p class="mt-1 text-xs text-gray-500">
                        Los usuarios y roles los gestiona el administrador de la empresa desde su propio panel.
                    </p>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Usuario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="usuario in usuarios" :key="usuario.id">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ usuario.name }}</div>
                                <div class="text-sm text-gray-500">{{ usuario.email }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="capitalize text-gray-900">{{ usuario.rol || '—' }}</span>
                                <span
                                    v-if="usuario.es_admin"
                                    class="ml-2 rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-800"
                                >
                                    administra la empresa
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="[
                                        'rounded-full px-2 py-1 text-xs font-medium',
                                        usuario.activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{ usuario.activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="usuarios.length === 0">
                            <td colspan="3" class="px-6 py-10 text-center text-sm text-gray-500">
                                Esta empresa no tiene usuarios.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PlataformaLayout>
</template>
