/**
 * Clase para gestionar el cambio de contraseña
 */
class PasswordManager {
    constructor() {
        this.config = {
            endpoints: {
                changePassword: `${base_url}/Settings/changePassword`
            },
            selectors: {
                form: '#passwordForm',
                currentPassword: '#currentPassword',
                newPassword: '#newPassword',
                confirmPassword: '#confirmPassword',
                passwordStrength: '#passwordStrength',
                strengthProgress: '.strength-progress',
                strengthText: '.strength-text',
                passwordMatch: '#passwordMatch',
                saveButton: '#savePasswordBtn',
                togglePassword: '.toggle-password',
                requirements: {
                    length: '#reqLength',
                    lower: '#reqLower',
                    upper: '#reqUpper',
                    number: '#reqNumber'
                }
            },
            passwordRequirements: {
                minLength: 8,
                requireLower: true,
                requireUpper: true,
                requireNumber: true
            }
        };

        this.translations = {
            get: (key) => LanguageManager.getTranslation(`settings.password.${key}`)
        };

        this.state = {
            passwordValid: false,
            passwordsMatch: false,
            passwordStrength: 0
        };

        this.init();
    }

    /**
     * Inicializa el componente
     */
    init() {
        this.bindEvents();
    }

    /**
     * Vincula eventos a elementos del DOM
     */
    bindEvents() {
        const form = document.querySelector(this.config.selectors.form);
        const newPasswordInput = document.querySelector(this.config.selectors.newPassword);
        const confirmPasswordInput = document.querySelector(this.config.selectors.confirmPassword);
        const toggles = document.querySelectorAll(this.config.selectors.togglePassword);

        // Evento para enviar el formulario
        if (form) {
            form.addEventListener('submit', (e) => this.handleSubmit(e));
        }

        // Eventos para validar la contraseña en tiempo real
        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', () => this.validateNewPassword());
            newPasswordInput.addEventListener('keyup', () => this.validateNewPassword());
        }

