/**
 * Clase para gestionar un modal personalizado
 */
class CustomModal {
    constructor(options = {}) {
        this.options = {
            title: 'Modal',
            closeOnClickOutside: true,
            onClose: null,
            scrollable: true,
            ...options
        };

        this.modal = null;
        this.overlay = null;
        this.initialized = false;

        // Crear estructura del modal
        this.createModal();
    }

    /**
     * Crea la estructura HTML del modal
     */
    createModal() {
        // Crear overlay
        this.overlay = document.createElement('div');
        this.overlay.className = 'custom-modal-overlay';

        // Crear contenedor del modal
        this.modal = document.createElement('div');
        this.modal.className = 'custom-modal';

        // Crear header
        const header = document.createElement('div');
        header.className = 'custom-modal-header';

        const title = document.createElement('h3');
        title.className = 'custom-modal-title';
        title.textContent = this.options.title;

        const closeBtn = document.createElement('button');
        closeBtn.className = 'custom-modal-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.setAttribute('aria-label', 'Cerrar');
        closeBtn.addEventListener('click', () => this.close());

        header.appendChild(title);
        header.appendChild(closeBtn);

        // Crear body
        const body = document.createElement('div');
        body.className = 'custom-modal-body';

        // Ensamblar modal
        this.modal.appendChild(header);
        this.modal.appendChild(body);
        this.overlay.appendChild(this.modal);

        // Añadir eventos
        if (this.options.closeOnClickOutside) {
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) {
                    this.close();
                }
            });
        }

        // Añadir al DOM
        document.body.appendChild(this.overlay);

        this.initialized = true;
    }

    /**
     * Configura si el cuerpo del modal es scrollable
     * @param {boolean} scrollable - Si el cuerpo debe tener scroll o no
     */
    setScrollable(scrollable) {
        const body = this.modal.querySelector('.custom-modal-body');
        if (body) {
            if (scrollable) {
                body.classList.add('scrollable');
            } else {
                body.classList.remove('scrollable');
            }
        }
        this.options.scrollable = scrollable;
    }

    /**
     * Establece el contenido del modal
     * @param {string|HTMLElement} content - Contenido HTML o elemento DOM
     */
    setContent(content) {
        const body = this.modal.querySelector('.custom-modal-body');

        if (!body) return;

        // Limpiar contenido actual
        body.innerHTML = '';

        // Añadir nuevo contenido
        if (typeof content === 'string') {
            body.innerHTML = content;
        } else if (content instanceof HTMLElement) {
            body.appendChild(content);
        }
    }

    /**
     * Establece el título del modal
     * @param {string} title - Título del modal
     */
    setTitle(title) {
        const titleEl = this.modal.querySelector('.custom-modal-title');
        if (titleEl) {
            titleEl.textContent = title;
        }
    }

    /**
     * Abre el modal
     */
    open() {
        if (!this.initialized) {
            this.createModal();
        }

        this.overlay.classList.add('active');

        // Evitar scroll en el body mientras el modal está abierto
        document.body.style.overflow = 'hidden';

        // Disparar evento
        const event = new CustomEvent('modal:open', { detail: { modal: this } });
        document.dispatchEvent(event);
    }

    /**
     * Cierra el modal
     */
    close() {
        if (!this.initialized) return;

        this.overlay.classList.remove('active');

        // Restaurar scroll del body
        document.body.style.overflow = '';

        // Ejecutar callback si existe
        if (typeof this.options.onClose === 'function') {
            this.options.onClose();
        }

        // Disparar evento
        const event = new CustomEvent('modal:close', { detail: { modal: this } });
        document.dispatchEvent(event);
    }

    /**
     * Destruye el modal
     */
    destroy() {
        if (!this.initialized) return;

        this.overlay.remove();
        this.initialized = false;
    }
}