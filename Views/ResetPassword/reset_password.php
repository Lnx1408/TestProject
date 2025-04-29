<!DOCTYPE html>
<html lang="es">

<head>
  <meta http-equiv="Expires" content="0">
  <meta http-equiv="Last-Modified" content="0">
  <meta http-equiv="Cache-Control" content="no-cache, mustrevalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="author" content="Yermin Lino">
  <meta name="theme-color" content="#009688">
  <link rel="shortcut icon" href="<?= media(); ?>/images/favicon.ico">
  <!-- Main CSS-->
  <?php
   if (!empty($data['page_libraries_css'])) {
      foreach ($data['page_libraries_css'] as $css) {
         echo '<link rel="stylesheet" href="' . media() . '/css/' . $css . '">';
      }
   }
   if (!empty($data['page_css'])) {
      foreach ($data['page_css'] as $css) {
         echo '<link rel="stylesheet" href="' . media() . '/css/' . $css . '">';
      }
   }
   ?>
  <title><?= $data['page_tag']; ?></title>
</head>

<body>
  <section class="material-half-bg">
    <div class="backgorund-login"></div>
  </section>

  <section class="login-content">
    <div class="logo">
      <!--<h1><?= $data['page_title']; ?></h1>-->
    </div>
    <div class="login-box">
      <!-- Formulario de Cambio de Contraseña -->
      <form class="form-section active" id="formResetPassword">
        <h3 class="login-head">
          <img class="logo-img-login" src="<?= media(); ?>/images/logoinicio.png" alt="Logo del juego">
          <p>
            <i class="fa fa-lg fa-fw fa-lock"></i>Restablece tu contraseña
          </p>
        </h3>
        
        <input type="hidden" id="resetToken" value="<?= isset($_GET['token']) ? $_GET['token'] : ''; ?>">
        
        <div class="mb-3">
          <label class="form-label">NUEVA CONTRASEÑA</label>
          <input id="txtNewPassword" name="txtNewPassword" class="form-control" type="password" placeholder="Ingresa tu nueva contraseña" required>
        </div>
        
        <div class="mb-3">
          <label class="form-label">CONFIRMAR CONTRASEÑA</label>
          <input id="txtConfirmPassword" name="txtConfirmPassword" class="form-control" type="password" placeholder="Confirma tu contraseña" required>
        </div>
        
        <div id="alertResetPassword" class="text-center"></div>
        
        <div class="mb-3 btn-container d-grid">
          <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-unlock fa-lg fa-fw"></i>CAMBIAR CONTRASEÑA</button>
        </div>
        
        <div class="mb-3 mt-3">
          <p class="semibold-text mb-0">
            <a href="<?= base_url(); ?>/login"><i class="fa fa-angle-left fa-fw"></i> Volver al inicio de sesión</a>
          </p>
        </div>
      </form>
    </div>
  </section>

  <script>
    const base_url = "<?= base_url(); ?>";
  </script>
  <!-- Essential javascripts for application to work-->
  <script src="<?= media(); ?>/js/jquery-3.7.1.min.js"></script>
  <script src="<?= media(); ?>/js/login/popper.min.js"></script>
  <script src="<?= media(); ?>/js/login/bootstrap.min.js"></script>
  <script src="<?= media(); ?>/js/login/fontawesome.js"></script>
  <script src="<?= media(); ?>/js/login/main.js"></script>
  <!-- The javascript plugin to display page loading on top-->
  <script src="<?= media(); ?>/js/plugins/pace.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js"></script>
  <?php
  if (!empty($data['page_functions_js'])) {
    foreach ($data['page_functions_js'] as $js) {
      echo '<script src="' . media() . '/js/' . $js . '"></script>';
    }
  }
  ?>
</body>

</html>