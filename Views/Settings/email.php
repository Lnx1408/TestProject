<?php headerGame($data); ?>

<main class="main container" id="main">
    <div class="email-container">
        <!-- Navegación de migas de pan -->
        <div class="breadcrumbs-container">
            <ul class="breadcrumbs">
                <?php foreach ($data['breadcrumbs'] as $index => $crumb): ?>
                    <li>
                        <?php if (!empty($crumb['url'])): ?>
                            <a href="<?= base_url() . '/' . $crumb['url']; ?>"><?= $crumb['name']; ?></a>
                        <?php else: ?>
                            <a href="javascript:void(0);"><?= $crumb['name']; ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Cabecera -->
        <div class="email-header">
            <h1 class="email-title" data-i18n="settings.email.title">Cambiar Correo Electrónico</h1>
            <p class="email-description" data-i18n="settings.email.description">Actualiza tu dirección de correo electrónico</p>
        </div>

        <!-- Contenido -->
        <div class="email-content">
            <form id="emailForm" class="email-form">
                <div class="form-section">
                    <div class="form-group">
                        <label data-i18n="settings.email.current_email">Correo electrónico actual</label>
                        <div class="readonly-field" id="currentEmail"></div>
                    </div>

                    <div class="form-group">
                        <label for="newEmail" data-i18n="settings.email.new_email">Nuevo correo electrónico</label>
                        <input type="email" id="newEmail" name="newEmail" class="form-control" required>
                        <div id="emailValidation" class="validation-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword" data-i18n="settings.email.current_password">Contraseña actual</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="form-control" required>
                            <i class='bx bx-hide toggle-password' data-target="password"></i>
                        </div>
                        <small class="form-hint" data-i18n="settings.email.password_hint">Ingresa tu contraseña actual para confirmar el cambio</small>
                    </div>
                </div>

                <div class="email-security-info">
                    <div class="security-icon">
                        <i class='bx bx-envelope'></i>
                    </div>
                    <div class="security-text">
                        <h3 data-i18n="settings.email.info_title">Sobre tu correo electrónico</h3>
                        <p data-i18n="settings.email.info_description">Tu correo electrónico es tu principal método de contacto y recuperación de cuenta. Asegúrate de ingresar un correo válido al que tengas acceso.</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="saveEmailBtn" class="btn btn-primary" data-i18n="settings.email.submit_button">
                        Cambiar correo electrónico
                    </button>
                </div>
            </form>
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