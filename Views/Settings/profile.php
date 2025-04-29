<?php headerGame($data); ?>

<main class="main container" id="main">
    <div class="profile-container">
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
        <div class="profile-header">
            <h1 class="profile-title" data-i18n="settings.profile.title">Información del Perfil</h1>
            <p class="profile-description" data-i18n="settings.profile.description">Actualiza tu información personal</p>
        </div>

        <!-- Formulario de perfil -->
        <div class="profile-content">
            <form id="profileForm" class="profile-form">
                <!-- Avatar -->
                <div class="avatar-section">
                    <div class="player-profile">
                        <div class="avatar-circle" id="profileAvatar">
                            <span class="initials-profile" id="profileInitials">--</span>
                        </div>
                    </div>
                    <p class="avatar-info" data-i18n="settings.profile.avatar_info">Avatar generado a partir de tus iniciales</p>
                </div>

                <!-- Información personal -->
                <div class="form-section">
                    <h2 class="section-title" data-i18n="settings.profile.personal_data">Datos Personales</h2>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="profileFirstName" data-i18n="settings.profile.first_name">Nombres</label>
                            <div class="input-wrapper editable-field">
                                <input type="text" class="form-control editable" id="profileFirstName" name="nombres">
                                <i class='bx bx-pencil edit-icon'></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="profileLastName" data-i18n="settings.profile.last_name">Apellidos</label>
                            <div class="input-wrapper editable-field">
                                <input type="text" class="form-control editable" id="profileLastName" name="apellidos">
                                <i class='bx bx-pencil edit-icon'></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label data-i18n="settings.profile.username">Usuario</label>
                            <div class="readonly-field" id="profileUsername"></div>
                        </div>

                        <div class="form-group">
                            <label data-i18n="settings.profile.email">Correo electrónico</label>
                            <div class="readonly-field" id="profileEmail"></div>
                        </div>

                        <div class="form-group">
                            <label data-i18n="settings.profile.user_type">Tipo de usuario</label>
                            <div class="readonly-field" id="profileType"></div>
                        </div>

                        <div class="form-group">
                            <label data-i18n="settings.profile.registration_date">Fecha de registro</label>
                            <div class="readonly-field" id="profileRegDate"></div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción (inicialmente ocultos) -->
                <div class="form-actions" id="formActions" style="display: none;">
                    <button type="button" class="btn btn-outline-secondary" id="cancelChangesBtn" data-i18n="settings.profile.buttons.cancel">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="saveChangesBtn" data-i18n="settings.profile.buttons.save">Guardar cambios</button>
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