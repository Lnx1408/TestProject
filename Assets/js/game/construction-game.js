class ConstructionGame {
    constructor() {
        this.gameCode = '';
        this.reqActualBD = 0;
        this.reqTotalesBd = 0;
        this.attemptsBD = 0;

        this.currentRequirement = null;
        this.fragments = [];
        this.attempts = 0;
        this.startTime = null;
        this.currentAttemptStartTime = null;
        this.timer = null;
        this.movementsTracking = new Map();
        this.fragmentLocations = new Map(); // Para rastrear ubicación actual de cada fragmento
        this.dragStartTimes = new Map(); // Para rastrear cuando inicia el drag

        this.dynamicStyleSheet = this.createDynamicStyleSheet();
        this.translations = {
            get: (key) => LanguageManager.getTranslation(`game_construction.${key}`)
        };

        document.addEventListener('translationsLoaded', () => {
            this.initializePlaceholders();
        });
        
        document.addEventListener('languageChanged', () => {
            this.initializePlaceholders();
        });
    }

    init(gameCode) {
        this.gameCode = gameCode;
        this.startTime = new Date();
        this.currentAttemptStartTime = new Date();
        this.initializeTimer();
        this.loadGameData();
        this.initializeEventListeners();
    }

    createDynamicStyleSheet() {
        const existingStyle = document.getElementById('game-dynamic-styles');
        if (existingStyle) {
            return existingStyle.sheet;
        }

        const style = document.createElement('style');
        style.id = 'game-dynamic-styles';
        document.head.appendChild(style);
        return style.sheet;
    }

    initializePlaceholders() {
        const placeholderText = this.translations.get('tooltips.drag_fragments');
        if (!placeholderText) {
            console.warn('Translation not available yet for placeholders');
            return;
        }

        // Limpiar reglas existentes
        while (this.dynamicStyleSheet.cssRules.length > 0) {
            this.dynamicStyleSheet.deleteRule(0);
        }

        const cssRule = `.construction-zone.single-panel:empty::after {
            content: "${placeholderText}";
            opacity: 0.7;
        }`;

        this.dynamicStyleSheet.insertRule(cssRule, 0);
    }

    async loadGameData() {
        try {
            let urlpost = '/Game/get_requirements_construcion?game=' + this.gameCode;
            urlpost = base_url + urlpost;

            const response = await fetch(urlpost);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message);
            }

            // Verificar si no hay más requisitos (juego completado)
            if (data.gameCompleted) {
                this.handleGameCompleted();
                return;
            }

            this.currentRequirement = data.data;
            this.fragments = data.data.fragmentos;
            this.attemptsBD = data.intentos;
            this.reqActualBD = data.reqActual;
            this.reqTotalesBd = data.reqTotales;

            this.setupGameInterface();
            this.initializeDragAndDrop();

        } catch (error) {
            console.error('Error loading game data:', error);
            this.showError(error.message);
        }
    }

    async handleGameCompleted() {

        // Ocultar interfaz del juego
        const constructionZone = document.getElementById('constructionZone');
        const fragmentsBank = document.getElementById('fragmentsBank');
        if (constructionZone) constructionZone.style.display = 'none';
        if (fragmentsBank) fragmentsBank.style.display = 'none';

                // Deshabilitar botón de validación si existe
                const validateButton = document.getElementById('validateBtn');
                if (validateButton) validateButton.disabled = true;

        const result = await Swal.fire({
            icon: 'success',
            title: this.translations.get('messages.game_completed'),
            html: `<div class="completion-content">
                    <p>${this.translations.get('messages.congratulations')}</p>
                   </div>`,
            confirmButtonText: this.translations.get('buttons.back_to_dashboard'),
            allowOutsideClick: false,
            customClass: {
                container: 'game-completion-modal',
                popup: 'game-completion-popup',
                content: 'game-completion-content'
            }
        });

        if (result.isConfirmed) {
            window.location.href = `${base_url}/dashboard`;
        }
    }

    initializeEventListeners() {
        // Eventos de drag and drop (código existente...)

        // Agregar evento al botón de validar
        const validateButton = document.getElementById('validateBtn');
        if (validateButton) {
            validateButton.addEventListener('click', () => {
                this.validateConstruction();
            });
        }
    }

    closeModal() {
        if (!this.modal) return;
        this.modal.classList.remove('show');
        setTimeout(() => {
            this.modal.style.display = 'none';
            document.body.style.overflow = ''; // Restaurar scroll

            // Si la construcción fue correcta, preparar siguiente requisito
            if (this.lastResult?.isCorrect) {
                this.prepareNextRequirement();
            }
        }, 300); // Tiempo para la animación
    }

    setupGameInterface() {
        const reqActual = document.getElementById('currentRequirement');
        reqActual.innerHTML = this.reqActualBD;
        const reqTotales = document.getElementById('totalRequirements');
        reqTotales.innerHTML = this.reqTotalesBd;
        const intentos = document.getElementById('attempts');
        intentos.innerHTML = this.attemptsBD;

        // Configurar zona de construcción como un único panel
        const constructionZone = document.getElementById('constructionZone');
        constructionZone.innerHTML = '';
        constructionZone.className = 'construction-zone single-panel';

        // Configurar banco de fragmentos
        const fragmentsBank = document.getElementById('fragmentsBank');
        fragmentsBank.innerHTML = '';

        // Mezclar y mostrar fragmentos
        const shuffledFragments = [...this.fragments].sort(() => Math.random() - 0.5);
        shuffledFragments.forEach(fragment => {
            const fragmentElement = document.createElement('div');
            fragmentElement.className = 'fragment';
            fragmentElement.draggable = true;
            fragmentElement.id = `fragment-${fragment.id_fragmento}`;
            fragmentElement.textContent = fragment.texto;
            fragmentElement.dataset.fragmentId = fragment.id_fragmento;
            fragmentsBank.appendChild(fragmentElement);
        });
    }

    initializeDragAndDrop() {
        const constructionZone = document.getElementById('constructionZone');
        const fragmentsBank = document.getElementById('fragmentsBank');

        document.addEventListener('dragstart', (e) => {
            if (!e.target.classList.contains('fragment')) return;
            const fragmentId = e.target.dataset.fragmentId;
            this.dragStartTimes.set(fragmentId, Date.now());

            e.target.classList.add('dragging');
            e.dataTransfer.setData('text/plain', e.target.id);
            e.dataTransfer.effectAllowed = 'move';
        });

        document.addEventListener('dragend', (e) => {
            if (!e.target.classList.contains('fragment')) return;
            e.target.classList.remove('dragging');

            // Trackear el movimiento cuando termina el drag
            const fragmentId = e.target.dataset.fragmentId;
            const isInConstructionZone = e.target.closest('#constructionZone') !== null;
            this.trackMovement(fragmentId, isInConstructionZone ? 'construction' : 'bank');
        });

        constructionZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            const afterElement = this.getDragAfterElement(constructionZone, e.clientY);
            const draggable = document.querySelector('.dragging');

            if (draggable) {
                if (afterElement) {
                    constructionZone.insertBefore(draggable, afterElement);
                } else {
                    constructionZone.appendChild(draggable);
                }
            }
        });

        fragmentsBank.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        fragmentsBank.addEventListener('drop', (e) => {
            e.preventDefault();
            const fragmentId = e.dataTransfer.getData('text/plain');
            const fragment = document.getElementById(fragmentId);
            if (fragment) {
                fragmentsBank.appendChild(fragment);
                this.trackMovement(fragment.dataset.fragmentId, 'bank');
            }
        });
    }

    // Método para determinar la posición de inserción basado en el cursor
    getDragAfterElement(container, y) {
        const draggableElements = [
            ...container.querySelectorAll('.fragment:not(.dragging)')
        ];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    trackMovement(fragmentId, type = 'construction') {
        if (!this.movementsTracking.has(fragmentId)) {
            this.movementsTracking.set(fragmentId, {
                moves: 0,
                position: 0,
                placementTime: 0,
                lastPlacementTime: 0  // Nuevo: para guardar el último tiempo individual
            });
            this.fragmentLocations.set(fragmentId, 'bank');
        }

        const tracking = this.movementsTracking.get(fragmentId);
        const previousLocation = this.fragmentLocations.get(fragmentId);

        // Incrementar movimientos si hay cambio real de ubicación
        if (previousLocation !== type) {
            tracking.moves++;
            this.fragmentLocations.set(fragmentId, type);
        }

        const constructionZone = document.getElementById('constructionZone');


        if (type === 'construction') {
            const fragments = Array.from(constructionZone.children);

            // Actualizar la posición de TODOS los fragmentos en la zona de construcción
            fragments.forEach((fragment, index) => {
                const currentFragmentId = fragment.dataset.fragmentId;
                if (!this.movementsTracking.has(currentFragmentId)) {
                    this.movementsTracking.set(currentFragmentId, {
                        moves: 0,
                        position: 0,
                        placementTime: 0,
                        lastPlacementTime: 0
                    });
                }

                const currentTracking = this.movementsTracking.get(currentFragmentId);
                const newPosition = index + 1;

                // Si la posición cambió, incrementar el contador de movimientos
                if (currentTracking.position !== newPosition && currentTracking.position !== 0) {
                    currentTracking.moves++;
                }

                currentTracking.position = newPosition;

                // Actualizar el tiempo de colocación solo para el fragmento que se está moviendo
                if (currentFragmentId === fragmentId) {
                    const dragStartTime = this.dragStartTimes.get(currentFragmentId);
                    if (dragStartTime) {
                        // Calcular el tiempo de este movimiento específico
                        const currentPlacementTime = Date.now() - dragStartTime;

                        // Guardar este tiempo individual
                        currentTracking.lastPlacementTime = Math.floor(currentPlacementTime / 1000);

                        // Acumular el tiempo total
                        currentTracking.placementTime += currentPlacementTime;
                    }
                }
            });
        } else {
            // Si se mueve al banco, actualizar su posición a 0
            tracking.position = 0;

            // También actualizamos el tiempo si se está moviendo al banco
            const dragStartTime = this.dragStartTimes.get(fragmentId);
            if (dragStartTime) {
                const currentPlacementTime = Date.now() - dragStartTime;
                tracking.lastPlacementTime = Math.floor(currentPlacementTime / 1000);
                tracking.placementTime += currentPlacementTime;
            }

            // Recalcular las posiciones de los fragmentos restantes en la zona de construcción
            const remainingFragments = Array.from(constructionZone.children);
            remainingFragments.forEach((fragment, index) => {
                const currentFragmentId = fragment.dataset.fragmentId;
                const currentTracking = this.movementsTracking.get(currentFragmentId);
                if (currentTracking) {
                    const newPosition = index + 1;
                    if (currentTracking.position !== newPosition) {
                        currentTracking.moves++;
                    }
                    currentTracking.position = newPosition;
                }
            });
        }

        console.log(`Tracking movement for fragment ${fragmentId}:`, {
            moves: tracking.moves,
            position: tracking.position,
            location: type,
            previousLocation: previousLocation,
            totalPlacementTime: Math.floor(tracking.placementTime / 1000), // Convertir a segundos para el log
            lastPlacementTime: tracking.lastPlacementTime
        });

        // Debug: mostrar el estado actual de todos los fragmentos
        /*
        this.movementsTracking.forEach((data, id) => {
            console.log(`Fragment ${id} current state:`, data);
        });
        */

        this.movementsTracking.forEach((data, id) => {
            console.log(`Fragment ${id} current state:`, {
                ...data,
                placementTime: Math.floor(data.placementTime / 1000) // Convertir a segundos para el log
            });
        });
    }


    getCurrentConstruction() {
        const constructionZone = document.getElementById('constructionZone');
        return Array.from(constructionZone.children).map((fragment, index) => ({
            position: index + 1,
            fragmentId: fragment.dataset.fragmentId
        }));
    }

    initializeTimer() {
        this.timer = setInterval(() => {
            const currentTime = new Date();
            const diff = Math.floor((currentTime - this.startTime) / 1000);
            const minutes = Math.floor(diff / 60);
            const seconds = diff % 60;
            document.getElementById('timer').textContent =
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
    }

    isConstructionComplete(construction) {
        return construction.length === 5; // Todos los slots deben estar ocupados
    }

    resetConstruction() {
        const fragmentsBank = document.getElementById('fragmentsBank');
        document.querySelectorAll('.fragment-slot .fragment').forEach(fragment => {
            fragmentsBank.appendChild(fragment);
        });
        document.querySelectorAll('.fragment-slot').forEach(slot => {
            slot.classList.remove('occupied');
        });
    }

    async validateConstruction() {
        const construction = this.getCurrentConstruction();

        if (construction.length === 0) {
            await Swal.fire({
                icon: 'warning',
                title: this.translations.get('messages.validation.empty.title'),
                text: this.translations.get('messages.validation.empty.text'),
                confirmButtonText: this.translations.get('buttons.ok'),
                customClass: {
                    container: 'game-validation-modal',
                    popup: 'game-validation-popup'
                }
            });
            return;
        }

        // Preparar los movimientos en el formato correcto
        const movements = {};
        this.movementsTracking.forEach((data, fragmentId) => {
            movements[fragmentId] = {
                moves: data.moves,
                position: data.position,
                placementTime: Math.floor(data.placementTime / 1000) // Convertir a segundos
            };
        });

        console.log('Movements to send:', movements); // Para debugging

        const validationData = {
            //requirementId: this.currentRequirement.id_requisito,
            requirementId: this.currentRequirement.id_requisito_partida,
            construction: construction,
            timeSpent: Math.floor((new Date() - this.currentAttemptStartTime) / 1000),
            movements: movements,
            attemptNumber: this.attempts + 1
        };

        try {
            let urlpost = '/Game/validate_construction';
            urlpost = base_url + urlpost;

            const response = await fetch(urlpost, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(validationData)
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            // Actualizar intentos
            this.attempts++;
            document.getElementById('attempts').textContent = this.attempts;

            // Mostrar feedback
            this.showFeedback(result);

            if (result.isCorrect) {
                await this.handleCorrectConstruction();
            } else {
                // Reiniciar tracking para el siguiente intento
                this.movementsTracking.clear();
                this.currentAttemptStartTime = new Date();
            }

        } catch (error) {
            console.error('Error validating construction:', error);
            this.showError('Error al validar la construcción: ' + error.message);
        }
    }

    async showFeedback(result) {
        // Asegurarnos de que accuracy sea un número
        const accuracy = parseFloat(result.accuracy) || 0;

        const feedbackHtml = `
            <div class="feedback-container">
                <div class="feedback-stats">
                    <div class="stat-item">
                        <i class='bx bx-target-lock'></i>
                        <p>${this.translations.get('messages.feedback.accuracy')}: ${accuracy.toFixed(1)}%</p>
                    </div>
                    <div class="stat-item">
                        <i class='bx bx-check-circle'></i>
                        <p>${this.translations.get('messages.feedback.correct_fragments')}: ${result.correctFragments} ${this.translations.get('messages.feedback.of')} ${result.totalFragments}</p>
                    </div>
                    <div class="stat-item">
                        <i class='bx bx-timer'></i>
                        <p>${this.translations.get('messages.feedback.attempt')}: ${result.attemptNumber}</p>
                    </div>
                </div>
                ${!result.isCorrect ? `
                    <div class="feedback-hint">
                        <p>${this.translations.get('messages.feedback.hint')}</p>
                    </div>
                ` : ''}
            </div>
        `;

        await Swal.fire({
            icon: result.isCorrect ? 'success' : 'info',
            title: result.isCorrect ? 
                this.translations.get('messages.feedback.success_title') : 
                this.translations.get('messages.feedback.try_again_title'),
            html: feedbackHtml,
            confirmButtonText: this.translations.get('buttons.continue'),
            customClass: {
                container: 'game-feedback-modal',
                popup: 'game-feedback-construction-popup',
                content: 'game-feedback-content'
            }
        });
        
        if (!result.isCorrect) {
            const constructionZone = document.getElementById('constructionZone');
            const fragmentsBank = document.getElementById('fragmentsBank');
            Array.from(constructionZone.children).forEach(fragment => {
                fragmentsBank.appendChild(fragment);
            });
            this.currentAttemptStartTime = new Date();
        }
        // Debug para ver qué estamos recibiendo
        console.log('Feedback result:', result);
    }

    async showError(message) {
        await Swal.fire({
            icon: 'error',
            title: this.translations.get('errors.title'),
            text: message,
            confirmButtonText: this.translations.get('buttons.ok'),
            customClass: {
                container: 'game-error-modal',
                popup: 'game-error-popup'
            }
        });
    }

    async handleCorrectConstruction() {
        try {
            // Reiniciar el tracking y estados necesarios
            this.movementsTracking.clear();

            // Usar el método existente para cargar el siguiente requisito
            await this.loadGameData();

        } catch (error) {
            console.error('Error loading next requirement:', error);
            this.showError(error.message);
        }
    }
}

let game;

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    const gameCode = new URLSearchParams(window.location.search).get('code');
    if (gameCode) {
        game = new ConstructionGame();
        game.init(gameCode);
    }
});

