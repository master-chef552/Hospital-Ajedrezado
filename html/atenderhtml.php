<?php
session_start();
if (empty($_SESSION['id_usuario'])) {
    header('Location: login.html');
    exit;
}

$id_paciente = $_POST['id_paciente'] ?? null;
$id_cita     = $_POST['id_cita'] ?? null;

if (!$id_paciente || !$id_cita) {
    die("Faltan datos necesarios para atender la cita.");
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Atender Cita</title>
  <link rel="icon" href="../imagenes/kaguyafondo.jpg" type="image/png">
  <link rel="stylesheet" href="../css/atender.css">
</head>
<body>

  <!-- Navbar -->
  <div class="navbar">
    <div class="hospital-name">Hospital Ajedrezado</div>
    <div class="profile-button">
      <img src="../imagenes/sesion.png" alt="Perfil">
    </div>
  </div>

  <!-- Bienvenida -->
  <div class="welcome">
    <h1>Aquí puede generar recetas para las citas o agregar observaciones</h1>
  </div>

  <!-- Contenido -->
  <div class="recetas">
    <h3>Diagnóstico</h3>
    <input type="text" id="diagnostico" placeholder="Ingrese el diagnóstico del paciente" required>

    <h3>Medicamento</h3>
    <div class="medicamento">
      <div class="ordenMedicamento">
        <h4>Nombre del Medicamento</h4>
        <input type="text" id="nombreMedicamento" placeholder="Ingrese el nombre del medicamento" required>
      </div>
      <div class="ordenMedicamento">
        <h4>Dosis</h4>
        <input type="text" id="dosisMedicamento" placeholder="Ingrese la dosis del medicamento" required>
      </div>
    </div>

    <h3>Observaciones</h3>
    <textarea id="observaciones" placeholder="Ingrese las observaciones del paciente" rows="4" required></textarea>

    <button id="btn-falto" class="btn-fin" data-idcita="<?php echo $id_cita; ?>">El paciente faltó a la cita</button>
    <button id="btn-finalizar" class="btn-fin" data-idcita="<?php echo $id_cita; ?>">Finalizar la cita</button>
  </div>



   <!-- JavaScript al final -->
  <script>
    
    // Elimina la entrada anterior del historial
    history.pushState(null, "", location.href);
    window.onpopstate = function () {
      history.pushState(null, "", location.href);
    };




//se registra la inasistencia del paciente
    document.getElementById('btn-falto').addEventListener('click', function () {
    const idCita = this.dataset.idcita;

    if (!idCita) {
      alert('No se puede registrar la falta porque falta el ID de la cita.');
      return;
    }

    fetch('../php/atender.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({
        action: 'registrarFalta',
        id_cita: idCita
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Falta registrada correctamente.');
        // Redirigir, actualizar o limpiar
      } else {
        alert('Error: ' + (data.error || 'No se pudo registrar la falta.'));
      }
    })
    .catch(error => {
      console.error('Error en fetch:', error);
      alert('Ocurrió un error al registrar la falta. ');
    });
  });






    document.getElementById('btn-finalizar').addEventListener('click', function () {
  const idCita = this.dataset.idcita;
  const diagnostico = document.getElementById('diagnostico').value.trim();
  const nombreMedicamento = document.getElementById('nombreMedicamento').value.trim();
  const dosis = document.getElementById('dosisMedicamento').value.trim();
  const observaciones = document.getElementById('observaciones').value.trim();

  if (!idCita || !diagnostico || !nombreMedicamento || !dosis || !observaciones) {
    alert('Por favor, llena todos los campos antes de finalizar la cita.');
    return;
  }

  fetch('../php/atender.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
      action: 'finalizarCita',
      id_usuario: <?php echo $_SESSION['id_usuario']; ?>, // Asegúrate de que el ID del usuario esté disponible
      id_paciente: <?php echo $id_paciente; ?>, // Asegúrate de que el ID del paciente esté disponible
      id_cita: idCita,
      diagnostico: diagnostico,
      nombre_medicamento: nombreMedicamento,
      dosis: dosis,
      observaciones: observaciones
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('Cita finalizada correctamente.');
      // Aquí puedes redirigir o actualizar la vista
    } else {
      alert('Error: ' + (data.error || 'No se pudo finalizar la cita.'));
    }
  })
  .catch(error => {
    console.error('Error en fetch:', error);
    alert('Ocurrió un error al finalizar la cita.');
  });
});






  </script>

</body>
</html>
