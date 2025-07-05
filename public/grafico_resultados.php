<?php  
session_start();
require_once '../src/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['evaluacion_id'])) {
    $_SESSION['evaluacion_id'] = $_POST['evaluacion_id'];
} elseif (!isset($_SESSION['evaluacion_id'])) {
    echo "ID del evaluado no proporcionado.";
    exit;
}

$evaluacion_id = $_SESSION['evaluacion_id'];


$dimensiones = [];
$stmt_dim = $conexion->prepare("SELECT id, nombre FROM dimensiones");
$stmt_dim->execute();
$res_dim = $stmt_dim->get_result();
while ($row = $res_dim->fetch_assoc()) {
    $dimensiones[] = $row;
}

$data = [];

foreach ($dimensiones as $dim) {
    $dimension_id = $dim['id'];
    $nombre_dim = $dim['nombre'];

    $stmt_preg = $conexion->prepare("
        SELECT ep.id AS eval_pregunta_id, p.texto
        FROM evaluacion_preguntas ep
        INNER JOIN preguntas p ON ep.pregunta_id = p.id
        WHERE p.dimension_id = ? AND ep.evaluacion_id = ?
    ");
    $stmt_preg->bind_param("ii", $dimension_id, $evaluacion_id);
    $stmt_preg->execute();
    $res_preg = $stmt_preg->get_result();

    $preguntas = [];
    while ($preg = $res_preg->fetch_assoc()) {
        $auto = obtener_promedio($conexion, 'autoevaluaciones', $evaluacion_id, $preg['eval_pregunta_id']);
        $jefe = obtener_promedio_relacion($conexion, $evaluacion_id, $preg['eval_pregunta_id'], 'Jefe');
        $colega = obtener_promedio_relacion($conexion, $evaluacion_id, $preg['eval_pregunta_id'], 'Colega');
        $colaborador = obtener_promedio_relacion($conexion, $evaluacion_id, $preg['eval_pregunta_id'], 'Colaborador');

        $valores = array_filter([$auto, $jefe, $colega, $colaborador], fn($v) => $v > 0);
        $gap = (count($valores) >= 2) ? round(max($valores) - min($valores), 2) : 0;

        $preguntas[] = [
            'id' => $preg['eval_pregunta_id'],
            'texto' => $preg['texto'],
            'auto' => $auto,
            'Jefe' => $jefe,
            'Colega' => $colega,
            'Colaborador' => $colaborador,
            'gap' => $gap,
            'conteo' => [
                'auto' => contar_respuestas($conexion, 'autoevaluaciones', $evaluacion_id, $preg['eval_pregunta_id']),
                'Jefe' => contar_respuestas_relacion($conexion, $evaluacion_id, $preg['eval_pregunta_id'], 'Jefe'),
                'Colega' => contar_respuestas_relacion($conexion, $evaluacion_id, $preg['eval_pregunta_id'], 'Colega'),
                'Colaborador' => contar_respuestas_relacion($conexion, $evaluacion_id, $preg['eval_pregunta_id'], 'Colaborador')
            ]
        ];
    }

    if (!empty($preguntas)) {
        $data[] = [
            'nombre' => $nombre_dim,
            'preguntas' => $preguntas
        ];
    }
}

function obtener_promedio($conexion, $tabla, $evaluacion_id, $eval_pregunta_id) {
    $stmt = $conexion->prepare("SELECT AVG(valor) AS promedio FROM $tabla WHERE evaluacion_id = ? AND evaluacion_pregunta_id = ?");
    $stmt->bind_param("ii", $evaluacion_id, $eval_pregunta_id);
    $stmt->execute();
    return round($stmt->get_result()->fetch_assoc()['promedio'] ?? 0, 2);
}

function obtener_promedio_relacion($conexion, $evaluacion_id, $eval_pregunta_id, $relacion) {
    $stmt = $conexion->prepare("SELECT AVG(valor) AS promedio FROM respuestas WHERE evaluacion_id = ? AND evaluacion_pregunta_id = ? AND relacion = ?");
    $stmt->bind_param("iis", $evaluacion_id, $eval_pregunta_id, $relacion);
    $stmt->execute();
    return round($stmt->get_result()->fetch_assoc()['promedio'] ?? 0, 2);
}

function contar_respuestas($conexion, $tabla, $evaluacion_id, $eval_pregunta_id) {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM $tabla WHERE evaluacion_id = ? AND evaluacion_pregunta_id = ?");
    $stmt->bind_param("ii", $evaluacion_id, $eval_pregunta_id);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['total'];
}

function contar_respuestas_relacion($conexion, $evaluacion_id, $eval_pregunta_id, $relacion) {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM respuestas WHERE evaluacion_id = ? AND evaluacion_pregunta_id = ? AND relacion = ?");
    $stmt->bind_param("iis", $evaluacion_id, $eval_pregunta_id, $relacion);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['total'];
}

function promedio_dimension($dim_data, $campo) {
    $suma = 0;
    $total = 0;
    foreach ($dim_data['preguntas'] as $p) {
        if ($p[$campo] > 0) {
            $suma += $p[$campo];
            $total++;
        }
    }
    return $total ? round($suma / $total, 2) : 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Resultados por Dimensión</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f2f2f2; margin: 0; }
    h1 { text-align: center; margin-top: 20px; color: #2c3e50; }
    .opciones { text-align: center; margin-top: 20px; }
    .opciones label { margin: 0 15px; font-size: 16px; }
    .grafico-container {
      background: white; border-radius: 12px; padding: 20px;
      margin: 30px auto; width: 90%; max-width: 1000px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    canvas { width: 100% !important; background: #fff; }
    #descargar {
      display: block; margin: 30px auto; padding: 10px 20px;
      background: #007bff; color: white; border: none;
      border-radius: 6px; font-size: 16px; cursor: pointer;
    }
    table { border-collapse: collapse; width: 100%; margin-top: 10px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
    th { background-color: #1abc9c; color: white; }
    .barra { height: 18px; background-color: #e0e0e0; border-radius: 10px; margin: 5px 0; overflow: hidden; }
    .relleno { height: 100%; border-radius: 10px; line-height: 18px; color: white; padding-left: 8px; font-size: 13px; white-space: nowrap; }
    .auto { background-color: #2980b9; }
    .jefe { background-color: #c0392b; }
    .colega { background-color: #f39c12; }
    .colaborador { background-color: #27ae60; }
    .pregunta { margin-bottom: 25px; }
    .pregunta-texto { font-weight: bold; margin-bottom: 8px; }
    .area-titulo { font-size: 18px; margin-top: 30px; margin-bottom: 15px; color: #2c3e50; border-bottom: 2px solid #ccc; }
    .gap { font-style: italic; color: #555; font-size: 14px; margin-top: 4px; }
    
    .btn-descargar {
  margin-top: 20px;
  background: #007bff;
  color: white;
  border: none;
  padding: 10px 20px;
  font-size: 16px;
  border-radius: 6px;
  cursor: pointer;
}
.btn-descargar[disabled] {
  background-color: #999;
  cursor: not-allowed;
}
.spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid white;
  border-top: 2px solid transparent;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-left: 5px;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.contenedor-descarga {
  text-align: center;
  margin-top: 30px;
  margin-bottom: 50px;
}
.boton-volver-fijo {
  position: fixed;
  top: 20px;
  right: 20px;
  background: #1abc9c;
  color: white;
  padding: 10px 18px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: bold;
  z-index: 9999;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  transition: background 0.3s ease;
}
.boton-volver-fijo:hover {
  background: #16a085;
}

  </style>
</head>
<body>

<h1>Resultados por Dimensión</h1>

<div class="grafico-container" style="text-align:center;">
  <div style="margin-bottom: 10px;">
    <label><input type="checkbox" id="mostrarTabla" checked> Mostrar tabla resumen</label>
    <label><input type="checkbox" id="mostrarGraficas" checked> Mostrar gráficas</label>
    <label><input type="checkbox" id="mostrarPreguntas" checked> Mostrar preguntas individuales</label>
  </div>
  <button id="descargar-top" class="btn-descargar">Descargar PDF</button>
  <p id="mensaje-aviso" style="text-align:center; color:#c0392b; font-weight:bold; display:none; margin-top:10px;">
  Selecciona al menos una opción para mostrar y generar el PDF.
</p>


</div>

<a href="dashboard.php" class="boton-volver-fijo">← Volver</a>

<div id="tablaResumen" class="grafico-container">
  <h2>Resumen por Área</h2>

  <?php
    $resumen_areas = [];
    foreach ($data as $dim) {
        $auto = promedio_dimension($dim, 'auto');
        $jefe = promedio_dimension($dim, 'Jefe');
        $colega = promedio_dimension($dim, 'Colega');
        $colaborador = promedio_dimension($dim, 'Colaborador');
        $general = round(($auto + $jefe + $colega + $colaborador) / 4, 2);
        $resumen_areas[] = [
            'nombre' => $dim['nombre'],
            'auto' => $auto,
            'jefe' => $jefe,
            'colega' => $colega,
            'colaborador' => $colaborador,
            'general' => $general
        ];
    }

    // Ordenar para detectar mayor y menor promedio
    $ordenado = $resumen_areas;
    usort($ordenado, fn($a, $b) => $a['general'] <=> $b['general']);
    $area_baja = $ordenado[0];
    $area_alta = $ordenado[count($ordenado) - 1];
  ?>

<?php
  // Buscar sus índices reales en la tabla original
  $indice_baja = array_search($area_baja['nombre'], array_column($resumen_areas, 'nombre'));
  $indice_alta = array_search($area_alta['nombre'], array_column($resumen_areas, 'nombre'));
?>
<p style="text-align:center; font-weight:bold; margin-bottom:10px;">
  Mayor área de oportunidad: 
  <span style="color:#c0392b">#<?= $indice_baja + 1 ?> <?= htmlspecialchars($area_baja['nombre']) ?></span> |
  Área con mejor resultado: 
  <span style="color:#27ae60">#<?= $indice_alta + 1 ?> <?= htmlspecialchars($area_alta['nombre']) ?></span>
</p>


  <table>
    <thead>
      <tr>
        <th>Área</th>
        <th>Autoevaluación</th>
        <th>Jefe</th>
        <th>Colega</th>
        <th>Colaborador</th>
        <th>Promedio General</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($resumen_areas as $index => $area): ?>
      <tr>
        <td style="text-align:left;"><?= ($index + 1) . '. ' . htmlspecialchars($area['nombre']) ?></td>
        <td><?= $area['auto'] ?></td>
        <td><?= $area['jefe'] ?></td>
        <td><?= $area['colega'] ?></td>
        <td><?= $area['colaborador'] ?></td>
        <td><?= $area['general'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>


<div id="graficasSeccion">
  <?php foreach ($data as $index => $dim): ?>
    <div class="grafico-container grafica">
      <h2><?= htmlspecialchars($dim['nombre']) ?></h2>
      <canvas id="grafico<?= $index ?>" height="<?= count($dim['preguntas']) * 60 ?>"></canvas>
    </div>
  <?php endforeach; ?>
</div>

<div id="tablaPreguntas">
  <?php foreach ($data as $i => $dim): 
    $bloques = array_chunk($dim['preguntas'], 9);
    foreach ($bloques as $b => $bloque): ?>
    <div class="grafico-container pregunta-bloque">
      <div class="area-titulo"><?= ($i+1) . '. ' . htmlspecialchars($dim['nombre']) ?> (<?= ($b+1) ?>)</div>
      <?php foreach ($bloque as $j => $preg): 
        $numero = ($i+1) . '.' . (($b * 9) + $j + 1);
      ?>
      <div class="pregunta">
        <div class="pregunta-texto"><?= $numero ?>. <?= htmlspecialchars($preg['texto']) ?></div>
        <div class="barra"><div class="relleno auto" style="width: <?= ($preg['auto'] / 6) * 100 ?>%"><?= $preg['auto'] ?> Autoevaluación (<?= $preg['conteo']['auto'] ?>)</div></div>
        <div class="barra"><div class="relleno jefe" style="width: <?= ($preg['Jefe'] / 6) * 100 ?>%"><?= $preg['Jefe'] ?> Jefe (<?= $preg['conteo']['Jefe'] ?>)</div></div>
        <div class="barra"><div class="relleno colega" style="width: <?= ($preg['Colega'] / 6) * 100 ?>%"><?= $preg['Colega'] ?> Colega (<?= $preg['conteo']['Colega'] ?>)</div></div>
        <div class="barra"><div class="relleno colaborador" style="width: <?= ($preg['Colaborador'] / 6) * 100 ?>%"><?= $preg['Colaborador'] ?> Colaborador (<?= $preg['conteo']['Colaborador'] ?>)</div></div>
        <div class="gap">Gap: <?= $preg['gap'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; endforeach; ?>
</div>
<script>
const data = <?= json_encode($data) ?>;

data.forEach((dim, index) => {
  const etiquetas = dim.preguntas.map(p => {
    const palabras = p.texto.split(' ');
    const lineas = [];
    let linea = '';
    palabras.forEach(palabra => {
      if ((linea + palabra).length > 45) {
        lineas.push(linea.trim());
        linea = '';
      }
      linea += palabra + ' ';
    });
    if (linea.trim()) lineas.push(linea.trim());
    return lineas;
  });

  const auto = dim.preguntas.map(p => p.auto);
  const jefe = dim.preguntas.map(p => p.Jefe);
  const colega = dim.preguntas.map(p => p.Colega);
  const colaborador = dim.preguntas.map(p => p.Colaborador);
  const ctx = document.getElementById('grafico' + index).getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: etiquetas,
      datasets: [
        { label: 'Autoevaluación', data: auto, backgroundColor: 'rgba(33, 97, 140, 0.7)', borderColor: 'rgba(33, 97, 140, 1)', borderWidth: 1, barThickness: 15 },
        { label: 'Jefe', data: jefe, type: 'line', borderColor: '#c0392b', fill: false },
        { label: 'Colega', data: colega, type: 'line', borderColor: '#f39c12', fill: false },
        { label: 'Colaborador', data: colaborador, type: 'line', borderColor: '#27ae60', fill: false }
      ]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: { legend: { position: 'top' }},
      scales: {
        x: { beginAtZero: true, max: 6, ticks: { stepSize: 1 }},
        y: { ticks: { autoSkip: false, font: { size: 12 } }, offset: true }
      }
    }
  });
});

function iniciarGeneracion(btn) {
  btn.disabled = true;
  const originalText = btn.textContent;
  btn.innerHTML = 'Generando PDF <span class="spinner"></span>';

  generarPDF().then(() => {
    btn.disabled = false;
    btn.innerHTML = originalText;
  });
}

async function generarPDF() {
  const { jsPDF } = window.jspdf;
  const pdf = new jsPDF('p', 'mm', 'a4');
  let y = 10;
  const secciones = [];

  if (document.getElementById('mostrarTabla').checked) secciones.push(document.getElementById('tablaResumen'));
  if (document.getElementById('mostrarGraficas').checked) document.querySelectorAll(".grafico-container.grafica").forEach(g => secciones.push(g));
  if (document.getElementById('mostrarPreguntas').checked) document.querySelectorAll(".pregunta-bloque").forEach(p => secciones.push(p));

  for (let i = 0; i < secciones.length; i++) {
    await html2canvas(secciones[i], { scale: 1.2 }).then(canvasImage => {
      const imgData = canvasImage.toDataURL('image/jpeg', 0.9);
      const imgProps = pdf.getImageProperties(imgData);
      const pdfWidth = 180;
      const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

      if (y + pdfHeight > 280) {
        pdf.addPage();
        y = 10;
      }

      pdf.addImage(imgData, 'JPEG', 15, y, pdfWidth, pdfHeight);
      y += pdfHeight + 10;
    });
  }

  pdf.save("reporte_evaluacion.pdf");
}

document.getElementById("descargar-top").addEventListener("click", function() {
  iniciarGeneracion(this);
});

function actualizarVisibilidad() {
  const mostrarTabla = document.getElementById('mostrarTabla').checked;
  const mostrarGraficas = document.getElementById('mostrarGraficas').checked;
  const mostrarPreguntas = document.getElementById('mostrarPreguntas').checked;

  document.getElementById('tablaResumen').style.display = mostrarTabla ? 'block' : 'none';

  document.querySelectorAll('.grafico-container.grafica').forEach(grafica => {
    grafica.style.display = mostrarGraficas ? 'block' : 'none';
  });

  document.querySelectorAll('.pregunta-bloque').forEach(preg => {
    preg.style.display = mostrarPreguntas ? 'block' : 'none';
  });

  const haySeleccion = mostrarTabla || mostrarGraficas || mostrarPreguntas;
  const mensaje = document.getElementById('mensaje-aviso');
  const botones = document.querySelectorAll('.btn-descargar');

  // Mostrar mensaje si no hay selección, ocultar botones
  mensaje.style.display = haySeleccion ? 'none' : 'block';
  botones.forEach(btn => {
    btn.style.display = haySeleccion ? 'inline-block' : 'none';
  });
}



// Ejecutar al cargar
actualizarVisibilidad();

// Asignar eventos a los checkboxes
document.getElementById('mostrarTabla').addEventListener('change', actualizarVisibilidad);
document.getElementById('mostrarGraficas').addEventListener('change', actualizarVisibilidad);
document.getElementById('mostrarPreguntas').addEventListener('change', actualizarVisibilidad);

</script>

</body>
</html>
