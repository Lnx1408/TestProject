<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div class="game-container">

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
        <div class="header-section report-actions">
                <h1 class="page-title" id="page-title-r">Asignar Docente Revisor a:</h1>
        </div>
        
        <div class="report-actions">
        </div>

         <!-- Tabla principal -->
        <div class="bottom-data">
            <div class="orders container-table">
                <div class="header-table">
                    <i class='bx ri-user-community-fill'></i>
                    <h3>Docentes Registrados</h3>
                </div>
                <table id="tableJugadores" class="table-players nowrap">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Estudiante</th>
                            <th>Nombre Usuario</th>
                            <th>Correo</th>
                            <th>Tiempo de Juego</th>
                            <th>Es Revisor</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!--- FIN CONTENIDO --->
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