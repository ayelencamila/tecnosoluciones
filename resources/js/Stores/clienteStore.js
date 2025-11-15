import { defineStore } from 'pinia';

export const useClienteStore = defineStore('cliente', {
  state: () => ({
    clientes: [],
    loading: false,
    meta: {
      current_page: 1,
      per_page: 10,
      total: 0,
      last_page: 1,
      from: null,
      to: null,
    },
    stats: {
      total: 0,
      activos: 0,
      mayoristas: 0,
      minoristas: 0,
    },
    filters: {
      search: '',
      tipo_cliente_id: null,
      estado_cliente_id: null,
      provincia_id: null,
      localidad_id: null,
      sortBy: 'nombre',
    },
  }),

  getters: {
    isLoading: (state) => state.loading,
    
    filteredClientes: (state) => {
      let filtered = [...state.clientes];
      
      if (state.filters.search) {
        const search = state.filters.search.toLowerCase();
        filtered = filtered.filter(cliente => 
          cliente.nombre?.toLowerCase().includes(search) ||
          cliente.apellido?.toLowerCase().includes(search) ||
          cliente.DNI?.includes(search) ||
          cliente.mail?.toLowerCase().includes(search) ||
          cliente.whatsapp?.includes(search)
        );
      }
      
      if (state.filters.tipo_cliente_id) {
        filtered = filtered.filter(cliente => 
          cliente.tipoClienteID === state.filters.tipo_cliente_id
        );
      }
      
      if (state.filters.estado_cliente_id) {
        filtered = filtered.filter(cliente => 
          cliente.estadoClienteID === state.filters.estado_cliente_id
        );
      }
      
      if (state.filters.provincia_id) {
        filtered = filtered.filter(cliente => 
          cliente.direccion?.localidad?.provincia?.provinciaID === state.filters.provincia_id
        );
      }
      
      if (state.filters.localidad_id) {
        filtered = filtered.filter(cliente => 
          cliente.direccion?.localidadID === state.filters.localidad_id
        );
      }
      
      return filtered;
    },

    totalClientes: (state) => state.stats.total || state.clientes.length,
    
    clientesActivos: (state) => state.stats.activos || 0,
    
    clientesMayoristas: (state) => state.stats.mayoristas || 0,
    
    clientesMinoristas: (state) => state.stats.minoristas || 0,
  },

  actions: {
    setClientes(clientes) {
      this.clientes = Array.isArray(clientes) ? clientes : [];
    },

    setMeta(meta) {
      if (meta) {
        this.meta = {
          current_page: meta.current_page || 1,
          per_page: meta.per_page || 10,
          total: meta.total || 0,
          last_page: meta.last_page || 1,
          from: meta.from || null,
          to: meta.to || null,
        };
      }
    },

    setStats(stats) {
      if (stats) {
        this.stats = {
          total: stats.total || 0,
          activos: stats.activos || 0,
          mayoristas: stats.mayoristas || 0,
          minoristas: stats.minoristas || 0,
        };
      }
    },

    setLoading(loading) {
      this.loading = loading;
    },

    updateFilters(filters) {
      this.filters = { ...this.filters, ...filters };
    },

    resetFilters() {
      this.filters = {
        search: '',
        tipo_cliente_id: null,
        estado_cliente_id: null,
        provincia_id: null,
        localidad_id: null,
        sortBy: 'nombre',
      };
    },

    setPagination(pagination) {
      this.meta = { ...this.meta, ...pagination };
    },

    addCliente(cliente) {
      this.clientes.unshift(cliente);
      // Actualizar stats
      this.stats.total++;
      if (cliente.estado_cliente?.nombreEstado?.toLowerCase() === 'activo') {
        this.stats.activos++;
      }
      if (cliente.tipo_cliente?.nombreTipo?.toLowerCase() === 'mayorista') {
        this.stats.mayoristas++;
      } else if (cliente.tipo_cliente?.nombreTipo?.toLowerCase() === 'minorista') {
        this.stats.minoristas++;
      }
    },

    updateCliente(updatedCliente) {
      const index = this.clientes.findIndex(c => c.clienteID === updatedCliente.clienteID);
      if (index !== -1) {
        const oldCliente = this.clientes[index];
        this.clientes[index] = updatedCliente;
        
        // Actualizar stats si es necesario
        this.recalculateStats();
      }
    },

    removeCliente(clienteId) {
      const clienteIndex = this.clientes.findIndex(c => c.clienteID === clienteId);
      if (clienteIndex !== -1) {
        const cliente = this.clientes[clienteIndex];
        this.clientes.splice(clienteIndex, 1);
        
        // Actualizar stats
        this.stats.total--;
        if (cliente.estado_cliente?.nombreEstado?.toLowerCase() === 'activo') {
          this.stats.activos--;
        }
        if (cliente.tipo_cliente?.nombreTipo?.toLowerCase() === 'mayorista') {
          this.stats.mayoristas--;
        } else if (cliente.tipo_cliente?.nombreTipo?.toLowerCase() === 'minorista') {
          this.stats.minoristas--;
        }
      }
    },

    recalculateStats() {
      this.stats = {
        total: this.clientes.length,
        activos: this.clientes.filter(c => c.estado_cliente?.nombreEstado?.toLowerCase() === 'activo').length,
        mayoristas: this.clientes.filter(c => c.tipo_cliente?.nombreTipo?.toLowerCase() === 'mayorista').length,
        minoristas: this.clientes.filter(c => c.tipo_cliente?.nombreTipo?.toLowerCase() === 'minorista').length,
      };
    },

    // Métodos de utilidad
    getClienteById(clienteId) {
      return this.clientes.find(c => c.clienteID === clienteId);
    },

    getClientesByTipo(tipoId) {
      return this.clientes.filter(c => c.tipoClienteID === tipoId);
    },

    getClientesByEstado(estadoId) {
      return this.clientes.filter(c => c.estadoClienteID === estadoId);
    },

    searchClientes(searchTerm) {
      if (!searchTerm) return this.clientes;
      
      const search = searchTerm.toLowerCase();
      return this.clientes.filter(cliente => 
        cliente.nombre?.toLowerCase().includes(search) ||
        cliente.apellido?.toLowerCase().includes(search) ||
        cliente.DNI?.includes(search) ||
        cliente.mail?.toLowerCase().includes(search) ||
        cliente.whatsapp?.includes(search)
      );
    },
  },
});