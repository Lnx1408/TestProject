document.addEventListener('DOMContentLoaded', function () {
    initFormToggles();
    initFormSubmission();
});

// Inicializar alternancia entre formularios
function initFormToggles() {
    const forms = document.querySelectorAll('.form-section');
    const loginBox = document.querySelector('.login-box');

    if (forms.length > 0) {
        loginBox.style.minHeight = '15vh';
        loginBox.style.maxHeight = '90vh';
    }

    document.querySelectorAll('[data-toggle="form"]').forEach(trigger => {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            const targetForm = this.getAttribute('data-target');
            switchForm(targetForm);
        });
    });
}

function switchForm(targetFormId) {
    const forms = document.querySelectorAll('.form-section');
    const loginBox = document.querySelector('.login-box');
    // Remover clase activa de todos los formularios
    forms.forEach(form => form.classList.remove('active'));
    // Añadir clase activa al formulario objetivo
    const targetForm = document.querySelector(`[data-form="${targetFormId}"]`);
    if (targetForm) {
        targetForm.classList.add('active');
        // Ajustar el min-height de manera dinámica
        const targetHeight = targetForm.scrollHeight;
        loginBox.style.minHeight = `${targetHeight}px`;
        loginBox.style.maxHeight = `${targetHeight}px`;
    }
}

function initFormSubmission() {
    const formRegister = document.querySelector("#formRegister");
    const formLogin = document.querySelector("#formLogin");

    if (formRegister) {
        formRegister.onsubmit = function (e) {
            e.preventDefault();
            const fieldsToValidate = [
                { id: '#txtUserRegister', name: 'Usuario' },
                { id: '#txtFirstNameRegister', name: 'Nombres' },
                { id: '#txtLastNameRegister', name: 'Apellidos' },
                { id: '#txtEmailRegister', name: 'Email' },
                { id: '#txtPasswordRegister', name: 'Contraseña' }
            ];

            if (!validateFormValues(fieldsToValidate)) {
                showAlert("error", "Oops...", "Verifique que todos los datos estén completos.");
                return;
            }
            Swal.fire({
                title: "Registrar Usuario",
                text: "¿Quiere proceder con el registro?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Continuar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    sendRequest('/Login/registerUser', new FormData(formRegister), handleResponse);
                }
            });
        };
    }
    if (formLogin) {
        formLogin.onsubmit = function (e) {
            e.preventDefault();
            const strEmail = document.querySelector('#txtEmail').value.trim();
            const strPass = document.querySelector('#txtPassword').value.trim();
            if (!validateForm(strEmail, strPass)) {
                showAlert("error", "Oops...", "Verifique que todos los datos estén completos.");
                return;
            }
            sendRequest('/Login/loginUser', new FormData(formLogin), handleResponseLogin);
        };
    }
}

// Validar los campos del formulario
function validateFormValues(fields) {
    return fields.every(field => {
        const element = document.querySelector(field.id);
        const value = element.value.trim();
        return value !== "";
    });
}

function validateForm(email, password) {
    return email !== "" && password !== "";
}

// Mostrar alertas
function showAlert(icon, title, text) {
    Swal.fire({ icon, title, text });
}

// Función para desencriptar la respuesta
function decryptResponse(encryptedDataWithIv) {
    try {
        var Sha256 = CryptoJS.SHA256;
        var Utf8 = CryptoJS.enc.Utf8;
        var Base64 = CryptoJS.enc.Base64;
        var AES = CryptoJS.AES;
        var secret_key = "TheQuickBrownFoxWasJumping";
        var key = Sha256(secret_key).toString(CryptoJS.enc.Hex).substr(0, 32);
        var encryptedDataWithIvBytes = Base64.parse(encryptedDataWithIv);

        var iv = CryptoJS.lib.WordArray.create(encryptedDataWithIvBytes.words.slice(0, 4));
        var encryptedData = CryptoJS.lib.WordArray.create(encryptedDataWithIvBytes.words.slice(4));

        // Desencriptar
        var decrypted = AES.decrypt(
            { ciphertext: encryptedData },
            Utf8.parse(key),
            { iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }
        );
        return decrypted.toString(Utf8);

    } catch (error) {
        console.error('Error al desencriptar:', error);
        return null;
    }
}

//FUNCION PARA ENCRIPTAR EL REQUEST
function encryptData(data) {
    var Sha256 = CryptoJS.SHA256;
    var Utf8 = CryptoJS.enc.Utf8;
    var Hex = CryptoJS.enc.Hex;
    var Base64 = CryptoJS.enc.Base64;
    var AES = CryptoJS.AES;
    var secret_key = "TheQuickBrownFoxWasJumping";
    var key = Sha256(secret_key).toString(Hex).substr(0, 32);
    var iv = CryptoJS.lib.WordArray.random(16);
    var encryptedData = AES.encrypt(Utf8.parse(data), Utf8.parse(key), {
        iv: iv,
        mode: CryptoJS.mode.CBC,
        padding: CryptoJS.pad.Pkcs7
    });

    var encryptedDataWithIv = Base64.stringify(iv.concat(encryptedData.ciphertext));
    return encryptedDataWithIv;
}


// Enviar solicitud AJAX
function sendRequest(url, formData, callback) {
    // Convertir FormData a un objeto JSON
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });
    const jsonData = JSON.stringify(data);
    const encryptedData = encryptData(jsonData);

    const request = new XMLHttpRequest();
    request.open("POST", base_url + url, true);
    request.setRequestHeader("Content-Type", "application/json;charset=UTF-8");

    request.onreadystatechange = async function () {
        if (request.readyState === 4) {
            if (request.status === 200) {
                try {
                    const decryptedResponse = await decryptResponse(request.responseText);
                    if (decryptedResponse) {
                        callback(decryptedResponse);
                    } else {
                        showAlert("error", "Error", "No se pudo desencriptar la respuesta");
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert("error", "Error", "Error al procesar la respuesta");
                }
            } else {
                showAlert("error", "Atención", "Error en el proceso.");
            }
        }
    };
    request.send(JSON.stringify({ encryptedData: encryptedData }));
}

// Manejar la respuesta de la solicitud
function handleResponse(response) {
    try {
        const parsedResponse = JSON.parse(response);
        if (parsedResponse && parsedResponse.status) {
            Swal.fire({
                title: "Registro exitoso",
                text: "Ha sido registrado con éxito",
                icon: "success"
            }).then(() => {
                switchForm('login');
            });
            // Aquí puedes agregar lógica adicional después del registro exitoso, como redirigir a otra página o resetear el formulario
        } else {
            showAlert("error", "Error", parsedResponse.msg || "Ocurrió un problema.");
        }
    } catch (error) {
        showAlert("error", "Error", "No se pudo procesar la respuesta del servidor.");
    }
}

function handleResponseLogin(response) {
    try {
        const parsedResponse = JSON.parse(response);
        if (parsedResponse && parsedResponse.status) {
            //Redirigir a mi pagina de inicio 
            window.location = base_url+'/dashboard';
        } else {
            showAlert("error", "Error", parsedResponse.msg || "Ocurrió un problema.");
        }
    } catch (error) {
        showAlert("error", "Error", "No se pudo procesar la respuesta del servidor.");
    }
}
