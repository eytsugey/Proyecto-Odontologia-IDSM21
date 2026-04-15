<?php
function enviarWhatsApp($telefono, $mensaje){

    $telefono = preg_replace('/\D/', '', $telefono);

    if(substr($telefono, 0, 2) !== '52'){
        $telefono = '52' . $telefono;
    }

    $url = "https://wa.me/".$telefono."?text=".urlencode($mensaje);

    echo "<script>window.open('$url','_blank');</script>";
}