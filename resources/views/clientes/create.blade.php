@extends('layouts.app')

@section('title', 'Nuevo Cliente - TecnoSoluciones')

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
                    <span class="text-gray-900 font-medium">Nuevo Cliente</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('header')
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                <i class="fas fa-user-plus text-primary-600 mr-3"></i>
                Registrar Nuevo Cliente
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Complete la información requerida para registrar un nuevo cliente en el sistema
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('clientes.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver al Listado
            </a>
        </div>
    </div>
@endsection

@section('content')
    <!-- Formulario de Registro -->
    <form action="{{ route('clientes.store') }}" method="POST" class="space-y-8">
        @csrf
        
        <!-- Indicador de Progreso -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-tasks text-gray-400 mr-2"></i>
                    Progreso del Registro
                </h3>
            </div>
            <div class="px-6 py-4">
                <div class="flex items-center">
                    <div class="flex items-center text-sm">
                        <div class="flex-shrink-0 w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center">
                            <span class="text-xs font-medium">1</span>
                        </div>
                        <div class="ml-3 text-primary-600 font-medium">Datos Personales</div>
                    </div>
                    <div class="flex-1 h-px bg-gray-300 mx-4"></div>
                    <div class="flex items-center text-sm">
                        <div class="flex-shrink-0 w-6 h-6 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center">
                            <span class="text-xs font-medium">2</span>
                        </div>
                        <div class="ml-3 text-gray-500">Dirección</div>
                    </div>
                    <div class="flex-1 h-px bg-gray-300 mx-4"></div>
                    <div class="flex items-center text-sm">
                        <div class="flex-shrink-0 w-6 h-6 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center">
                            <span class="text-xs font-medium">3</span>
                        </div>
                        <div class="ml-3 text-gray-500">Configuración</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 1: Datos Personales -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200" id="seccion-datos-personales">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-primary-600"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900">Información Personal</h3>
                        <p class="text-sm text-gray-500">Datos básicos de identificación del cliente</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label for="nombre" class="block text-sm font-medium text-gray-700">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nombre" id="nombre" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('nombre') border-red-300 @enderror" 
                               value="{{ old('nombre') }}" 
                               placeholder="Ingrese el nombre">
                        @error('nombre')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="apellido" class="block text-sm font-medium text-gray-700">
                            Apellido <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="apellido" id="apellido" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('apellido') border-red-300 @enderror" 
                               value="{{ old('apellido') }}" 
                               placeholder="Ingrese el apellido">
                        @error('apellido')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="DNI" class="block text-sm font-medium text-gray-700">
                            Documento de Identidad <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="DNI" id="DNI" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('DNI') border-red-300 @enderror" 
                               value="{{ old('DNI') }}" 
                               placeholder="Ej: 12345678">
                        @error('DNI')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="mail" class="block text-sm font-medium text-gray-700">
                            Correo Electrónico
                        </label>
                        <input type="email" name="mail" id="mail" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('mail') border-red-300 @enderror" 
                               value="{{ old('mail') }}" 
                               placeholder="ejemplo@correo.com">
                        @error('mail')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="whatsapp" class="block text-sm font-medium text-gray-700">
                            <i class="fab fa-whatsapp text-green-500 mr-1"></i>
                            WhatsApp
                        </label>
                        <input type="text" name="whatsapp" id="whatsapp" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('whatsapp') border-red-300 @enderror" 
                               value="{{ old('whatsapp') }}" 
                               placeholder="Ej: +54 9 11 1234-5678">
                        @error('whatsapp')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="telefono" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-phone text-gray-400 mr-1"></i>
                            Teléfono Fijo
                        </label>
                        <input type="text" name="telefono" id="telefono" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('telefono') border-red-300 @enderror" 
                               value="{{ old('telefono') }}" 
                               placeholder="Ej: 011 4123-4567">
                        @error('telefono')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 2: Dirección -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200" id="seccion-direccion">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-blue-600"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900">Información de Dirección</h3>
                        <p class="text-sm text-gray-500">Ubicación geográfica del cliente</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label for="calle" class="block text-sm font-medium text-gray-700">
                            Calle <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="calle" id="calle" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('calle') border-red-300 @enderror" 
                               value="{{ old('calle') }}" 
                               placeholder="Ej: Av. Corrientes">
                        @error('calle')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="altura" class="block text-sm font-medium text-gray-700">
                            Altura <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="altura" id="altura" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('altura') border-red-300 @enderror" 
                               value="{{ old('altura') }}" 
                               placeholder="Ej: 1234">
                        @error('altura')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="pisoDepto" class="block text-sm font-medium text-gray-700">
                            Piso/Departamento
                        </label>
                        <input type="text" name="pisoDepto" id="pisoDepto" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('pisoDepto') border-red-300 @enderror" 
                               value="{{ old('pisoDepto') }}" 
                               placeholder="Ej: 3° A">
                        @error('pisoDepto')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="barrio" class="block text-sm font-medium text-gray-700">
                            Barrio
                        </label>
                        <input type="text" name="barrio" id="barrio" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('barrio') border-red-300 @enderror" 
                               value="{{ old('barrio') }}" 
                               placeholder="Ej: Microcentro">
                        @error('barrio')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="codigoPostal" class="block text-sm font-medium text-gray-700">
                            Código Postal <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="codigoPostal" id="codigoPostal" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('codigoPostal') border-red-300 @enderror" 
                               value="{{ old('codigoPostal') }}" 
                               placeholder="Ej: 1043">
                        @error('codigoPostal')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="provincia_id" class="block text-sm font-medium text-gray-700">
                            Provincia <span class="text-red-500">*</span>
                        </label>
                        <select name="provincia_id" id="provincia_id" 
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('provincia_id') border-red-300 @enderror">
                            <option value="">Seleccione una Provincia</option>
                            @foreach($provincias as $provincia)
                                <option value="{{ $provincia->provinciaID }}" {{ old('provincia_id') == $provincia->provinciaID ? 'selected' : '' }}>
                                    {{ $provincia->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('provincia_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="localidad_id" class="block text-sm font-medium text-gray-700">
                            Localidad <span class="text-red-500">*</span>
                        </label>
                        <select name="localidad_id" id="localidad_id" 
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('localidad_id') border-red-300 @enderror">
                            <option value="">Seleccione una Localidad</option>
                        </select>
                        @error('localidad_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Seleccione primero una provincia para cargar las localidades
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 3: Configuración del Cliente -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200" id="seccion-configuracion">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-cog text-green-600"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900">Configuración del Cliente</h3>
                        <p class="text-sm text-gray-500">Tipo de cliente y configuración comercial</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label for="tipo_cliente_id" class="block text-sm font-medium text-gray-700">
                            Tipo de Cliente <span class="text-red-500">*</span>
                        </label>
                        <select name="tipo_cliente_id" id="tipo_cliente_id" 
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('tipo_cliente_id') border-red-300 @enderror">
                            <option value="">Seleccione un Tipo</option>
                            @foreach($tiposCliente as $tipo)
                                <option value="{{ $tipo->tipoClienteID }}" data-nombre="{{ $tipo->nombreTipo }}" {{ old('tipo_cliente_id') == $tipo->tipoClienteID ? 'selected' : '' }}>
                                    <i class="fas fa-{{ $tipo->nombreTipo === 'Mayorista' ? 'store' : 'user' }}"></i>
                                    {{ $tipo->nombreTipo }}
                                </option>
                            @endforeach
                        </select>
                        @error('tipo_cliente_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="estado_cliente_id" class="block text-sm font-medium text-gray-700">
                            Estado del Cliente <span class="text-red-500">*</span>
                        </label>
                        <select name="estado_cliente_id" id="estado_cliente_id" 
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('estado_cliente_id') border-red-300 @enderror">
                            <option value="">Seleccione un Estado</option>
                            @foreach($estadosCliente as $estado)
                                <option value="{{ $estado->estadoClienteID }}" {{ old('estado_cliente_id') == $estado->estadoClienteID ? 'selected' : '' }}>
                                    {{ $estado->nombreEstado }}
                                </option>
                            @endforeach
                        </select>
                        @error('estado_cliente_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- Información sobre tipos de cliente -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-blue-800">Información sobre Tipos de Cliente</h4>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li><strong>Minorista:</strong> Cliente final, compras individuales</li>
                                    <li><strong>Mayorista:</strong> Cliente comercial con cuenta corriente y límite de crédito</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 4: Cuenta Corriente (Condicional) -->
        <div id="cuentaCorrienteFields" class="hidden bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-credit-card text-purple-600"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900">Configuración de Cuenta Corriente</h3>
                        <p class="text-sm text-gray-500">Configuración comercial para clientes mayoristas</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-1">
                        <label for="limiteCredito" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-dollar-sign text-green-500 mr-1"></i>
                            Límite de Crédito
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">$</span>
                            <input type="number" step="0.01" name="limiteCredito" id="limiteCredito" 
                                   class="block w-full pl-8 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('limiteCredito') border-red-300 @enderror" 
                                   value="{{ old('limiteCredito', 0.00) }}" 
                                   placeholder="0.00">
                        </div>
                        @error('limiteCredito')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Monto máximo de crédito disponible</p>
                    </div>
                    
                    <div class="space-y-1">
                        <label for="diasGracia" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-calendar-alt text-blue-500 mr-1"></i>
                            Días de Gracia
                        </label>
                        <input type="number" name="diasGracia" id="diasGracia" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('diasGracia') border-red-300 @enderror" 
                               value="{{ old('diasGracia', 0) }}" 
                               placeholder="0" 
                               min="0">
                        @error('diasGracia')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Días adicionales para el pago</p>
                    </div>
                    
                    <div class="space-y-1">
                        <label for="estado_cuenta_corriente_id" class="block text-sm font-medium text-gray-700">
                            Estado de Cuenta <span class="text-red-500">*</span>
                        </label>
                        <select name="estado_cuenta_corriente_id" id="estado_cuenta_corriente_id" 
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('estado_cuenta_corriente_id') border-red-300 @enderror">
                            <option value="">Seleccione un Estado</option>
                            @foreach($estadosCuentaCorriente as $estadoCC)
                                <option value="{{ $estadoCC->estadoCuentaCorrienteID }}" {{ old('estado_cuenta_corriente_id') == $estadoCC->estadoCuentaCorrienteID ? 'selected' : '' }}>
                                    {{ $estadoCC->nombreEstado }}
                                </option>
                            @endforeach
                        </select>
                        @error('estado_cuenta_corriente_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- Información sobre cuenta corriente -->
                <div class="mt-6 p-4 bg-purple-50 rounded-lg border border-purple-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-lightbulb text-purple-400"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-purple-800">Información sobre Cuenta Corriente</h4>
                            <div class="mt-2 text-sm text-purple-700">
                                <p>La cuenta corriente permite al cliente realizar compras a crédito dentro del límite establecido. El saldo inicial siempre comienza en $0.00.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-asterisk text-red-500 mr-1"></i>
                        Los campos marcados con asterisco son obligatorios
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('clientes.index') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                            <i class="fas fa-times mr-2"></i>
                            Cancelar
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                            <i class="fas fa-save mr-2"></i>
                            Registrar Cliente
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoClienteSelect = document.getElementById('tipo_cliente_id');
    const cuentaCorrienteFields = document.getElementById('cuentaCorrienteFields');
    const provinciaSelect = document.getElementById('provincia_id');
    const localidadSelect = document.getElementById('localidad_id');
    const oldLocalidadId = "{{ old('localidad_id') }}";

    // Funcionalidad de cuenta corriente
    function toggleCuentaCorrienteFields() {
        const selectedOption = tipoClienteSelect.options[tipoClienteSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.nombre === 'Mayorista') {
            cuentaCorrienteFields.classList.remove('hidden');
            // Actualizar indicador de progreso
            updateProgressStep(3, true);
        } else {
            cuentaCorrienteFields.classList.add('hidden');
            updateProgressStep(3, false);
        }
    }

    // Funcionalidad de localidades
    function loadLocalidades(provinciaId) {
        localidadSelect.innerHTML = '<option value="">Cargando Localidades...</option>';
        if (!provinciaId) {
            localidadSelect.innerHTML = '<option value="">Seleccione una Provincia primero</option>';
            return;
        }

        fetch(`/api/provincias/${provinciaId}/localidades`)
            .then(response => response.json())
            .then(data => {
                localidadSelect.innerHTML = '<option value="">Seleccione una Localidad</option>';
                data.forEach(localidad => {
                    const option = document.createElement('option');
                    option.value = localidad.localidadID;
                    option.textContent = localidad.nombre;
                    if (localidad.localidadID == oldLocalidadId) {
                        option.selected = true;
                    }
                    localidadSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error al cargar localidades:', error);
                localidadSelect.innerHTML = '<option value="">Error al cargar localidades</option>';
            });
    }

    // Actualizar indicador de progreso
    function updateProgressStep(step, active) {
        // Esta función se puede expandir para actualizar el indicador visual de progreso
        console.log(`Paso ${step} ${active ? 'activado' : 'desactivado'}`);
    }

    // Event listeners
    tipoClienteSelect.addEventListener('change', toggleCuentaCorrienteFields);
    provinciaSelect.addEventListener('change', function() {
        loadLocalidades(this.value);
    });

    // Inicialización
    toggleCuentaCorrienteFields();
    
    if (provinciaSelect.value) {
        loadLocalidades(provinciaSelect.value);
    }

    // Validación en tiempo real
    const requiredFields = document.querySelectorAll('input[required], select[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('border-red-300');
            } else {
                this.classList.remove('border-red-300');
                this.classList.add('border-green-300');
            }
        });
    });
});
</script>
@endpush
@endsection