class GameEntryManager {
    constructor() {

        this.config = {
            endpoints: {
                validateCode: `${base_url}/Game/validate_game_code`,
                games: {
                    classification: `${base_url}/Game/game_clasification`,
                    construction: `${base_url}/Game/game_construction`
                }
            }
        };

        this.translations = {
            get: (key) => LanguageManager.getTranslation(`game_welcome.${key}`)
        };
        this.bindEvents();
    }

    bindEvents() {
        const startButton = document.getElementById('startGameBtn');
        if (startButton) {
            startButton.addEventListener('click', () => this.showGameCodeModal());
        }
    }

    async showGameCodeModal() {
        const { value: gameCode } = await Swal.fire({
            title: this.translations.get('modal.title'),
            input: 'text',
            inputPlaceholder: this.translations.get('modal.input_placeholder'),
            showCancelButton: true,
            confirmButtonText: this.translations.get('modal.submit_button'),
            cancelButtonText: this.translations.get('modal.cancel_button'),
            customClass: {
                popup: 'game-entry-popup',
            },
            confirmButtonColor: '#1976D2',
            inputValidator: (value) => {
                if (!value) {
                    return this.translations.get('modal.invalid_code');
                }
            }
        });

        if (gameCode) {
            await this.validateAndRedirect(gameCode);
        }
    }

    async validateAndRedirect(gameCode) {
        try {
            Swal.fire({
                title: this.translations.get('modal.loading'),
                allowOutsideClick: false,
                customClass: {
                    popup: 'game-entry-popup',
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const requestData = {
                gamecode: gameCode
            };

            const response = await fetch(this.config.endpoints.validateCode, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt(requestData)
                })
            });

            const data = await response.json();
            const decryptedData = CryptoModule.decrypt(data.data);

            if (decryptedData.success) {
                // Redirigir según el tipo de juego
                const gameUrl = decryptedData.gameType === 'MOD-CLASS'
                    ? this.config.endpoints.games.classification
                    : this.config.endpoints.games.construction;

                window.location.href = `${gameUrl}?code=${gameCode}`;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: this.translations.get('modal.invalid_code'),
                    text: decryptedData.message,
                    confirmButtonColor: '#1976D2',
                    customClass: {
                        popup: 'game-entry-popup',
                    }
                });
            }
        } catch (error) {
            console.error('Error validating game code:', error);
            Swal.fire({
                icon: 'error',
                title: this.translations.get('modal.error_message'),
                confirmButtonColor: '#1976D2',
                customClass: {
                    popup: 'game-entry-popup',
                }
            });
        }
    }
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', async () => {
    new GameEntryManager();
});