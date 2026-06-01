<script setup>
/**
 * Pagina: Mi Empresa (CU multi-tenant)
 *
 * Permite al admin de la empresa editar el nombre, datos de contacto y
 * cambiar/eliminar el logo. El alcance es siempre la empresa propia del
 * usuario logueado.
 */
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import AlertMessage from '@/Components/AlertMessage.vue';

const props = defineProps({
    empresa: {
        type: Object,
        required: true,
    },
});

const logoInput = ref(null);
const logoPreview = ref(null);

const form = useForm({
    nombre: props.empresa.nombre ?? '',
    cuit: props.empresa.cuit ?? '',
    telefono: props.empresa.telefono ?? '',
    email: props.empresa.email ?? '',
    direccion: props.empresa.direccion ?? '',
    logo: null,
});

const deleteLogoForm = useForm({});

const currentLogoUrl = computed(() => {
    if (logoPreview.value) return logoPreview.value;
    return props.empresa.logo_url;
});

const initials = computed(() => {
    return (props.empresa.nombre ?? 'E')
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

const selectLogo = () => logoInput.value?.click();

const onLogoChange = () => {
    const file = logoInput.value?.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => (logoPreview.value = e.target.result);
    reader.readAsDataURL(file);

    form.logo = file;
};

const cancelLogo = () => {
    logoPreview.value = null;
    form.logo = null;
    if (logoInput.value?.value) logoInput.value.value = null;
};

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            _method: 'PUT',
        }))
        .post(route('empresa.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                logoPreview.value = null;
                if (logoInput.value?.value) logoInput.value.value = null;
                form.logo = null;
            },
        });
};

const deleteLogo = () => {
    if (!confirm('Estas seguro de eliminar el logo de la empresa?')) return;

    deleteLogoForm.delete(route('empresa.logo.destroy'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Mi Empresa" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Mi Empresa
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <!-- Flash messages globales (success/error) -->
                <AlertMessage
                    v-if="$page.props.flash?.success"
                    :message="$page.props.flash.success"
                    type="success"
                />
                <AlertMessage
                    v-if="$page.props.flash?.error"
                    :message="$page.props.flash.error"
                    type="error"
                />

                <!-- Card: Logo -->
                <section class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">Logo de la empresa</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Subi una imagen para que aparezca en comprobantes y en el panel.
                            Maximo 2MB.
                        </p>
                    </header>

                    <div class="mt-6 flex items-center gap-6">
                        <div class="relative">
                            <img
                                v-if="currentLogoUrl"
                                :src="currentLogoUrl"
                                :alt="empresa.nombre"
                                class="h-24 w-24 rounded-lg object-cover border-4 border-indigo-100 shadow-lg bg-white"
                            />
                            <div
                                v-else
                                class="h-24 w-24 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center border-4 border-indigo-100 shadow-lg"
                            >
                                <span class="text-2xl font-bold text-white">{{ initials }}</span>
                            </div>

                            <button
                                type="button"
                                @click="selectLogo"
                                class="absolute bottom-0 right-0 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full p-2 shadow-lg transition-colors"
                                title="Cambiar logo"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </button>
                        </div>

                        <div class="flex-1">
                            <input
                                ref="logoInput"
                                type="file"
                                class="hidden"
                                accept="image/*"
                                @change="onLogoChange"
                            />

                            <div class="flex flex-wrap gap-2">
                                <template v-if="logoPreview">
                                    <span class="inline-flex items-center text-sm text-gray-700">
                                        Nueva imagen seleccionada. Hace click en "Guardar cambios" para aplicar.
                                    </span>
                                    <button
                                        type="button"
                                        @click="cancelLogo"
                                        class="inline-flex items-center px-3 py-1.5 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300"
                                    >
                                        Cancelar seleccion
                                    </button>
                                </template>
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="selectLogo"
                                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                                    >
                                        Seleccionar logo
                                    </button>
                                    <DangerButton
                                        v-if="empresa.logo"
                                        @click="deleteLogo"
                                        :disabled="deleteLogoForm.processing"
                                    >
                                        Eliminar logo
                                    </DangerButton>
                                </template>
                            </div>

                            <InputError class="mt-2" :message="form.errors.logo" />
                        </div>
                    </div>
                </section>

                <!-- Card: Datos de la empresa -->
                <section class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">Datos de la empresa</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Estos datos aparecen en comprobantes y notificaciones a clientes.
                        </p>
                    </header>

                    <form @submit.prevent="submit" class="mt-6 space-y-6">
                        <div>
                            <InputLabel for="nombre" value="Nombre *" />
                            <TextInput
                                id="nombre"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.nombre"
                                required
                                autofocus
                                maxlength="150"
                            />
                            <InputError class="mt-2" :message="form.errors.nombre" />
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <InputLabel for="cuit" value="CUIT" />
                                <TextInput
                                    id="cuit"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.cuit"
                                    placeholder="20123456789"
                                    maxlength="20"
                                />
                                <p class="mt-1 text-xs text-gray-500">Los guiones se eliminan automaticamente.</p>
                                <InputError class="mt-2" :message="form.errors.cuit" />
                            </div>

                            <div>
                                <InputLabel for="telefono" value="Telefono" />
                                <TextInput
                                    id="telefono"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.telefono"
                                    maxlength="50"
                                />
                                <InputError class="mt-2" :message="form.errors.telefono" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                maxlength="150"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="direccion" value="Direccion" />
                            <TextInput
                                id="direccion"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.direccion"
                                maxlength="255"
                            />
                            <InputError class="mt-2" :message="form.errors.direccion" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">
                                Guardar cambios
                            </PrimaryButton>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="text-sm text-green-600">
                                    Guardado correctamente.
                                </p>
                            </Transition>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
