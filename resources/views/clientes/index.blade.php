@extends('layouts.app')

@section('title', 'Gestión de Clientes - TecnoSoluciones')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <i class="fas fa-home text-gray-400 mr-2"></i>
                <span class="text-gray-500">Inicio</span>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-900 font-medium">Clientes</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('header')
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                <i class="fas fa-users text-primary-600 mr-3"></i>
                Gestión de Clientes
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Administra la información de todos los clientes registrados en el sistema
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('clientes.create') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                <i class="fas fa-plus mr-2"></i>
                Nuevo Cliente
            </a>
        </div>
    </div>
@endsection

@section('content')
    <!-- Filtros y Búsqueda -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
                <i class="fas fa-search text-gray-400 mr-2"></i>
                Filtros de Búsqueda
            </h3>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar Cliente</label>
                    <div class="relative">
                        <input type="text" id="search" name="search" 
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                               placeholder="Nombre, DNI o WhatsApp...">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="filter_tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cliente</label>
                    <select id="filter_tipo" name="filter_tipo" 
                            class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                        <option value="">Todos los tipos</option>
                        <!-- Opciones dinámicas -->
                    </select>
                </div>
                <div>
                    <label for="filter_estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select id="filter_estado" name="filter_estado" 
                            class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                        <option value="">Todos los estados</option>
                        <!-- Opciones dinámicas -->
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas Resumidas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Clientes</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $clientes->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user-check text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Activos</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $clientes->where('estadoCliente.nombreEstado', 'Activo')->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-store text-purple-600"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Mayoristas</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $clientes->where('tipoCliente.nombreTipo', 'Mayorista')->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Minoristas</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $clientes->where('tipoCliente.nombreTipo', 'Minorista')->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Clientes -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-table text-gray-400 mr-2"></i>
                    Lista de Clientes
                </h3>
                <div class="text-sm text-gray-500">
                    Mostrando {{ $clientes->count() }} {{ $clientes->count() === 1 ? 'cliente' : 'clientes' }}
                </div>
            </div>
        </div>
        
        <div class="overflow-hidden">
            @if($clientes->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center space-x-1">
                                        <span>Cliente</span>
                                        <i class="fas fa-sort text-gray-400"></i>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center space-x-1">
                                        <span>Contacto</span>
                                        <i class="fas fa-sort text-gray-400"></i>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center space-x-1">
                                        <span>Tipo</span>
                                        <i class="fas fa-sort text-gray-400"></i>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center space-x-1">
                                        <span>Estado</span>
                                        <i class="fas fa-sort text-gray-400"></i>
                                    </div>
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($clientes as $cliente)
                                <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-primary-600">
                                                        {{ strtoupper(substr($cliente->nombre, 0, 1) . substr($cliente->apellido, 0, 1)) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $cliente->nombre }} {{ $cliente->apellido }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    DNI: {{ $cliente->DNI }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="flex items-center mb-1">
                                                <i class="fab fa-whatsapp text-green-500 mr-2"></i>
                                                {{ $cliente->whatsapp }}
                                            </div>
                                            @if($cliente->mail)
                                                <div class="flex items-center text-gray-500">
                                                    <i class="fas fa-envelope mr-2"></i>
                                                    {{ $cliente->mail }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $tipoColor = $cliente->tipoCliente->nombreTipo === 'Mayorista' ? 'purple' : 'blue';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $tipoColor }}-100 text-{{ $tipoColor }}-800">
                                            <i class="fas fa-{{ $cliente->tipoCliente->nombreTipo === 'Mayorista' ? 'store' : 'user' }} mr-1"></i>
                                            {{ $cliente->tipoCliente->nombreTipo ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $estadoColor = match($cliente->estadoCliente->nombreEstado ?? '') {
                                                'Activo' => 'green',
                                                'Inactivo' => 'red',
                                                'Suspendido' => 'yellow',
                                                default => 'gray'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $estadoColor }}-100 text-{{ $estadoColor }}-800">
                                            <div class="w-1.5 h-1.5 bg-{{ $estadoColor }}-400 rounded-full mr-2"></div>
                                            {{ $cliente->estadoCliente->nombreEstado ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a href="{{ route('clientes.show', $cliente->clienteID) }}" 
                                               class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                                                <i class="fas fa-eye mr-1"></i>
                                                Ver
                                            </a>
                                            
                                            @if($cliente->estadoCliente->nombreEstado === 'Activo')
                                                <a href="{{ route('clientes.edit', $cliente->clienteID) }}" 
                                                   class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                                                    <i class="fas fa-edit mr-1"></i>
                                                    Editar
                                                </a>
                                                
                                                <a href="{{ route('clientes.confirm-delete', $cliente->clienteID) }}" 
                                                   class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-150 ease-in-out">
                                                    <i class="fas fa-user-times mr-1"></i>
                                                    Dar de Baja
                                                </a>
                                            @else
                                                <span class="inline-flex items-center px-3 py-1 text-xs font-medium text-gray-500 bg-gray-100 rounded-md">
                                                    <i class="fas fa-ban mr-1"></i>
                                                    Inactivo
                                                </span>
                                            @endif
                                            
                                            @if($cliente->estadoCliente->nombreEstado === 'Activo')
                                                <form action="{{ route('clientes.destroy', $cliente->clienteID) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out"
                                                            onclick="return confirm('¿Estás seguro de que deseas eliminar permanentemente este cliente?\n\nEsta acción NO se puede deshacer.\n\nPara dar de baja use la opción correspondiente.')">
                                                        <i class="fas fa-trash mr-1"></i>
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Estado Vacío -->
                <div class="text-center py-12">
                    <div class="w-24 h-24 mx-auto mb-4 text-gray-300">
                        <i class="fas fa-users text-6xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay clientes registrados</h3>
                    <p class="text-sm text-gray-500 mb-6">Comienza agregando el primer cliente al sistema.</p>
                    <a href="{{ route('clientes.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <i class="fas fa-plus mr-2"></i>
                        Crear Primer Cliente
                    </a>
                </div>
            @endif
        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidad de búsqueda en tiempo real
    const searchInput = document.getElementById('search');
    const tableRows = document.querySelectorAll('tbody tr');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Animación de aparición de estadísticas
    const stats = document.querySelectorAll('.grid .bg-white');
    stats.forEach((stat, index) => {
        setTimeout(() => {
            stat.style.opacity = '0';
            stat.style.transform = 'translateY(20px)';
            stat.style.transition = 'all 0.3s ease-out';
            setTimeout(() => {
                stat.style.opacity = '1';
                stat.style.transform = 'translateY(0)';
            }, 50);
        }, index * 100);
    });
});
</script>
@endpush
@endsection