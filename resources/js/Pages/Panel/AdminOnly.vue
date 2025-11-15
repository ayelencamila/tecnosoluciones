<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Panel de Administración
      </h2>
    </template>

    <div class="py-6">
      <p class="mb-2">
        Hola {{ user.name }} (rol: {{ user.role }})
      </p>
      <p>
        Esta vista es <strong>solo para Admin</strong>. Si ves esto, es porque tu usuario tiene rol <code>admin</code>.
      </p>
    </div>
  </AuthenticatedLayout>
</template>
