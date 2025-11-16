<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    descuento: Object, // null si es create, objeto si es edit
});

const isEditing = computed(() => !!props.descuento);

const form = useForm({
    codigo: props.descuento?.codigo || '',
    descripcion: props.descuento?.descripcion || '',
    tipo: props.descuento?.tipo || 'porcentaje',
    valor: props.descuento?.valor || '',
    activo: props.descuento?.activo ?? true,
    valido_desde: props.descuento?.valido_desde || '',
    valido_hasta: props.descuento?.valido_hasta || '',
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('descuentos.update', props.descuento.descuento_id), {
            preserveScroll: true,
        });
    } else {
        form.post(route('descuentos.store'), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Editar Descuento' : 'Nuevo Descuento'" />
    
    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ isEditing ? 'Editar Descuento' : 'Nuevo Descuento' }}
                </h2>
                <Link :href="route('descuentos.index')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                    &larr; Volver al Listado
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <!-- Código -->
                        <div>
                            <InputLabel for="codigo" value="Código del Descuento *" />
                            <TextInput
                                id="codigo"
                                v-model="form.codigo"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Ej: VERANO2025, DESC10"
                                required
                            />
                            <InputError :message="form.errors.codigo" class="mt-2" />
                            <p class="text-xs text-gray-500 mt-1">El código debe ser único y fácil de recordar</p>
                        </div>

                        <!-- Descripción -->
                        <div>
                            <InputLabel for="descripcion" value="Descripción *" />
                            <textarea
                                id="descripcion"
                                v-model="form.descripcion"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                rows="3"
                                placeholder="Descripción detallada del descuento"
                                required
                            ></textarea>
                            <InputError :message="form.errors.descripcion" class="mt-2" />
                        </div>

                        <!-- Tipo y Valor -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="tipo" value="Tipo de Descuento *" />
                                <SelectInput
                                    id="tipo"
                                    v-model="form.tipo"
                                    class="mt-1 block w-full"
                                    required
                                >
                                    <option value="porcentaje">Porcentaje (%)</option>
                                    <option value="monto_fijo">Monto Fijo ($)</option>
                                </SelectInput>
                                <InputError :message="form.errors.tipo" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="valor" value="Valor *" />
                                <TextInput
                                    id="valor"
                                    v-model="form.valor"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :max="form.tipo === 'porcentaje' ? 100 : undefined"
                                    class="mt-1 block w-full"
                                    :placeholder="form.tipo === 'porcentaje' ? 'Ej: 15' : 'Ej: 500.00'"
                                    required
                                />
                                <InputError :message="form.errors.valor" class="mt-2" />
                                <p v-if="form.tipo === 'porcentaje'" class="text-xs text-gray-500 mt-1">Máximo 100%</p>
                            </div>
                        </div>

                        <!-- Vigencia -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="valido_desde" value="Válido Desde" />
                                <TextInput
                                    id="valido_desde"
                                    v-model="form.valido_desde"
                                    type="date"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.valido_desde" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="valido_hasta" value="Válido Hasta" />
                                <TextInput
                                    id="valido_hasta"
                                    v-model="form.valido_hasta"
                                    type="date"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.valido_hasta" class="mt-2" />
                            </div>
                        </div>

                        <!-- Estado (solo en edición) -->
                        <div v-if="isEditing" class="flex items-center">
                            <input
                                id="activo"
                                v-model="form.activo"
                                type="checkbox"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <label for="activo" class="ml-2 block text-sm text-gray-900">
                                Descuento activo
                            </label>
                        </div>

                        <!-- Vista previa del descuento -->
                        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-indigo-900 mb-2">Vista Previa</h3>
                            <div class="text-sm text-indigo-700">
                                <p><strong>Código:</strong> {{ form.codigo || '(sin código)' }}</p>
                                <p><strong>Tipo:</strong> {{ form.tipo === 'porcentaje' ? 'Porcentaje' : 'Monto Fijo' }}</p>
                                <p><strong>Valor:</strong> 
                                    <span v-if="form.tipo === 'porcentaje'">{{ form.valor }}%</span>
                                    <span v-else>${{ form.valor }}</span>
                                </p>
                                <p v-if="form.valido_desde || form.valido_hasta"><strong>Vigencia:</strong> 
                                    <span v-if="form.valido_desde">Desde {{ form.valido_desde }}</span>
                                    <span v-if="form.valido_hasta"> hasta {{ form.valido_hasta }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-end gap-4 pt-4 border-t">
                            <Link :href="route('descuentos.index')">
                                <DangerButton type="button">
                                    Cancelar
                                </DangerButton>
                            </Link>
                            <PrimaryButton type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear Descuento') }}
                            </PrimaryButton>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
