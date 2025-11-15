@extends('layouts.app')

@section('title', 'Detalles del Cliente - TecnoSoluciones')

@section('breadcrumb')
    <ol class="flex items-center space-x-4 text-sm">
        <li>
            <a href="{{ route('clientes.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-users mr-1"></i>
                Clientes
            </a>
        </li>
        <li>
            <i class="fas fa-chevron-right text-gray-400"></i>
        </li>
        <li class="text-gray-900 font-medium">
            {{ $cliente->nombre }} {{ $cliente->apellido }}
        </li>
    </ol>
@endsection

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-user text-primary-600 mr-2"></i>
                {{ $cliente->nombre }} {{ $cliente->apellido }}
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                DNI: {{ $cliente->DNI }} • 
                {{ $cliente->tipoCliente->nombreTipo ?? 'N/A' }} • 
                {{ $cliente->estadoCliente->nombreEstado ?? 'N/A' }}
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('clientes.edit', $cliente->clienteID) }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                <i class="fas fa-edit mr-2"></i>
                Editar Cliente
            </a>
            
            @if($cliente->estadoCliente->nombreEstado === 'Activo')
                <a href="{{ route('clientes.confirm-delete', $cliente->clienteID) }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out">
                    <i class="fas fa-user-times mr-2"></i>
                    Dar de Baja
                </a>
            @endif
            
            <a href="{{ route('clientes.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver al Listado
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Información Principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Datos Personales -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-user text-primary-600 mr-2"></i>
                        Datos Personales
                    </h3>
                </div>
                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Nombre Completo</label>
                                <p class="text-sm text-gray-900">{{ $cliente->nombre }} {{ $cliente->apellido }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">DNI</label>
                                <p class="text-sm text-gray-900">{{ $cliente->DNI }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Email</label>
                                <p class="text-sm text-gray-900">{{ $cliente->mail ?: 'No especificado' }}</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">WhatsApp</label>
                                <p class="text-sm text-gray-900">
                                    @if($cliente->whatsapp)
                                        <i class="fab fa-whatsapp text-green-500 mr-1"></i>
                                        {{ $cliente->whatsapp }}
                                    @else
                                        <span class="text-gray-400">No especificado</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Teléfono</label>
                                <p class="text-sm text-gray-900">{{ $cliente->telefono ?: 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Comercial -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-briefcase text-blue-600 mr-2"></i>
                        Estado Comercial
                    </h3>
                </div>
                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Tipo de Cliente</label>
                            <p class="text-sm text-gray-900">
                                @if($cliente->tipoCliente)
                                    <i class="fas fa-{{ $cliente->tipoCliente->nombreTipo === 'Mayorista' ? 'store' : 'user' }} mr-1"></i>
                                    {{ $cliente->tipoCliente->nombreTipo }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Estado</label>
                            <p class="text-sm text-gray-900">
                                @if($cliente->estadoCliente)
                                    @php
                                        $estadoColor = match($cliente->estadoCliente->nombreEstado) {
                                            'Activo' => 'green',
                                            'Inactivo' => 'red',
                                            'Suspendido' => 'yellow',
                                            default => 'gray'
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $estadoColor }}-100 text-{{ $estadoColor }}-800">
                                        <div class="w-1.5 h-1.5 bg-{{ $estadoColor }}-400 rounded-full mr-2"></div>
                                        {{ $cliente->estadoCliente->nombreEstado }}
                                    </span>
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dirección -->
            @if($cliente->direccion)
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-map-marker-alt text-red-600 mr-2"></i>
                        Dirección
                    </h3>
                </div>
                <div class="px-6 py-6">
                    <div class="space-y-2">
                        <p class="text-sm text-gray-900">
                            <strong>Calle:</strong> {{ $cliente->direccion->calle }} {{ $cliente->direccion->altura }}
                        </p>
                        @if($cliente->direccion->pisoDepto)
                            <p class="text-sm text-gray-900">
                                <strong>Piso/Depto:</strong> {{ $cliente->direccion->pisoDepto }}
                            </p>
                        @endif
                        @if($cliente->direccion->barrio)
                            <p class="text-sm text-gray-900">
                                <strong>Barrio:</strong> {{ $cliente->direccion->barrio }}
                            </p>
                        @endif
                        <p class="text-sm text-gray-900">
                            <strong>Localidad:</strong> {{ $cliente->direccion->localidad->nombre ?? 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-900">
                            <strong>Provincia:</strong> {{ $cliente->direccion->localidad->provincia->nombre ?? 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-900">
                            <strong>Código Postal:</strong> {{ $cliente->direccion->codigoPostal }}
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Cuenta Corriente -->
            @if($cliente->cuentaCorriente)
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-credit-card text-green-600 mr-2"></i>
                        Estado de Cuenta Corriente
                    </h3>
                </div>
                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Saldo Actual</label>
                                <p class="text-lg font-semibold text-gray-900">
                                    ${{ number_format($cliente->cuentaCorriente->saldo, 2, ',', '.') }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Límite de Crédito</label>
                                <p class="text-sm text-gray-900">
                                    ${{ number_format($cliente->cuentaCorriente->limiteCredito, 2, ',', '.') }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Días de Gracia</label>
                                <p class="text-sm text-gray-900">{{ $cliente->cuentaCorriente->diasGracia }} días</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Estado de Cuenta</label>
                                <p class="text-sm text-gray-900">
                                    {{ $cliente->cuentaCorriente->estadoCuentaCorriente->nombreEstado ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar: Historial de Transacciones -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-history text-purple-600 mr-2"></i>
                        Historial de Transacciones
                    </h3>
                </div>
                <div class="px-6 py-6">
                    @if(isset($historialAuditoria) && $historialAuditoria->count() > 0)
                        <div class="space-y-4">
                            @foreach($historialAuditoria->take(10) as $auditoria)
                                <div class="border-l-4 border-blue-200 pl-4 py-3">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $auditoria->accion }}
                                            </p>
                                            @if($auditoria->detalles)
                                                <p class="text-sm text-gray-500 mt-1">{{ $auditoria->detalles }}</p>
                                            @endif
                                            @if($auditoria->motivo)
                                                <p class="text-xs text-gray-400 mt-1">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    {{ $auditoria->motivo }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="text-right text-xs text-gray-500 ml-4">
                                            {{ $auditoria->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($historialAuditoria->count() > 10)
                            <div class="mt-4 text-center">
                                <p class="text-sm text-gray-500">
                                    Mostrando las últimas 10 transacciones de {{ $historialAuditoria->count() }} totales
                                </p>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-history text-gray-300 text-3xl mb-3"></i>
                            <p class="text-sm text-gray-500">No hay transacciones registradas</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection