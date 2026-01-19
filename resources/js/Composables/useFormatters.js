/**
 * Composable: Formateo de datos (Frontend)
 * 
 * Centraliza lógica de formateo de moneda, fechas, números
 * Beneficios:
 * - Reutilización en múltiples componentes
 * - Consistencia en toda la aplicación
 * - Facilita cambios globales de formato
 * 
 * Uso:
 * import { useFormatters } from '@/Composables/useFormatters';
 * const { formatMoneda, formatFecha } = useFormatters();
 */

export function useFormatters() {
    /**
     * Formatea un valor numérico como moneda argentina
     * 
     * @param {number|string} valor - Valor a formatear
     * @param {string} moneda - Código ISO de moneda (default: ARS)
     * @returns {string} Valor formateado (ej: "$1.234,56")
     */
    const formatMoneda = (valor, moneda = 'ARS') => {
        if (valor === null || valor === undefined || valor === '') return '-';
        
        return Number(valor).toLocaleString('es-AR', {
            style: 'currency',
            currency: moneda,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    };

    /**
     * Formatea una fecha según configuración regional argentina
     * 
     * @param {string|Date} fecha - Fecha a formatear
     * @param {boolean} incluirHora - Si debe incluir hora:minuto (default: false)
     * @returns {string} Fecha formateada (ej: "19/01/2026" o "19/01/2026 14:30")
     */
    const formatFecha = (fecha, incluirHora = false) => {
        if (!fecha) return '-';

        const opciones = {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        };

        if (incluirHora) {
            opciones.hour = '2-digit';
            opciones.minute = '2-digit';
        }

        return new Date(fecha).toLocaleDateString('es-AR', opciones);
    };

    /**
     * Formatea una fecha en formato ISO para input type="date"
     * 
     * @param {string|Date} fecha - Fecha a formatear
     * @returns {string} Fecha en formato YYYY-MM-DD
     */
    const formatFechaISO = (fecha) => {
        if (!fecha) return '';
        const d = new Date(fecha);
        return d.toISOString().split('T')[0];
    };

    /**
     * Formatea un número con separadores de miles
     * 
     * @param {number|string} numero - Número a formatear
     * @param {number} decimales - Cantidad de decimales (default: 0)
     * @returns {string} Número formateado (ej: "1.234")
     */
    const formatNumero = (numero, decimales = 0) => {
        if (numero === null || numero === undefined || numero === '') return '-';

        return Number(numero).toLocaleString('es-AR', {
            minimumFractionDigits: decimales,
            maximumFractionDigits: decimales,
        });
    };

    /**
     * Formatea un porcentaje
     * 
     * @param {number|string} valor - Valor decimal (ej: 0.15 para 15%)
     * @param {number} decimales - Cantidad de decimales (default: 2)
     * @returns {string} Porcentaje formateado (ej: "15,00%")
     */
    const formatPorcentaje = (valor, decimales = 2) => {
        if (valor === null || valor === undefined || valor === '') return '-';

        return Number(valor).toLocaleString('es-AR', {
            style: 'percent',
            minimumFractionDigits: decimales,
            maximumFractionDigits: decimales,
        });
    };

    /**
     * Formatea un CUIT/CUIL con guiones
     * 
     * @param {string} cuit - CUIT sin formato (11 dígitos)
     * @returns {string} CUIT formateado (ej: "20-12345678-9")
     */
    const formatCUIT = (cuit) => {
        if (!cuit) return '-';
        
        const limpio = cuit.replace(/\D/g, '');
        if (limpio.length !== 11) return cuit;
        
        return `${limpio.slice(0, 2)}-${limpio.slice(2, 10)}-${limpio.slice(10)}`;
    };

    /**
     * Formatea un teléfono argentino
     * 
     * @param {string} telefono - Número sin formato
     * @returns {string} Teléfono formateado (ej: "+54 9 11 1234-5678")
     */
    const formatTelefono = (telefono) => {
        if (!telefono) return '-';
        
        const limpio = telefono.replace(/\D/g, '');
        
        // Formato móvil: +54 9 11 1234-5678
        if (limpio.length >= 10) {
            const codigo = limpio.slice(0, 2);
            const area = limpio.slice(2, 4);
            const inicio = limpio.slice(4, 8);
            const fin = limpio.slice(8);
            return `+54 9 ${area} ${inicio}-${fin}`;
        }
        
        return telefono;
    };

    /**
     * Formatea tiempo transcurrido desde una fecha (ej: "hace 2 días")
     * 
     * @param {string|Date} fecha - Fecha a comparar
     * @returns {string} Tiempo relativo
     */
    const formatTiempoRelativo = (fecha) => {
        if (!fecha) return '-';

        const ahora = new Date();
        const pasado = new Date(fecha);
        const diferencia = ahora - pasado;

        const segundos = Math.floor(diferencia / 1000);
        const minutos = Math.floor(segundos / 60);
        const horas = Math.floor(minutos / 60);
        const dias = Math.floor(horas / 24);

        if (segundos < 60) return 'hace un momento';
        if (minutos < 60) return `hace ${minutos} minuto${minutos > 1 ? 's' : ''}`;
        if (horas < 24) return `hace ${horas} hora${horas > 1 ? 's' : ''}`;
        if (dias < 30) return `hace ${dias} día${dias > 1 ? 's' : ''}`;

        return formatFecha(fecha);
    };

    /**
     * Trunca un texto largo con ellipsis
     * 
     * @param {string} texto - Texto a truncar
     * @param {number} maxLength - Longitud máxima (default: 50)
     * @returns {string} Texto truncado
     */
    const truncarTexto = (texto, maxLength = 50) => {
        if (!texto) return '-';
        if (texto.length <= maxLength) return texto;
        return texto.slice(0, maxLength) + '...';
    };

    return {
        formatMoneda,
        formatFecha,
        formatFechaISO,
        formatNumero,
        formatPorcentaje,
        formatCUIT,
        formatTelefono,
        formatTiempoRelativo,
        truncarTexto,
    };
}
