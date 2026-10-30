<script setup>
import { Head } from '@inertiajs/vue3';
import LegalLayout from '@/Layouts/LegalLayout.vue';

/*
 * ESTRUCTURA de los Términos y Condiciones, lista para redactar.
 *
 * Cada sección tiene su encabezado definitivo y un bloque .pendiente con lo
 * que hay que cubrir ahí. Al redactar, reemplazá el <div class="pendiente">
 * por los <p> del texto real.
 *
 * Contexto importante para la redacción: el sistema se vende como servicio a
 * varias empresas. Hay dos relaciones distintas que el texto debería
 * distinguir, porque las obligaciones no son las mismas:
 *
 *   - TecnoSoluciones con la EMPRESA que contrata el sistema.
 *   - La empresa con los USUARIOS que ella misma da de alta.
 */
</script>

<template>
    <Head title="Términos y Condiciones" />

    <LegalLayout titulo="Términos y Condiciones de Uso">
        <div class="pendiente">
            Texto pendiente de redacción. La estructura y los encabezados ya están
            definidos; falta el contenido legal de cada sección.
        </div>

        <h2>1. Objeto</h2>
        <div class="pendiente">
            Qué se contrata: una licencia de uso del sistema de gestión, en la
            modalidad de servicio, sin transferencia de la propiedad del software.
        </div>

        <h2>2. Alta de la cuenta y de los usuarios</h2>
        <div class="pendiente">
            Aclarar que las cuentas de empresa las habilita el proveedor del
            sistema, y que cada empresa es responsable de dar de alta y de baja a
            sus propios usuarios, de los roles y permisos que les asigne, y de todo
            lo que esos usuarios hagan con sus credenciales.
        </div>

        <h2>3. Responsabilidades de la empresa usuaria</h2>
        <div class="pendiente">
            Veracidad de los datos cargados, uso de credenciales personales e
            intransferibles, obligación de dar de baja los accesos de quien deja de
            trabajar en la empresa, y de contar con el consentimiento de sus propios
            clientes para cargar sus datos y notificarlos por WhatsApp.
        </div>

        <h2>4. Responsabilidades del proveedor</h2>
        <div class="pendiente">
            Disponibilidad del servicio, resguardo de la información, aislamiento de
            los datos entre empresas distintas, y alcance del soporte.
        </div>

        <h2>5. Uso aceptable</h2>
        <div class="pendiente">
            Conductas prohibidas: intentar acceder a datos de otra empresa, vulnerar
            los controles de acceso, usar el sistema con fines ilícitos o cargar
            datos de terceros sin su consentimiento.
        </div>

        <h2>6. Suspensión del acceso</h2>
        <div class="pendiente">
            Causales por las que el proveedor puede suspender el acceso de una
            empresa, incluido el incumplimiento del pago, con qué aviso previo, y
            qué pasa con los datos durante la suspensión. Dejar constancia de que
            suspender el acceso no elimina la información.
        </div>

        <h2>7. Propiedad de los datos</h2>
        <div class="pendiente">
            Punto central: los datos cargados pertenecen a la empresa usuaria, no al
            proveedor. Definir cómo puede exportarlos y qué ocurre con ellos si
            termina la relación.
        </div>

        <h2>8. Propiedad intelectual</h2>
        <div class="pendiente">
            Titularidad del software, de su código y de su diseño, y los límites de
            la licencia que se otorga.
        </div>

        <h2>9. Limitación de responsabilidad</h2>
        <div class="pendiente">
            Alcance de la responsabilidad del proveedor por interrupciones, pérdida
            de datos o decisiones de negocio tomadas con la información del sistema.
        </div>

        <h2>10. Vigencia y baja del servicio</h2>
        <div class="pendiente">
            Plazo, renovación, cómo se solicita la baja y qué pasa con los datos
            después de la baja.
        </div>

        <h2>11. Modificaciones</h2>
        <div class="pendiente">
            Cómo se notifican los cambios a estos términos y desde cuándo rigen.
        </div>

        <h2>12. Ley aplicable y jurisdicción</h2>
        <div class="pendiente">
            Legislación argentina aplicable y tribunales competentes.
        </div>
    </LegalLayout>
</template>
