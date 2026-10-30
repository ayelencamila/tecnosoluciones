<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PlataformaLayout from '@/Layouts/PlataformaLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    empresas: Array,
    empresaActiva: Number,
});

const mostrarAlta = ref(false);

const form = useForm({
    nombre: '',
    cuit: '',
    telefono: '',
    email: '',
    direccion: '',
    admin_name: '',
    admin_email: '',
    admin_telefono: '',
    admin_password: '',
    admin_password_confirmation: '',
});

const crearEmpresa = () => {
    form.post(route('super.empresas.store'), {
        onSuccess: () => {
            form.reset();
            mostrarAlta.value = false;
        },
    });
};

const entrar = (empresa) => {
    router.post(route('super.empresas.entrar', empresa.id));
};

// Confirmacion de suspension: corta el acceso de todos los usuarios de esa
// empresa, asi que no conviene que sea un click directo.
const empresaAConfirmar = ref(null);

const cambiarAcceso = () => {
    router.post(route('super.empresas.acceso', empresaAConfirmar.value.id), {}, {
        preserveScroll: true,
        onFinish: () => (empresaAConfirmar.value = null),
    });
};
</script>

<template>
    <Head title="Empresas" />

    <PlataformaLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Empresas clientes</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ empresas.length }} empresa(s) en el sistema
                    </p>
                </div>
                <PrimaryButton @click="mostrarAlta = true">+ Nueva empresa</PrimaryButton>
            </div>
        </template>

        <div class="overflow-hidden rounded-lg bg-white shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Empresa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Usuarios</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Alta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Acceso</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr
                        v-for="empresa in empresas"
                        :key="empresa.id"
                        :class="empresa.id === empresaActiva ? 'bg-amber-50' : ''"
                    >
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">
                                {{ empresa.nombre }}
                                <span
                                    v-if="empresa.id === empresaActiva"
                                    class="ml-2 rounded-full bg-amber-200 px-2 py-0.5 text-xs text-amber-900"
                                >
                                    trabajando aquí
                                </span>
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ empresa.cuit || 'sin CUIT' }} · {{ empresa.email || 'sin email' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ empresa.usuarios_activos }} activo(s)
                            <span class="text-gray-400">de {{ empresa.usuarios_total }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ empresa.creada }}</td>
                        <td class="px-6 py-4">
                            <span
                                :class="[
                                    'rounded-full px-2 py-1 text-xs font-medium',
                                    empresa.activa ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800',
                                ]"
                            >
                                {{ empresa.activa ? 'Habilitado' : 'Suspendido' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <div class="flex justify-end gap-2">
                                <Link
                                    :href="route('super.empresas.show', empresa.id)"
                                    class="rounded border border-gray-300 px-3 py-1 text-gray-700 hover:bg-gray-50"
                                >
                                    Ver
                                </Link>
                                <button
                                    type="button"
                                    class="rounded bg-indigo-600 px-3 py-1 text-white hover:bg-indigo-700"
                                    @click="entrar(empresa)"
                                >
                                    Entrar
                                </button>
                                <button
                                    type="button"
                                    :class="[
                                        'rounded px-3 py-1 text-white',
                                        empresa.activa ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700',
                                    ]"
                                    @click="empresaAConfirmar = empresa"
                                >
                                    {{ empresa.activa ? 'Suspender' : 'Reactivar' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="empresas.length === 0">
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                            Todavía no hay empresas. Creá la primera con "Nueva empresa".
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Alta de empresa: crea empresa + rol administrador + usuario admin -->
        <Modal :show="mostrarAlta" max-width="2xl" @close="mostrarAlta = false">
            <form class="p-6" @submit.prevent="crearEmpresa">
                <h2 class="text-lg font-semibold text-gray-900">Nueva empresa cliente</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Se crean juntos la empresa, su rol administrador y su usuario administrador.
                </p>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel for="nombre" value="Nombre de la empresa *" />
                        <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.nombre" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="cuit" value="CUIT" />
                        <TextInput id="cuit" v-model="form.cuit" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.cuit" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="telefono" value="Teléfono" />
                        <TextInput id="telefono" v-model="form.telefono" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.telefono" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="email" value="Email de la empresa" />
                        <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                        <InputError :message="form.errors.email" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="direccion" value="Dirección" />
                        <TextInput id="direccion" v-model="form.direccion" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.direccion" class="mt-1" />
                    </div>
                </div>

                <h3 class="mt-8 border-t border-gray-200 pt-6 text-sm font-semibold uppercase tracking-wide text-gray-700">
                    Usuario administrador
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Esta persona va a gestionar los roles y usuarios de su empresa.
                </p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="admin_name" value="Nombre completo *" />
                        <TextInput id="admin_name" v-model="form.admin_name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.admin_name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="admin_telefono" value="Teléfono" />
                        <TextInput id="admin_telefono" v-model="form.admin_telefono" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.admin_telefono" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="admin_email" value="Email de acceso *" />
                        <TextInput id="admin_email" v-model="form.admin_email" type="email" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.admin_email" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="admin_password" value="Contraseña *" />
                        <TextInput id="admin_password" v-model="form.admin_password" type="password" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.admin_password" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="admin_password_confirmation" value="Repetir contraseña *" />
                        <TextInput
                            id="admin_password_confirmation"
                            v-model="form.admin_password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            required
                        />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="mostrarAlta = false">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">
                        {{ form.processing ? 'Creando...' : 'Crear empresa' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Confirmacion de suspension / reactivacion -->
        <Modal :show="empresaAConfirmar !== null" max-width="lg" @close="empresaAConfirmar = null">
            <div v-if="empresaAConfirmar" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ empresaAConfirmar.activa ? 'Suspender acceso' : 'Reactivar acceso' }}
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    <template v-if="empresaAConfirmar.activa">
                        Los {{ empresaAConfirmar.usuarios_activos }} usuario(s) de
                        <strong>{{ empresaAConfirmar.nombre }}</strong> van a perder el acceso de inmediato,
                        incluso si tienen la sesión abierta. Sus datos no se eliminan y podés reactivarla cuando quieras.
                    </template>
                    <template v-else>
                        Los usuarios de <strong>{{ empresaAConfirmar.nombre }}</strong> van a poder volver a entrar al sistema.
                    </template>
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="empresaAConfirmar = null">Cancelar</SecondaryButton>
                    <button
                        type="button"
                        :class="[
                            'rounded px-4 py-2 text-sm font-medium text-white',
                            empresaAConfirmar.activa ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700',
                        ]"
                        @click="cambiarAcceso"
                    >
                        {{ empresaAConfirmar.activa ? 'Sí, suspender' : 'Sí, reactivar' }}
                    </button>
                </div>
            </div>
        </Modal>
    </PlataformaLayout>
</template>
