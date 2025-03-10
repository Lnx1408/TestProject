<?php

//Retorna la url del proyecto
function base_url()
{
    return BASE_URL;
}

//Retorna nombre del proyecto
function name_project()
{
    return "ReqScape";
}

//Retorna la url de Assets
function media()
{
    return BASE_URL . "/Assets";
}


function headerGame($data = "")
{
    $view_header = "Views/Template/header_game.php";
    require_once($view_header);
}

function footerGame($data = "")
{
    $view_footer = "Views/Template/footer_game.php";
    require_once($view_footer);
}

function ReportNarrative($data = "")
{
    $view_header = "Libraries/Reports/ReportNarrativeConstructor.php";
    require_once($view_header);
}

//Muestra información formateada
function dep($data)
{
    $format  = print_r('<pre>');
    $format .= print_r($data);
    $format .= print_r('</pre>');
    return $format;
}

function isActiveRoute($currentSection, $route) {
    return $currentSection === $route ? 'active-link' : '';
}

function encryptResponse2($data)
{
    $secretKey = 'TheQuickBrownFoxWasJumping';
    $secretIv = '4f01bede9221586c';
    $cipher = 'aes-256-cbc';
    
    $key = substr(hash('sha256', $secretKey), 0, 32);
    $iv = substr(hash('sha256', $secretIv), 0, 16);

    // Encrypt the data
    $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);

    // Encode the result in base64
    return $encrypted;
}

function encryptResponse($data)
{
    $secretKey = 'TheQuickBrownFoxWasJumping';
    $cipher = 'aes-256-cbc';
    
    // Derivar clave de 32 bytes (256 bits)
    $key = substr(hash('sha256', $secretKey), 0, 32);

    // Generar IV aleatorio de 16 bytes
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

    // Encriptar los datos con AES-256-CBC
    $encryptedData = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    // Concatenar IV + datos encriptados, luego codificar en base64
    $encryptedDataWithIv = base64_encode($iv . $encryptedData);

    return $encryptedDataWithIv;
}

function decryptData($encryptedDataWithIv)
{
    $secretKey = 'TheQuickBrownFoxWasJumping';
    $cipher = 'aes-256-cbc';

    // Derivar la clave secreta (32 bytes)
    $key = substr(hash('sha256', $secretKey), 0, 32);

    // Decodificar los datos en base64
    $encryptedDataWithIv = base64_decode($encryptedDataWithIv);

    // Extraer IV (primeros 16 bytes) y datos cifrados
    $iv = substr($encryptedDataWithIv, 0, 16);
    $encryptedData = substr($encryptedDataWithIv, 16);

    // Desencriptar los datos
    $decryptedData = openssl_decrypt($encryptedData, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    return $decryptedData;
}

function getModal(string $nameModal, $data)
{
    $view_modal = "Views/Template/Modals/{$nameModal}.php";
    require_once $view_modal;
}
//Envio de correos
function sendEmail($data, $template)
{
    $asunto = $data['asunto'];
    $emailDestino = $data['email'];
    $empresa = NOMBRE_REMITENTE;
    $remitente = EMAIL_REMITENTE;
    //ENVIO DE CORREO
    $de = "MIME-Version: 1.0\r\n";
    $de .= "Content-type: text/html; charset=UTF-8\r\n";
    $de .= "From: {$empresa} <{$remitente}>\r\n";
    ob_start();
    require_once("Views/Template/Email/" . $template . ".php");
    $mensaje = ob_get_clean();
    $send = mail($emailDestino, $asunto, $mensaje, $de);
    return $send;
}



//Elimina exceso de espacios entre palabras
function strClean($strCadena)
{
    $string = preg_replace(['/\s+/', '/^\s|\s$/'], [' ', ''], $strCadena);
    $string = trim($string); //Elimina espacios en blanco al inicio y al final
    $string = stripslashes($string); // Elimina las \ invertidas
    $string = str_ireplace("<script>", "", $string);
    $string = str_ireplace("</script>", "", $string);
    $string = str_ireplace("<script src>", "", $string);
    $string = str_ireplace("<script type=>", "", $string);
    $string = str_ireplace("SELECT * FROM", "", $string);
    $string = str_ireplace("DELETE FROM", "", $string);
    $string = str_ireplace("INSERT INTO", "", $string);
    $string = str_ireplace("SELECT COUNT(*) FROM", "", $string);
    $string = str_ireplace("DROP TABLE", "", $string);
    $string = str_ireplace("OR '1'='1", "", $string);
    $string = str_ireplace('OR "1"="1"', "", $string);
    $string = str_ireplace('OR ´1´=´1´', "", $string);
    $string = str_ireplace("is NULL; --", "", $string);
    $string = str_ireplace("is NULL; --", "", $string);
    $string = str_ireplace("LIKE '", "", $string);
    $string = str_ireplace('LIKE "', "", $string);
    $string = str_ireplace("LIKE ´", "", $string);
    $string = str_ireplace("OR 'a'='a", "", $string);
    $string = str_ireplace('OR "a"="a', "", $string);
    $string = str_ireplace("OR ´a´=´a", "", $string);
    $string = str_ireplace("OR ´a´=´a", "", $string);
    $string = str_ireplace("--", "", $string);
    $string = str_ireplace("^", "", $string);
    $string = str_ireplace("[", "", $string);
    $string = str_ireplace("]", "", $string);
    $string = str_ireplace("==", "", $string);
    return $string;
}
//Genera una contraseña de 10 caracteres
function passGenerator($length = 10)
{
    $pass = "";
    $longitudPass = $length;
    $cadena = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
    $longitudCadena = strlen($cadena);

    for ($i = 1; $i <= $longitudPass; $i++) {
        $pos = rand(0, $longitudCadena - 1);
        $pass .= substr($cadena, $pos, 1);
    }
    return $pass;
}
//Genera un token
function token()
{
    $r1 = bin2hex(random_bytes(10));
    $r2 = bin2hex(random_bytes(10));
    $r3 = bin2hex(random_bytes(10));
    $r4 = bin2hex(random_bytes(10));
    $token = $r1 . '-' . $r2 . '-' . $r3 . '-' . $r4;
    return $token;
}
//Formato para valores monetarios
function formatMoney($cantidad)
{
    $cantidad = number_format($cantidad, 2, SPD, SPM);
    return $cantidad;
}
