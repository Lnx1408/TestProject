class Game {

    constructor() {
        this.gameCode = '';
        this.attempts = 0;
        this.totalMoves = 0;
        this.movesInCurrentAttempt = 0;
        this.startTime = null;
        this.currentAttemptStartTime = null;
        this.timer = null;
        this.requirements = [];
        this.pendingRequirements = [];
        this.totalInitialRequirements = 0;
        this.totalCorrectlyClassified = 0;

        //NUEVO CODIGO
        this.requirementMovements = new Map();
        this.dynamicStyleSheet = this.createDynamicStyleSheet();

        this.translations = {
            get: (key) => LanguageManager.getTranslation(`game_classification.${key}`)
        };

        document.addEventListener('translationsLoaded', () => {
            this.initializePlaceholders();
        });
        
        document.addEventListener('languageChanged', () => {
            this.initializePlaceholders();
        });
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
        const placeholderText = this.translations.get('placeholders.drag_requirements');
        if (!placeholderText) {
            console.warn('Translation not available yet for placeholders');
            return;
        }

        // Limpiar reglas existentes
        while (this.dynamicStyleSheet.cssRules.length > 0) {
            this.dynamicStyleSheet.deleteRule(0);
        }

        const cssRule = `.requirements-list:empty::after {
            content: "${placeholderText}";
            opacity: 0.7;
        }`;

        this.dynamicStyleSheet.insertRule(cssRule, 0);
    }

    init(gameCode) {
        this.gameCode = gameCode;
        this.startTime = new Date();
        this.currentAttemptStartTime = new Date();

        this.initializeTimer();
        this.loadRequirements();
        this.initializeEventListeners();
        this.initializeScrollBehavior();
    }

    initializeEventListeners() {
        // Evento para el botón de validación
        document.getElementById('validateBtn').addEventListener('click', () => {
            this.validateClassification();
        });

        // Configurar eventos de drag and drop para los paneles
        ['ambiguousRequirements', 'nonAmbiguousRequirements', 'initialRequirements'].forEach(panelId => {
            const panel = document.getElementById(panelId);
            panel.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });

            panel.addEventListener('drop', (e) => {
                e.preventDefault();
                const reqId = e.dataTransfer.getData('text/plain');
                const reqElement = document.getElementById(reqId);

                if (reqElement) {
                    const targetPanel = e.target.closest('.requirements-list').id;
                    if (e.target.classList.contains('requirements-list')) {
                        e.target.appendChild(reqElement);
                    } else if (e.target.classList.contains('requirement-card')) {
                        e.target.parentNode.insertBefore(reqElement, e.target.nextSibling);
                    } else if (e.target.closest('.requirements-list')) {
                        e.target.closest('.requirements-list').appendChild(reqElement);
                    }

                    // Incrementar contadores globales
                    this.totalMoves++;
                    this.movesInCurrentAttempt++;

                    // Registrar movimiento específico del requisito
                    const reqIdNum = reqId.replace('req-', '');
                    if (!this.requirementMovements.has(reqIdNum)) {
                        this.requirementMovements.set(reqIdNum, {
                            ambiguous: 0,
                            nonAmbiguous: 0
                        });
                    }

                    // Incrementar el contador específico según el panel destino
                    const movements = this.requirementMovements.get(reqIdNum);
                    if (targetPanel === 'ambiguousRequirements') {
                        movements.ambiguous++;
                    } else if (targetPanel === 'nonAmbiguousRequirements') {
                        movements.nonAmbiguous++;
                    }

                    document.getElementById('moves').textContent = this.totalMoves;

                    // Debug para verificar los movimientos
                    console.log('Movimientos del requisito:', reqIdNum, this.requirementMovements.get(reqIdNum));
                }
            });
        });
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

    initializeScrollBehavior() {
        const panels = document.querySelectorAll('.panel');

        panels.forEach(panel => {
            const requirementsList = panel.querySelector('.requirements-list');

            // Verificar si se necesita scroll
            const checkScroll = () => {
                if (requirementsList.scrollHeight > requirementsList.clientHeight) {
                    panel.classList.add('has-scroll');
                } else {
                    panel.classList.remove('has-scroll');
                }
            };

            // Observar cambios en el contenido
            const observer = new MutationObserver(checkScroll);
            observer.observe(requirementsList, { childList: true });

            // Verificar scroll inicial
            checkScroll();

            // Efecto hover en el panel
            panel.addEventListener('mouseenter', () => {
                panel.classList.add('active');
            });

            panel.addEventListener('mouseleave', () => {
                panel.classList.remove('active');
            });
        });
    }

    async showGameUnavailable() {
        await Swal.fire({
            icon: 'warning',
            title: this.translations.get('modals.game_unavailable'),
            text: this.translations.get('modals.feedback.game_unavailable'),
            confirmButtonColor: '#1976D2',
            confirmButtonText: this.translations.get('buttons.accept'),
            customClass: {
                container: 'game-feedback-modal',
                popup: 'game-feedback-popup',
                content: 'game-feedback-content'
            },
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = base_url + '/dashboard';
            }
        });
    }

    async loadRequirements() {
        try {
            if (this.requirements.length === 0) {
                let urlpost = '/Game/get_requirements?game=' + this.gameCode;
                urlpost = base_url + urlpost;

                const response = await fetch(urlpost);
                const data = await response.json();

                if (!data.success) {
                     await this.showGameUnavailable();
                    return;
                }

                // Inicializar el estado del juego basado en la respuesta
                if (data.gameState.inProgress && data.requirements.length > 0) {
                    // Es un juego en progreso, cargar el estado anterior
                    this.requirements = data.requirements;
                    this.pendingRequirements = [...data.requirements];
                    this.totalInitialRequirements = data.requirements[0].total_requisitos_inicial; // Este vendría del SP
                    this.attempts = data.lastAttempt.attemptNumber;
                    this.totalCorrectlyClassified = Math.floor((data.lastAttempt.progressiveAccuracy * this.totalInitialRequirements) / 100);

                    // Actualizar contadores en la interfaz
                    document.getElementById('attempts').textContent = this.attempts;

                    console.log('Cargando juego en progreso:', {
                        requisitosRestantes: this.pendingRequirements.length,
                        intentoActual: this.attempts,
                        precision: data.lastAttempt.progressiveAccuracy
                    });
                }
                else if (data.gameState.inProgress && data.requirements.length == 0) {
                    await this.showGameUnavailable();
                    return;
                }
                else {
                    // Es un juego nuevo
                    this.requirements = data.requirements;
                    this.pendingRequirements = [...data.requirements];
                    this.totalInitialRequirements = data.requirements.length;
                    this.attempts = 0;
                    this.totalCorrectlyClassified = 0;

                    console.log('Iniciando nuevo juego:', {
                        totalRequisitos: this.totalInitialRequirements
                    });
                }

                // Registro para debugging
                console.log('Estado del juego cargado:', {
                    esNuevo: data.gameState.isNewGame,
                    enProgreso: data.gameState.inProgress,
                    requisitos: this.requirements,
                    mensaje: data.message
                });
            }

            // Mostrar los requisitos en la interfaz
            this.displayPendingRequirements();

            // Si es un juego en progreso, actualizar el historial de intentos
            if (this.attempts > 0) {
                /*
                this.updateAttemptsHistory({
                    attemptMetrics: {
                        attemptNumber: this.attempts,
                        moves: 0, // Este valor vendría de la base de datos
                        timeInSeconds: 0 // Este valor vendría de la base de datos
                    },
                    correctCount: this.totalCorrectlyClassified,
                    incorrectCount: this.pendingRequirements.length,
                    accuracy: (this.totalCorrectlyClassified / this.totalInitialRequirements) * 100
                });
                */
            }

        } catch (error) {
            console.error('Error loading requirements:', error);
            // Mostrar mensaje de error al usuario
            const messageContainer = document.createElement('div');
            messageContainer.className = 'error-message';
            messageContainer.textContent = `Error: ${error.message}`;
            document.querySelector('.game-container').prepend(messageContainer);
        }
    }

    displayPendingRequirements() {
        const container = document.getElementById('initialRequirements');
        container.innerHTML = '';
        this.resetMovementsTracking();

        this.pendingRequirements.forEach(req => {
            const reqElement = document.createElement('div');
            reqElement.className = 'requirement-card';
            reqElement.draggable = true;
            reqElement.id = `req-${req.id_requisito}`;
            reqElement.textContent = req.descripcion;

            reqElement.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', e.target.id);
                e.target.classList.add('dragging');
                // Agregar clase al panel de destino potencial
                document.querySelectorAll('.requirements-list').forEach(list => {
                    list.classList.add('potential-target');
                });
            });

            reqElement.addEventListener('dragend', (e) => {
                e.target.classList.remove('dragging');
                // Remover clase de paneles
                document.querySelectorAll('.requirements-list').forEach(list => {
                    list.classList.remove('potential-target');
                    list.classList.remove('drag-over');
                });
            });

            container.appendChild(reqElement);
        });

        // Limpiar los paneles de clasificación
        document.getElementById('ambiguousRequirements').innerHTML = '';
        document.getElementById('nonAmbiguousRequirements').innerHTML = '';

        // Inicializar comportamiento de scroll
        this.initializeScrollBehavior();
    }

    handleDrop(e) {
        e.preventDefault();
        const reqId = e.dataTransfer.getData('text/plain');
        const reqElement = document.getElementById(reqId);

        if (reqElement) {
            if (e.target.classList.contains('requirements-list')) {
                e.target.appendChild(reqElement);
            } else if (e.target.classList.contains('requirement-card')) {
                e.target.parentNode.insertBefore(reqElement, e.target.nextSibling);
            } else if (e.target.closest('.requirements-list')) {
                e.target.closest('.requirements-list').appendChild(reqElement);
            }
            this.totalMoves++;
            this.movesInCurrentAttempt++;
            document.getElementById('moves').textContent = this.totalMoves;
        }
    }

    async validateClassification() {
        const ambiguousPanel = document.getElementById('ambiguousRequirements');
        const nonAmbiguousPanel = document.getElementById('nonAmbiguousRequirements');

        const classifiedRequirements = [
            ...Array.from(ambiguousPanel.children),
            ...Array.from(nonAmbiguousPanel.children)
        ];

        /*
        if (classifiedRequirements.length < this.pendingRequirements.length) {
            alert('Por favor, clasifica todos los requisitos antes de validar.');
            return;
        }*/
        if (classifiedRequirements.length < this.pendingRequirements.length) {
            Swal.fire({
                icon: 'warning',
                title: this.translations.get('modals.validation_result'),
                text: this.translations.get('modals.feedback.classify_all'),
                confirmButtonColor: '#1976D2',
                customClass: {
                    container: 'game-feedback-modal',
                    popup: 'game-feedback-popup',
                    content: 'game-feedback-content'
                }
            });
            return;
        }

        const currentAttemptTime = Math.floor((new Date() - this.currentAttemptStartTime) / 1000);

        // Guardamos el total actual antes de la validación
        const currentTotal = this.pendingRequirements.length;

        // Convertir el Map de movimientos a un objeto para el envío
        const movementsData = {};
        this.requirementMovements.forEach((value, key) => {
            movementsData[key] = {
                id: key,
                movimientosAmbiguo: value.ambiguous,
                movimientosNoAmbiguo: value.nonAmbiguous
            };
        });

        const classification = {
            ambiguous: Array.from(ambiguousPanel.children).map(el => el.id.replace('req-', '')),
            nonAmbiguous: Array.from(nonAmbiguousPanel.children).map(el => el.id.replace('req-', '')),
            attemptMetrics: {
                attemptNumber: this.attempts + 1,
                timeInSeconds: currentAttemptTime,
                moves: this.movesInCurrentAttempt,
                totalRequirementsInAttempt: currentTotal
            },
            requirementMovements: movementsData
        };

        try {

            let urlpost = '/Game/validate_moves';
            urlpost = base_url + urlpost;

            const response = await fetch(urlpost, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    gameCode: this.gameCode,
                    classification: classification,
                    totalMoves: this.totalMoves,
                    totalTime: Math.floor((new Date() - this.startTime) / 1000)
                })
            });

            const result = await response.json();

            this.attempts++;
            document.getElementById('attempts').textContent = this.attempts;

            // Actualizar pendientes antes de mostrar el feedback
            const incorrectOnes = result.incorrectRequirements || [];
            this.pendingRequirements = this.pendingRequirements.filter(req =>
                incorrectOnes.some(incorrect =>
                    incorrect.id === req.id_requisito.toString()
                )
            );

            // Preparar resultado actualizado para el feedback
            const updatedResult = {
                ...result,
                attemptMetrics: {
                    ...classification.attemptMetrics,
                    totalRequirementsInAttempt: currentTotal
                }
            };

            this.showFeedback(updatedResult);

            // Limpiar paneles
            ambiguousPanel.innerHTML = '';
            nonAmbiguousPanel.innerHTML = '';

            if (this.pendingRequirements.length === 0) {
                this.gameCompleted();
            } else {
                this.currentAttemptStartTime = new Date();
                this.movesInCurrentAttempt = 0;
                this.displayPendingRequirements();
            }

        } catch (error) {
            console.error('Error validating classification:', error);
        }
    }

    updateAttemptsHistory(result) {
        const tableBody = document.getElementById('historyTableBody');
        const row = document.createElement('tr');

        // Total de requisitos en este intento
        const totalRequirementsInAttempt = result.attemptMetrics.totalRequirementsInAttempt;

        // Requisitos correctos en este intento
        const incorrectsInAttempt = result.incorrectRequirements.length;
        const correctsInAttempt = totalRequirementsInAttempt - incorrectsInAttempt;

        // Actualizar el total de requisitos clasificados correctamente
        this.totalCorrectlyClassified += correctsInAttempt;

        // Calcular la precisión progresiva
        const progressiveAccuracy = ((this.totalCorrectlyClassified / this.totalInitialRequirements) * 100).toFixed(2);

        // Calcular otras métricas
        const successPressure = ((correctsInAttempt / totalRequirementsInAttempt) * 100).toFixed(2);
        const errorPressure = ((incorrectsInAttempt / totalRequirementsInAttempt) * 100).toFixed(2);
        const accuracy = ((correctsInAttempt / totalRequirementsInAttempt) * 100).toFixed(2);

        // Formatear tiempo
        const minutes = Math.floor(result.attemptMetrics.timeInSeconds / 60);
        const seconds = result.attemptMetrics.timeInSeconds % 60;
        const timeFormatted = `${minutes}:${seconds.toString().padStart(2, '0')}`;

        // Debug para verificar valores
        console.log('Métricas del intento:', {
            attemptNumber: result.attemptMetrics.attemptNumber,
            totalInitial: this.totalInitialRequirements,
            totalCorrectlyClassified: this.totalCorrectlyClassified,
            correctsInAttempt,
            progressiveAccuracy
        });

        row.innerHTML = `
            <td>${result.attemptMetrics.attemptNumber}</td>
            <td>${timeFormatted}</td>
            <td>${result.attemptMetrics.moves}</td>
            <td>${totalRequirementsInAttempt}</td>
            <td>${correctsInAttempt}</td>
            <td>${incorrectsInAttempt}</td>
            <td>${accuracy}%</td>
            <td>${successPressure}%</td>
            <td>${errorPressure}%</td>
            <td class="progressive-accuracy">${progressiveAccuracy}%</td>
        `;

        tableBody.appendChild(row);
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    showFeedback_old(result) {

        //this.updateAttemptsHistory(result);

        const modal = document.getElementById('feedbackModal');
        const content = document.getElementById('feedbackContent');

        // Formatear tiempo en minutos:segundos
        const minutes = Math.floor(result.attemptMetrics.timeInSeconds / 60);
        const seconds = result.attemptMetrics.timeInSeconds % 60;
        const timeFormatted = `${minutes}:${seconds.toString().padStart(2, '0')}`;

        let feedbackHtml = `
            <h3>Resultado del Intento ${result.attemptMetrics.attemptNumber}</h3>
            <div class="metrics-summary">
                <p>Tiempo de este intento: ${timeFormatted}</p>
                <p>Movimientos en este intento: ${result.attemptMetrics.moves}</p>
                <p>Requisitos correctos: ${result.correctCount}</p>
                <p>Requisitos incorrectos: ${result.incorrectCount}</p>
                <p>Precisión: ${result.accuracy}%</p>
            </div>
        `;

        if (result.incorrectRequirements && result.incorrectRequirements.length > 0) {
            feedbackHtml += `
                <div class="incorrect-feedback">
                    <h4>Requisitos que necesitan ser reclasificados:</h4>
                    <div class="requirements-feedback">
            `;

            result.incorrectRequirements.forEach(req => {
                feedbackHtml += `
                    <div class="incorrect-requirement">
                        <p class="requirement-description">${req.description}</p>
                        <p class="requirement-feedback">${req.feedback}</p>
                    </div>
                `;
            });

            feedbackHtml += `
                    </div>
                </div>
            `;
        } else {
            feedbackHtml += `
                <div class="success-feedback">
                    <h4>¡Felicitaciones!</h4>
                    <p>Has clasificado correctamente todos los requisitos.</p>
                </div>
            `;
        }

        content.innerHTML = feedbackHtml;
        modal.style.display = 'block';
    }

    showFeedback(result) {
        const minutes = Math.floor(result.attemptMetrics.timeInSeconds / 60);
        const seconds = result.attemptMetrics.timeInSeconds % 60;
        const timeFormatted = `${minutes}:${seconds.toString().padStart(2, '0')}`;

        let htmlContent = `
            <div class="metrics-summary">
                <p>${this.translations.get('modals.metrics.time_spent')}: ${timeFormatted}</p>
                <p>${this.translations.get('modals.metrics.moves_made')}: ${result.attemptMetrics.moves}</p>
                <p>${this.translations.get('modals.metrics.correct_req')}: ${result.correctCount}</p>
                <p>${this.translations.get('modals.metrics.incorrect_req')}: ${result.incorrectCount}</p>
                <p>${this.translations.get('modals.metrics.accuracy')}: ${result.accuracy}%</p>
            </div>
        `;

        if (result.incorrectRequirements?.length > 0) {
            htmlContent += `
                <div class="incorrect-feedback">
                    <h4>${this.translations.get('modals.feedback.reclassify_title')}</h4>
                    <div class="requirements-feedback">
                        ${result.incorrectRequirements.map(req => `
                            <div class="incorrect-requirement">
                                <p class="requirement-description">${req.description}</p>
                                <p class="requirement-feedback">${req.feedback}</p>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        } else {
            htmlContent += `
                <div class="success-feedback">
                    <h4>${this.translations.get('modals.feedback.success_title')}</h4>
                    <p>${this.translations.get('modals.feedback.success_message')}</p>
                </div>
            `;
        }

        Swal.fire({
            title: this.translations.get('modals.attempt_result'),
            html: htmlContent,
            confirmButtonText: this.translations.get('buttons.continue'),
            confirmButtonColor: '#1976D2',
            customClass: {
                container: 'game-feedback-modal',
                popup: 'game-feedback-popup',
                content: 'game-feedback-content'
            },
            didClose: () => {
                if (this.pendingRequirements.length === 0) {
                    this.gameCompleted();
                } else {
                    this.currentAttemptStartTime = new Date();
                    this.movesInCurrentAttempt = 0;
                    this.displayPendingRequirements();
                }
            }
        });
    }

    gameCompleted_old() {
        clearInterval(this.timer);
        const totalTime = Math.floor((new Date() - this.startTime) / 1000);
        const minutes = Math.floor(totalTime / 60);
        const seconds = totalTime % 60;

        const modal = document.getElementById('feedbackModal');
        const content = document.getElementById('feedbackContent');

        content.innerHTML = `
            <h3>¡Juego Completado!</h3>
            <div class="final-stats">
                <h4>Estadísticas Finales:</h4>
                <ul>
                    <li>Intentos totales: ${this.attempts}</li>
                    <li>Movimientos totales: ${this.totalMoves}</li>
                    <li>Tiempo total: ${minutes}:${seconds.toString().padStart(2, '0')}</li>
                </ul>
            </div>
        `;

        modal.style.display = 'block';
        document.getElementById('validateBtn').disabled = true;
    }

    gameCompleted() {
        clearInterval(this.timer);
        const totalTime = Math.floor((new Date() - this.startTime) / 1000);
        const minutes = Math.floor(totalTime / 60);
        const seconds = totalTime % 60;

        Swal.fire({
            title: this.translations.get('modals.game_completed.title'),
            html: `
                <div class="final-stats">
                    <h4>${this.translations.get('modals.game_completed.stats_title')}</h4>
                    <ul>
                        <li>${this.translations.get('modals.game_completed.total_attempts')}: ${this.attempts}</li>
                        <li>${this.translations.get('modals.game_completed.total_moves')}: ${this.totalMoves}</li>
                        <li>${this.translations.get('modals.game_completed.total_time')}: ${minutes}:${seconds.toString().padStart(2, '0')}</li>
                    </ul>
                </div>
            `,
            confirmButtonText: this.translations.get('buttons.continue'),
            confirmButtonColor: '#1976D2',
            customClass: {
                container: 'game-feedback-modal',
                popup: 'game-feedback-popup',
                content: 'game-feedback-content'
            },
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = base_url + '/dashboard';
            }
        });;

        document.getElementById('validateBtn').disabled = true;
    }

    // Nueva función para reiniciar el Map de movimientos
    resetMovementsTracking() {
        this.requirementMovements.clear();
        console.log('Movimientos reiniciados para nuevo intento');
    }
}

// Función para cerrar el modal de retroalimentación
function closeFeedbackModal() {
    document.getElementById('feedbackModal').style.display = 'none';
}

function updatePlaceholders() {
    const elements = document.querySelectorAll('[data-i18n-placeholder]');
    elements.forEach(element => {
        const key = element.getAttribute('data-i18n-placeholder');
        const translation = i18next.t(key); // O la función que uses para traducir
        element.setAttribute('data-placeholder', translation);
    });
}

// Inicialización cuando el documento está listo
document.addEventListener('DOMContentLoaded', () => {
    const game = new Game();
    const gameCode = new URLSearchParams(window.location.search).get('code');
    if (gameCode) {
        game.init(gameCode);
    }
});


