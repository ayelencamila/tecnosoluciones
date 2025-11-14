# 📦 ProductoStore - Guía de Uso

## ¿Qué es este archivo?

Es un **almacén global de productos** usando Pinia. Permite compartir datos de productos entre diferentes partes de tu aplicación sin tener que pedirlos al servidor cada vez.

---

## 🎯 ¿Cuándo usarlo?

### ✅ USA el Store cuando:
- Implementes **Ventas** y necesites buscar productos rápidamente
- Implementes **Compras** y necesites actualizar stock
- Quieras mostrar productos en **múltiples vistas** al mismo tiempo
- Necesites **cachear** datos para mejorar rendimiento
- Implementes un **carrito de compras** o **cotizaciones**

### ❌ NO uses el Store si:
- Solo estás en el CRUD de Productos (usa Inertia directamente)
- Los datos se usan en una sola vista
- Prefieres datos siempre frescos del servidor

---

## 🚀 Cómo usar el Store

### 1. Importar en tu componente Vue

```vue
<script setup>
import { useProductoStore } from '@/stores/productoStore';

const productoStore = useProductoStore();
</script>
```

### 2. Ejemplos de uso

#### 📋 Listar todos los productos
```vue
<script setup>
import { onMounted } from 'vue';
import { useProductoStore } from '@/stores/productoStore';

const store = useProductoStore();

onMounted(async () => {
    await store.fetchProductos();
    console.log('Productos cargados:', store.productos);
});
</script>

<template>
    <div v-for="producto in store.productos" :key="producto.id">
        {{ producto.nombre }} - Stock: {{ producto.stockActual }}
    </div>
</template>
```

#### 🔍 Buscar un producto por código
```vue
<script setup>
import { ref } from 'vue';
import { useProductoStore } from '@/stores/productoStore';

const store = useProductoStore();
const codigoBuscado = ref('PROD-001');

const buscar = () => {
    const producto = store.buscarPorCodigo(codigoBuscado.value);
    if (producto) {
        console.log('Encontrado:', producto.nombre);
    } else {
        console.log('No existe');
    }
};
</script>
```

#### 💰 Obtener precio para un tipo de cliente
```vue
<script setup>
import { useProductoStore } from '@/stores/productoStore';

const store = useProductoStore();

const calcularPrecio = (productoId, tipoClienteId) => {
    const precio = store.obtenerPrecioVigente(productoId, tipoClienteId);
    return precio || 0;
};
</script>
```

#### 📦 Verificar stock disponible (para Ventas)
```vue
<script setup>
import { useProductoStore } from '@/stores/productoStore';

const store = useProductoStore();

const agregarAlCarrito = (productoId, cantidad) => {
    if (store.verificarStock(productoId, cantidad)) {
        // ✅ Hay stock suficiente
        console.log('Se puede vender');
    } else {
        // ❌ No hay stock
        alert('Stock insuficiente');
    }
};
</script>
```

#### ➕ Crear un producto
```vue
<script setup>
import { useProductoStore } from '@/stores/productoStore';

const store = useProductoStore();

const guardar = async () => {
    try {
        const nuevoProducto = await store.crearProducto({
            codigo: 'PROD-100',
            nombre: 'Notebook HP',
            categoriaProductoID: 1,
            estadoProductoID: 1,
            stockActual: 10,
            stockMinimo: 2,
            precios: [
                { tipoClienteID: 1, precio: 500000 }
            ]
        });
        
        console.log('Producto creado:', nuevoProducto);
        // Ahora ya está en store.productos automáticamente
    } catch (error) {
        console.error('Error:', store.error);
    }
};
</script>
```

#### ✏️ Actualizar un producto
```vue
<script setup>
import { useProductoStore } from '@/stores/productoStore';

const store = useProductoStore();

const actualizar = async (id) => {
    try {
        await store.actualizarProducto(id, {
            nombre: 'Nuevo nombre',
            stockActual: 20,
            motivo: 'Actualización de stock'
        });
        
        // El producto se actualiza automáticamente en store.productos
        console.log('Actualizado');
    } catch (error) {
        console.error('Error:', store.error);
    }
};
</script>
```

#### 🗑️ Eliminar un producto
```vue
<script setup>
import { useProductoStore } from '@/stores/productoStore';

const store = useProductoStore();

const eliminar = async (id) => {
    try {
        await store.eliminarProducto(id, 'Producto discontinuado');
        console.log('Eliminado');
    } catch (error) {
        console.error('Error:', store.error);
    }
};
</script>
```

---

## 🎨 Getters Disponibles (Datos calculados)

```vue
<script setup>
import { useProductoStore } from '@/stores/productoStore';

const store = useProductoStore();

// Solo productos activos
console.log(store.productosActivos);

// Productos con stock bajo
console.log(store.productosStockBajo);

// Total de productos
console.log(store.totalProductos);
</script>
```

---

