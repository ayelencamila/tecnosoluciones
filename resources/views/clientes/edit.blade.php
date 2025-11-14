@extends('layouts.app')

@section('title', 'Editar Cliente - TecnoSoluciones')

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
        <li>
            <a href="{{ route('clientes.show', $cliente->clienteID) }}" class="text-gray-500 hover:text-gray-700">
                {{ $cliente->nombre }} {{ $cliente->apellido }}
            </a>
        </li>
        <li>
            <i class="fas fa-chevron-right text-gray-400"></i>
        </li>
        <li class="text-gray-900 font-medium">
            Editar
        </li>
    </ol>
@endsection

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-edit text-primary-600 mr-2"></i>
                Editar Cliente
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                Modifica los datos de {{ $cliente->nombre }} {{ $cliente->apellido }} (DNI: {{ $cliente->DNI }})
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('clientes.show', $cliente->clienteID) }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                <i class="fas fa-eye mr-2"></i>
                Ver Detalles
            </a>
            <a href="{{ route('clientes.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver al Listado
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <form action="{{ route('clientes.update', $cliente->clienteID) }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')
            
            <!-- Datos Personales -->
            <div class="px-6 py-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-user text-primary-600 mr-2"></i>
                    Datos Personales
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               value="{{ old('nombre', $cliente->nombre) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('nombre') border-red-300 @enderror" 
                               required>
                        @error('nombre')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2">
                            Apellido <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="apellido" 
                               name="apellido" 
                               value="{{ old('apellido', $cliente->apellido) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('apellido') border-red-300 @enderror" 
                               required>
                        @error('apellido')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="DNI" class="block text-sm font-medium text-gray-700 mb-2">
                            DNI <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="DNI" 
                               name="DNI" 
                               value="{{ old('DNI', $cliente->DNI) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('DNI') border-red-300 @enderror" 
                               required>
                        @error('DNI')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="mail" class="block text-sm font-medium text-gray-700 mb-2">
                            Correo Electrónico
                        </label>
                        <input type="email" 
                               id="mail" 
                               name="mail" 
                               value="{{ old('mail', $cliente->mail) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('mail') border-red-300 @enderror">
                        @error('mail')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-2">
                            WhatsApp
                        </label>
                        <input type="text" 
                               id="whatsapp" 
                               name="whatsapp" 
                               value="{{ old('whatsapp', $cliente->whatsapp) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('whatsapp') border-red-300 @enderror">
                        @error('whatsapp')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">
                            Teléfono
                        </label>
                        <input type="text" 
                               id="telefono" 
                               name="telefono" 
                               value="{{ old('telefono', $cliente->telefono) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('telefono') border-red-300 @enderror">
                        @error('telefono')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Dirección -->
            <div class="px-6 py-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-map-marker-alt text-primary-600 mr-2"></i>
                    Dirección
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="provincia_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Provincia <span class="text-red-500">*</span>
                        </label>
                        <select id="provincia_id" 
                                name="provincia_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('provincia_id') border-red-300 @enderror" 
                                required>
                            <option value="">Selecciona una provincia</option>
                            @foreach($provincias as $provincia)
                                <option value="{{ $provincia->provinciaID }}" 
                                        @if(old('provincia_id', $cliente->direccion->localidad->provinciaID ?? '') == $provincia->provinciaID) selected @endif>
                                    {{ $provincia->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('provincia_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="localidad_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Localidad <span class="text-red-500">*</span>
                        </label>
                        <select id="localidad_id" 
                                name="localidad_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('localidad_id') border-red-300 @enderror" 
                                required>
                            <option value="">Selecciona una localidad</option>
                            @if($cliente->direccion && $cliente->direccion->localidad)
                                <option value="{{ $cliente->direccion->localidad->localidadID }}" selected>
                                    {{ $cliente->direccion->localidad->nombre }}
                                </option>
                            @endif
                        </select>
                        @error('localidad_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="calle" class="block text-sm font-medium text-gray-700 mb-2">
                            Calle <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="calle" 
                               name="calle" 
                               value="{{ old('calle', $cliente->direccion->calle ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('calle') border-red-300 @enderror" 
                               required>
                        @error('calle')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="altura" class="block text-sm font-medium text-gray-700 mb-2">
                            Altura <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="altura" 
                               name="altura" 
                               value="{{ old('altura', $cliente->direccion->altura ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('altura') border-red-300 @enderror" 
                               required>
                        @error('altura')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="pisoDepto" class="block text-sm font-medium text-gray-700 mb-2">
                            Piso/Departamento
                        </label>
                        <input type="text" 
                               id="pisoDepto" 
                               name="pisoDepto" 
                               value="{{ old('pisoDepto', $cliente->direccion->pisoDepto ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('pisoDepto') border-red-300 @enderror">
                        @error('pisoDepto')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="barrio" class="block text-sm font-medium text-gray-700 mb-2">
                            Barrio
                        </label>
                        <input type="text" 
                               id="barrio" 
                               name="barrio" 
                               value="{{ old('barrio', $cliente->direccion->barrio ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('barrio') border-red-300 @enderror">
                        @error('barrio')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="codigoPostal" class="block text-sm font-medium text-gray-700 mb-2">
                            Código Postal <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="codigoPostal" 
                               name="codigoPostal" 
                               value="{{ old('codigoPostal', $cliente->direccion->codigoPostal ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('codigoPostal') border-red-300 @enderror" 
                               required>
                        @error('codigoPostal')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Clasificación del Cliente -->
            <div class="px-6 py-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-tags text-primary-600 mr-2"></i>
                    Clasificación del Cliente
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="tipo_cliente_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Cliente <span class="text-red-500">*</span>
                        </label>
                        <select id="tipo_cliente_id" 
                                name="tipo_cliente_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('tipo_cliente_id') border-red-300 @enderror" 
                                required>
                            <option value="">Selecciona un tipo</option>
                            @foreach($tiposCliente as $tipo)
                                <option value="{{ $tipo->tipoClienteID }}" 
                                        data-nombre="{{ $tipo->nombreTipo }}"
                                        @if(old('tipo_cliente_id', $cliente->tipoClienteID) == $tipo->tipoClienteID) selected @endif>
                                    {{ $tipo->nombreTipo }} - {{ $tipo->descripcion }}
                                </option>
                            @endforeach
                        </select>
                        @error('tipo_cliente_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="estado_cliente_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Estado del Cliente <span class="text-red-500">*</span>
                        </label>
                        <select id="estado_cliente_id" 
                                name="estado_cliente_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('estado_cliente_id') border-red-300 @enderror" 
                                required>
                            <option value="">Selecciona un estado</option>
                            @foreach($estadosCliente as $estado)
                                <option value="{{ $estado->estadoClienteID }}" 
                                        @if(old('estado_cliente_id', $cliente->estadoClienteID) == $estado->estadoClienteID) selected @endif>
                                    {{ $estado->nombreEstado }} - {{ $estado->descripcion }}
                                </option>
                            @endforeach
                        </select>
                        @error('estado_cliente_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Cuenta Corriente (solo para Mayoristas) -->
            <div id="cuentaCorrienteFields" class="px-6 py-6 @if(!$cliente->cuentaCorriente || $cliente->tipoCliente->nombreTipo !== 'Mayorista') hidden @endif">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-credit-card text-primary-600 mr-2"></i>
                    Cuenta Corriente (Solo para Mayoristas)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="limiteCredito" class="block text-sm font-medium text-gray-700 mb-2">
                            Límite de Crédito
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">$</span>
                            <input type="number" 
                                   id="limiteCredito" 
                                   name="limiteCredito" 
                                   value="{{ old('limiteCredito', $cliente->cuentaCorriente->limiteCredito ?? 0) }}"
                                   step="0.01" 
                                   min="0"
                                   class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('limiteCredito') border-red-300 @enderror">
                        </div>
                        @error('limiteCredito')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="diasGracia" class="block text-sm font-medium text-gray-700 mb-2">
                            Días de Gracia
                        </label>
                        <input type="number" 
                               id="diasGracia" 
                               name="diasGracia" 
                               value="{{ old('diasGracia', $cliente->cuentaCorriente->diasGracia ?? 0) }}"
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('diasGracia') border-red-300 @enderror">
                        @error('diasGracia')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="estado_cuenta_corriente_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Estado de la Cuenta
                        </label>
                        <select id="estado_cuenta_corriente_id" 
                                name="estado_cuenta_corriente_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('estado_cuenta_corriente_id') border-red-300 @enderror">
                            <option value="">Selecciona un estado</option>
                            @foreach($estadosCuentaCorriente as $estado)
                                <option value="{{ $estado->estadoCuentaCorrienteID }}" 
                                        @if(old('estado_cuenta_corriente_id', $cliente->cuentaCorriente->estadoCuentaCorrienteID ?? '') == $estado->estadoCuentaCorrienteID) selected @endif>
                                    {{ $estado->nombreEstado }}
                                </option>
                            @endforeach
                        </select>
                        @error('estado_cuenta_corriente_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Los campos marcados con <span class="text-red-500">*</span> son obligatorios
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('clientes.show', $cliente->clienteID) }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                        <i class="fas fa-save mr-2"></i>
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoClienteSelect = document.getElementById('tipo_cliente_id');
        const cuentaCorrienteFields = document.getElementById('cuentaCorrienteFields');
        const provinciaSelect = document.getElementById('provincia_id');
        const localidadSelect = document.getElementById('localidad_id');

        // Función para mostrar/ocultar campos de cuenta corriente
        function toggleCuentaCorrienteFields() {
            const selectedOption = tipoClienteSelect.options[tipoClienteSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.nombre === 'Mayorista') {
                cuentaCorrienteFields.classList.remove('hidden');
            } else {
                cuentaCorrienteFields.classList.add('hidden');
            }
        }

        // Función para cargar localidades por provincia
        function loadLocalidades(provinciaId, selectedLocalidadId = null) {
            if (!provinciaId) {
                localidadSelect.innerHTML = '<option value="">Selecciona una localidad</option>';
                return;
            }

            localidadSelect.innerHTML = '<option value="">Cargando...</option>';

            fetch(`/api/provincias/${provinciaId}/localidades`)
                .then(response => response.json())
                .then(data => {
                    localidadSelect.innerHTML = '<option value="">Selecciona una localidad</option>';
                    data.forEach(localidad => {
                        const option = document.createElement('option');
                        option.value = localidad.localidadID;
                        option.textContent = localidad.nombre;
                        if (selectedLocalidadId && localidad.localidadID == selectedLocalidadId) {
                            option.selected = true;
                        }
                        localidadSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error cargando localidades:', error);
                    localidadSelect.innerHTML = '<option value="">Error cargando localidades</option>';
                });
        }

        // Event listeners
        tipoClienteSelect.addEventListener('change', toggleCuentaCorrienteFields);
        provinciaSelect.addEventListener('change', function() {
            loadLocalidades(this.value);
        });

        // Inicializar estado actual
        toggleCuentaCorrienteFields();

        // Cargar localidades si hay provincia seleccionada
        const currentProvinciaId = provinciaSelect.value;
        const currentLocalidadId = {{ $cliente->direccion->localidadID ?? 'null' }};
        if (currentProvinciaId) {
            loadLocalidades(currentProvinciaId, currentLocalidadId);
        }
    });
</script>
@endpush