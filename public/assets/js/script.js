// /public/assets/js/script.js
document.querySelector("form").addEventListener("submit", function(event) {
    const usuario = document.querySelector('input[name="usuario"]');
    const contraseña = document.querySelector('input[name="contraseña"]');

    if (!usuario.value || !contraseña.value) {
        event.preventDefault();  // Detener el envío del formulario
        alert("Por favor, completa todos los campos.");
    }
});

