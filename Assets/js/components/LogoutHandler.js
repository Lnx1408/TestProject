const LogoutModule = {
    translations: {
        get: (key) => LanguageManager.getTranslation(`logout.${key}`)
    },

    init() {
        this.bindEvents();
    },

    bindEvents() {
        const logoutButtons = document.querySelectorAll('.logout-button');
        logoutButtons.forEach(button => {
            button.addEventListener('click', () => this.showLogoutConfirmation());
        });
    },

    async showLogoutConfirmation() {
        try {
            const result = await Swal.fire({
                title: this.translations.get('confirmation.title'),
                text: this.translations.get('confirmation.message'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1976D2',
                cancelButtonColor: '#D32F2F',
                confirmButtonText: this.translations.get('confirmation.confirm'),
                cancelButtonText: this.translations.get('confirmation.cancel'),
                customClass: {
                    container: 'logout-confirmation-modal',
                    popup: 'logout-confirmation-popup',
                }
            });

            if (result.isConfirmed) {
                window.location.href = `${base_url}/Logout`;
            }
        } catch (error) {
            console.error('Error in logout process:', error);
            // Mostrar mensaje de error si algo falla
            Swal.fire({
                title: this.translations.get('errors.title'),
                text: this.translations.get('errors.message'),
                icon: 'error',
                confirmButtonColor: '#1976D2',
                customClass: {
                    container: 'logout-confirmation-modal',
                    popup: 'logout-confirmation-popup',
                }
            });
        }
    }
};

document.addEventListener('DOMContentLoaded', () => LogoutModule.init());