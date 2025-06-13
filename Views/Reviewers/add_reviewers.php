<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div class="game-container">

        <!-- Botón Ver más -->
        <div class="header-section">
                <h1 class="page-title">Asignar Usuarios Revisores</h1>
            </div>

        <!-- Nueva sección para el botón del reporte -->
        <div class="report-actions">
            <button id="generateReportBtn" class="btn btn-primary">
                <i class='bx bx-file'></i>
                <span data-i18n="details_classification.buttons.generate_report">Generar Reporte</span>
            </button>
        </div>

         <!-- Tabla principal -->
        <div class="bottom-data">
            <div class="orders container-table">
                <div class="header-table">
                    <i class='bx ri-user-community-fill'></i>
                    <h3 >Estudiantes Subscritos</h3>
                </div>
                <table id="tableJugadores" class="table-players nowrap">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Usuario</th>
                            <th>Nombres</th>
                            <th>Apellidos</th>
                            <th>Correo</th>
                            <th>Es Revisor</th>
                            <th>Estado</th>
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