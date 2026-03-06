<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crear Cuenta | Sistema Odontológico</title>
<link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="login-container">
    
    <div class="logo">🦷</div>
    <h2>Crear Cuenta</h2>

    <form id="registerForm">

        <div class="form-group">
            <label>Nombre completo</label>
            <input type="text" id="nombre" required>
        </div>

        <div class="form-group">
            <label>Correo</label>
            <input type="email" id="email" required>
        </div>

        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" id="password" required>
        </div>

        <div class="form-group">
        <label>Confirmar contraseña</label>
        <input type="password" id="confirmar" required>
        </div>

        <button type="submit" class="btn">Registrar</button>

        <div class="extra">
            <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
        </div>

        <p id="mensaje" style="margin-top:10px;"></p>

    </form>

</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const nombre = document.getElementById('nombre').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const mensaje = document.getElementById('mensaje');

    try {
        const res = await fetch('../api/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, email, password })
        });

        const data = await res.json();

        if (!res.ok) {
            mensaje.style.color = "red";
            mensaje.textContent = data.error || "Error al registrar";
            return;
        }

        mensaje.style.color = "green";
        mensaje.textContent = "Usuario creado correctamente";

        setTimeout(() => {
            window.location.href = "login.php";
        }, 1500);

    } catch (error) {
        mensaje.style.color = "red";
        mensaje.textContent = "Error de conexión con el servidor";
    }
});
</script>

</body>
</html>