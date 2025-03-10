class LevelsGameManager {
    constructor() {
        this.config = {
            endpoints: {
                createClassification: `${base_url}/Levels/create_classification`,
                createConstruction: `${base_url}/Levels/create_construction`
            }
        };

        this.translations = {
            get: (key) => LanguageManager.getTranslation(`levels_welcome.${key}`)
        };

        this.bindEvents();
    }

    bindEvents() {
        const createButton = document.getElementById('createGameBtn');
        if (createButton) {
            createButton.addEventListener('click', () => this.showGameTypeModal());
        }
    }

    showGameTypeModal() {
        Swal.fire({
            title: this.translations.get('modal.title'),
            html: `
                <p class="modal-subtitle">${this.translations.get('modal.subtitle')}</p>
                <div class="game-types-container">
                    <button class="game-type-btn classification" onclick="window.location.href='${this.config.endpoints.createClassification}'">
                        <i class='bx ri-list-check-3'></i>
                        <h3>${this.translations.get('modal.classification.title')}</h3>
                        <p>${this.translations.get('modal.classification.description')}</p>
                    </button>
                    <button class="game-type-btn construction" onclick="window.location.href='${this.config.endpoints.createConstruction}'">
                        <i class='bx ri-puzzle-2-fill'></i>
                        <h3>${this.translations.get('modal.construction.title')}</h3>
                        <p>${this.translations.get('modal.construction.description')}</p>
                    </button>
                </div>
            `,
            showCancelButton: true,
            showConfirmButton: false,
            cancelButtonText: this.translations.get('modal.cancel_button'),
            width: '600px',
            customClass: {
                container: 'game-type-modal',
                popup: 'game-levels-popup',
            }
        });
    }
}

// Inicialización
document.addEventListener('DOMContentLoaded', async () => {
    new LevelsGameManager();
});