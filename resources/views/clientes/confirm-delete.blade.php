@extends('layouts.app')

@section('title', 'Dar de Baja Cliente - TecnoSoluciones')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <i class="fas fa-home text-gray-400 mr-2"></i>
                <a href="{{ route('clientes.index') }}" class="text-gray-500 hover:text-gray-700">Inicio</a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('clientes.index') }}" class="text-gray-500 hover:text-gray-700">Clientes</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('clientes.show', $cliente->clienteID) }}" class="text-gray-500 hover:text-gray-700">{{ $cliente->nombre }} {{ $cliente->apellido }}</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-900 font-medium">Dar de Baja</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('header')
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                <i class="fas fa-user-times text-red-600 mr-3"></i>
                Dar de Baja Cliente
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Confirme la baja del cliente e indique el motivo de la operación
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('clientes.show', $cliente->clienteID) }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                <i class="fas fa-arrow-left mr-2"></i>
                Cancelar
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if(count($operacionesPendientes) > 0)
        <!-- Alerta de operaciones pendientes -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-red-800">
                        No es posible dar de baja este cliente
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p class="mb-3">El cliente tiene las siguientes operaciones activas pendientes:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($operacionesPendientes as $operacion)
                                <li>{{ $operacion }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-3 font-medium">
                            Por favor, complete o cancele estas operaciones antes de dar de baja al cliente.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Información del cliente -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-user text-gray-400 mr-2"></i>
                    Información del Cliente
                </h3>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Datos Personales</h4>
                        <div class="space-y-2">
                            <p><strong>Nombre:</strong> {{ $cliente->nombre }} {{ $cliente->apellido }}</p>
                            <p><strong>DNI:</strong> {{ $cliente->DNI }}</p>
                            <p><strong>Email:</strong> {{ $cliente->mail ?: 'No especificado' }}</p>
                            <p><strong>WhatsApp:</strong> {{ $cliente->whatsapp }}</p>
                            @if($cliente->telefono)
                                <p><strong>Teléfono:</strong> {{ $cliente->telefono }}</p>
                            @endif
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Información Comercial</h4>
                        <div class="space-y-2">
                            <p><strong>Tipo:</strong> {{ $cliente->tipoCliente->nombreTipo ?? 'N/A' }}</p>
                            <p><strong>Estado Actual:</strong> 
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ $cliente->estadoCliente->nombreEstado ?? 'N/A' }}
                                </span>
                            </p>
                            @if($cliente->cuentaCorriente)
                                <p><strong>Cuenta Corriente:</strong> 
                                    Límite ${{ number_format($cliente->cuentaCorriente->limiteCredito, 2) }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advertencia -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-yellow-800">
                        Confirmar Baja de Cliente
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p class="mb-2">Al dar de baja este cliente:</p>
                        <ul class="list-disc list-inside space-y-1 mb-3">
                            <li>Su estado cambiará a <strong>"Inactivo"</strong></li>
                            <li>No podrá realizar nuevas operaciones comerciales</li>
                            <li>Su cuenta corriente será deshabilitada (si existe)</li>
                            <li>La operación quedará registrada en el historial del sistema</li>
                        </ul>
                        <p class="font-medium">Esta acción puede revertirse cambiando el estado del cliente posteriormente.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de baja -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-edit text-red-600 mr-2"></i>
                    Motivo de la Baja
                </h3>
            </div>
            <div class="px-6 py-6">
                <form action="{{ route('clientes.dar-de-baja', $cliente->clienteID) }}" method="POST">
                    @csrf
                    
                    <div class="space-y-6">
                        <div>
                            <label for="motivo" class="block text-sm font-medium text-gray-700 mb-2">
                                Motivo de la baja <span class="text-red-500">*</span>
                            </label>
                            <textarea name="motivo" id="motivo" rows="4" 
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('motivo') border-red-300 @enderror" 
                                      placeholder="Indique el motivo por el cual se da de baja este cliente..."
                                      required>{{ old('motivo') }}</textarea>
                            @error('motivo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                Este motivo quedará registrado en el historial de operaciones del sistema.
                            </p>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Esta acción cambiará el estado del cliente a "Inactivo"
                            </div>
                            <div class="flex space-x-3">
                                <a href="{{ route('clientes.show', $cliente->clienteID) }}" 
                                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancelar
                                </a>
                                <button type="submit" 
                                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out"
                                        onclick="return confirm('¿Está seguro de que desea dar de baja este cliente?\n\nEsta acción cambiará su estado a Inactivo.')">
                                    <i class="fas fa-user-times mr-2"></i>
                                    Confirmar Baja
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection