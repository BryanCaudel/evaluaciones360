document.getElementById("btnCargarGraficas").addEventListener("click", () => {
    const usuarioID = document.getElementById("usuario").value;

    if (!usuarioID) {
        alert("Por favor, selecciona un usuario.");
        return;
    }

    // Cargar los datos del servidor
    fetch(`../src/controllers/ResultadosPorUsuarioController.php?usuario_id=${usuarioID}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            // Mostrar el contenedor de gráficas
            document.getElementById("graficasContainer").style.display = "block";

            // Configurar las etiquetas y valores
            const labels = data.map(d => d.NombreDimension);
            const valores = data.map(d => parseFloat(d.Promedio));

            // Crear el gráfico
            const ctx = document.getElementById("graficoDimensiones").getContext("2d");
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Puntuación Promedio",
                        data: valores,
                        backgroundColor: "rgba(75, 192, 192, 0.2)",
                        borderColor: "rgba(75, 192, 192, 1)",
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        })
        .catch(error => console.error("Error al cargar los datos:", error));
});
