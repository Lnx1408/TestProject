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
  <meta name="author" content="Abel OSH">
  <meta name="theme-color" content="#009688">
  <link rel="shortcut icon" href="<?= media(); ?>/images/favicon.ico">
  <!-- Main CSS-->
  <link rel="stylesheet" type="text/css" href="<?= media(); ?>/css/main.css">
  <link rel="stylesheet" type="text/css" href="<?= media(); ?>/css/login.css">
  <link rel="stylesheet" type="text/css" href="<?= media(); ?>/css/style.css">
  <link rel="stylesheet" type="text/css" href="<?= media(); ?>/css/plugins/sweetalert2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

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
      <!-- Formulario de Login -->
      <form class="form-section active" id="formLogin" data-form="login">
        <!--<h3 class="login-head"><i class="fa fa-lg fa-fw fa-user"></i>INICIAR SESIÓN</h3>-->
        <h3 class="login-head">
          <img class="logo-img-login" src="<?= media(); ?>/images/logoinicio.png" alt="Logo del juego">
        </h3>
        <div class="mb-3">
          <label class="form-label">USUARIO</label>
          <input id="txtEmail" name="txtEmail" class="form-control" type="text" placeholder="Email" autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">CONTRASEÑA</label>
          <input id="txtPassword" name="txtPassword" class="form-control" type="password" placeholder="Contraseña">
        </div>
        <div class="mb-3">
          <select class="selectpicker show-tick" data-width="fit">
            <a href="#cz">
              <option data-content='<span class="flag-icon flag-icon-cz"></span> English'>Čeština</option>
            </a>
            <a href="#de">
              <option data-content='<span class="flag-icon flag-icon-de"></span> Español'>Deutsch</option>
            </a>
            <a href="#en">
              <option data-content='<span class="flag-icon flag-icon-gb"></span> Español'>English</option>
            </a>
          </select>
        </div>
        <div class="mb-3">
          <div class="utility">
            <p class="semibold-text mb-2">
              <a href="#" data-toggle="form" data-target="recover">¿Olvidaste tu contraseña?</a>
            </p>
            <p class="semibold-text mb-2">
              <a href="#" data-toggle="form" data-target="register">Registrarse</a>
            </p>
          </div>
        </div>
        <div id="alertLogin" class="text-center"></div>
        <div class="mb-3 btn-container d-grid">
          <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-sign-in-alt"></i> INICIAR SESIÓN</button>
        </div>
      </form>

      <!-- Formulario de Recuperar Contraseña -->
      <form class="form-section" id="formRecetPass" data-form="recover">
        <h3 class="login-head">
          <img class="logo-img-login" src="<?= media(); ?>/images/logoinicio.png" alt="Logo del juego">
          <p>
            <i class="fa fa-lg fa-fw fa-lock"></i>¿Olvidaste contraseña?<i class="bi bi-person-lock me-2"></i>
          </p>
        </h3>
        <div class="mb-3">
          <label class="form-label">EMAIL</label>
          <input id="txtEmailReset" name="txtEmailReset" class="form-control" type="email" placeholder="Email">
        </div>
        <div class="mb-3 btn-container d-grid">
          <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-unlock fa-lg fa-fw"></i>REINICIAR<i class="bi bi-unlock me-2 fs-5"></i></button>
        </div>
        <div class="mb-3 mt-3">
          <p class="semibold-text mb-0">
            <a href="#" data-toggle="form" data-target="login"><i class="fa fa-angle-left fa-fw"></i> Iniciar sesión</a>
          </p>
        </div>
      </form>

      <!-- Formulario de Registro (Nuevo) -->
      <form class="form-section" id="formRegister" data-form="register">
        <h3 class="login-head">
          <img class="logo-img-login" src="<?= media(); ?>/images/logoinicio.png" alt="Logo del juego">
          <!--<p><i class="fa fa-lg fa-fw fa-user-plus"></i>REGISTRO</p>-->
        </h3>

        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text"><i class="fa fa-user"></i></span>
            <select id="SelectTypeUser" name="txtTypeUser" class="form-select show-tick" data-width="fit">
              <option value="D">Docente</option>
              <option value="E">Estudiante</option>
            </select>
          </div>
        </div>

        <!--Usuario-->
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text"><i class="fa fa-user"></i></span>
            <input id="txtUserRegister" name="txtUserRegister" class="form-control" type="text" placeholder="Usuario">
          </div>
        </div>
        <!--Fin Usuario-->

        <!--Nombres-->
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
            <input id="txtFirstNameRegister" name="txtFirstNameRegister" class="form-control" type="text" placeholder="Nombres">
          </div>
        </div>
        <!--Fin Nombres-->

        <!--Apellidos-->
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
            <input id="txtLastNameRegister" name="txtLastNameRegister" class="form-control" type="text" placeholder="Apellidos">
          </div>
        </div>
        <!--Fin Apellidos-->

        <!--Correo-->
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
            <input id="txtEmailRegister" name="txtEmailRegister" class="form-control" type="email" placeholder="Correo">
          </div>
        </div>
        <!--Fin Correo-->

        <!--Contraseña-->
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text"><i class="fa fa-lock"></i></span>
            <input id="txtPasswordRegister" name="txtPasswordRegister" class="form-control" type="password" placeholder="Contraseña">
          </div>
        </div>
        <!--Fin Contraseña-->

        <div class="mb-3 btn-container d-grid">
          <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-user-plus fa-lg fa-fw"></i>REGISTRARSE</button>
        </div>
        <div class="mb-3 mt-3">
          <p class="semibold-text mb-0">
            <a href="#" data-toggle="form" data-target="login"><i class="fa fa-angle-left fa-fw"></i> Volver al login</a>
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
  <!-- The javascript plugin to display page loading on top-->
  <script src="<?= media(); ?>/js/plugins/pace.min.js"></script>
  <!--<script type="text/javascript" src="<?= media(); ?>/js/plugins/sweetalert.min.js"></script>-->
  <script type="text/javascript" src="<?= media(); ?>/js/plugins/sweetalert2.all.min.js"></script>
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