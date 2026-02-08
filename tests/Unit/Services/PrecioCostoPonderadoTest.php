<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\UnidadMedida;
use App\Models\Stock;
use App\Models\Deposito;

class PrecioCostoPonderadoTest extends TestCase
{
    use RefreshDatabase;

    protected Producto $producto;
    protected Deposito $deposito;

    protected function setUp(): void
    {
        parent::setUp();

        $categoria = CategoriaProducto::create(['nombre' => 'Repuestos', 'activo' => true]);
        $estado = EstadoProducto::create(['nombre' => 'Activo', 'descripcion' => 'Disponible']);
        $unidad = UnidadMedida::create(['nombre' => 'Unidad', 'abreviatura' => 'u', 'activo' => true]);
        $this->deposito = Deposito::create([
            'nombre' => 'Depósito Principal', 'activo' => true, 'esPrincipal' => true,
        ]);

        $this->producto = Producto::create([
            'codigo' => 'TEST-CPP',
            'nombre' => 'Producto Test Costo',
            'categoriaProductoID' => $categoria->id,
            'estadoProductoID' => $estado->id,
            'unidad_medida_id' => $unidad->id,
            'precio_costo' => null,
        ]);

        Stock::create([
            'productoID' => $this->producto->id,
            'deposito_id' => $this->deposito->deposito_id,
            'cantidad_disponible' => 0,
            'stock_minimo' => 0,
        ]);
    }

    /** @test */
    public function primera_recepcion_toma_precio_directo()
    {
        // Sin stock previo → el costo es directamente el precio de la recepción
        $stock = Stock::where('productoID', $this->producto->id)->first();
        $stock->update(['cantidad_disponible' => 10]);

        $this->producto->actualizarCostoPonderado(10, 5000);

        $this->producto->refresh();
        $this->assertEquals(5000, $this->producto->precio_costo);
    }

    /** @test */
    public function calcula_promedio_ponderado_correctamente()
    {
        // Simular stock existente: 10 unidades a $5.000
        $stock = Stock::where('productoID', $this->producto->id)->first();
        $stock->update(['cantidad_disponible' => 10]);
        $this->producto->update(['precio_costo' => 5000]);

        // Recibir 10 más a $7.000 → stock total = 20
        $stock->update(['cantidad_disponible' => 20]);

        $this->producto->actualizarCostoPonderado(10, 7000);

        // Promedio ponderado: (10*5000 + 10*7000) / 20 = 6000
        $this->producto->refresh();
        $this->assertEquals(6000, $this->producto->precio_costo);
    }

    /** @test */
    public function calcula_promedio_con_cantidades_desiguales()
    {
        // 20 unidades a $3.000
        $stock = Stock::where('productoID', $this->producto->id)->first();
        $stock->update(['cantidad_disponible' => 20]);
        $this->producto->update(['precio_costo' => 3000]);

        // Recibir 5 más a $6.000 → stock total = 25
        $stock->update(['cantidad_disponible' => 25]);

        $this->producto->actualizarCostoPonderado(5, 6000);

        // (20*3000 + 5*6000) / 25 = (60000 + 30000) / 25 = 3600
        $this->producto->refresh();
        $this->assertEquals(3600, $this->producto->precio_costo);
    }

    /** @test */
    public function sin_costo_previo_toma_precio_nuevo()
    {
        // Producto con stock pero sin costo (precio_costo = 0 o null)
        $stock = Stock::where('productoID', $this->producto->id)->first();
        $stock->update(['cantidad_disponible' => 5]);
        $this->producto->update(['precio_costo' => 0]);

        // Recibir 5 a $10.000
        $stock->update(['cantidad_disponible' => 10]);

        $this->producto->actualizarCostoPonderado(5, 10000);

        $this->producto->refresh();
        $this->assertEquals(10000, $this->producto->precio_costo);
    }

    /** @test */
    public function multiples_recepciones_actualizan_progresivamente()
    {
        $stock = Stock::where('productoID', $this->producto->id)->first();

        // 1ra recepción: 10 a $1.000
        $stock->update(['cantidad_disponible' => 10]);
        $this->producto->actualizarCostoPonderado(10, 1000);
        $this->producto->refresh();
        $this->assertEquals(1000, $this->producto->precio_costo);

        // 2da recepción: 10 a $2.000 → (10*1000 + 10*2000) / 20 = 1500
        $stock->update(['cantidad_disponible' => 20]);
        $this->producto->actualizarCostoPonderado(10, 2000);
        $this->producto->refresh();
        $this->assertEquals(1500, $this->producto->precio_costo);

        // 3ra recepción: 20 a $3.000 → (20*1500 + 20*3000) / 40 = 2250
        $stock->update(['cantidad_disponible' => 40]);
        $this->producto->actualizarCostoPonderado(20, 3000);
        $this->producto->refresh();
        $this->assertEquals(2250, $this->producto->precio_costo);
    }
}
