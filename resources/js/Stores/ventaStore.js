import { defineStore } from 'pinia';

export const useVentaStore = defineStore('venta', {
    state: () => ({
        // Datos iniciales de la venta
        clienteSeleccionado: null,
        metodoPago: 'efectivo', // O 'cuenta_corriente', 'tarjeta', etc.
        observaciones: '',

        // Carrito
        items: [], // Array de {productoID, codigo, nombre, stockActual, cantidad, precioUnitario, descuentosAplicados: []}

        // Descuentos aplicados a la venta total
        descuentosGlobalesAplicados: [],

        // Totales calculados (reactivos)
        subtotalBruto: 0,
        totalDescuentos: 0,
        totalNeto: 0,
    }),

    getters: {
        // Getter principal para el cálculo de totales
        calcularTotales(state) {
            let subtotalBruto = 0;
            let totalDescuentosItems = 0;

            // --- 1. Calcular Subtotal Bruto y Descuentos por Item ---
            state.items.forEach(item => {
                const subtotalItem = item.cantidad * item.precioUnitario;
                subtotalBruto += subtotalItem;

                let descuentoItemMonto = 0;

                // Asegúrate de que item.descuentosAplicados exista y sea un array
                if (item.descuentosAplicados && Array.isArray(item.descuentosAplicados)) {
                    item.descuentosAplicados.forEach(descuento => {
                        if (descuento.tipo === 'porcentaje') {
                            descuentoItemMonto += subtotalItem * (descuento.valor / 100);
                        } else if (descuento.tipo === 'monto_fijo') {
                            // Usamos Math.min para asegurar que el descuento no exceda el subtotal del item
                            descuentoItemMonto += Math.min(descuento.valor, subtotalItem);
                        }
                    });
                }
                
                // Agregamos el subtotal calculado y el descuento al item para que sea accesible en el template
                item.subtotalCalculado = subtotalItem;
                item.descuentoItemMonto = descuentoItemMonto;

                totalDescuentosItems += descuentoItemMonto;
            });

            // --- 2. Calcular Descuentos Globales ---
            let totalDescuentosGlobales = 0;
            if (state.descuentosGlobalesAplicados && Array.isArray(state.descuentosGlobalesAplicados)) {
                state.descuentosGlobalesAplicados.forEach(descuento => {
                    if (descuento.tipo === 'porcentaje') {
                        // Los descuentos globales se aplican sobre el subtotal bruto
                        totalDescuentosGlobales += subtotalBruto * (descuento.valor / 100);
                    } else if (descuento.tipo === 'monto_fijo') {
                        // Usamos Math.min para asegurar que el descuento no exceda el subtotal bruto
                         totalDescuentosGlobales += Math.min(descuento.valor, subtotalBruto);
                    }
                });
            }

            const totalDescuentos = totalDescuentosItems + totalDescuentosGlobales;
            // El total neto no puede ser negativo
            const totalNeto = Math.max(0, subtotalBruto - totalDescuentos);

            // Actualizar el estado del store con los totales calculados (para el formulario de Inertia)
            this.subtotalBruto = subtotalBruto;
            this.totalDescuentos = totalDescuentos;
            this.totalNeto = totalNeto;

            // RETORNAMOS NÚMEROS FLOTANTES SIN FORMATO (Punto de la corrección)
            return {
                subtotalBruto: subtotalBruto,
                totalDescuentosItems: totalDescuentosItems, // Agregado para el resumen
                totalDescuentosGlobales: totalDescuentosGlobales, // Agregado para el resumen
                totalNeto: totalNeto,
            };
        }
    },

    actions: {
        setCliente(cliente) {
             this.clienteSeleccionado = cliente;
             // VentaController carga stocks, debemos usar el accessor 'stock_total' si existe
             this.items = this.items.map(item => {
                 const fullProduct = this.productos.find(p => p.id === item.productoID);
                 // Si el cliente cambia, recalcula precios de items si es necesario (lógica avanzada)
                 return item;
             });
             this.calcularTotales;
        },

        // Método para agregar un producto al carrito
        agregarItem(producto, cantidad, precioUnitario) {
             // ... [Resto de la lógica de agregarItem es correcto]
             const existingItemIndex = this.items.findIndex(item => item.productoID === producto.id);

            if (existingItemIndex !== -1) {
                // Si ya existe, simplemente actualiza la cantidad
                this.items[existingItemIndex].cantidad += cantidad;
            } else {
                const newItem = {
                    productoID: producto.id,
                    codigo: producto.codigo,
                    nombre: producto.nombre,
                    // Obtenemos el stock usando el accessor stock_total del modelo de Laravel
                    stockTotal: producto.stock_total, 
                    cantidad: cantidad,
                    precioUnitario: precioUnitario,
                    precio_producto_id: producto.precios.find(p => p.tipoClienteID === this.clienteSeleccionado?.tipoClienteID)?.id || null, // Guardar el ID del precio usado
                    descuentos_item: [], 
                };
                this.items.push(newItem);
            }
            // Ejecuta el getter (es reactivo, pero se llama para forzar el cálculo inicial)
            this.calcularTotales; 
        },

        // Método para actualizar la cantidad de un item existente
        actualizarCantidadItem(index, nuevaCantidad) {
            if (nuevaCantidad <= 0.01) {
                this.removerItem(index); 
            } else {
                this.items[index].cantidad = nuevaCantidad;
                this.calcularTotales;
            }
        },

        // Método para remover un item del carrito
        removerItem(index) {
            this.items.splice(index, 1);
            this.calcularTotales;
        },

        // Método para aplicar un descuento global (al total de la venta)
        aplicarDescuentoGlobal(descuento) {
            const alreadyApplied = this.descuentosGlobalesAplicados.some(d => d.descuento_id === descuento.descuento_id);
            if (!alreadyApplied) {
                this.descuentosGlobalesAplicados.push(descuento);
                this.calcularTotales;
            } else {
                console.warn(`El descuento ${descuento.codigo} ya ha sido aplicado.`);
            }
        },

        // Método para remover un descuento global
        removerDescuentoGlobal(descuentoId) {
            this.descuentosGlobalesAplicados = this.descuentosGlobalesAplicados.filter(d => d.descuento_id !== descuentoId);
            this.calcularTotales;
        },

        // Limpiar el estado después de una venta exitosa
        limpiarVenta() {
            this.clienteSeleccionado = null;
            this.metodoPago = 'efectivo';
            this.observaciones = '';
            this.items = [];
            this.descuentosGlobalesAplicados = [];
            this.subtotalBruto = 0;
            this.totalDescuentos = 0;
            this.totalNeto = 0;
        }
    }
});