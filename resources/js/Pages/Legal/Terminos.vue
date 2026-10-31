<script setup>
import { Head } from '@inertiajs/vue3';
import LegalLayout from '@/Layouts/LegalLayout.vue';

/**
 * Términos y Condiciones — TEXTO DE EJEMPLO.
 *
 * Redactado como referencia para mostrar qué debe cubrir cada cláusula. NO es
 * asesoramiento legal y no debe publicarse sin revisión profesional,
 * especialmente las cláusulas de responsabilidad, pago y rescisión, que son
 * las que definen el riesgo comercial real.
 *
 * Mientras `config('legal.textos_definitivos')` sea false, el layout muestra
 * una franja que declara la página como borrador.
 *
 * Qué regula este documento
 * -------------------------
 * La relación entre el PROVEEDOR del sistema y la EMPRESA que lo contrata.
 * No regula la relación entre esa empresa y sus propios clientes: las
 * condiciones de reparación, garantía y abandono de equipos las fija cada
 * empresa en su propia nota de recepción.
 */

defineProps({
    borrador: { type: Boolean, default: false },
    actualizado: { type: String, default: null },
    responsable: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head title="Términos y Condiciones" />

    <LegalLayout titulo="Términos y Condiciones" :actualizado="actualizado" :borrador="borrador">
        <p>
            Estos términos regulan el uso del sistema de gestión provisto por
            <strong>{{ responsable.razon_social }}</strong> (en adelante, «el
            proveedor») por parte de las empresas que lo contratan (en
            adelante, «la empresa usuaria»). El uso del sistema implica su
            aceptación.
        </p>

        <h2>1. Objeto</h2>
        <p>
            El proveedor otorga a la empresa usuaria una licencia de uso, no
            exclusiva e intransferible, sobre un sistema de gestión comercial
            que incluye administración de clientes, productos, stock, ventas,
            reparaciones, compras, pagos y reportes, en la modalidad de
            software como servicio.
        </p>

        <h2>2. Alta y cuentas de usuario</h2>
        <p>
            El alta de la empresa usuaria la realiza el proveedor. A partir de
            ese momento, el administrador designado por la empresa es quien
            crea las cuentas de su personal y asigna los permisos
            correspondientes.
        </p>
        <p>
            Cada cuenta es personal e intransferible. La empresa usuaria es
            responsable de la confidencialidad de las credenciales de su
            personal y de toda actividad realizada desde sus cuentas, y debe
            notificar al proveedor ante cualquier uso no autorizado.
        </p>

        <h2>3. Uso permitido</h2>
        <p>
            El sistema debe utilizarse conforme a la ley y a su finalidad
            comercial. Queda prohibido intentar acceder a datos de otras
            empresas usuarias, vulnerar los mecanismos de seguridad, realizar
            ingeniería inversa sobre el software, revenderlo o cederlo a
            terceros, y utilizarlo para almacenar contenido ilícito.
        </p>

        <h2>4. Datos de la empresa usuaria</h2>
        <p>
            Los datos cargados en el sistema son propiedad de la empresa
            usuaria. El proveedor los trata únicamente para prestar el
            servicio, en carácter de encargado del tratamiento, conforme a la
            Política de Privacidad.
        </p>
        <p>
            La empresa usuaria es responsable de la licitud de los datos que
            carga y de contar con el consentimiento necesario de sus propios
            clientes, así como de informarles el tratamiento de su información.
        </p>

        <h2>5. Disponibilidad del servicio</h2>
        <p>
            El proveedor procura mantener el servicio disponible de forma
            continua, pero no garantiza una disponibilidad ininterrumpida. El
            servicio puede suspenderse temporalmente por tareas de
            mantenimiento, que se comunicarán con antelación razonable cuando
            sean programadas.
        </p>

        <h2>6. Respaldos</h2>
        <p>
            El proveedor realiza copias de respaldo periódicas de la base de
            datos y de los archivos cargados. Los respaldos tienen por objeto
            la recuperación ante fallas y se conservan durante un plazo
            determinado. La empresa usuaria puede solicitar una exportación de
            sus propios datos.
        </p>

        <h2>7. Precio y facturación</h2>
        <p>
            El servicio se abona según el plan contratado, con la periodicidad
            y los valores acordados. Los precios pueden actualizarse,
            notificando a la empresa usuaria con antelación suficiente para que
            pueda decidir si continúa con el servicio.
        </p>

        <h2>8. Suspensión por falta de pago</h2>
        <p>
            La falta de pago habilita al proveedor a suspender el acceso al
            sistema, previa notificación. La suspensión no implica la
            eliminación de los datos, que se conservan durante un plazo
            razonable para permitir la regularización o su exportación.
        </p>

        <h2>9. Límites del plan contratado</h2>
        <p>
            Cada plan define límites de uso. Los límites de capacidad —como la
            cantidad de usuarios— impiden crear nuevos recursos una vez
            alcanzados. Los límites de volumen operativo no interrumpen la
            operación diaria: al superarse, se notifica a la empresa usuaria
            para acordar la adecuación del plan.
        </p>

        <h2>10. Responsabilidad</h2>
        <p>
            El sistema se provee como herramienta de gestión. El proveedor no
            es responsable por las decisiones comerciales de la empresa
            usuaria, por los datos que esta carga ni por el uso que su personal
            haga del sistema dentro de los permisos asignados.
        </p>
        <p>
            La responsabilidad del proveedor se limita, en todos los casos, al
            monto abonado por la empresa usuaria en los últimos períodos de
            servicio, conforme a lo acordado contractualmente.
        </p>

        <h2>11. Rescisión</h2>
        <p>
            Cualquiera de las partes puede dar por terminada la relación
            notificando a la otra con antelación razonable. Al finalizar, la
            empresa usuaria tiene derecho a obtener una copia de sus datos
            dentro de un plazo determinado, vencido el cual el proveedor podrá
            eliminarlos definitivamente.
        </p>

        <h2>12. Modificaciones de estos términos</h2>
        <p>
            Estos términos pueden actualizarse. La fecha de última
            actualización se indica al comienzo de esta página y los cambios
            sustanciales se notificarán a las empresas usuarias.
        </p>

        <h2>13. Ley aplicable y jurisdicción</h2>
        <p>
            Estos términos se rigen por las leyes de la República Argentina.
            Ante cualquier controversia, las partes se someten a los tribunales
            ordinarios competentes, renunciando a cualquier otro fuero.
        </p>

        <h2>14. Contacto</h2>
        <p>
            {{ responsable.razon_social }} — {{ responsable.domicilio }} —
            {{ responsable.email }}.
        </p>
    </LegalLayout>
</template>