        // Eventos para validar la coincidencia de contraseñas
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', () => this.checkPasswordMatch());
            confirmPasswordInput.addEventListener('keyup', () => this.checkPasswordMatch());
        }

        // Eventos para mostrar/ocultar contraseñas
        toggles.forEach(toggle => {
            toggle.addEventListener('click', () => this.togglePasswordVisibility(toggle));
        });
    }

    /**
     * Valida la nueva contraseña
     */
    validateNewPassword() {
        const password = document.querySelector(this.config.selectors.newPassword).value;
        const requirements = this.config.passwordRequirements;

        // Verificar requisitos individuales
        const meetsLength = password.length >= requirements.minLength;
        const meetsLower = !requirements.requireLower || /[a-z]/.test(password);
        const meetsUpper = !requirements.requireUpper || /[A-Z]/.test(password);
        const meetsNumber = !requirements.requireNumber || /[0-9]/.test(password);

        // Actualizar iconos de requisitos
        this.updateRequirementStatus(this.config.selectors.requirements.length, meetsLength);
        this.updateRequirementStatus(this.config.selectors.requirements.lower, meetsLower);
        this.updateRequirementStatus(this.config.selectors.requirements.upper, meetsUpper);
        this.updateRequirementStatus(this.config.selectors.requirements.number, meetsNumber);

        // Calcular fortaleza de la contraseña
        let strength = 0;
        if (password.length > 0) {
            if (meetsLength) strength += 25;
            if (meetsLower) strength += 25;
            if (meetsUpper) strength += 25;
            if (meetsNumber) strength += 25;
        }

        // Actualizar indicador de fortaleza
        this.updateStrengthIndicator(strength);

        // Actualizar estado global
        this.state.passwordValid = meetsLength && meetsLower && meetsUpper && meetsNumber;
        this.updateSaveButton();
    }

    /**
     * Actualiza el estado visual de un requisito
     * @param {string} selector - Selector del elemento
     * @param {boolean} isValid - Si el requisito se cumple
     */
    updateRequirementStatus(selector, isValid) {
        const element = document.querySelector(selector);
        if (!element) return;

        const icon = element.querySelector('i');
        if (!icon) return;

        if (isValid) {
            icon.className = 'bx bx-check';
            element.style.color = 'var(--success)';
        } else {
            icon.className = 'bx bx-x';
            element.style.color = 'var(--text-color-secondary)';
        }
    }

    /**
     * Actualiza el indicador visual de fortaleza
     * @param {number} strength - Porcentaje de fortaleza (0-100)
     */
    updateStrengthIndicator(strength) {
        const progressBar = document.querySelector(this.config.selectors.strengthProgress);
        const strengthText = document.querySelector(this.config.selectors.strengthText);

        if (!progressBar || !strengthText) return;

        // Actualizar el ancho de la barra
        progressBar.style.width = `${strength}%`;

        // Quitar clases anteriores
        progressBar.classList.remove('weak', 'medium', 'strong', 'very-strong');

        // Determinar clase y texto según fortaleza
        let strengthClass = '';
        let strengthMessage = '';

        if (strength === 0) {
            strengthMessage = this.translations.get('strength.default') || 'Fortaleza de contraseña';
        } else if (strength <= 25) {
            strengthClass = 'weak';
            strengthMessage = this.translations.get('strength.weak') || 'Débil';
        } else if (strength <= 50) {
            strengthClass = 'medium';
            strengthMessage = this.translations.get('strength.medium') || 'Media';
        } else if (strength <= 75) {
            strengthClass = 'strong';
            strengthMessage = this.translations.get('strength.strong') || 'Fuerte';
        } else {
            strengthClass = 'very-strong';
            strengthMessage = this.translations.get('strength.very_strong') || 'Muy fuerte';
        }

        // Aplicar clase y texto (solo si la clase no está vacía)
        if (strengthClass) {
            progressBar.classList.add(strengthClass);
        }
        strengthText.textContent = strengthMessage;

        // Guardar fortaleza actual
        this.state.passwordStrength = strength;
    }

    /**
     * Verifica si las contraseñas coinciden
     */
    checkPasswordMatch() {
        const newPassword = document.querySelector(this.config.selectors.newPassword).value;
        const confirmPassword = document.querySelector(this.config.selectors.confirmPassword).value;
        const matchIndicator = document.querySelector(this.config.selectors.passwordMatch);

        if (!matchIndicator) return;

        const icon = matchIndicator.querySelector('i');
        const isMatching = newPassword === confirmPassword && newPassword.length > 0;

        // Actualizar indicador visual
        if (isMatching) {
            matchIndicator.classList.add('matching');
            if (icon) icon.className = 'bx bx-check';
            matchIndicator.textContent = confirmPassword.length > 0 ? (this.translations.get('validation.passwords_match') || 'Las contraseñas coinciden') : '';
            if (icon) matchIndicator.prepend(icon);
        } else {
            matchIndicator.classList.remove('matching');
            if (icon) icon.className = 'bx bx-x';
            matchIndicator.textContent = confirmPassword.length > 0 ? (this.translations.get('validation.passwords_not_match') || 'Las contraseñas no coinciden') : '';
            if (icon) matchIndicator.prepend(icon);
        }

        // Actualizar estado
        this.state.passwordsMatch = isMatching;
        this.updateSaveButton();
    }

    /**
     * Actualiza el estado del botón guardar
     */
    updateSaveButton() {
        const saveBtn = document.querySelector(this.config.selectors.saveButton);
        if (!saveBtn) return;

        saveBtn.disabled = !(this.state.passwordValid && this.state.passwordsMatch);
    }

    /**
     * Alterna la visibilidad de un campo de contraseña
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

        const currentPassword = document.querySelector(this.config.selectors.currentPassword).value;
        const newPassword = document.querySelector(this.config.selectors.newPassword).value;

        // Verificar que los campos estén completos
        if (!currentPassword || !newPassword) {
            this.showNotification('error', this.translations.get('errors.complete_fields') || 'Por favor, completa todos los campos');
            return;
        }

        // Verificar requisitos de contraseña
        if (!this.state.passwordValid) {
            this.showNotification('error', this.translations.get('errors.invalid_password') || 'La nueva contraseña no cumple con los requisitos');
            return;
        }

        // Verificar que las contraseñas coinciden
        if (!this.state.passwordsMatch) {
            this.showNotification('error', this.translations.get('errors.passwords_not_match') || 'Las contraseñas no coinciden');
            return;
        }

        // Mostrar diálogo de confirmación
        const confirmResult = await Swal.fire({
            title: this.translations.get('confirm.title'),
            text: this.translations.get('confirm.message'),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1976D2',
            cancelButtonColor: '#D32F2F',
            confirmButtonText: this.translations.get('confirm.yes'),
            cancelButtonText: this.translations.get('confirm.no'),
            customClass: {
                container: 'settings-confirmation-modal',
                popup: 'settings-confirmation-popup',
            }
        });

        if (!confirmResult.isConfirmed) return;

        // Enviar petición de cambio de contraseña
        try {
            // Mostrar indicador de carga
            this.toggleLoading(true);

            const response = await fetch(this.config.endpoints.changePassword, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt({
                        currentPassword,
                        newPassword
                    })
                })
            });

            const result = await response.json();
            const decryptedData = CryptoModule.decrypt(result.data);

            if (!decryptedData.status) {
                throw new Error(decryptedData.msg || this.translations.get('errors.change_password') || 'Error al cambiar la contraseña');
            }
            // Resetear formulario
            this.resetForm();
            await Swal.fire({
                icon: 'success',
                title: (this.translations.get('success.title') || '¡Éxito!'),
                text: (this.translations.get('success.message') || 'Contraseña actualizada correctamente'),
                confirmButtonColor: 'var(--primary)',
                customClass: {
                    container: 'settings-confirmation-modal',
                    popup: 'settings-confirmation-popup',
                }
            }).then(() => {
                window.location.href = `${base_url}/settings?section=account`;
            });
        } catch (error) {
            console.error('Error changing password:', error);
            this.showNotification('error', error.message || this.translations.get('errors.general') || 'Error al cambiar la contraseña');
        } finally {
            this.toggleLoading(false);
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
            title: type === 'success' ? (this.translations.get('messages.success') || '¡Éxito!')
                : (this.translations.get('messages.error') || 'Error'),
            text: message,
            timerProgressBar: type === 'success',
            showConfirmButton: true,
            confirmButtonColor: 'var(--primary)',
            customClass: {
                container: 'settings-confirmation-modal',
                popup: 'settings-confirmation-popup',
            }
        });
    }

    /**
     * Alterna el estado de carga
     * @param {boolean} isLoading - Estado de carga
     */
    toggleLoading(isLoading) {
        const form = document.querySelector(this.config.selectors.form);
        const saveBtn = document.querySelector(this.config.selectors.saveButton);

        if (form) {
            const inputs = form.querySelectorAll('input');
            inputs.forEach(input => {
                input.disabled = isLoading;
            });
        }

        if (saveBtn) {
            if (isLoading) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = `<i class="bx bx-loader-alt bx-spin"></i> ${this.translations.get('buttons.processing') || 'Procesando...'}`;
            } else {
                saveBtn.disabled = !(this.state.passwordValid && this.state.passwordsMatch);
                saveBtn.innerHTML = this.translations.get('buttons.change_password') || 'Cambiar contraseña';
            }
        }
    }

    /**
     * Resetea el formulario
     */
    resetForm() {
        const form = document.querySelector(this.config.selectors.form);
        if (form) {
            form.reset();
        }

        // Reiniciar indicadores
        const progressBar = document.querySelector(this.config.selectors.strengthProgress);
        const strengthText = document.querySelector(this.config.selectors.strengthText);

        if (progressBar) {
            progressBar.style.width = '0%';
            progressBar.classList.remove('weak', 'medium', 'strong', 'very-strong');
        }

        if (strengthText) {
            strengthText.textContent = this.translations.get('strength.default') || 'Fortaleza de contraseña';
        }

        // Reiniciar requisitos
        Object.values(this.config.selectors.requirements).forEach(selector => {
            const element = document.querySelector(selector);
            if (!element) return;

            const icon = element.querySelector('i');
            if (icon) {
                icon.className = 'bx bx-x';
            }
            element.style.color = 'var(--text-color-secondary)';
        });

        // Reiniciar indicador de coincidencia
        const matchIndicator = document.querySelector(this.config.selectors.passwordMatch);
        if (matchIndicator) {
            matchIndicator.classList.remove('matching');
            const icon = matchIndicator.querySelector('i');
            if (icon) {
                icon.className = 'bx bx-x';
            }
            matchIndicator.textContent = '';
            if (icon) matchIndicator.prepend(icon);
        }

        // Reiniciar estado
        this.state.passwordValid = false;
        this.state.passwordsMatch = false;
        this.state.passwordStrength = 0;

        // Actualizar botón
        this.updateSaveButton();
    }
}

// Inicializar el módulo cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {
    window.passwordManager = new PasswordManager();
});