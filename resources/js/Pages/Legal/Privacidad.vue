<script setup>
import { Head } from '@inertiajs/vue3';
import LegalLayout from '@/Layouts/LegalLayout.vue';

/*
 * ESTRUCTURA de la Política de Privacidad, lista para redactar.
 *
 * Cada sección tiene su encabezado definitivo y un bloque .pendiente con lo
 * que hay que cubrir ahí. Al redactar, reemplazá el <div class="pendiente">
 * por los <p> del texto real.
 *
 * Contexto importante para la redacción: el sistema es multi-empresa. Hay
 * dos roles distintos frente a la ley 25.326 de Protección de Datos
 * Personales, y conviene que el texto los separe:
 *
 *   - Cada EMPRESA cliente es responsable de los datos de SUS clientes
 *     (nombre, DNI, teléfono, dirección, historial de reparaciones).
 *   - TecnoSoluciones, como proveedor del software, es quien trata esos
 *     datos por cuenta de la empresa.
 */
</script>

<template>
    <Head title="Política de Privacidad" />

    <LegalLayout titulo="Política de Privacidad">
        <div class="pendiente">
            Texto pendiente de redacción. La estructura y los encabezados ya están
            definidos; falta el contenido legal de cada sección.
        </div>

        <h2>1. Quién trata tus datos</h2>
        <div class="pendiente">
            Identificar a la empresa que opera el sistema (razón social, CUIT,
            domicilio y correo de contacto) y aclarar la distinción entre la empresa
            cliente, que es responsable de los datos de sus propios clientes, y
            TecnoSoluciones, que los trata por cuenta de ella como proveedor del
            software.
        </div>

        <h2>2. Qué datos recolectamos</h2>
        <div class="pendiente">
            Enumerar las categorías que el sistema efectivamente guarda hoy:
            <ul>
                <li>De los usuarios del sistema: nombre, correo, teléfono, foto de perfil.</li>
                <li>De los clientes de cada empresa: nombre, apellido, DNI, correo, teléfono, WhatsApp, dirección.</li>
                <li>De la operación: ventas, pagos, cuentas corrientes, reparaciones e imágenes de los equipos.</li>
                <li>De seguridad: dirección IP, navegador y registro de auditoría de cada acción.</li>
            </ul>
        </div>

        <h2>3. Para qué los usamos</h2>
        <div class="pendiente">
            Explicar las finalidades concretas: gestionar ventas y reparaciones,
            avisar al cliente por WhatsApp el estado de su equipo, controlar cuentas
            corrientes y dejar trazabilidad de quién hizo cada cosa.
        </div>

        <h2>4. Con quién los compartimos</h2>
        <div class="pendiente">
            Mencionar a los terceros que intervienen por el funcionamiento del
            sistema: el proveedor de mensajería que entrega los WhatsApp y el de
            correo electrónico. Aclarar que no se venden ni ceden datos.
        </div>

        <h2>5. Cuánto tiempo los conservamos</h2>
        <div class="pendiente">
            Definir los plazos de conservación, y aclarar que el registro de
            auditoría se depura por antigüedad mientras los comprobantes se
            conservan por el plazo que exige la normativa fiscal.
        </div>

        <h2>6. Cómo los protegemos</h2>
        <div class="pendiente">
            Describir las medidas ya implementadas: contraseñas cifradas, acceso
            restringido por rol y permiso, aislamiento de los datos entre empresas,
            bloqueo de cuenta por intentos fallidos, enmascaramiento de datos
            sensibles en la auditoría y registro de IP de cada acceso.
        </div>

        <h2>7. Tus derechos</h2>
        <div class="pendiente">
            Derechos de acceso, rectificación, actualización y supresión previstos
            en la Ley 25.326, cómo ejercerlos y en cuánto tiempo se responde.
            Incluir la mención a la Agencia de Acceso a la Información Pública como
            órgano de control.
        </div>

        <h2>8. Cookies</h2>
        <div class="pendiente">
            Aclarar que el sistema usa únicamente cookies técnicas, necesarias para
            mantener la sesión abierta, y que no se utilizan cookies de análisis ni
            de publicidad. Si en el futuro se agregan scripts de seguimiento, habrá
            que incorporar además un componente de consentimiento previo.
        </div>

        <h2>9. Cambios en esta política</h2>
        <div class="pendiente">
            Cómo se comunican las modificaciones y desde cuándo rigen.
        </div>

        <h2>10. Contacto</h2>
        <div class="pendiente">
            Correo y domicilio donde dirigir consultas o reclamos sobre datos
            personales.
        </div>
    </LegalLayout>
</template>
