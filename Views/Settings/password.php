<?php headerGame($data); ?>

<main class="main container" id="main">
    <div class="password-container">
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
        <div class="password-header">
            <h1 class="password-title" data-i18n="settings.password.title">Cambiar Contraseña</h1>
            <p class="password-description" data-i18n="settings.password.description">Actualiza tu contraseña de acceso</p>
        </div>

        <!-- Contenido -->
        <div class="password-content">
            <form id="passwordForm" class="password-form">
                <div class="form-section">
                    <div class="form-group">
                        <label for="currentPassword" data-i18n="settings.password.current_password">Contraseña actual</label>
                        <div class="password-field">
                            <input type="password" id="currentPassword" name="currentPassword" class="form-control" required>
                            <i class='bx bx-hide toggle-password' data-target="currentPassword"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="newPassword" data-i18n="settings.password.new_password">Nueva contraseña</label>
                        <div class="password-field">
                            <input type="password" id="newPassword" name="newPassword" class="form-control" required>
                            <i class='bx bx-hide toggle-password' data-target="newPassword"></i>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-progress"></div>
                            </div>
                            <span class="strength-text" data-i18n="settings.password.strength.default">Fortaleza de contraseña</span>
                        </div>
                        <ul class="password-requirements">
                            <li id="reqLength"><i class='bx bx-x'></i> <span data-i18n="settings.password.requirements.length">Mínimo 8 caracteres</span></li>
                            <li id="reqLower"><i class='bx bx-x'></i> <span data-i18n="settings.password.requirements.lowercase">Al menos una letra minúscula</span></li>
                            <li id="reqUpper"><i class='bx bx-x'></i> <span data-i18n="settings.password.requirements.uppercase">Al menos una letra mayúscula</span></li>
                            <li id="reqNumber"><i class='bx bx-x'></i> <span data-i18n="settings.password.requirements.number">Al menos un número</span></li>
                        </ul>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword" data-i18n="settings.password.confirm_password">Confirmar nueva contraseña</label>
                        <div class="password-field">
                            <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                            <i class='bx bx-hide toggle-password' data-target="confirmPassword"></i>
                        </div>
                        <div id="passwordMatch" class="match-indicator">
                            <i class='bx bx-x'></i> <span data-i18n="settings.password.validation.passwords_not_match">Las contraseñas no coinciden</span>
                        </div>
                    </div>
                </div>

                <div class="password-security-info">
                    <div class="security-icon">
                        <i class='bx bx-shield'></i>
                    </div>
                    <div class="security-text">
                        <h3 data-i18n="settings.password.info_title">Sobre tu contraseña</h3>
                        <p data-i18n="settings.password.info_description">Es importante crear una contraseña fuerte y única para proteger tu cuenta. No la compartas con nadie ni uses la misma contraseña para otros servicios.</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="savePasswordBtn" class="btn btn-primary" disabled data-i18n="settings.password.buttons.change_password">
                        Cambiar contraseña
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