<?php headerGame($data); ?>

<!--=============== MAIN ===============-->

<main
  class="main container"
  id="main">

  <div class="welcome-container">

    <!-- #region Student -->
    <div
      style="display: flex; justify-content: center; align-items: center; flex-direction: column;">
      <!-- Welcome -->
      <div style="display: flex; text-align: center;">
        <h1
          data-i18n="dashboard_welcome.student.welcome"
          style="font-size: 32px; font-weight: bold; color: #00CED1;">
          👋🏼 Bienvenido a

        </h1>
      </div>

      <!-- Logo -->
      <div
        class="logo-section"
        style="margin-top: 32px;">
        <img
          src="<?= media(); ?>/images/logoinicio.png"
          alt="Logo ReqScape"
          class="game-logo" style="height: 30vh;width: auto;">
      </div>

      <!-- Phrase 1  -->
      <div
        style="text-align: center; margin-top: 32px; max-width: 600px; height: auto;">
        <p
          data-i18n="dashboard_welcome.student.phrase1"
          style=" font-size: 24px; font-weight: bold; color: #363949">
          Estamos encantados de tenerte aquí. Tu viaje de aprendizaje y
          crecimiento comienza ahora.

        </p>
      </div>

      <!-- Phrase 2 -->
      <div style="margin-top: 32px; max-width: 600px; height: auto;">
        <p
          data-i18n="dashboard_welcome.student.phrase2"
          style=" font-size: 17px; font-weight: bold;">
          Te espera un espacio dedicado al aprendizaje.

        </p>
      </div>
    </div>
    <!-- #endregion -->

  </div>

</main>

<script>
  const base_url = "<?= base_url(); ?>";
</script>

<?php footerGame($data); ?>