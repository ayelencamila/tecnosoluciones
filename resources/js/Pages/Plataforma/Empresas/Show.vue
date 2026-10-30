<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import PlataformaLayout from '@/Layouts/PlataformaLayout.vue';

const props = defineProps({
    empresa: Object,
    usuarios: Array,
});

const entrar = () => {
    router.post(route('super.empresas.entrar', props.empresa.id));
};

/* --- Edición de los datos de la empresa ---------------------------------
 * El panel arranca en modo lectura y se pasa a edición con un botón. Es a
 * propósito, siguiendo a Kendall & Kendall: el uso habitual de esta pantalla
 * es consultar, y un formulario siempre abierto invita a modificar por error
 * datos que casi nunca cambian.
 */
const editando = ref(false);

const form = useForm({
    nombre: props.empresa.nombre,
    cuit: props.empresa.cuit ?? '',
    telefono: props.empresa.telefono ?? '',
    email: props.empresa.email ?? '',
    direccion: props.empresa.direccion ?? '',
});

const guardar = () => {
    form.put(route('super.empresas.update', props.empresa.id), {
        preserveScroll: true,
        onSuccess: () => {
            editando.value = false;
        },
    });
};

const cancelar = () => {
    form.reset();
    form.clearErrors();
    editando.value = false;
};

/* --- Restablecer el acceso del administrador ----------------------------
 * La contraseña temporal llega por flash una sola vez. No se guarda en
 * ninguna parte, así que si el operador cierra la pantalla sin copiarla hay
 * que volver a restablecerla.
 */
const page = usePage();
const passwordTemporal = computed(() => page.props.flash?.password_temporal ?? null);
const copiado = ref(false);

const restablecerAcceso = (usuario) => {
    const confirmado = window.confirm(
        `Se va a generar una contraseña temporal para ${usuario.name}, levantar el bloqueo por intentos fallidos y reactivar la cuenta.\n\n` +
            'La contraseña actual deja de funcionar. ¿Continuar?',
    );

    if (!confirmado) {
        return;
    }

    copiado.value = false;

    router.post(
        route('super.empresas.restablecer-acceso', [props.empresa.id, usuario.id]),
        {},
        { preserveScroll: true },
    );
};

const copiarPassword = async () => {
    try {
        await navigator.clipboard.writeText(passwordTemporal.value);
        copiado.value = true;
    } catch {
        // Si el navegador niega el portapapeles, el texto está a la vista
        // para copiarlo a mano: no hace falta avisar nada.
    }
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

        <!-- Contraseña temporal recién generada -->
        <div
            v-if="passwordTemporal"
            class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4"
        >
            <h2 class="text-sm font-semibold text-amber-900">Contraseña temporal generada</h2>
            <p class="mt-1 text-sm text-amber-800">
                Se muestra una sola vez y no queda guardada en ningún lado. Copiala ahora y
                entregásela al cliente por un canal acordado con él; pedile que la cambie al entrar.
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <code class="rounded border border-amber-300 bg-white px-3 py-2 font-mono text-base text-gray-900">
                    {{ passwordTemporal }}
                </code>
                <button
                    type="button"
                    class="rounded bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700"
                    @click="copiarPassword"
                >
                    {{ copiado ? 'Copiada' : 'Copiar' }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Datos de la empresa -->
            <div class="rounded-lg bg-white p-6 shadow lg:col-span-1">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Datos</h2>
                    <button
                        v-if="!editando"
                        type="button"
                        class="text-sm text-indigo-600 hover:text-indigo-800"
                        @click="editando = true"
                    >
                        Editar
                    </button>
                </div>

                <!-- Lectura -->
                <dl v-if="!editando" class="mt-4 space-y-3 text-sm">
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

                <!-- Edición -->
                <form v-else class="mt-4 space-y-4" @submit.prevent="guardar">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input
                            id="nombre"
                            v-model="form.nombre"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                        <p v-if="form.errors.nombre" class="mt-1 text-sm text-red-600">{{ form.errors.nombre }}</p>
                    </div>

                    <div>
                        <label for="cuit" class="block text-sm font-medium text-gray-700">CUIT</label>
                        <input
                            id="cuit"
                            v-model="form.cuit"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                        <p v-if="form.errors.cuit" class="mt-1 text-sm text-red-600">{{ form.errors.cuit }}</p>
                    </div>

                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <input
                            id="telefono"
                            v-model="form.telefono"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                        <p v-if="form.errors.telefono" class="mt-1 text-sm text-red-600">{{ form.errors.telefono }}</p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
                        <input
                            id="direccion"
                            v-model="form.direccion"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                        <p v-if="form.errors.direccion" class="mt-1 text-sm text-red-600">{{ form.errors.direccion }}</p>
                    </div>

                    <p class="text-xs text-gray-500">
                        El identificador y el estado del acceso no se editan acá: el primero se usa
                        en claves internas y el segundo tiene su propia acción, suspender o reactivar.
                    </p>

                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Guardar
                        </button>
                        <button
                            type="button"
                            class="text-sm text-gray-600 hover:text-gray-900"
                            @click="cancelar"
                        >
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Usuarios de la empresa -->
            <div class="rounded-lg bg-white shadow lg:col-span-2">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Usuarios ({{ usuarios.length }})
                    </h2>
                    <p class="mt-1 text-xs text-gray-500">
                        Los usuarios y roles los gestiona el administrador de la empresa desde su
                        propio panel. Desde acá solo se le devuelve el acceso si quedó afuera.
                    </p>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Usuario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Acciones</th>
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
                                <div v-if="usuario.bloqueado" class="mt-1 text-xs text-red-600">
                                    Bloqueado hasta {{ usuario.bloqueado_hasta }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    v-if="usuario.es_admin"
                                    type="button"
                                    class="text-sm text-indigo-600 hover:text-indigo-800"
                                    @click="restablecerAcceso(usuario)"
                                >
                                    Restablecer acceso
                                </button>
                                <span v-else class="text-xs text-gray-400">
                                    lo resuelve su administrador
                                </span>
                            </td>
                        </tr>
                        <tr v-if="usuarios.length === 0">
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                Esta empresa no tiene usuarios.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PlataformaLayout>
</template>
