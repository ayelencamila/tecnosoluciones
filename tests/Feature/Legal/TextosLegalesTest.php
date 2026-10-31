<?php

namespace Tests\Feature\Legal;

use Tests\TestCase;

/**
 * Las páginas legales llevan un texto de EJEMPLO hasta que alguien redacte el
 * definitivo. Una política de privacidad es una declaración vinculante, así
 * que un texto de relleno que se parece a uno real es más peligroso que no
 * tener ninguno: nadie nota que es falso.
 *
 * Por eso el aviso no depende de que alguien se acuerde de sacarlo, sino de
 * una bandera de configuración que arranca apagada.
 */
class TextosLegalesTest extends TestCase
{
    /** @test */
    public function las_paginas_legales_son_publicas(): void
    {
        $this->get(route('legal.privacidad'))->assertOk();
        $this->get(route('legal.terminos'))->assertOk();
    }

    /** @test */
    public function por_defecto_las_paginas_se_marcan_como_borrador(): void
    {
        $this->assertFalse(
            config('legal.textos_definitivos'),
            'El texto arranca como borrador: declararlo definitivo es una decisión explícita'
        );

        foreach (['legal.privacidad', 'legal.terminos'] as $ruta) {
            $props = $this->get(route($ruta))->viewData('page')['props'];
            $this->assertTrue($props['borrador'], "La vista {$ruta} debe recibir borrador = true");
        }
    }

    /** @test */
    public function con_la_bandera_activa_dejan_de_marcarse_como_borrador(): void
    {
        config(['legal.textos_definitivos' => true]);

        $props = $this->get(route('legal.privacidad'))->viewData('page')['props'];

        $this->assertFalse($props['borrador']);
    }

    /**
     * Los datos del responsable salen del config para no quedar repetidos en
     * cada vista y para poder completarlos sin tocar el código.
     *
     * @test
     */
    public function las_paginas_reciben_los_datos_del_responsable(): void
    {
        $props = $this->get(route('legal.privacidad'))->viewData('page')['props'];

        $this->assertArrayHasKey('responsable', $props);
        $this->assertArrayHasKey('razon_social', $props['responsable']);
        $this->assertArrayHasKey('email', $props['responsable']);
        $this->assertNotEmpty($props['actualizado']);
    }
}
