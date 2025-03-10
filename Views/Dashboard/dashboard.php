<?php headerGame($data); ?>

<!--=============== MAIN ===============-->
<main class="main container" id="main">
  <div>
    <h1><i class="fa fa-dashboard"></i><?= $data['page_title'] ?></h1>
  </div>
</main>

<script>
    const base_url = "<?= base_url(); ?>";
</script>
<?php footerGame($data); ?>