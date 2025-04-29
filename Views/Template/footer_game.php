
<!--=============== MAIN JS ===============-->
      <script src="<?= media(); ?>/js/plugins/sidebar/main.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js"></script>
      <script src="<?= media(); ?>/js/CryptoModule.js"></script>
      <script src="<?= media(); ?>/js/plugins/sweetalert2.all.min.js"></script>
      <script src="<?= media(); ?>/js/components/LogoutHandler.js"></script>
      <script src="<?= media(); ?>/js/plugins/avatar.js"></script>
      <script>
        function updateAvatarColor() {
            const avatarElement = document.getElementById('avatar-info');
            if (!avatarElement || !window.AvatarModule) return;

            const username = avatarElement.getAttribute('data-username') || '';
            const color = AvatarModule.getAvatarColor(username);
            avatarElement.style.backgroundColor = color;
         }
         window.addEventListener('load', updateAvatarColor);
      </script>
   </body>
</html>