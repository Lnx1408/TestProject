/**
 * Clase para gestionar el cambio de correo electrónico
 */
class EmailManager {
    constructor() {
        this.config = {
            endpoints: {
                getProfileInfo: `${base_url}/Settings/getProfileInfo`,
                changeEmail: `${base_url}/Settings/changeEmail`
            },
            selectors: {
                form: '#emailForm',
                currentEmail: '#currentEmail',
                newEmail: '#newEmail',
                password: '#password',
                emailValidation: '#emailValidation',
                saveButton: '#saveEmailBtn',
                togglePassword: '.toggle-password'
            }
        };
        
        this.translations = {
            get: (key) => LanguageManager.getTranslation(`settings.email.${key}`)
        };

        this.state = {
            loading: false,
            emailValid: false,
            currentEmail: ''
        };
        
        this.init();
    }
    
    /**
     * Inicializa el componente
     */
    async init() {
        this.bindEvents();
        await this.loadCurrentEmail();
    }
    
    /**
     * Vincula eventos a elementos del DOM
     */
    bindEvents() {
        const form = document.querySelector(this.config.selectors.form);
        const newEmailInput = document.querySelector(this.config.selectors.newEmail);
        const togglePassword = document.querySelector(this.config.selectors.togglePassword);
        
        // Evento para enviar el formulario
        if (form) {
            form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
        
        // Evento para validar correo electrónico en tiempo real
        if (newEmailInput) {
            newEmailInput.addEventListener('input', () => this.validateEmail());
            newEmailInput.addEventListener('blur', () => this.validateEmail(true));
        }
        
        // Evento para toggle de contraseña
        if (togglePassword) {
            togglePassword.addEventListener('click', () => this.togglePasswordVisibility(togglePassword));
        }
    }
    
    /**
     * Carga el correo electrónico actual del usuario
     */
    async loadCurrentEmail() {
        try {
            this.setLoading(true);
            
            const response = await fetch(this.config.endpoints.getProfileInfo);
            const result = await response.json();
            
            if (!result.data) {
                throw new Error(this.translations.get('errors.no_data') || 'No se recibieron datos del servidor');
            }
            
            const decryptedData = CryptoModule.decrypt(result.data);
            
            if (!decryptedData.status) {
                throw new Error(decryptedData.msg || this.translations.get('errors.load_profile') || 'Error al cargar datos del perfil');
            }
            
            // Guardar y mostrar el correo actual
            this.state.currentEmail = decryptedData.profileData.correo || '';
            document.querySelector(this.config.selectors.currentEmail).textContent = this.state.currentEmail;
            
        } catch (error) {
            console.error('Error loading email:', error);
            this.showNotification('error', this.translations.get('errors.load_email') || 'Error al cargar el correo electrónico');
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * Valida el formato del nuevo correo electrónico
     * @param {boolean} showMessage - Si se debe mostrar un mensaje de validación
     */
    validateEmail(showMessage = false) {
        const newEmail = document.querySelector(this.config.selectors.newEmail).value;
        const validationEl = document.querySelector(this.config.selectors.emailValidation);
        
        if (!validationEl) return;
        
        // Limpiar mensaje anterior
        validationEl.textContent = '';
        validationEl.className = 'validation-message';
        
        // Si está vacío, no mostrar mensaje
        if (!newEmail) {
            this.state.emailValid = false;
            return;
        }
        
        // Validar formato de correo electrónico
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValidFormat = emailRegex.test(newEmail);
        
        // Validar que sea diferente al correo actual
        const isDifferent = newEmail.toLowerCase() !== this.state.currentEmail.toLowerCase();
        
        if (!isValidFormat) {
            validationEl.textContent = this.translations.get('validation.invalid_format') || 'Formato de correo electrónico inválido';
            validationEl.className = 'validation-message invalid';
            this.state.emailValid = false;
        } else if (!isDifferent) {
            validationEl.textContent = this.translations.get('validation.same_as_current') || 'El nuevo correo debe ser diferente al actual';
            validationEl.className = 'validation-message invalid';
            this.state.emailValid = false;
        } else if (showMessage) {
            validationEl.textContent = this.translations.get('validation.valid_email') || 'Correo electrónico válido';
            validationEl.className = 'validation-message valid';
            this.state.emailValid = true;
        } else {
            this.state.emailValid = true;
        }
    }
    
    /**
     * Alterna la visibilidad del campo de contraseña
     * @param {HTMLElement} toggleElement - Elemento de alternancia
     */
    togglePasswordVisibility(toggleElement) {
        if (!toggleElement) return;
        
        const targetId = toggleElement.getAttribute('data-target');
        const passwordInput = document.getElementById(targetId);
        
        if (!passwordInput) return;
        
        // Cambiar tipo de input
        const isVisible = passwordInput.type === 'text';
        passwordInput.type = isVisible ? 'password' : 'text';
        
        // Actualizar icono
        toggleElement.className = isVisible ? 'bx bx-hide toggle-password' : 'bx bx-show toggle-password';
    }
    
    /**
     * Maneja el envío del formulario
     * @param {Event} e - Evento submit
     */
    async handleSubmit(e) {
        e.preventDefault();
        
        // Validar correo
        this.validateEmail(true);
        
        const newEmail = document.querySelector(this.config.selectors.newEmail).value;
        const password = document.querySelector(this.config.selectors.password).value;
        
        // Verificar que los campos estén completos
        if (!newEmail || !password) {
            this.showNotification('error', this.translations.get('errors.complete_fields') || 'Por favor, completa todos los campos');
            return;
        }
        
        // Verificar que el correo sea válido
        if (!this.state.emailValid) {
            return; // El mensaje ya se muestra en la UI
        }
        
        // Confirmar cambio
        const confirmResult = await Swal.fire({
            title: this.translations.get('confirm.title') || '¿Cambiar correo electrónico?',
            text: (this.translations.get('confirm.message') || '¿Estás seguro de cambiar tu correo a email').replace('{email}', newEmail),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1976D2',
            cancelButtonColor: '#D32F2F',
            confirmButtonText: this.translations.get('confirm.yes') || 'Sí, cambiar',
            cancelButtonText: this.translations.get('confirm.no') || 'Cancelar',
            customClass: {
                container: 'settings-confirmation-modal',
                popup: 'settings-confirmation-popup',
            }
        });
        
        if (!confirmResult.isConfirmed) return;
        
        // Enviar petición de cambio de correo
        try {
            // Mostrar indicador de carga
            this.setLoading(true);
            
            const response = await fetch(this.config.endpoints.changeEmail, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt({
                        newEmail,
                        password
                    })
                })
            });
            
            const result = await response.json();
            const decryptedData = CryptoModule.decrypt(result.data);
            
            if (!decryptedData.status) {
                throw new Error(decryptedData.msg || this.translations.get('errors.change_email') || 'Error al cambiar el correo electrónico');
            }
            
            // Mostrar mensaje de éxito y redirigir
            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Correo electrónico actualizado correctamente',
                confirmButtonColor: 'var(--primary)',
                customClass: {
                    container: 'settings-confirmation-modal',
                    popup: 'settings-confirmation-popup',
                }
            }).then(() => {
                window.location.href = `${base_url}/settings?section=account`;
            });
            
        } catch (error) {
            console.error('Error changing email:', error);
            this.showNotification('error', error.message || this.translations.get('errors.general') || 'Error al cambiar el correo electrónico');
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * Muestra una notificación
     * @param {string} type - Tipo de notificación (success, error)
     * @param {string} message - Mensaje a mostrar
     */
    showNotification(type, message) {
        return Swal.fire({
            icon: type,
            title: type === 'success' ? 
                this.translations.get('messages.success') || '¡Éxito!' 
                : this.translations.get('messages.error') || 'Error',
            text: message,
            confirmButtonColor: 'var(--primary)',
            customClass: {
                container: 'settings-confirmation-modal',
                popup: 'settings-confirmation-popup',
            }
        });
    }
    
    /**
     * Establece el estado de carga
     * @param {boolean} isLoading - Estado de carga
     */
    setLoading(isLoading) {
        this.state.loading = isLoading;
        
        const form = document.querySelector(this.config.selectors.form);
        const saveBtn = document.querySelector(this.config.selectors.saveButton);
        
        if (!form || !saveBtn) return;
        
        // Deshabilitar elementos durante la carga
        const inputs = form.querySelectorAll('input');
        inputs.forEach(input => {
            input.disabled = isLoading;
        });
        
        // Actualizar botón
        if (isLoading) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<i class="bx bx-loader-alt bx-spin"></i> ${this.translations.get('buttons.processing') || 'Procesando...'}`;
        } else {
            saveBtn.disabled = false;
            saveBtn.innerHTML = this.translations.get('buttons.change_email') || 'Cambiar correo electrónico';
        }
    }
}

// Inicializar el módulo cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {
    window.emailManager = new EmailManager();
});