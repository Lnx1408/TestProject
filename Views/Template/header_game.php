<!DOCTYPE html>
<html lang="en">

<head>
   <meta name="description" content="Juego Tesis">
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">

   <link rel="shortcut icon" href="<?= media(); ?>/images/favicon.ico">
   <!--=============== BOX ICONS ===============-->
   <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

   <!--=============== REMIXICONS ===============-->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.5.0/remixicon.css">
   <!--=============== CSS ===============-->
   <link rel="stylesheet" href="<?= media(); ?>/css/plugins/sidebar/styles.css">
   <link rel="stylesheet" href="<?= media(); ?>/css/plugins/sweetalert2.min.css">

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

   <script src="<?= media(); ?>/js/i18n/languageManager.js"></script>
   <script>
      document.addEventListener('DOMContentLoaded', async () => {
         await LanguageManager.init();
      });
   </script>

   <title><?= $data['page_tag'] ?></title>
</head>

<body>
   <!--=============== HEADER ===============-->
   <header class="header" id="header">
      <div class="header__container">
         <a href="<?= base_url(); ?>" class="header__logo">
            <i class="ri-cloud-fill"></i>
            <span><?= name_project(); ?></span>
         </a>

         <button class="header__toggle" id="header-toggle">
            <i class="ri-menu-line"></i>
         </button>
      </div>
   </header>
   <?php require_once("nav_game.php"); ?>