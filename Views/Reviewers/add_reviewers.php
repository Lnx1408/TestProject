<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div id="ai-generator-modal-izi" class="iziModal"></div>
    <div class="game-container">
        <div class="create-game-container">
            <!-- Header con título y contador -->
            <div class="header-section">
                <h1 class="page-title">Asignar Usuarios Revisores</h1>
            </div>

            <!-- Tabla principal -->
            <div class="bottom-data">
                <div class="data-info container-table">
                    <div class="header-table">
                        <i class='bx ri-user-community-fill'></i>
                        <h2 >Estudiantes Subscritos</h2>
                    </div>
                    
                    
                    <table id="userReviewerTable" class="nowrap">
                        <thead>
                            <tr>
                                <th></th>
                                <th >Usuario</th>
                                <th >Nombres</th> 
                                <th >Correo</th>
                                <th >Es Revisor</th>
                                <th >Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
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