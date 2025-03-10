<?php headerGame($data); ?>

<main class="main container" id="main">
   <!--- INICIO CONTENIDO --->

   <div class="game-container">
      <div class="analyse player-dashboard">
         <div class="player-profile">
            <div class="player-info">
               <div class="avatar-circle" style="background-color: var(--primary)">
                  <span class="initials">AC</span>
               </div>
               <div class="player-details">
                  <span class="player-name">Alexander Castro</span>
                  <span class="player-email">alexander@mail.com</span>
               </div>
            </div>
         </div>
         <div class="player-stats">
            <div class="card-item li">
               <i class='bx light-success ri-timer-flash-line'></i>
               <span class="info">
                  <div class="title-wrapper">
                     <p class="analyse-title" data-i18n="details_classification.stats.time.title">Tiempo</p>
                     <i class='bx bx-info-circle info-icon' onclick='DashboardModule.showStatsInfo("time")'></i>
                  </div>
                  <p class="analyse-info" id="tiempo-total">
                     0 min
                  </p>
               </span>
            </div>
            <div class="card-item li">
               <i class='bx light-primary ri-reset-left-line'></i>
               <span class="info">
                  <div class="title-wrapper">
                     <p class="analyse-title" data-i18n="details_classification.stats.attempts.title">Número Intentos</p>
                     <i class='bx bx-info-circle info-icon' onclick='DashboardModule.showStatsInfo("attempts")'></i>
                  </div>
                  <p class="analyse-info" id="numero-intentos">
                     0
                  </p>
               </span>
            </div>
         </div>
      </div>

      <!-- Nueva sección para el botón del reporte -->
      <div class="report-actions">
         <button id="generateReportBtn" class="btn btn-primary">
            <i class='bx bx-file'></i>
            <span data-i18n="details_classification.buttons.generate_report">Generar Reporte</span>
         </button>
      </div>

      <div class="bottom-data">
         <div class="orders">
            <div class="header-table">
               <i class='bx ri-user-community-fill'></i>
               <h3 data-i18n="details_classification.table.title">Intentos</h3>
            </div>
            <table id="tableIntentos" class="table-players nowrap">
               <thead>
                  <tr>
                     <th></th>
                     <th>Num. Intento</th>
                     <th>Tiempo</th>
                     <th>Num. Movimientos</th>
                     <th>Requisitos</th>
                     <th>Correctos</th>
                     <th>Incorrectos</th>
                     <th>Precision Aciertos</th>
                     <th>Precision Errores</th>
                     <th>Precision Progresiva</th>
                     <th>Fecha</th>
                     <th>Ver</th>
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