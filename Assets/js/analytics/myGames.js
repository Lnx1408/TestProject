class MyGamesManager {
    constructor() {
        this.config = {
            endpoints: {
                getGames: `${base_url}/Analytics/get_my_games`,
            },
            initialLoadLimit: 10,  // Límite inicial de cards a mostrar
            loadMoreLimit: 5,      // Cantidad de cards adicionales a cargar con "Ver más"
            colors: [
                { bg: '#E3F2FD', accent: '#2196F3' }, // Blue
                { bg: '#F3E5F5', accent: '#9C27B0' }, // Purple
                { bg: '#E8F5E9', accent: '#4CAF50' }, // Green
                { bg: '#FFF3E0', accent: '#FF9800' }, // Orange
                { bg: '#F3E5F5', accent: '#9C27B0' }, // Purple
                { bg: '#E1F5FE', accent: '#03A9F4' }, // Light Blue
                { bg: '#E0F2F1', accent: '#009688' }, // Teal
                { bg: '#F1F8E9', accent: '#8BC34A' }, // Light Green
            ]
        };

        this.state = {
            classification: {
                offset: 0,
                hasMore: true,
                items: []
            },
            construction: {
                offset: 0,
                hasMore: true,
                items: []
            },
            searchTerm: ''
        };

        this.translations = {
            get: (key) => LanguageManager.getTranslation(`my_games.${key}`)
        };

        this.elements = {
            classificationGrid: document.getElementById('classificationGrid'),
            constructionGrid: document.getElementById('constructionGrid'),
            loadMoreClassification: document.getElementById('loadMoreClassification'),
            loadMoreConstruction: document.getElementById('loadMoreConstruction'),
            loadLessClassification: document.getElementById('loadLessClassification'),
            loadLessConstruction: document.getElementById('loadLessConstruction'),
            searchInput: document.getElementById('searchInput'),
            classificationTotal: document.getElementById('classificationTotal'),
            constructionTotal: document.getElementById('constructionTotal')
        };

        this.bindEvents();
        this.init();
    }

    bindEvents() {
        // Event listeners para botones "Ver más"
        if (this.elements.loadMoreClassification) {
            this.elements.loadMoreClassification.addEventListener('click', () => 
                this.loadMore('classification'));
        }
        if (this.elements.loadMoreConstruction) {
            this.elements.loadMoreConstruction.addEventListener('click', () => 
                this.loadMore('construction'));
        }

        // Event listeners para botones "Ver menos"
        if (this.elements.loadLessClassification) {
            this.elements.loadLessClassification.addEventListener('click', () => 
                this.loadLess('classification'));
        }
        if (this.elements.loadLessConstruction) {
            this.elements.loadLessConstruction.addEventListener('click', () => 
                this.loadLess('construction'));
        }

        // Event listener para búsqueda
        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener('input', 
                this.debounce(() => this.handleSearch(), 300));
        }

        // Event listeners para botones de colapsar/expandir
        document.querySelectorAll('.toggle-view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.toggleSection(e.currentTarget));
        });

        // Manejar cambios de idioma
        document.addEventListener('languageChanged', () => {
            this.updateTranslations();
        });
    }

    async init() {
        try {
            await this.loadInitialData();
        } catch (error) {
            console.error('Error initializing games:', error);
            this.showError(this.translations.get('errors.initialization'));
        }
    }

    async loadInitialData() {
        // Mostrar skeletons mientras carga
        this.showLoadingState();

        try {
            const response = await fetch(this.config.endpoints.getGames, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt({
                        offset: {
                            classification: this.state.classification.offset,
                            construction: this.state.construction.offset
                        },
                        limit: this.config.itemsPerLoad
                    })
                })
            });

            const data = await response.json();
            const decryptedData = CryptoModule.decrypt(data.data);

            if (!decryptedData.success) {
                throw new Error(decryptedData.message);
            }

            // Actualizar estado con los datos iniciales
            this.state.classification.items = decryptedData.classification || [];
            this.state.construction.items = decryptedData.construction || [];
            
            // Actualizar totales
            this.updateTotals(decryptedData.totals);
            
            // Renderizar datos iniciales
            this.renderGames();

            // Verificar si hay más datos para cargar
            this.updateLoadMoreButtons(decryptedData.hasMore);

        } catch (error) {
            console.error('Error loading initial data:', error);
            this.showError(this.translations.get('errors.loading'));
        } finally {
            this.hideLoadingState();
        }
    }

    updateTotals(totals) {
        if (this.elements.classificationTotal) {
            this.elements.classificationTotal.textContent = totals.classification || 0;
        }
        if (this.elements.constructionTotal) {
            this.elements.constructionTotal.textContent = totals.construction || 0;
        }
    }

    renderGames() {
        this.renderGameSection('classification');
        this.renderGameSection('construction');
    }

    createGameCard(game, type, index) {
        const color = this.config.colors[index % this.config.colors.length];
        const formattedDate = new Date(game.createdAt).toLocaleDateString();
        
        return `
            <div class="game-card" 
                 data-code="${game.code}" 
                 style="--card-accent-color: ${color.accent}; background-color: ${color.bg}">
                <div class="game-code">${game.code}</div>
                <div class="game-details">
                    <div class="game-detail">
                        <i class='bx bx-calendar'></i>
                        <span>${formattedDate}</span>
                    </div>
                    <div class="game-detail">
                        <i class='bx bx-user'></i>
                        <span>${game.totalStudents} ${this.translations.get('cards.students')}</span>
                    </div>
                </div>
            </div>
        `;
    }

    async handleCardClick(gameCode, type) {
        const result = await Swal.fire({
            title: this.translations.get('modals.view_details.title'),
            text: this.translations.get('modals.view_details.message').replace('{code}', gameCode),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: this.translations.get('buttons.view'),
            cancelButtonText: this.translations.get('buttons.cancel'),
            confirmButtonColor: '#1976D2',
            customClass: {
                container: 'analytics-type-modal',
                popup: 'analytics-levels-popup',
            },
        });

        if (result.isConfirmed) {
            const baseUrl = `${base_url}/Analytics/game_${type}`;
            window.location.href = `${baseUrl}?gamecode=${gameCode}`;
        }
    }

    async loadMore(type) {
        const button = this.elements[`loadMore${type.charAt(0).toUpperCase() + type.slice(1)}`];
        if (button) {
            button.disabled = true;
            button.classList.add('loading');
        }

        try {
            const totalItems = this.state[type].items.length;
            const currentDisplayed = this.state[type].offset + this.config.initialLoadLimit;

            // Verificar si hay más items para mostrar
            if (currentDisplayed >= totalItems) {
                button.disabled = true;
                return;
            }

            // Incrementar offset
            this.state[type].offset += this.config.loadMoreLimit;
            
            // Renderizar con nuevo offset
            this.renderGameSection(type);

            // Mostrar botón "Ver menos"
            const lessButton = this.elements[`loadLess${type.charAt(0).toUpperCase() + type.slice(1)}`];
            if (lessButton) {
                lessButton.classList.remove('hidden');
            }

        } catch (error) {
            console.error(`Error loading more ${type} games:`, error);
            this.showError(this.translations.get('errors.loading_more'));
        } finally {
            if (button) {
                button.disabled = false;
                button.classList.remove('loading');
            }
        }
    }

    loadLess(type) {
        // Si estamos en el límite inicial o menos, no hacer nada
        if (this.state[type].offset <= 0) {
            return;
        }

        // Decrementar offset
        this.state[type].offset = Math.max(0, this.state[type].offset - this.config.loadMoreLimit);

        // Renderizar con nuevo offset
        this.renderGameSection(type);

        // Actualizar estado de los botones
        this.updateButtonsState(type);
    }

    updateButtonsState(type) {
        const moreButton = this.elements[`loadMore${type.charAt(0).toUpperCase() + type.slice(1)}`];
        const lessButton = this.elements[`loadLess${type.charAt(0).toUpperCase() + type.slice(1)}`];

        //if (!moreButton || !lessButton) return;
        if (!moreButton) return;
        const totalItems = this.state[type].items.length;
        const currentDisplayed = this.state[type].offset + this.config.initialLoadLimit;

        // Controlar botón "Ver más"
        moreButton.disabled = currentDisplayed >= totalItems;

        if (!lessButton) return;
        // Controlar botón "Ver menos"
        lessButton.classList.toggle('hidden', this.state[type].offset <= 0);
        lessButton.disabled = this.state[type].offset <= 0;
    }

    renderGameSection(type) {
        const grid = this.elements[`${type}Grid`];
        if (!grid) return;

        const games = this.state[type].items;
        const searchTerm = this.state.searchTerm.toLowerCase();
        const currentLimit = this.state[type].offset + this.config.initialLoadLimit;

        // Filtrar juegos según término de búsqueda
        let filteredGames = searchTerm 
            ? games.filter(game => game.code.toLowerCase().includes(searchTerm))
            : games;

        // Limitar el número de juegos según el offset actual
        filteredGames = filteredGames.slice(0, currentLimit);

        grid.innerHTML = filteredGames.map((game, index) => 
            this.createGameCard(game, type, index)).join('');

        // Agregar event listeners a las cards
        grid.querySelectorAll('.game-card').forEach(card => {
            card.addEventListener('click', () => this.handleCardClick(card.dataset.code, type));
        });

        // Actualizar estado de los botones
        this.updateButtonsState(type);
    }
    
    handleSearch() {
        // Actualizar término de búsqueda
        this.state.searchTerm = this.elements.searchInput.value;
        
        // Re-renderizar ambas secciones con el filtro aplicado
        this.renderGames();
    }

    toggleSection(button) {
        const section = button.closest('.games-section');
        const grid = section.querySelector('.games-grid');
        const icon = button.querySelector('i');

        button.classList.toggle('collapsed');
        grid.classList.toggle('hidden');

        // Animar icono
        icon.style.transform = button.classList.contains('collapsed') 
            ? 'rotate(-180deg)' 
            : 'rotate(0)';
    }

    updateLoadMoreButtons(hasMore) {
        for (const type of ['classification', 'construction']) {
            const button = this.elements[`loadMore${type.charAt(0).toUpperCase() + type.slice(1)}`];
            if (button) {
                button.style.display = hasMore[type] ? 'flex' : 'none';
            }
        }
    }

    updateLoadMoreButton(type, hasMore) {
        const button = this.elements[`loadMore${type.charAt(0).toUpperCase() + type.slice(1)}`];
        if (button) {
            button.style.display = hasMore ? 'flex' : 'none';
            button.disabled = false;
            button.classList.remove('loading');

            // Actualizar texto del botón según el estado
            const span = button.querySelector('span');
            if (span) {
                span.textContent = this.translations.get('buttons.load_more');
            }
        }
    }

    showLoadingState() {
        ['classification', 'construction'].forEach(type => {
            const grid = this.elements[`${type}Grid`];
            if (grid) {
                grid.innerHTML = Array(this.config.itemsPerLoad)
                    .fill(this.getSkeletonCard())
                    .join('');
            }
        });
    }

    hideLoadingState() {
        ['classification', 'construction'].forEach(type => {
            const grid = this.elements[`${type}Grid`];
            if (grid && !grid.children.length) {
                grid.innerHTML = `
                    <div class="no-games-message">
                        ${this.translations.get('messages.no_games')}
                    </div>
                `;
            }
        });
    }

    getSkeletonCard() {
        return `
            <div class="game-card skeleton">
                <div class="game-code skeleton-text"></div>
                <div class="game-details">
                    <div class="game-detail skeleton-text"></div>
                    <div class="game-detail skeleton-text"></div>
                </div>
            </div>
        `;
    }

    updateTranslations() {
        // Actualizar texto del botón de carga
        document.querySelectorAll('.load-more-btn span').forEach(span => {
            span.textContent = this.translations.get('buttons.load_more');
        });

        // Actualizar placeholder de búsqueda
        if (this.elements.searchInput) {
            this.elements.searchInput.placeholder = 
                this.translations.get('search.placeholder');
        }

        // Re-renderizar las cards para actualizar textos
        this.renderGames();
    }

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: this.translations.get('errors.title'),
            text: message,
            confirmButtonColor: '#1976D2',
            customClass: {
                container: 'analytics-type-modal',
                popup: 'analytics-levels-popup',
            },
        });
    }

    // Utilidad para debounce
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', () => {
    window.myGamesManager = new MyGamesManager();
});