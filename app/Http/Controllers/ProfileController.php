<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'Perfil actualizado correctamente.');
    }

    /**
     * Update the user's profile photo.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'max:2048'], // Max 2MB
        ], [
            'foto.required' => 'Debe seleccionar una imagen.',
            'foto.image' => 'El archivo debe ser una imagen.',
            'foto.max' => 'La imagen no debe superar los 2MB.',
        ]);

        $user = $request->user();

        // Eliminar foto anterior si existe
        if ($user->foto_perfil && Storage::disk('public')->exists($user->foto_perfil)) {
            Storage::disk('public')->delete($user->foto_perfil);
        }

        // Guardar nueva foto
        $path = $request->file('foto')->store('fotos-perfil', 'public');
        $user->update(['foto_perfil' => $path]);

        return Redirect::route('profile.edit')->with('status', 'Foto de perfil actualizada correctamente.');
    }

    /**
     * Remove the user's profile photo.
     */
    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->foto_perfil && Storage::disk('public')->exists($user->foto_perfil)) {
            Storage::disk('public')->delete($user->foto_perfil);
        }

        $user->update(['foto_perfil' => null]);

        return Redirect::route('profile.edit')->with('status', 'Foto de perfil eliminada.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Debe ingresar su contraseña.',
            'password.current_password' => 'La contraseña es incorrecta.',
        ]);

        $user = $request->user();

        // Eliminar foto si existe
        if ($user->foto_perfil && Storage::disk('public')->exists($user->foto_perfil)) {
            Storage::disk('public')->delete($user->foto_perfil);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
