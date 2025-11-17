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
        // Nota: Este getter también actualiza el estado, lo cual es una práctica un poco inusual
        // para getters puros, pero es efectivo para mantener los totales sincronizados reactivamente.
        // Una alternativa sería tener un action 'recalcularTotales' que se llame cuando el estado relevante cambie.
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
                            descuentoItemMonto += descuento.valor;
                        }
                    });
                }

                totalDescuentosItems += descuentoItemMonto;
            });

            // --- 2. Calcular Descuentos Globales ---
            let totalDescuentosGlobales = 0;
            // Asegúrate de que state.descuentosGlobalesAplicados exista y sea un array
            if (state.descuentosGlobalesAplicados && Array.isArray(state.descuentosGlobalesAplicados)) {
                state.descuentosGlobalesAplicados.forEach(descuento => {
                    if (descuento.tipo === 'porcentaje') {
                        // Los descuentos globales se aplican sobre el subtotal bruto
                        totalDescuentosGlobales += subtotalBruto * (descuento.valor / 100);
                    } else if (descuento.tipo === 'monto_fijo') {
                        totalDescuentosGlobales += descuento.valor;
                    }
                });
            }


            const totalDescuentos = totalDescuentosItems + totalDescuentosGlobales;
            const totalNeto = subtotalBruto - totalDescuentos;

            // Actualizar el estado del store con los totales calculados
            // Esto es lo que hace que los totales sean reactivos y se actualicen automáticamente
            state.subtotalBruto = subtotalBruto;
            state.totalDescuentos = totalDescuentos;
            state.totalNeto = totalNeto;

            return {
                subtotalBruto: subtotalBruto.toFixed(2),
                totalDescuentos: totalDescuentos.toFixed(2),
                totalNeto: totalNeto.toFixed(2),
            };
        }
    },

    actions: {
        // Método para agregar un producto al carrito
        agregarItem(producto, cantidad, precioUnitario) {
            // Verificar si el producto ya está en el carrito para no duplicar
            const existingItemIndex = this.items.findIndex(item => item.productoID === producto.id);

            if (existingItemIndex !== -1) {
                // Si ya existe, simplemente actualiza la cantidad
                this.items[existingItemIndex].cantidad += cantidad;
            } else {
                const newItem = {
                    productoID: producto.id,
                    codigo: producto.codigo,
                    nombre: producto.nombre,
                    stockActual: producto.stockActual,
                    cantidad: cantidad,
                    precioUnitario: precioUnitario,
                    precio_producto_id: producto.precio_producto_id || null,
                    descuentos_item: [], // Para descuentos específicos del ítem
                };
                this.items.push(newItem);
            }
            this.calcularTotales; // Recalcular inmediatamente
        },

        // Método para actualizar la cantidad de un item existente
        actualizarCantidadItem(index, nuevaCantidad) {
            if (nuevaCantidad <= 0) {
                this.removerItem(index); // Si la cantidad es 0 o menos, remover
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
        // Esto es solo un ejemplo; la lógica real de aplicación de descuentos podría ser más compleja
        aplicarDescuentoGlobal(descuento) {
            // Verificar si el descuento ya está aplicado
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