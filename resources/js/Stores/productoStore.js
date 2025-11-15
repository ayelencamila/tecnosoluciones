import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useProductoStore = defineStore('producto', () => {
    // ============================================
    // ESTADO (La "nevera central" de productos)
    // ============================================
    const productos = ref([]);
    const producto = ref(null);
    const categorias = ref([]);
    const estados = ref([]);
    const tiposCliente = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // ============================================
    // GETTERS (Datos calculados/filtrados)
    // ============================================
    
    // Productos activos
    const productosActivos = computed(() => 
        productos.value.filter(p => p.estado?.nombre === 'Activo')
    );

    // Productos con stock bajo
    const productosStockBajo = computed(() => 
        productos.value.filter(p => p.stockActual < p.stockMinimo)
    );

    // Total de productos
    const totalProductos = computed(() => productos.value.length);

    // Buscar producto por código
    const buscarPorCodigo = (codigo) => {
        return productos.value.find(p => p.codigo === codigo);
    };

    // Buscar producto por ID
    const buscarPorId = (id) => {
        return productos.value.find(p => p.id === id);
    };

    // Obtener precio vigente para un tipo de cliente
    const obtenerPrecioVigente = (productoId, tipoClienteId) => {
        const prod = buscarPorId(productoId);
        if (!prod || !prod.precios_vigentes) return null;
        
        const precio = prod.precios_vigentes.find(
            p => p.tipo_cliente_id === tipoClienteId || p.tipoClienteID === tipoClienteId
        );
        return precio ? parseFloat(precio.precio) : null;
    };

    // ============================================
    // ACTIONS (Funciones que modifican el estado)
    // ============================================

    /**
     * Cargar todos los productos con filtros opcionales
     * @param {Object} filtros - { search, categoria, estado, stock_bajo }
     */
    const fetchProductos = async (filtros = {}) => {
        loading.value = true;
        error.value = null;
        
        try {
            const params = new URLSearchParams(filtros).toString();
            const response = await axios.get(`/productos?${params}`);
            
            // Si la respuesta es de Inertia, extraer los datos
            if (response.data.props && response.data.props.productos) {
                productos.value = response.data.props.productos.data || response.data.props.productos;
            } else if (response.data.data) {
                productos.value = response.data.data;
            } else {
                productos.value = response.data;
            }
            
            return productos.value;
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al cargar productos';
            console.error('Error fetchProductos:', err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Cargar un producto específico por ID
     * @param {Number} id - ID del producto
     */
    const fetchProducto = async (id) => {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await axios.get(`/productos/${id}`);
            
            if (response.data.props && response.data.props.producto) {
                producto.value = response.data.props.producto;
            } else {
                producto.value = response.data;
            }
            
            return producto.value;
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al cargar producto';
            console.error('Error fetchProducto:', err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Crear un nuevo producto
     * @param {Object} datos - Datos del producto a crear
     */
    const crearProducto = async (datos) => {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await axios.post('/productos', datos);
            const nuevoProducto = response.data.producto || response.data;
            
            // Agregar a la lista local
            productos.value.unshift(nuevoProducto);
            
            return nuevoProducto;
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al crear producto';
            console.error('Error crearProducto:', err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Actualizar un producto existente
     * @param {Number} id - ID del producto
     * @param {Object} datos - Datos actualizados
     */
    const actualizarProducto = async (id, datos) => {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await axios.put(`/productos/${id}`, datos);
            const productoActualizado = response.data.producto || response.data;
            
            // Actualizar en la lista local
            const index = productos.value.findIndex(p => p.id === id);
            if (index !== -1) {
                productos.value[index] = productoActualizado;
            }
            
            // Actualizar el producto individual si está cargado
            if (producto.value?.id === id) {
                producto.value = productoActualizado;
            }
            
            return productoActualizado;
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al actualizar producto';
            console.error('Error actualizarProducto:', err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Eliminar (dar de baja) un producto
     * @param {Number} id - ID del producto
     * @param {String} motivo - Motivo de la baja
     */
    const eliminarProducto = async (id, motivo) => {
        loading.value = true;
        error.value = null;
        
        try {
            await axios.delete(`/productos/${id}`, { data: { motivo } });
            
            // Remover de la lista local o actualizar estado
            const index = productos.value.findIndex(p => p.id === id);
            if (index !== -1) {
                // Cambiar estado a Inactivo en lugar de eliminar
                if (productos.value[index].estado) {
                    productos.value[index].estado.nombre = 'Inactivo';
                    productos.value[index].estadoProductoID = estados.value.find(e => e.nombre === 'Inactivo')?.id;
                }
            }
            
            return true;
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al eliminar producto';
            console.error('Error eliminarProducto:', err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Cargar catálogos (categorías, estados, tipos de cliente)
     */
    const fetchCatalogos = async () => {
        try {
            // Estos datos normalmente vienen en la respuesta de create/edit
            // Aquí puedes hacer llamadas individuales si tienes endpoints
            // Por ahora, se cargarán cuando uses las vistas de Inertia
            return { categorias: categorias.value, estados: estados.value, tiposCliente: tiposCliente.value };
        } catch (err) {
            console.error('Error fetchCatalogos:', err);
            throw err;
        }
    };

    /**
     * Actualizar stock de un producto (para usar en Ventas/Compras)
     * @param {Number} productoId - ID del producto
     * @param {Number} cantidad - Cantidad a sumar/restar
     * @param {String} tipo - ENTRADA o SALIDA
     */
    const actualizarStock = (productoId, cantidad, tipo = 'SALIDA') => {
        const index = productos.value.findIndex(p => p.id === productoId);
        if (index !== -1) {
            if (tipo === 'ENTRADA') {
                productos.value[index].stockActual += cantidad;
            } else {
                productos.value[index].stockActual -= cantidad;
            }
        }
    };

    /**
     * Verificar disponibilidad de stock
     * @param {Number} productoId - ID del producto
     * @param {Number} cantidadRequerida - Cantidad necesaria
     * @returns {Boolean} - true si hay stock suficiente
     */
    const verificarStock = (productoId, cantidadRequerida) => {
        const prod = buscarPorId(productoId);
        if (!prod) return false;
        return prod.stockActual >= cantidadRequerida;
    };

    /**
     * Limpiar el estado (útil al cerrar sesión)
     */
    const limpiarStore = () => {
        productos.value = [];
        producto.value = null;
        categorias.value = [];
        estados.value = [];
        tiposCliente.value = [];
        error.value = null;
        loading.value = false;
    };

    // ============================================
    // RETURN (Exponer al resto de la aplicación)
    // ============================================
    return {
        // Estado
        productos,
        producto,
        categorias,
        estados,
        tiposCliente,
        loading,
        error,

        // Getters
        productosActivos,
        productosStockBajo,
        totalProductos,
        buscarPorCodigo,
        buscarPorId,
        obtenerPrecioVigente,

        // Actions
        fetchProductos,
        fetchProducto,
        crearProducto,
        actualizarProducto,
        eliminarProducto,
        fetchCatalogos,
        actualizarStock,
        verificarStock,
        limpiarStore,
    };
});
