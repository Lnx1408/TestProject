class teacherManagement {
    constructor() {
        this.config = {
            endpoints: {
                register: `${base_url}/Teacher/registerTeacher`,
            },
            forms: {
                register: '#teacherForm',
            },
            selectors: {
                loginBox: '.login-box',
                formSections: '.form-section'
            }
        };

        this.state = {
            activeForm: 'teacher'
        };

        this.init();
    }

    init() {
        this.initializeFormToggles();
        this.initializeFormSubmissions();
    }

    initializeFormToggles() {
        document.querySelectorAll('[data-toggle="form"]').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const targetForm = trigger.getAttribute('data-target');
                this.state.activeForm =targetForm;
            });
        });
    }


    initializeFormSubmissions() {
        const formRegister = document.querySelector(this.config.forms.register);
        
        formRegister.onsubmit = (e) => this.handleRegistration(e);
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

    validateFormFields(fields) {
        return fields.every(field => {
            const element = document.querySelector(field.id);
            return element.value.trim() !== "";
        });
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

    showAlert(icon, title, text) {
        return Swal.fire({ icon, title, text });
    }

}

// Inicialización cuando el DOM está cargado
document.addEventListener('DOMContentLoaded', () => {
    window.teacherManagement = new teacherManagement();
});