<script setup>
import { Head } from '@inertiajs/vue3';
import LegalLayout from '@/Layouts/LegalLayout.vue';

/**
 * Política de Privacidad — TEXTO DE EJEMPLO.
 *
 * Está redactado como referencia sobre la Ley 25.326 de Protección de Datos
 * Personales, para mostrar qué debe cubrir cada sección y con qué nivel de
 * detalle. NO es asesoramiento legal y no debe publicarse sin revisión.
 *
 * Mientras `config('legal.textos_definitivos')` sea false, el layout muestra
 * una franja que declara la página como borrador.
 *
 * La distinción clave al redactar el texto real
 * ---------------------------------------------
 * Hay DOS relaciones distintas y conviene no mezclarlas:
 *
 *  1. Entre la empresa cliente y SUS clientes: la empresa que contrata el
 *     sistema es la RESPONSABLE de los datos que carga (art. 2). Decide qué
 *     recolecta, para qué y cuánto lo conserva.
 *
 *  2. Entre el proveedor del sistema y la empresa cliente: el proveedor es
 *     ENCARGADO del tratamiento (art. 25). Trata esos datos por cuenta de la
 *     empresa, siguiendo sus instrucciones, y no puede usarlos para fines
 *     propios.
 *
 * Esta página cubre el punto 2, que es lo que el proveedor declara. Cada
 * empresa cliente necesita además su propia política hacia sus clientes.
 */

defineProps({
    borrador: { type: Boolean, default: false },
    actualizado: { type: String, default: null },
    responsable: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head title="Política de Privacidad" />

    <LegalLayout titulo="Política de Privacidad" :actualizado="actualizado" :borrador="borrador">
        <p>
            Esta política explica cómo se tratan los datos personales dentro de
            este sistema de gestión, quién es responsable de ellos y qué
            derechos tiene cada persona sobre su propia información. Está
            redactada conforme a la Ley 25.326 de Protección de Datos
            Personales de la República Argentina.
        </p>

        <h2>1. Quién trata tus datos</h2>
        <p>
            El sistema es provisto por <strong>{{ responsable.razon_social }}</strong>,
            CUIT {{ responsable.cuit }}, con domicilio en {{ responsable.domicilio }}.
        </p>
        <p>
            Es importante distinguir dos roles. La <strong>empresa que utiliza
            este sistema</strong> es la responsable de los datos de sus propios
            clientes: decide qué información carga, con qué finalidad y durante
            cuánto tiempo la conserva. El <strong>proveedor del sistema</strong>
            actúa como encargado del tratamiento: aloja y procesa esa
            información por cuenta de la empresa, conforme a sus instrucciones,
            y no la utiliza para fines propios ni la cede a terceros.
        </p>

        <h2>2. Qué datos se recolectan</h2>
        <p>Según el tipo de usuario, el sistema almacena:</p>
        <ul>
            <li>
                <strong>Usuarios del sistema</strong> (personal de la empresa):
                nombre, correo electrónico, teléfono, fotografía de perfil,
                rol asignado y registro de actividad dentro del sistema.
            </li>
            <li>
                <strong>Clientes de la empresa</strong>: nombre y apellido,
                documento de identidad, correo electrónico, teléfono,
                domicilio, y el historial de operaciones asociadas (ventas,
                reparaciones, pagos y cuenta corriente).
            </li>
            <li>
                <strong>Proveedores</strong>: razón social, CUIT, datos de
                contacto y domicilio comercial.
            </li>
            <li>
                <strong>Datos técnicos</strong>: dirección IP, navegador y
                fecha y hora de cada operación registrada en la auditoría.
            </li>
        </ul>

        <h2>3. Con qué finalidad se usan</h2>
        <p>
            Los datos se utilizan exclusivamente para prestar el servicio
            contratado: registrar operaciones comerciales, dar seguimiento a
            reparaciones, administrar cuentas corrientes, emitir comprobantes,
            notificar al cliente el estado de su equipo y mantener un registro
            de auditoría de quién hizo cada cosa dentro del sistema.
        </p>
        <p>
            No se utilizan para publicidad, no se elaboran perfiles con fines
            comerciales ni se toman decisiones automatizadas que afecten a las
            personas.
        </p>

        <h2>4. Con quién se comparten</h2>
        <p>
            Los datos no se venden, alquilan ni ceden a terceros. Solo se
            comparten en tres supuestos: con los proveedores de infraestructura
            necesarios para operar el servicio (alojamiento, envío de correo y
            mensajería), que actúan bajo contrato y con obligación de
            confidencialidad; cuando lo exija una autoridad competente en el
            marco de la ley; y cuando la persona titular lo autorice
            expresamente.
        </p>
        <p>
            Cada empresa cliente accede únicamente a sus propios datos. El
            sistema impide técnicamente que una empresa vea la información de
            otra.
        </p>

        <h2>5. Cuánto tiempo se conservan</h2>
        <p>
            Los datos se conservan mientras dure la relación comercial y,
            posteriormente, durante el plazo exigido por la legislación
            comercial e impositiva aplicable. Cumplido ese plazo, se eliminan o
            anonimizan. Los registros de auditoría se conservan por un período
            determinado y luego se purgan automáticamente.
        </p>

        <h2>6. Cómo se protegen</h2>
        <p>
            Se aplican medidas técnicas y organizativas conforme al artículo 9
            de la Ley 25.326: acceso mediante usuario y contraseña personal,
            contraseñas almacenadas cifradas, permisos diferenciados por rol de
            modo que cada persona acceda solo a lo que su tarea requiere,
            bloqueo automático ante intentos fallidos de acceso, registro de
            auditoría de las operaciones, aislamiento entre empresas y copias
            de respaldo periódicas y cifradas.
        </p>

        <h2>7. Derechos de las personas</h2>
        <p>
            Toda persona tiene derecho a solicitar el acceso, la rectificación,
            la actualización y, cuando corresponda, la supresión de sus datos
            personales, de forma gratuita, en los términos de los artículos 14
            a 16 de la Ley 25.326.
        </p>
        <p>
            Para ejercer estos derechos respecto de los datos cargados por una
            empresa, la solicitud debe dirigirse a esa empresa, que es la
            responsable. Para consultas sobre el funcionamiento del sistema
            puede escribirse a {{ responsable.email }}.
        </p>
        <p>
            La Agencia de Acceso a la Información Pública, en su carácter de
            autoridad de aplicación, tiene atribuciones para atender las
            denuncias y reclamos por incumplimiento de las normas vigentes en
            materia de protección de datos personales.
        </p>

        <h2>8. Cookies</h2>
        <p>
            El sistema utiliza únicamente cookies técnicas, necesarias para
            mantener la sesión iniciada y para proteger los formularios contra
            falsificación de solicitudes. No se utilizan cookies de análisis,
            publicidad ni seguimiento de terceros, por lo que no se requiere
            consentimiento previo para su uso.
        </p>

        <h2>9. Cambios en esta política</h2>
        <p>
            Esta política puede actualizarse para reflejar cambios en el
            servicio o en la normativa aplicable. La fecha de última
            actualización se indica al comienzo de esta página. Los cambios
            sustanciales se comunicarán a las empresas usuarias por los medios
            de contacto registrados.
        </p>

        <h2>10. Contacto</h2>
        <p>
            Por consultas sobre esta política o sobre el tratamiento de datos
            personales: {{ responsable.email }} — {{ responsable.domicilio }}.
        </p>
    </LegalLayout>
</template>
