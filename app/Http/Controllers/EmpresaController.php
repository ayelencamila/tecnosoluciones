<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmpresaRequest;
use App\Models\Auditoria;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion de la empresa (tenant) del usuario autenticado.
 *
 * Cubre el requerimiento del profesor: cada empresa debe poder editar su
 * nombre y cambiar su logo cuando lo desee. El alcance es siempre la
 * empresa propia del usuario - no se accede a otras empresas desde aqui.
 */
class EmpresaController extends Controller
{
    /**
     * Muestra el formulario de edicion de la empresa del usuario logueado.
     */
    public function edit(): Response
    {
        $empresa = $this->empresaActual();

        return Inertia::render('Empresa/Edit', [
            'empresa' => [
                'id'         => $empresa->id,
                'nombre'     => $empresa->nombre,
                'slug'       => $empresa->slug,
                'cuit'       => $empresa->cuit,
                'telefono'   => $empresa->telefono,
                'email'      => $empresa->email,
                'direccion'  => $empresa->direccion,
                'logo'       => $empresa->logo,
                'logo_url'   => $empresa->logo ? Storage::disk('public')->url($empresa->logo) : null,
            ],
        ]);
    }

    /**
     * Actualiza datos de la empresa. Maneja upload/borrado de logo.
     */
    public function update(UpdateEmpresaRequest $request): RedirectResponse
    {
        $empresa = $this->empresaActual();

        $datosAnteriores = $empresa->only([
            'nombre', 'cuit', 'telefono', 'email', 'direccion', 'logo',
        ]);

        $cuitLimpio = $request->input('cuit')
            ? preg_replace('/\D/', '', $request->input('cuit'))
            : null;

        $datosNuevos = [
            'nombre'    => $request->input('nombre'),
            'cuit'      => $cuitLimpio,
            'telefono'  => $request->input('telefono'),
            'email'     => $request->input('email'),
            'direccion' => $request->input('direccion'),
        ];

        try {
            DB::beginTransaction();

            if ($request->hasFile('logo')) {
                if ($empresa->logo && Storage::disk('public')->exists($empresa->logo)) {
                    Storage::disk('public')->delete($empresa->logo);
                }
                $datosNuevos['logo'] = $request->file('logo')->store('empresas/logos', 'public');
            } elseif ($request->boolean('eliminar_logo')) {
                if ($empresa->logo && Storage::disk('public')->exists($empresa->logo)) {
                    Storage::disk('public')->delete($empresa->logo);
                }
                $datosNuevos['logo'] = null;
            }

            $empresa->update($datosNuevos);

            Auditoria::registrar(
                Auditoria::ACCION_MODIFICAR_EMPRESA,
                'empresas',
                $empresa->id,
                $datosAnteriores,
                $empresa->only(array_keys($datosAnteriores)),
                'Edicion de datos de la empresa desde panel admin'
            );

            DB::commit();

            return redirect()
                ->route('empresa.edit')
                ->with('success', 'Datos de la empresa actualizados correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la empresa: ' . $e->getMessage());
        }
    }

    /**
     * Borra solo el logo (sin tocar el resto de los datos).
     */
    public function deleteLogo(): RedirectResponse
    {
        $empresa = $this->empresaActual();

        if (! $empresa->logo) {
            return back()->with('success', 'La empresa no tiene logo configurado.');
        }

        $logoAnterior = $empresa->logo;

        if (Storage::disk('public')->exists($logoAnterior)) {
            Storage::disk('public')->delete($logoAnterior);
        }

        $empresa->update(['logo' => null]);

        Auditoria::registrar(
            Auditoria::ACCION_MODIFICAR_EMPRESA,
            'empresas',
            $empresa->id,
            ['logo' => $logoAnterior],
            ['logo' => null],
            'Eliminacion de logo de empresa'
        );

        return back()->with('success', 'Logo eliminado correctamente.');
    }

    /**
     * Devuelve la empresa del usuario autenticado. Falla con 404 si por
     * alguna razon el usuario no tiene empresa asociada (no deberia pasar
     * con el modelo multi-tenant actual).
     */
    private function empresaActual(): Empresa
    {
        $empresa = auth()->user()?->empresa;

        abort_if(! $empresa, 404, 'Empresa no encontrada para el usuario actual.');

        return $empresa;
    }
}