## 🛒 Ejemplo Completo: Módulo de Ventas

```vue
<!-- VentaCreate.vue -->
<template>
    <div>
        <h1>Nueva Venta</h1>
        
        <!-- Buscar producto -->
        <input v-model="codigoBuscar" @input="buscarProducto" placeholder="Código">
        
        <!-- Producto encontrado -->
        <div v-if="productoSeleccionado">
            <p>{{ productoSeleccionado.nombre }}</p>
            <p>Stock disponible: {{ productoSeleccionado.stockActual }}</p>
            <p>Precio: ${{ precioCliente }}</p>
            
            <input v-model.number="cantidad" type="number">
            <button @click="agregarLinea">Agregar</button>
        </div>
        
        <!-- Líneas de venta -->
        <table>
            <tr v-for="linea in lineasVenta" :key="linea.productoId">
                <td>{{ linea.nombre }}</td>
                <td>{{ linea.cantidad }}</td>
                <td>${{ linea.subtotal }}</td>
            </tr>
        </table>
        
        <p>Total: ${{ total }}</p>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useProductoStore } from '@/stores/productoStore';

const productoStore = useProductoStore();

const codigoBuscar = ref('');
const productoSeleccionado = ref(null);
const cantidad = ref(1);
const lineasVenta = ref([]);
const tipoClienteId = ref(1); // Cliente normal

// Buscar producto mientras escribe
const buscarProducto = () => {
    productoSeleccionado.value = productoStore.buscarPorCodigo(codigoBuscar.value);
};

// Obtener precio para el tipo de cliente
const precioCliente = computed(() => {
    if (!productoSeleccionado.value) return 0;
    return productoStore.obtenerPrecioVigente(
        productoSeleccionado.value.id, 
        tipoClienteId.value
    );
});

// Agregar línea de venta
const agregarLinea = () => {
    if (!productoSeleccionado.value) return;
    
    // Verificar stock
    if (!productoStore.verificarStock(productoSeleccionado.value.id, cantidad.value)) {
        alert('Stock insuficiente');
        return;
    }
    
    lineasVenta.value.push({
        productoId: productoSeleccionado.value.id,
        nombre: productoSeleccionado.value.nombre,
        cantidad: cantidad.value,
        precioUnitario: precioCliente.value,
        subtotal: cantidad.value * precioCliente.value
    });
    
    // Limpiar
    codigoBuscar.value = '';
    productoSeleccionado.value = null;
    cantidad.value = 1;
};

// Calcular total
const total = computed(() => {
    return lineasVenta.value.reduce((sum, linea) => sum + linea.subtotal, 0);
});
</script>
```

---

## 🔧 Métodos Disponibles

| Método | Descripción | Ejemplo |
|--------|-------------|---------|
| `fetchProductos(filtros)` | Cargar productos con filtros | `await store.fetchProductos({ search: 'HP' })` |
| `fetchProducto(id)` | Cargar un producto | `await store.fetchProducto(1)` |
| `crearProducto(datos)` | Crear producto | `await store.crearProducto({...})` |
| `actualizarProducto(id, datos)` | Actualizar producto | `await store.actualizarProducto(1, {...})` |
| `eliminarProducto(id, motivo)` | Eliminar producto | `await store.eliminarProducto(1, 'Descontinuado')` |
| `buscarPorCodigo(codigo)` | Buscar por código | `store.buscarPorCodigo('PROD-001')` |
| `buscarPorId(id)` | Buscar por ID | `store.buscarPorId(1)` |
| `obtenerPrecioVigente(prodId, tipoId)` | Obtener precio | `store.obtenerPrecioVigente(1, 2)` |
| `verificarStock(id, cantidad)` | Verificar disponibilidad | `store.verificarStock(1, 5)` |
| `actualizarStock(id, cantidad, tipo)` | Actualizar stock local | `store.actualizarStock(1, 10, 'ENTRADA')` |
| `limpiarStore()` | Limpiar todo | `store.limpiarStore()` |

---

## 💡 Consejos

1. **Carga inicial**: Carga los productos al inicio si los vas a usar mucho
2. **Loading state**: Usa `store.loading` para mostrar spinners
3. **Errores**: Revisa `store.error` si algo falla
4. **Reactivo**: Los cambios se reflejan automáticamente en todos los componentes
5. **No obligatorio**: Sigue usando Inertia en vistas simples

---

## 📝 Resumen para Ayelén

**Ahora tienes:**
- ✅ CRUD de Productos funcionando con Inertia (método actual)
- ✅ ProductoStore listo para usar cuando implementes Ventas/Compras

**Cuándo cambiar:**
- Cuando crees el módulo de **Ventas** → USA el Store para buscar productos rápido
- Cuando crees **Compras** → USA el Store para manejar stock
- Mientras solo uses el CRUD → Sigue con Inertia (más simple)

**No necesitas cambiar nada ahora**, el Store está ahí esperando para cuando lo necesites 😊
