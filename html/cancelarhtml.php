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
<!-- Inicio de cancelar.html -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Perfil del doctor</title>
  <link rel="icon" href="../imagenes/hospital-icon.png" type="image/png">
  <link rel="stylesheet" href="../css/agendar.css">
</head>
<body>
  <!-- Barra de navegación -->
  <div class="navbar">
      <div class="hospital-name">Hospital Ajedrezado</div>
    <div class="profile-button">
        <img src="../imagenes/sesion.png" alt="Perfil">
    </div>
  </div>

 
</body>
</html>

<!-- fin de cancelar.html -->