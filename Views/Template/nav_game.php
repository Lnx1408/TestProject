<?php
// Obtener datos del usuario
$userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$userType = $userData ? $userData['type'] : null;

// Función para obtener iniciales
function getInitials($firstName, $lastName)
{
   // Si ambos están vacíos, devolver '--'
   if (empty($firstName) && empty($lastName)) {
      return '--';
   }
   // Si solo hay un nombre (sin apellido)
   if (empty($lastName)) {
      return strtoupper(substr($firstName, 0, 2));
   }
   // Si hay nombre y apellido
   return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
}

function normalizeText($text)
{
   return ucwords(strtolower(trim($text)));
}

// Obtener primer nombre y primer apellido
$firstName = normalizeText(explode(' ', $userData['firstName'])[0] ?? '');
$lastName = normalizeText(explode(' ', $userData['lastName'])[0] ?? '');
$initials = getInitials($firstName, $lastName);
$fullName = $firstName . ' ' . $lastName;
$fullNameComplete = ($userData['firstName'] ?? '') . ' ' . ($userData['lastName'] ?? '');
$email = $userData['email'] ?? '';

// Definir menú según rol
$menuItems = [
   'dashboard' => [
      'icon' => 'ri-home-2-fill',
      'text' => 'Inicio',
      'roles' => [SessionManager::ROLE_ADMIN, SessionManager::ROLE_TEACHER, SessionManager::ROLE_STUDENT]
   ],
   'game' => [
      'icon' => 'ri-gamepad-fill',
      'text' => 'Juego',
      'roles' => [SessionManager::ROLE_ADMIN, SessionManager::ROLE_STUDENT, SessionManager::ROLE_TEACHER]
   ],
   'levels' => [
      'icon' => 'ri-stairs-fill',
      'text' => 'Niveles',
      'roles' => [SessionManager::ROLE_ADMIN, SessionManager::ROLE_TEACHER]
   ],
   'analytics' => [
      'icon' => 'ri-bar-chart-box-fill',
      'text' => 'Estadisticas',
      'roles' => [SessionManager::ROLE_ADMIN, SessionManager::ROLE_TEACHER]
   ],
   'analyticsStudent' => [
      'icon' => 'ri-bar-chart-box-fill',
      'text' => 'Análisis',
      'roles' => [SessionManager::ROLE_STUDENT]
   ]
];
$usersManagement = [
   'reviewers' => [
      'icon' => 'ri-user-search-fill',
      'text' => 'Revisores',
      'roles' => [SessionManager::ROLE_ADMIN, SessionManager::ROLE_TEACHER]
   ],
   'teachers' => [
      'icon' => 'ri-group-fill',
      'text' => 'Docentes',
      'roles' => [SessionManager::ROLE_ADMIN]
   ]
];
$settingsItems = [
   'settings' => [
      'icon' => 'ri-settings-3-fill',
      'text' => 'Ajustes',
      'roles' => [SessionManager::ROLE_ADMIN, SessionManager::ROLE_STUDENT, SessionManager::ROLE_TEACHER]
   ],
];
?>

<!--=============== SIDEBAR ===============-->
<nav class="sidebar" id="sidebar">
   <div class="sidebar__container">
      <div class="sidebar__user">
         <div class="sidebar__img" id="avatar-info" data-username="<?= htmlspecialchars($fullNameComplete, ENT_QUOTES, 'UTF-8') ?>">
            <!--<img src="assets/img/perfil.png" alt="image">-->
            <span class="initials"><?= $initials ?></span>
         </div>

         <div class="sidebar__info">
            <!--<h3>Rix Methil</h3>
                  <span>rix123@email.com</span>-->
            <h3><?= $fullName ?></h3>
            <span><?= $email ?></span>
         </div>
      </div>

      <div class="sidebar__content">
         <div>
            <h3 class="sidebar__title">GESTIÓN</h3>
            <!-- EXAMPLE AFTHER RESULT
            <div class="sidebar__list">
               <a href="<?= base_url(); ?>/analytics"
                  class="sidebar__link <?= isActiveRoute($data['current_section'], 'analytics') ?>">
                  <i class="ri-bar-chart-box-fill"></i>
                  <span data-i18n="nav.statistics">Estadisticas</span>
               </a>
            </div>
            -->
            <div class="sidebar__list">
               <?php foreach ($menuItems as $route => $item): ?>
                  <?php if (in_array($userType, $item['roles'])): ?>
                     <a href="<?= base_url(); ?>/<?= $route ?>"
                        class="sidebar__link <?= isActiveRoute($data['current_section'], $route) ?>">
                        <i class="ri <?= $item['icon'] ?>"></i>
                        <span data-i18n="nav.<?= $route ?>"><?= $item['text'] ?></span>
                     </a>
                  <?php endif; ?>
               <?php endforeach; ?>
            </div>
         </div>

         <div>
            <h3 class="sidebar__title">AJUSTES</h3>
            <div class="sidebar__list">
               <?php foreach ($settingsItems as $route => $item): ?>
                  <?php if (in_array($userType, $item['roles'])): ?>
                     <a href="<?= base_url(); ?>/<?= $route ?>"
                        class="sidebar__link <?= isActiveRoute($data['current_section'], $route) ?>">
                        <i class="ri <?= $item['icon'] ?>"></i>
                        <span data-i18n="nav.<?= $route ?>"><?= $item['text'] ?></span>
                     </a>
                  <?php endif; ?>
               <?php endforeach; ?>
            </div>
         </div>

         <?php if ($userType != SessionManager::ROLE_STUDENT): ?>
            <div>
            <h3 class="sidebar__title">ROLES</h3>
            <div class="sidebar__list">
               <?php foreach ($usersManagement as $route => $item): ?>
                  <?php if (in_array($userType, $item['roles'])): ?>
                     <a href="<?= base_url(); ?>/<?= $route ?>"
                        class="sidebar__link <?= isActiveRoute($data['current_section'], $route) ?>">
                        <i class="ri <?= $item['icon'] ?>"></i>
                        <span data-i18n="nav.<?= $route ?>"><?= $item['text'] ?></span>
                     </a>
                  <?php endif; ?>
               <?php endforeach; ?>
            </div>
         </div>         
         <?php endif; ?>
      </div>

      <div class="sidebar__actions">
         <button>
            <i class="ri-moon-clear-fill sidebar__link sidebar__theme" id="theme-button">
               <span data-i18n="nav.theme">Tema</span>
            </i>
         </button>

         <button class="sidebar__link" id="language-button">
            <i class="ri-translate-2"></i>
            <span id="language-text">ES</span>
         </button>

         <button class="sidebar__link logout-button">
            <i class="ri-logout-box-r-fill"></i>
            <span data-i18n="nav.logout">Cerrar Sesión</span>
         </button>
      </div>
   </div>
</nav>