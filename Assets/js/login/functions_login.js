class LoginModule {
    constructor() {
        this.config = {
            endpoints: {
                login: `${base_url}/Login/loginUser`,
                register: `${base_url}/Login/registerUser`,
                reset: `${base_url}/Login/requestReset`
            },
            forms: {
                login: '#formLogin',
                register: '#formRegister',
                reset: '#formRecetPass'
            },
            selectors: {
                loginBox: '.login-box',
                formSections: '.form-section'
            },
            minHeight: '15vh',
            maxHeight: '90vh'
        };

        this.state = {
            activeForm: 'login'
        };

        this.init();
    }

    init() {
        this.initializeFormToggles();
        this.initializeFormSubmissions();
    }

    initializeFormToggles() {
        const forms = document.querySelectorAll(this.config.selectors.formSections);
        const loginBox = document.querySelector(this.config.selectors.loginBox);

        if (forms.length > 0) {
            loginBox.style.minHeight = this.config.minHeight;
            loginBox.style.maxHeight = this.config.maxHeight;
        }

        document.querySelectorAll('[data-toggle="form"]').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const targetForm = trigger.getAttribute('data-target');
                this.switchForm(targetForm);
            });
        });
    }

    switchForm(targetFormId) {
        const forms = document.querySelectorAll(this.config.selectors.formSections);
        const loginBox = document.querySelector(this.config.selectors.loginBox);

        forms.forEach(form => form.classList.remove('active'));

        const targetForm = document.querySelector(`[data-form="${targetFormId}"]`);
        if (targetForm) {
            targetForm.classList.add('active');
            const targetHeight = targetForm.scrollHeight;
            loginBox.style.minHeight = `${targetHeight}px`;
            loginBox.style.maxHeight = `${targetHeight}px`;
        }

        this.state.activeForm = targetFormId;
    }

    initializeFormSubmissions() {
        const formLogin = document.querySelector(this.config.forms.login);
        const formRegister = document.querySelector(this.config.forms.register);
        const formResetPass = document.querySelector(this.config.forms.reset);

        if (formRegister) {
            formRegister.onsubmit = (e) => this.handleRegistration(e);
        }

        if (formLogin) {
            formLogin.onsubmit = (e) => this.handleLogin(e);
        }

        if (formResetPass) {
            formResetPass.onsubmit = (e) => this.handlePasswordReset(e);
        }
    }

    async handleRegistration(e) {
        e.preventDefault();

        const fieldsToValidate = [
            { id: '#SelectTypeUser', name: 'Tipo de Usuario' },
            { id: '#txtUserRegister', name: 'Usuario' },
            { id: '#txtFirstNameRegister', name: 'Nombres' },
            { id: '#txtLastNameRegister', name: 'Apellidos' },
            { id: '#txtEmailRegister', name: 'Email' },
            { id: '#txtPasswordRegister', name: 'Contraseña' }
        ];

        if (!this.validateFormFields(fieldsToValidate)) {
            this.showAlert("error", "Oops...", "Verifique que todos los datos estén completos.");
            return;
        }

        const result = await Swal.fire({
            title: "Registrar Usuario",
            text: "¿Quiere proceder con el registro?",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Continuar",
            cancelButtonText: "Cancelar"
        });

        if (result.isConfirmed) {
            const formData = new FormData(e.target);
            await this.sendRegistration(formData);
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        const email = document.querySelector('#txtEmail').value.trim();
        const password = document.querySelector('#txtPassword').value.trim();

        if (!this.validateLoginCredentials(email, password)) {
            this.showAlert("error", "Oops...", "Verifique que todos los datos estén completos.");
            return;
        }

        const formData = new FormData(e.target);
        await this.sendLogin(formData);
    }

    async handlePasswordReset(e) {
        e.preventDefault();
        const email = document.querySelector('#txtEmailReset').value.trim();
        if (!email) {
            this.showAlert("info", "Oops...", "Por favor ingresa tu correo electrónico.");
            return;
        }
        // Validar formato de email
        if (!this.validateEmail(email)) {
            this.showAlert("info", "Oops...", "Por favor ingresa un correo electrónico válido.");
            return;
        }
        await this.sendResetPassword(email);
    }

    validateFormFields(fields) {
        return fields.every(field => {
            const element = document.querySelector(field.id);
            return element.value.trim() !== "";
        });
    }

    validateLoginCredentials(email, password) {
        return email !== "" && password !== "";
    }

    async sendRegistration(formData) {
        try {
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            const response = await fetch(this.config.endpoints.register, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt(data)
                })
            });

            const result = await response.json();
            if (!result.data) {
                throw new Error('No se obtuvo una respuesta valida');
            }
            const decryptedResult = CryptoModule.decrypt(result.data);
            if (decryptedResult.status) {
                await Swal.fire({
                    title: "Registro exitoso",
                    text: "Ha sido registrado con éxito",
                    icon: "success"
                });
                this.switchForm('login');
            } else {
                this.showAlert("error", "Error", decryptedResult.msg || "Ocurrió un problema.");
            }
        } catch (error) {
            console.error('Error en registro:', error);
            this.showAlert("error", "Error", "Error al procesar la solicitud");
        }
    }

    async sendLogin(formData) {
        try {
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            const response = await fetch(this.config.endpoints.login, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt(data)
                })
            });

            const result = await response.json();
            if (!result.data) {
                throw new Error('No se obtuvo una respuesta valida');
            }

            const decryptedResult = CryptoModule.decrypt(result.data);
            if (decryptedResult.status) {
                window.location = base_url + '/dashboard';
            } else {
                this.showAlert("error", "Error", decryptedResult.msg || "Ocurrió un problema.");
            }
        } catch (error) {
            console.error('Error en login:', error);
            this.showAlert("error", "Error", "Error al procesar la solicitud");
        }
    }

    async sendResetPassword(email) {
        try {
            // Mostrar loading
            Swal.fire({
                title: "Procesando",
                text: "Estamos procesando tu solicitud...",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const response = await fetch(this.config.endpoints.reset, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt({ email })
                })
            });
            const result = await response.json();
            const decryptedResult = CryptoModule.decrypt(result.data);

            if (decryptedResult.status) {
                await this.showAlert("success", "¡Solicitud enviada!", decryptedResult.msg);
                document.querySelector('#txtEmailReset').value = '';
                this.switchForm('login');
            } else {
                this.showAlert("error", "Error", decryptedResult.msg || "Ocurrió un error al procesar la solicitud.");
            }
        } catch (error) {
            console.error('Error en login:', error);
            this.showAlert("error", "Error", "Error al procesar la solicitud");
        }
    }

    showAlert(icon, title, text) {
        return Swal.fire({ icon, title, text });
    }

    /**
    * Valida un formato de email
    */
    validateEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }
}

// Inicialización cuando el DOM está cargado
document.addEventListener('DOMContentLoaded', () => {
    window.loginModule = new LoginModule();
});