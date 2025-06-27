<?php
session_start();
if (empty($_SESSION['id_usuario'])) {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hospital Ajedrezado</title>
  <link rel="icon" href="../imagenes/hospital-icon.png" type="image/png">
  <link rel="stylesheet" href="../css/mainDoctor.css">
</head>
<body>
  

    <!-- Navbar -->
    <div class="navbar">
      <a href="mainDoctorhtml.php" class="color">
        <div class="hospital-name">Hospital Ajedrezado</div>
      </a>
      <div class="profile-button">
        <a href="perfilDoc.html">
          <img src="../imagenes/sesion.png" alt="Perfil">
        </a>
      </div>
    </div>

    <!-- Bienvenida -->
    <div class="welcome">
      <h1>Bienvenido</h1>
    </div>

    <!-- Agenda -->
    <div class="agenda">
      <h3>Agenda</h3>

      <div class="agenda-citasDatos">
        <div class="agenda-citas">
          <p>Citas</p>
        </div>

        <div class="agenda-datos">
          <p>Datos</p>
        </div>
      </div>

      <div class="agenda-footer">
        <button class="disenio" onclick="alert('Se a enviado la solicitud de cancelacion')">Solicitar cancelacion</button>                        <!--luego cambiar este boton-->
        <a href="atender.html"><button class="disenio">Atender cita</button> </a>          <!--luego cambiar este boton-->
      </div>
    </div> <!-- fin del div .agenda -->



    <!-- Historial del paciente -->
    <div class="historialPaciente">
      <h3>Historial del paciente</h3>
      <div class="infoPaciente">
        <!-- Información del paciente -->
      </div>
      
    </div>



    <script>
document.addEventListener("DOMContentLoaded", () => {
  fetch("../php/mainDoctor.php")
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        alert(data.error);
        return;
      }

      const citasDiv = document.querySelector(".agenda-citas");
      const datosDiv = document.querySelector(".agenda-datos");


      if (data.length === 0) {
        citasDiv.innerHTML += "<p>No hay citas próximas.</p>";
        return;
      }

      data.forEach(cita => {
        const fecha = new Date(cita.fecha_cita.date);
        const fechaStr = fecha.toLocaleDateString("es-MX");
        const horaStr = fecha.toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit" });

        citasDiv.innerHTML += `<p> ${cita.consultaID}</p>`;
        datosDiv.innerHTML += `<p>${fechaStr} - ${horaStr} ${cita.nombre_paciente} ${cita.apellido_paciente}</p>`;
      });
    })
    .catch(err => {
      console.error("Error al cargar citas:", err);
    });
});
</script>

    
</body>
</html>
