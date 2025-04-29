class PasswordResetModule {
    constructor() {
        this.config = {
            endpoints: {
                changePassword: `${base_url}/Login/changePassword`
            },
            forms: {
                resetPassword: '#formResetPassword'
            }
        };
        
        this.init();
    }

    init() {
        this.initializeFormSubmission();
        this.validateToken();
    }

    initializeFormSubmission() {
        const resetForm = document.querySelector(this.config.forms.resetPassword);
        
        if (resetForm) {
            resetForm.addEventListener('submit', (e) => this.handleResetPassword(e));
        }
    }

    validateToken() {
        // Verificar si hay un token en la URL
        const token = document.getElementById('resetToken').value;
        
        if (!token) {
            this.showAlert(
                'error', 
                'Token no válido', 
                'El enlace de recuperación es inválido o ha expirado. Por favor, solicita un nuevo enlace.',
                () => {
                    window.location.href = `${base_url}/login`;
                }
            );
        }
    }

    async handleResetPassword(e) {
        e.preventDefault();
        
        const token = document.getElementById('resetToken').value;
        const newPassword = document.getElementById('txtNewPassword').value;
        const confirmPassword = document.getElementById('txtConfirmPassword').value;
        
        // Validaciones básicas
        if (!newPassword || !confirmPassword) {
            this.showAlert('error', 'Error', 'Por favor completa todos los campos');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            this.showAlert('error', 'Error', 'Las contraseñas no coinciden');
            return;
        }
        
        if (newPassword.length < 6) {
            this.showAlert('error', 'Error', 'La contraseña debe tener al menos 6 caracteres');
            return;
        }
        
        try {
            // Mostrar loading
            Swal.fire({
                title: 'Procesando',
                text: 'Estamos actualizando tu contraseña...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            const response = await fetch(this.config.endpoints.changePassword, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt({
                        token: token,
                        password: newPassword
                    })
                })
            });
            
            const result = await response.json();
            const decryptedResult = CryptoModule.decrypt(result.data);
            
            if (decryptedResult.status) {
                this.showAlert(
                    'success', 
                    '¡Contraseña actualizada!', 
                    decryptedResult.msg,
                    () => {
                        window.location.href = `${base_url}/login`;
                    }
                );
            } else {
                this.showAlert('error', 'Error', decryptedResult.msg || 'Ha ocurrido un error al actualizar la contraseña');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Ha ocurrido un error en la solicitud');
        }
    }

    showAlert(icon, title, text, callback = null) {
        Swal.fire({
            icon: icon,
            title: title,
            text: text,
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Aceptar'
        }).then(() => {
            if (callback && typeof callback === 'function') {
                callback();
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.passwordResetModule = new PasswordResetModule();
});