<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow-lg border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <Link href="/" class="flex items-center">
                <span class="text-2xl font-bold text-indigo-600">TecnoSoluciones</span>
              </Link>
            </div>
            
            <div class="hidden md:ml-8 md:flex md:space-x-8">
              <Link 
                href="/clientes" 
                class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                :class="{ 'text-indigo-600 bg-indigo-50': $page.component.startsWith('Clientes') }"
              >
                Clientes
              </Link>
              <Link 
                href="/productos" 
                class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                :class="{ 'text-indigo-600 bg-indigo-50': $page.component.startsWith('Productos') }"
              >
                Productos
              </Link>
              <Link 
                href="/ventas" 
                class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                :class="{ 'text-indigo-600 bg-indigo-50': $page.component.startsWith('Ventas') }"
              >
                Ventas
              </Link>
              <Link 
                :href="route('pagos.index')" 
                class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                :class="{ 'text-indigo-600 bg-indigo-50': $page.component.startsWith('Pagos') }"
              >
                Pagos
              </Link>
              <Link 
                href="/descuentos" 
                class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                :class="{ 'text-indigo-600 bg-indigo-50': $page.component.startsWith('Descuentos') }"
              >
                Descuentos
              </Link>
              <Link 
                href="/auditorias" 
                class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                :class="{ 'text-indigo-600 bg-indigo-50': $page.component.startsWith('Auditorias') }"
              >
                Auditorías
              </Link>
            </div>
          </div>
          
          <div class="flex items-center">
            <div class="text-sm text-gray-600">
              Sistema de Gestión
            </div>
          </div>
        </div>
      </div>
    </nav>

    <main class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <slot />
      </div>
    </main>

    <div 
      v-if="$page.props.flash?.success || $page.props.flash?.error"
      class="fixed top-4 right-4 z-50"
    >
      <div 
        v-if="$page.props.flash?.success"
        class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center"
      >
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        {{ $page.props.flash?.success }}
      </div>
      
      <div 
        v-if="$page.props.flash?.error"
        class="bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center"
      >
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
        </svg>
        {{ $page.props.flash?.error }}
      </div>
    </div>
  </div>
</template>

<script>
// Usamos un script normal con export default para asegurar la compatibilidad como un componente de layout
import { Link } from '@inertiajs/vue3';

export default {
  components: {
    Link, // Necesitamos registrar el componente Link si lo usamos en el template
  },
  // Si tu layout necesita props, las definirías aquí
  // props: {
  //   user: Object,
  // },
};
</script>