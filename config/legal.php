<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Textos legales definitivos
    |--------------------------------------------------------------------------
    |
    | Mientras esto sea `false`, las paginas de Politica de Privacidad y
    | Terminos y Condiciones muestran una franja fija que las declara BORRADOR
    | y aclara que el texto es de ejemplo y no vincula a nadie.
    |
    | Por que existe esta bandera
    | ---------------------------
    | Una politica de privacidad es una declaracion vinculante: afirma que
    | tratamos los datos de cierta manera, los conservamos cierto tiempo y los
    | compartimos o no con terceros. Un texto de ejemplo que SE PARECE a uno
    | real es mas peligroso que no tener ninguno, porque nadie nota que es
    | falso --ni quien lo publica ni quien lo lee-- y termina siendo oponible.
    |
    | La bandera convierte "me acuerdo de reemplazarlo antes de produccion" en
    | algo que el sistema no deja olvidar.
    |
    | Para activarla: LEGAL_TEXTOS_DEFINITIVOS=true en el .env, UNA VEZ que el
    | texto haya sido redactado y revisado.
    |
    */

    'textos_definitivos' => env('LEGAL_TEXTOS_DEFINITIVOS', false),

    /*
    | Fecha de la ultima revision del texto legal, que se muestra al pie de
    | cada pagina. Se actualiza a mano cuando el texto cambia: no se usa la
    | fecha del archivo ni la del deploy, porque un deploy que no toco el
    | texto no es una revision del texto.
    */

    'actualizado' => env('LEGAL_ACTUALIZADO', '2026-10-31'),

    /*
    | Datos del responsable, para no repetirlos en cada vista. Son de ejemplo
    | hasta que se completen con los reales.
    */

    'responsable' => [
        'razon_social' => env('LEGAL_RAZON_SOCIAL', 'TecnoSoluciones SRL'),
        'cuit' => env('LEGAL_CUIT', '30-00000000-0'),
        'domicilio' => env('LEGAL_DOMICILIO', 'San Martín 465, San Javier, Misiones, Argentina'),
        'email' => env('LEGAL_EMAIL_CONTACTO', 'contacto@ejemplo.com'),
    ],

];
