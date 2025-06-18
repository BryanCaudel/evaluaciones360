// Selecciona la barra lateral, el botón y el contenido principal
const sidebar = document.getElementById('sidebar');
const toggleButton = document.getElementById('toggleSidebar');
const mainContent = document.querySelector('.main-content');

// Añade el evento al botón para ocultar/mostrar la barra
toggleButton.addEventListener('click', () => {
    sidebar.classList.toggle('hidden'); // Oculta/muestra la barra lateral
    mainContent.classList.toggle('shifted'); // Ajusta el margen del contenido principal
});
