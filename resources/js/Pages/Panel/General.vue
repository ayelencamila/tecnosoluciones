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
        Panel General
      </h2>
    </template>

    <div class="py-6">
      <p class="mb-2">
        Bienvenido/a, {{ user.name }} (rol: {{ user.role }})
      </p>
      <p>
        Esta vista es accesible por <strong>Admin</strong> y por <strong>Vendedor</strong>.
      </p>
    </div>
  </AuthenticatedLayout>
</template>