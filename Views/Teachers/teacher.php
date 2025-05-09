<?php headerGame($data); ?>

<main class="main container" id="main">
    <div class="teacher-container">
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
        <div class="teacher-header">
            <h1 class="teacher-title" data-i18n="teachers.teacher.title">Información del Perfil</h1>
            <p class="teacher-description" data-i18n="teachers.teacher.description">Actualiza tu información personal
            </p>
        </div>

        <!-- Formulario de perfil -->
        <div class="teacher-content">
            <form id="teacherForm" class="teacher-form">
                <div class="form-section">

                    <!--TipoUsuario-->
                    <div class="mb-3" style="display:none;">
                        <div class="input-group">
                            <select id="SelectTypeUser" name="txtTypeUser" class="form-select show-tick"
                                data-width="fit">
                                <option value="D">Docente</option>
                            </select>
                        </div>
                    </div>
                    <!--Usuario-->
                    <div class="form-group">
                        <label for="txtUserRegister" data-i18n="teachers.teacher.user">Usuario</label>
                        <div class="teacher-field">
                            <input id="txtUserRegister" name="txtUserRegister" class="form-control" type="text"
                                placeholder="aperalta" required autocomplete="off">
                            <i class='bx bx-user toggle-teacher' data-target="txtUserRegister"></i>
                        </div>
                    </div>

                    <!--Nombres-->
                    <div class="form-group">
                        <label for="txtFirstNameRegister" data-i18n="teachers.teacher.name">Nombres</label>
                        <div class="teacher-field">
                            <input id="txtFirstNameRegister" name="txtFirstNameRegister" class="form-control"
                                type="text" placeholder="Arlette" required autocomplete="off">
                            <i class='bx bx-id-card toggle-teacher' data-target="txtUserRegister"></i>
                        </div>
                    </div>

                    <!--Apellidos-->
                    <div class="form-group">
                        <label for="txtLastNameRegister" data-i18n="teachers.teacher.lastname">Apellidos</label>
                        <div class="teacher-field">
                            <input id="txtLastNameRegister" name="txtLastNameRegister" class="form-control" type="text"
                                placeholder="Peralta" required autocomplete="off">
                            <i class='bx bx-id-card toggle-teacher' data-target="txtUserRegister"></i>
                        </div>
                    </div>

                    <!--Correo-->
                    <div class="form-group">
                        <label for="txtEmailRegister" data-i18n="teachers.teacher.email">Correo</label>
                        <div class="teacher-field">
                            <input id="txtEmailRegister" name="txtEmailRegister" class="form-control" type="email"
                                placeholder="aperalta@example.com" required autocomplete="off">
                            <i class='bx bx-envelope toggle-teacher' data-target="txtUserRegister"></i>
                        </div>
                    </div>

                    <!--Password-->
                    <div class="form-group">
                        <label for="txtPasswordRegister" data-i18n="teachers.teacher.password">Contraseña</label>
                        <div class="teacher-field">
                            <input id="txtPasswordRegister" name="txtPasswordRegister" class="form-control" type="password" placeholder="Contraseña" required autocomplete="off">
                            <i class='bx bx-lock toggle-teacher' data-target="newPassword"></i>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-progress"></div>
                            </div>
                            <span class="strength-text" data-i18n="teachers.teacher.strength.default">Fortaleza de
                                contraseña</span>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="saveTeacherBtn" class="btn btn-primary" disabled
                        data-i18n="teachers.teacher.buttons.addTeacher">
                        REGISTRAR DOCENTE
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