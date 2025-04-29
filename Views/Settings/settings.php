<?php headerGame($data); ?>

<main class="main container" id="main">
    <div class="settings-container">
        <div class="settings-header">
            <h1 class="settings-title" data-i18n="settings.title">Ajustes</h1>
            <p class="settings-description" data-i18n="settings.description">Administra tu cuenta y preferencias</p>
        </div>

        <div class="settings-content">
            <!-- Estructura de acordeón para las opciones de configuración -->
            <div class="settings-menu">
                <!-- Sección: Información Personal -->
                <div class="settings-section">
                    <div class="op-section-header">
                        <button class="section-header" onclick="toggleSection('personalInfo')">
                            <div class="header-icon">
                                <i class='bx bx-user'></i>
                            </div>
                            <div class="header-text" data-i18n="settings.sections.personal_info">
                                Información Personal
                            </div>
                            <div class="header-chevron">
                                <i class='bx bx-chevron-down'></i>
                            </div>
                        </button>
                    </div>
                    <div id="personalInfo" class="section-content">
                        <a href="<?= base_url(); ?>/settings/profile" class="menu-option">
                            <div class="option-info">
                                <h3 data-i18n="settings.options.profile.title">Información del Perfil</h3>
                                <p data-i18n="settings.options.profile.description">Gestiona tu información personal</p>
                            </div>
                            <div class="option-chevron">
                                <i class='bx bx-chevron-right'></i>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Sección: Actividad y Seguridad -->
                <div class="settings-section">
                    <div class="op-section-header">
                        <button class="section-header" onclick="toggleSection('securityInfo')">
                            <div class="header-icon">
                                <i class='bx bx-shield'></i>
                            </div>
                            <div class="header-text" data-i18n="settings.sections.security">
                                Actividad y Seguridad
                            </div>
                            <div class="header-chevron">
                                <i class='bx bx-chevron-down'></i>
                            </div>
                        </button>
                    </div>
                    <div id="securityInfo" class="section-content">
                        <a href="<?= base_url(); ?>/settings/sessions" class="menu-option">
                            <div class="option-info">
                                <h3 data-i18n="settings.options.sessions.title">Historial de inicios de sesión</h3>
                                <p data-i18n="settings.options.sessions.description">Revisa y gestiona tus sesiones activas</p>
                            </div>
                            <div class="option-chevron">
                                <i class='bx bx-chevron-right'></i>
                            </div>
                        </a>
                        <a href="<?= base_url(); ?>/settings/activity" class="menu-option">
                            <div class="option-info">
                                <h3 data-i18n="settings.options.activity.title">Historial de actividad</h3>
                                <p data-i18n="settings.options.activity.description">Consulta tu historial detallado de inicios y cierres de sesión</p>
                            </div>
                            <div class="option-chevron">
                                <i class='bx bx-chevron-right'></i>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Sección: Configuración de Cuenta -->
                <div class="settings-section">
                    <div class="op-section-header">
                        <button class="section-header" onclick="toggleSection('accountInfo')">
                            <div class="header-icon">
                                <i class='bx bx-cog'></i>
                            </div>
                            <div class="header-text" data-i18n="settings.sections.account">
                                Configuración de Cuenta
                            </div>
                            <div class="header-chevron">
                                <i class='bx bx-chevron-down'></i>
                            </div>
                        </button>
                    </div>
                    <div id="accountInfo" class="section-content">
                        <a href="<?= base_url(); ?>/settings/password" class="menu-option">
                            <div class="option-info">
                                <h3 data-i18n="settings.options.password.title">Cambiar contraseña</h3>
                                <p data-i18n="settings.options.password.description">Actualiza tu contraseña de acceso</p>
                            </div>
                            <div class="option-chevron">
                                <i class='bx bx-chevron-right'></i>
                            </div>
                        </a>
                        <a href="<?= base_url(); ?>/settings/email" class="menu-option">
                            <div class="option-info">
                                <h3 data-i18n="settings.options.email.title">Cambiar correo</h3>
                                <p data-i18n="settings.options.email.description">Actualiza tu dirección de correo electrónico</p>
                            </div>
                            <div class="option-chevron">
                                <i class='bx bx-chevron-right'></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const base_url = "<?= base_url(); ?>";
</script>
<?php
if (!empty($data['page_functions_js'])) {
    foreach ($data['page_functions_js'] as $js) {
        echo '<script src="' . media() . '/js/' . $js . '"></script>';
    }
}
?>
<?php footerGame($data); ?>