/**
 * Clase para gestionar la funcionalidad de la página de perfil
 */
class ProfileManager {
    constructor() {
        this.config = {
            endpoints: {
                getProfileInfo: `${base_url}/Settings/getProfileInfo`,
                updateProfile: `${base_url}/Settings/updateProfile`
            },
            selectors: {
                form: '#profileForm',
                firstName: '#profileFirstName',
                lastName: '#profileLastName',
                username: '#profileUsername',
                email: '#profileEmail',
                userType: '#profileType',
                regDate: '#profileRegDate',
                formActions: '#formActions',
                cancelBtn: '#cancelChangesBtn',
                saveBtn: '#saveChangesBtn',
                avatar: '#profileAvatar',
                initials: '#profileInitials'
            }
        };

        // Sistema de traducciones específico para esta clase
        this.translations = {
            get: (key) => LanguageManager.getTranslation(`settings.profile.${key}`)
        };

        this.state = {
            loading: false,
            originalData: null,
            editable: ['nombres', 'apellidos'],
            hasChanges: false
        };

        this.init();
    }

    /**
     * Inicializa el módulo
     */
    async init() {
        this.bindEvents();
        await this.loadProfileData();
    }

    /**
     * Vincula eventos a elementos del DOM
     */
    bindEvents() {
        const form = document.querySelector(this.config.selectors.form);
        const cancelBtn = document.querySelector(this.config.selectors.cancelBtn);

        // Evento para detectar cambios en campos editables
        document.querySelectorAll('.editable').forEach(input => {
            input.addEventListener('input', () => this.handleInputChange(input));
        });

        // Evento para cancelar cambios
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.resetForm());
        }

        // Evento para enviar formulario
        if (form) {
            form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
    }

    /**
     * Carga los datos del perfil desde el servidor
     */
    async loadProfileData() {
        try {
            this.setLoading(true);

            const response = await fetch(this.config.endpoints.getProfileInfo);
            const result = await response.json();

            if (!result.data) {
                throw new Error(this.translations.get('errors.no_data') || 'No se recibieron datos del servidor');
            }

            const decryptedData = CryptoModule.decrypt(result.data);

            if (!decryptedData.status) {
                throw new Error(decryptedData.msg || this.translations.get('errors.load_failed') || 'Error al cargar datos del perfil');
            }

            this.populateForm(decryptedData.profileData);
            this.state.originalData = { ...decryptedData.profileData };
            this.updateAvatar(decryptedData.profileData.nombres, decryptedData.profileData.apellidos);

        } catch (error) {
            console.error('Error loading profile data:', error);
            this.showNotification('error', this.translations.get('errors.load_failed') || 'Error al cargar los datos del perfil');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Rellena el formulario con los datos del usuario
     * @param {Object} userData - Datos del usuario
     */
    populateForm(userData) {
        if (!userData) return;

        // Rellenar campos del formulario
        document.querySelector(this.config.selectors.firstName).value = userData.nombres || '';
        document.querySelector(this.config.selectors.lastName).value = userData.apellidos || '';

        // Actualizar campos de solo lectura
        document.querySelector(this.config.selectors.username).textContent = userData.usuario || '';
        document.querySelector(this.config.selectors.email).textContent = userData.correo || '';
        document.querySelector(this.config.selectors.userType).textContent = userData.tipo_usuario || '';

        // Formatear fecha si existe
        if (userData.fecha_registro) {
            const date = new Date(userData.fecha_registro);
            const formattedDate = date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.querySelector(this.config.selectors.regDate).textContent = formattedDate;
        } else {
            document.querySelector(this.config.selectors.regDate).textContent = this.translations.get('date_unavailable') || 'No disponible';
        }
    }

    /**
     * Actualiza el avatar con las iniciales
     * @param {string} firstName - Nombre
     * @param {string} lastName - Apellido
     */
    updateAvatar(firstName, lastName) {
        const avatarElement = document.querySelector(this.config.selectors.avatar);
        const initialsElement = document.querySelector(this.config.selectors.initials);

        if (avatarElement && initialsElement && AvatarModule) {
            AvatarModule.updateAvatar(avatarElement, initialsElement, firstName || '', lastName || '');
        }
    }

    /**
     * Maneja cambios en los inputs editables
     * @param {HTMLElement} input - Elemento input modificado
     */
    handleInputChange(input) {
        // Marcar el campo como editado si ha cambiado
        const originalValue = this.state.originalData?.[input.name] || '';
        const hasChanged = input.value !== originalValue;

        if (hasChanged) {
            input.classList.add('edited');
        } else {
            input.classList.remove('edited');
        }

        // Verificar si hay cambios en el formulario
        this.checkFormChanges();

        // Actualizar avatar en tiempo real
        if (input.name === 'nombres' || input.name === 'apellidos') {
            const firstName = document.querySelector(this.config.selectors.firstName).value;
            const lastName = document.querySelector(this.config.selectors.lastName).value;
            this.updateAvatar(firstName, lastName);
        }
    }

    /**
     * Verifica si hay cambios en el formulario
     */
    checkFormChanges() {
        if (!this.state.originalData) return;

        let hasChanges = false;

        // Verificar cada campo editable
        this.state.editable.forEach(fieldName => {
            const selector = this.getInputSelector(fieldName);
            const input = document.querySelector(selector);

            if (input && input.value !== this.state.originalData[fieldName]) {
                hasChanges = true;
            }
        });

        // Actualizar estado y mostrar/ocultar botones
        this.state.hasChanges = hasChanges;
        document.querySelector(this.config.selectors.formActions).style.display = hasChanges ? 'flex' : 'none';
    }

    /**
     * Resetea el formulario a los valores originales
     */
    resetForm() {
        if (!this.state.originalData) return;

        // Restaurar valores originales
        this.populateForm(this.state.originalData);

        // Actualizar avatar
        this.updateAvatar(
            this.state.originalData.nombres,
            this.state.originalData.apellidos
        );

        // Limpiar clases de edición
        document.querySelectorAll('.edited').forEach(input => {
            input.classList.remove('edited');
        });

        // Ocultar botones de acción
        document.querySelector(this.config.selectors.formActions).style.display = 'none';
        this.state.hasChanges = false;
    }

    /**
     * Maneja el envío del formulario
     * @param {Event} e - Evento submit
     */
    async handleSubmit(e) {
        e.preventDefault();

        if (!this.state.hasChanges) return;

        try {
            this.setLoading(true);

            // Extraer datos editados
            const formData = {
                nombres: document.querySelector(this.config.selectors.firstName).value,
                apellidos: document.querySelector(this.config.selectors.lastName).value
            };

            // Enviar datos al servidor
            const response = await fetch(this.config.endpoints.updateProfile, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt(formData)
                })
            });

            const result = await response.json();

            if (!result.data) {
                throw new Error(this.translations.get('errors.no_data') || 'No se recibieron datos del servidor');
            }

            const decryptedResult = CryptoModule.decrypt(result.data);

            if (!decryptedResult.status) {
                throw new Error(decryptedResult.msg || this.translations.get('errors.update_failed') || 'Error al actualizar perfil');
            }

            // Actualizar datos originales
            this.state.originalData = {
                ...this.state.originalData,
                ...formData
            };

            // Limpiar estado del formulario
            document.querySelectorAll('.edited').forEach(input => {
                input.classList.remove('edited');
            });

            // Ocultar botones de acción
            document.querySelector(this.config.selectors.formActions).style.display = 'none';
            this.state.hasChanges = false;

            // Mostrar mensaje de éxito
            this.showNotification('success', this.translations.get('update_success') || 'Perfil actualizado correctamente');

        } catch (error) {
            console.error('Error updating profile:', error);
            this.showNotification('error', error.message || this.translations.get('errors.update_failed') || 'Error al actualizar el perfil');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Establece el estado de carga
     * @param {boolean} isLoading - Estado de carga
     */
    setLoading(isLoading) {
        this.state.loading = isLoading;

        // Actualizar UI según estado de carga
        if (isLoading) {
            document.querySelectorAll('button').forEach(btn => {
                btn.disabled = true;
            });
        } else {
            document.querySelectorAll('button').forEach(btn => {
                btn.disabled = false;
            });
        }
    }

    /**
     * Muestra una notificación
     * @param {string} type - Tipo de notificación (success, error)
     * @param {string} message - Mensaje a mostrar
     */
    showNotification(type, message) {
        Swal.fire({
            icon: type,
            title: type === 'success'
                ? this.translations.get('notifications.success_title') || '¡Éxito!'
                : this.translations.get('notifications.error_title') || 'Error',
            text: message,
            timer: type === 'success' ? 5000 : undefined,
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
     * Obtiene el selector de un campo por su nombre
     * @param {string} fieldName - Nombre del campo
     * @returns {string} Selector CSS
     */
    getInputSelector(fieldName) {
        const selectors = {
            'nombres': this.config.selectors.firstName,
            'apellidos': this.config.selectors.lastName,
            'usuario': this.config.selectors.username,
            'correo': this.config.selectors.email,
            'tipo_usuario': this.config.selectors.userType
        };

        return selectors[fieldName] || '';
    }
}

// Inicializar el módulo cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {
    window.profileManager = new ProfileManager();
});