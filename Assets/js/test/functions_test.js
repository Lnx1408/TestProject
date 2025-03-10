// Módulo principal de funcionalidades
const DashboardModule = {
    // Configuración inicial
    config: {
        cardsToShow: 4,
        selectors: {
            showMoreBtn: '#showMoreBtn',
            cardItem: '.card-item',
            analyse: '.analyse',
            //tableRows: '.orders table tbody tr',
            tableRows: '#tableJugadores tbody tr',
            playersTable: '#tableJugadores'
        }
    },

    // Estado de la aplicación
    state: {
        isShowingAll: false
    },

    initializeDataTable() {
        if ($.fn.DataTable) {
            $(this.config.selectors.playersTable).DataTable({
                autoWidth: false,
                responsive: true,
                language: {
                    url: `${base_url}/Assets/js/plugins/datatables/es-ES.json`,
                    paginate: {
                        first: '«',
                        last: '»',
                        next: '›',
                        previous: '‹'
                    }
                },
                // ... más configuraciones de DataTable si las necesitas
            });
        }
    },

    // Inicialización
    init() {
        this.bindElements();
        this.setupEventListeners();
        this.initializeCards();
        this.initializeAvatars();
        this.initializeDataTable();
    },

    // Vinculación de elementos del DOM
    bindElements() {
        this.elements = {
            showMoreBtn: document.querySelector(this.config.selectors.showMoreBtn),
            hiddenCards: document.querySelectorAll(`${this.config.selectors.cardItem}.hidden`),
            analyseSection: document.querySelector(this.config.selectors.analyse)
        };
    },

    // Configuración de event listeners
    setupEventListeners() {
        if (this.elements.showMoreBtn) {
            this.elements.showMoreBtn.addEventListener('click', () => this.handleShowMoreClick());
        }
    },

    // Inicialización de las cards
    initializeCards() {
        if (this.elements.hiddenCards.length === 0 && this.elements.showMoreBtn) {
            this.elements.showMoreBtn.style.display = 'none';
        }
    },

    // Manejador del botón "Ver más/menos"
    handleShowMoreClick() {
        this.state.isShowingAll ? this.hideExtraCards() : this.showMoreCards();
    },

    // Mostrar más cards
    showMoreCards() {
        const stillHidden = document.querySelectorAll(`${this.config.selectors.cardItem}.hidden`);

        for (let i = 0; i < this.config.cardsToShow && i < stillHidden.length; i++) {
            this.animateCardReveal(stillHidden[i], i);
        }

        if (stillHidden.length <= this.config.cardsToShow) {
            this.updateShowMoreButton(true);
        }
    },

    // Ocultar cards extras
    hideExtraCards() {
        const allCards = document.querySelectorAll(this.config.selectors.cardItem);
        allCards.forEach((card, index) => {
            if (index >= this.config.cardsToShow) {
                card.classList.add('hidden');
            }
        });

        this.updateShowMoreButton(false);
        this.scrollToTop();
    },

    // Animación de revelar card
    animateCardReveal(card, index) {
        card.classList.remove('hidden');
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    },

    // Actualizar estado del botón
    updateShowMoreButton(isShowingAll) {
        this.state.isShowingAll = isShowingAll;
        this.elements.showMoreBtn.innerHTML = isShowingAll
            ? '<i class="bx bx-minus"></i>Ver menos'
            : '<i class="bx bx-plus"></i>Ver más';
    },

    // Scroll suave hacia arriba
    scrollToTop() {
        window.scrollTo({
            top: this.elements.analyseSection.offsetTop,
            behavior: 'smooth'
        });
    },

    // Utilidades para los avatares
    AvatarUtils: {
        getInitials(name) {
            const words = name.split(' ');
            return words.length === 1
                ? words[0].substring(0, 2).toUpperCase()
                : (words[0][0] + words[words.length - 1][0]).toUpperCase();
        },

        getAvatarColor(name) {
            const colors = [
                'var(--primary)',
                'var(--success)',
                'var(--warning)',
                'var(--danger)',
                '#7E57C2',
                '#26A69A',
                '#FF7043'
            ];

            let hash = 0;
            for (let i = 0; i < name.length; i++) {
                hash = name.charCodeAt(i) + ((hash << 5) - hash);
            }

            return colors[Math.abs(hash) % colors.length];
        },

        createAvatarElement(name) {
            const initials = this.getInitials(name);
            const color = this.getAvatarColor(name);

            const avatar = document.createElement('div');
            avatar.className = 'avatar-circle';
            avatar.style.backgroundColor = color;
            avatar.innerHTML = `<span class="initials">${initials}</span>`;

            return avatar;
        }
    },

    // Inicialización de avatares
    initializeAvatars() {
        document.querySelectorAll(this.config.selectors.tableRows).forEach(row => {
            const nameElement = row.querySelector('td:first-child p');
            if (!nameElement) return;

            const name = nameElement.textContent;
            const avatar = this.AvatarUtils.createAvatarElement(name);

            const imgElement = row.querySelector('td:first-child img');
            if (imgElement) {
                imgElement.replaceWith(avatar);
            }
        });
    }
};

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', () => DashboardModule.init());