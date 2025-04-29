<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .title {
            color: #1976D2;
            margin-top: 0;
        }
        .content {
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background-color: #1976D2;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            font-size: 12px;
            text-align: center;
            margin-top: 20px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://franklinparrales.es/ReqScape/Assets/images/logoinicio.png" alt="Logo ReqScape" class="logo">
            <h1 class="title">Recuperación de Contraseña</h1>
        </div>
        <div class="content">
            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en <?= name_project(); ?>.</p>
            <p>Para continuar con el proceso de recuperación, haz clic en el siguiente enlace:</p>
            
            <div style="text-align: center;">
                <a href="<?= $resetLink; ?>" class="button">Restablecer Contraseña</a>
            </div>
            
            <p>Si no solicitaste restablecer tu contraseña, puedes ignorar este correo. Este enlace expirará en 1 hora por seguridad.</p>
            
            <p>Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
            <p style="word-break: break-all;"><?= $resetLink; ?></p>
        </div>
        <div class="footer">
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
            <p>&copy; <?= date('Y'); ?> <?= name_project(); ?>. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>