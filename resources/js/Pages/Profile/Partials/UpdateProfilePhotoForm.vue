<script setup>
/**
 * Componente: Actualizar Foto de Perfil
 * 
 * Permite al usuario:
 * - Ver su foto de perfil actual
 * - Subir una nueva foto
 * - Eliminar la foto existente
 */
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';

const user = usePage().props.auth.user;

const photoInput = ref(null);
const photoPreview = ref(null);

const form = useForm({
    foto: null,
});

const deleteForm = useForm({});

// URL de la foto actual o preview
const currentPhotoUrl = computed(() => {
    if (photoPreview.value) {
        return photoPreview.value;
    }
    if (user.foto_perfil) {
        return `/storage/${user.foto_perfil}`;
    }
    return null;
});

// Iniciales del usuario para el avatar por defecto
const initials = computed(() => {
    return user.name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

// Seleccionar archivo
const selectNewPhoto = () => {
    photoInput.value.click();
};

// Manejar cambio de archivo
const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (!photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
    form.foto = photo;
};

// Subir foto
const uploadPhoto = () => {
    if (!form.foto) return;

    form.post(route('profile.photo.update'), {
        preserveScroll: true,
        onSuccess: () => {
            clearPhotoFileInput();
            photoPreview.value = null;
        },
        onError: () => {
            clearPhotoFileInput();
        },
    });
};

// Eliminar foto
const deletePhoto = () => {
    deleteForm.delete(route('profile.photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
        },
    });
};

// Cancelar selección
const cancelSelection = () => {
    photoPreview.value = null;
    form.foto = null;
    clearPhotoFileInput();
};

// Limpiar input de archivo
const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Foto de Perfil
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Actualiza tu foto de perfil. La imagen debe ser menor a 2MB.
            </p>
        </header>

        <div class="mt-6 flex items-center gap-6">
            <!-- Avatar actual o preview -->
            <div class="relative">
                <!-- Con foto -->
                <img 
                    v-if="currentPhotoUrl"
                    :src="currentPhotoUrl" 
                    :alt="user.name"
                    class="h-24 w-24 rounded-full object-cover border-4 border-indigo-100 shadow-lg"
                />
                
                <!-- Sin foto - Iniciales -->
                <div 
                    v-else
                    class="h-24 w-24 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center border-4 border-indigo-100 shadow-lg"
                >
                    <span class="text-2xl font-bold text-white">{{ initials }}</span>
                </div>

                <!-- Badge de cámara -->
                <button
                    type="button"
                    @click="selectNewPhoto"
                    class="absolute bottom-0 right-0 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full p-2 shadow-lg transition-colors"
                    title="Cambiar foto"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>
            </div>

            <!-- Información y botones -->
            <div class="flex-1">
                <div class="text-sm text-gray-600 mb-3">
                    <p class="font-medium text-gray-900">{{ user.name }}</p>
                    <p>{{ user.email }}</p>
                </div>

                <!-- Input oculto para archivo -->
                <input
                    ref="photoInput"
                    type="file"
                    class="hidden"
                    accept="image/*"
                    @change="updatePhotoPreview"
                />

                <!-- Botones según estado -->
                <div class="flex gap-2">
                    <!-- Si hay preview (foto seleccionada) -->
                    <template v-if="photoPreview">
                        <PrimaryButton 
                            @click="uploadPhoto"
                            :disabled="form.processing"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar
                        </PrimaryButton>
                        <button
                            type="button"
                            @click="cancelSelection"
                            class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Cancelar
                        </button>
                    </template>

                    <!-- Si no hay preview -->
                    <template v-else>
                        <button
                            type="button"
                            @click="selectNewPhoto"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Seleccionar Imagen
                        </button>

                        <DangerButton
                            v-if="user.foto_perfil"
                            @click="deletePhoto"
                            :disabled="deleteForm.processing"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Eliminar
                        </DangerButton>
                    </template>
                </div>

                <InputError class="mt-2" :message="form.errors.foto" />
            </div>
        </div>

        <!-- Mensaje de éxito -->
        <Transition
            enter-active-class="transition ease-in-out"
            enter-from-class="opacity-0"
            leave-active-class="transition ease-in-out"
            leave-to-class="opacity-0"
        >
            <p v-if="$page.props.flash?.status" class="mt-4 text-sm text-green-600">
                {{ $page.props.flash.status }}
            </p>
        </Transition>
    </section>
</template>
