// === Mostrar/Ocultar formularios ===
const registerForm = document.getElementById("register-form");
const loginForm = document.getElementById("login-form");

document.getElementById("show-register").addEventListener("click", () => {
    loginForm.style.display = "none";
    registerForm.style.display = "flex";
    document.getElementById("register-error").textContent = "";
    document.getElementById("register-success").textContent = "";
});

document.getElementById("show-login").addEventListener("click", () => {
    registerForm.style.display = "none";
    loginForm.style.display = "flex";
    document.getElementById("login-error").textContent = "";
});

// === Validación de contraseña ===
function validatePassword(password) {
    const minLength = 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSymbol = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    return password.length >= minLength && hasUpperCase && hasNumber && hasSymbol;
}

// === REGISTRO ===
// Endpoint: register.php
// Método: POST
registerForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(registerForm);
    const id = formData.get("id");
    const email = formData.get("email");
    const password = formData.get("password");
    const role = formData.get("role");

    // Validación en el frontend
    if (!id || !email || !password || !role) {
        document.getElementById("register-error").textContent = "Por favor, completa todos los campos.";
        document.getElementById("register-success").textContent = "";
        return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        document.getElementById("register-error").textContent = "Formato de correo inválido.";
        document.getElementById("register-success").textContent = "";
        return;
    }
    if (!validatePassword(password)) {
        document.getElementById("register-error").textContent = "La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un símbolo.";
        document.getElementById("register-success").textContent = "";
        return;
    }

    try {
        document.getElementById("register-error").textContent = "";
        document.getElementById("register-success").textContent = "Registrando...";
        const response = await fetch("../Controller/register.php", { method: "POST", body: formData });

        if (!response.ok) {
            document.getElementById("register-error").textContent = `Error ${response.status}: ${response.statusText}`;
            document.getElementById("register-success").textContent = "";
            return;
        }

        const data = await response.json();
        if (data.success) {
            document.getElementById("register-success").textContent = "Usuario registrado correctamente.";
            document.getElementById("register-error").textContent = "";
            registerForm.reset();
        } else {
            document.getElementById("register-error").textContent = data.message || "Error en el registro.";
            document.getElementById("register-success").textContent = "";
        }
    } catch (err) {
        document.getElementById("register-error").textContent = "Error de conexión con el servidor.";
        document.getElementById("register-success").textContent = "";
    }
});

// === LOGIN ===
// Endpoint: login.php
// Método: POST
loginForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(loginForm);
    const id = formData.get("id");
    const password = formData.get("password");

    // Validación en el frontend con depuración
    console.log("ID:", id, "Password:", password); // Para depurar
    if (!id || !password) {
        document.getElementById("login-error").textContent = "Por favor, completa todos los campos.";
        return;
    }

    try {
        document.getElementById("login-error").textContent = "Iniciando sesión...";
        const response = await fetch("../Controller/login.php", { method: "POST", body: formData });

        const data = await response.json();
        
        if (response.status === 423) {
            document.getElementById("login-error").textContent = `Error ${response.status}: ${data.message}`;
            return;
        }

        if (!response.ok) {
            document.getElementById("login-error").textContent = `Error ${response.status}: ${data.message}`;
            return;
        }

        if (data.success) {
            if (data.allowed_roles?.includes(data.role) || data.role === "administrador") {
                window.location.href = "dashboard.html";
            } else {
                document.getElementById("login-error").textContent = "Login exitoso, pero no tienes permisos para acceder al dashboard.";
            }
        } else {
            document.getElementById("login-error").textContent = data.message || "Credenciales incorrectas.";
        }
    } catch (err) {
        document.getElementById("login-error").textContent = "Error de conexión con el servidor.";
    }
});
