<?php
session_start();
if (empty($_SESSION['id_usuario'])) {
    header('Location: login.html');
    exit;
}
?>
<!-- Inicio de cobrar.html -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Perfil del doctor</title>
  <link rel="icon" href="../imagenes/kaguyafondo.jpg" type="image/png">
  <link rel="stylesheet" href="../css/darBajaDoc.css">
</head>
<body>
  <!-- Barra de navegación -->
  <div class="navbar">
      <div class="hospital-name">Hospital Ajedrezado</div>
    <div class="profile-button">
        <img src="../imagenes/sesion.png" alt="Perfil">
    </div>
  </div>



  <div class="contenedor">
  <div class="baja-doctor-box">
    <input type="text" id="cedulaDoctor" placeholder="Ingresar cédula" class="input-cedula" required>
    <button id="buscarDoctor" class="btn-buscar">Buscar Doctor</button>

    <div id="infoDoctor" class="info-doctor">
      <!-- Aquí se mostrará la información del doctor -->
    </div>

    <button class="btn-baja">Dar de baja doctor</button>
  </div>
</div>



 <!-- Script de búsqueda -->
  <script>
  let doctorData = null;

  document.getElementById('buscarDoctor').addEventListener('click', () => {
    const cedula = document.getElementById('cedulaDoctor').value.trim();
    const infoDiv = document.getElementById('infoDoctor');
    infoDiv.textContent = 'Buscando…';

    if (!cedula) {
      infoDiv.innerHTML = '<p>Por favor ingresa una cédula.</p>';
      return;
    }

    fetch('../php/darBaja.php?action=buscar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ cedula })
    })
    .then(res => {
      if (!res.ok) throw new Error('Error en la petición');
      return res.json();
    })
    .then(data => {
      if (data.error) {
        infoDiv.innerHTML = `<p style="color:red;">${data.error}</p>`;
        doctorData = null;
      } else {
        doctorData = data;
        infoDiv.innerHTML = `
          <p><strong>Cédula:</strong> ${data.cedula}</p>
          <p><strong>Nombre:</strong> ${data.nombre}</p>
          <p><strong>Teléfono:</strong> ${data.telefono}</p>
          <p><strong>Fecha de nacimiento:</strong> ${data.fecha_nac}</p>
          <p><strong>Citas pendientes:</strong> ${data.pendientes === 'si' ? 'Sí' : 'No'}</p>
        `;
      }
    })
    .catch(err => {
      console.error(err);
      infoDiv.textContent = 'Ocurrió un error al buscar.';
      doctorData = null;
    });
  });

  document.querySelector('.btn-baja').addEventListener('click', () => {
    const infoDiv = document.getElementById('infoDoctor');
    if (!doctorData) {
      alert('Primero debes buscar un doctor.');
      return;
    }
    if (doctorData.pendientes === 'si') {
      alert('No puedes dar de baja: aún tiene citas pendientes.');
      return;
    }

    // Si no hay pendientes, procedemos a dar de baja
    fetch('../php/darBaja.php?action=darBaja', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ cedula: doctorData.cedula })
    })
    .then(res => {
      if (!res.ok) throw new Error('Error en la petición de baja');
      return res.json();
    })
    .then(resp => {
      if (resp.error) {
        infoDiv.innerHTML = `<p style="color:red;">${resp.error}</p>`;
      } else {
        infoDiv.innerHTML = `<p style="color:green;">${resp.message || 'Doctor dado de baja exitosamente.'}</p>`;
        doctorData = null;
      }
    })
    .catch(err => {
      console.error(err);
      infoDiv.textContent = 'Ocurrió un error al dar de baja.';
    });
  });
</script>



 
</body>
</html>

<!-- fin de cobrar.html -->