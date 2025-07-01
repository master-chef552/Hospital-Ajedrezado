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
  <link rel="icon" href="../imagenes/kaguyafondo.jpg" type="image/png">
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

      <div class="botones">
            <p>Citas</p>
             <button type="submit" id="btn-atendidas" class="disenio">Atendidas</button>
             <button type="submit" id="btn-pendientes" class="disenio">Pendientes</button>
          </div>

      <div class="agenda-citasDatos">
        <div class="agenda-citas" id="agenda-citas">
          <!-- Aquí se mostrarán los id de las citas -->
          
        </div>

        
        <div class="agenda-datos" id ="agenda-datos">
          <!-- Aqui se mostraran los datos de las citas -->
        </div>
      </div>

      <div class="agenda-footer" id="agenda-footer">
        <!-- aqui van los botones para atender o cancelar una cita --> 
      </div>
    </div> <!-- fin del div .agenda -->



    <!-- Historial del paciente -->
    <div class="historialPaciente" id="historialPaciente">
      <h3>Historial del paciente</h3>
      <div class="infoPaciente">
        <!-- Historial medico del paciente -->
      </div>
      
    </div>



    <script>

      let todasLasCitas = [];


document.addEventListener("DOMContentLoaded", () => {
  fetch("../php/mainDoctor.php")
    .then(response => {
      if (!response.ok) {
        return response.text().then(text => {
          throw new Error(`Error HTTP ${response.status}: ${text}`);
        });
      }
      return response.json();
    })
    .then(data => {
      if (data.error) {
        alert("Error: " + data.error);
        return;
      }
      todasLasCitas = data; // Guardamos todas las citas
    })
    .catch(err => {
      console.error("Error al cargar citas:", err);
    });
});



function mostrarCitasAtendidas(data){
  const citasDiv = document.getElementById("agenda-citas");
  const datosDiv = document.getElementById("agenda-datos");
  const botones = document.getElementById("agenda-footer");

  datosDiv.innerHTML = ""; // Limpiar el contenido previo
  

  let botonesAC = `<p>Citas atendidas</p>`;

  if (data.length === 0) {
    citasDiv.innerHTML = "<p>No hay citas atendidas.</p>";
    return;
  }

  let citasHTML = `<table class="citas-table">
    <thead><tr>
      <th>Citas atendidas</th>
    </tr></thead><tbody>`;

  data.forEach(cita => {
    const idCita = cita.id_cita;

    if (cita.id_estado_cita == 6) {
  citasHTML += `<tr>
    <td data-label="ID Cita" style="text-align: center;">
      <button type="button" class="disenio btn-cita" data-idcita="${idCita}">Informacion de la cita:${idCita}</button>
    </td>
  </tr>`;
}

  });

  citasHTML += `</tbody></table>`;
  citasDiv.innerHTML = citasHTML;
  botones.innerHTML = botonesAC;
// Agregar el evento a los botones de las citas atendidas
 document.querySelectorAll(".btn-cita").forEach(btn => {
  btn.addEventListener("click", () => {
    const id = btn.dataset.idcita;  // toma el valor del atributo data-idcita
    const citaSeleccionada = data.find(c => c.id_cita == id);
    if (citaSeleccionada) {
      mostrarDatosCita(citaSeleccionada);  // pásalo como arreglo si tu función lo espera así
    }
  });
});


}


function mostrarCitasPendientes(data) {
  const citasDiv = document.getElementById("agenda-citas");
  const datosDiv = document.getElementById("agenda-datos");
  const botones = document.getElementById("agenda-footer");

  datosDiv.innerHTML = ""; // Limpiar contenido previo
  botones.innerHTML = "";  // Limpiar botones previos

  if (data.length === 0) {
    citasDiv.innerHTML = "<p>No hay citas próximas.</p>";
    return;
  }

  let citasHTML = `<table class="citas-table">
    <thead><tr><th>Próximas citas</th></tr></thead><tbody>`;

  data.forEach(cita => {
    if (cita.id_estado_cita == 2) {
      citasHTML += `<tr>
        <td style="text-align: center;">
          <button type="button" class="disenio btn-cita" 
            data-idcita="${cita.id_cita}" 
            data-idpaciente="${cita.id_paciente}" 
            data-idusuario="${cita.id_usuario}">
            Información de la cita: ${cita.id_cita}
          </button>
        </td>
      </tr>`;
    }
  });

  citasHTML += `</tbody></table>`;
  citasDiv.innerHTML = citasHTML;

  document.querySelectorAll(".btn-cita").forEach(btn => {
    btn.addEventListener("click", () => {
      const idCita = btn.dataset.idcita;
      const idPaciente = btn.dataset.idpaciente;
      const idUsuario = btn.dataset.idusuario;

      mostrarDatosCita(idCita);

      // Generamos los botones dinámicamente
      botones.innerHTML = `
  <form method="POST" action="../php/mainDoctor.php" style="display:inline;">
    <input type="hidden" name="action" value="cancelarCita">
    <input type="hidden" name="id_cita" value="${idCita}">
    <button type="submit" class="disenio">Solicitar cancelación para ${idCita}</button>
  </form>

  <form method="POST" action="atenderhtml.php" style="display:inline;">
  <input type="hidden" name="id_paciente" value="${idPaciente}">
  <input type="hidden" name="id_cita" value="${idCita}">
  <button type="submit" class="disenio1">Atender cita ${idCita}</button>
</form>

`;

    });
  });
}


//funcion para mostrar los datos de la cita y el historial del paciente
function mostrarDatosCita(id_cita) {
  const citasDiv = document.getElementById("agenda-datos");

  fetch(`../php/mainDoctor.php?action=getInfoCita&id_cita=${id_cita}`)

    .then(response => {
      if (!response.ok) {
        throw new Error("Error al obtener la cita");
      }
      return response.json();
    })
    .then(cita => {
      if (cita.error) {
        citasDiv.innerHTML = `<p>${cita.error}</p>`;
        return;
      }

      let datosHTML = `
<table class="datos-cita-table">
  <thead>
    <tr><th colspan="2">Datos de la cita</th></tr>
  </thead>
  <tbody>
    <tr><td><strong>Paciente:</strong></td><td>${cita.nombrePaciente}</td></tr>
    <tr><td><strong>Cédula del doctor:</strong></td><td>${cita.cedula}</td></tr>
    <tr><td><strong>Folio:</strong></td><td>${cita.folio}</td></tr>
    <tr><td><strong>Fecha:</strong></td><td>${cita.fecha_cita}</td></tr>
    <tr><td><strong>Consultorio:</strong></td><td>${cita.numero_consultorio}</td></tr>
  </tbody>
</table>`;



      citasDiv.innerHTML = datosHTML;

       mostrarHistorial(cita.id_paciente); // Llamar a la función para mostrar el historial del paciente
    })
    .catch(error => {
      citasDiv.innerHTML = `<p>Error al cargar los datos</p>`;
      console.error(error);
    });
   
}


//mostrar historial del paciente
function mostrarHistorial(id_paciente){
  const DivHistorial = document.getElementById("historialPaciente");

  fetch(`../php/mainDoctor.php?action=getHistorialPaciente&id_paciente=${id_paciente}`)
    .then(response => {
      if (!response.ok) {
        throw new Error("Error al obtener el historial");
      }
      return response.json();
    })
    .then(historial => {
      if (historial.error) {
        DivHistorial.innerHTML = `<p>${historial.error}</p>`;
        return;
      }

      let historialHTML = `
<table class="datos-cita-table">
  <thead>
    <tr><th colspan="2">Historial del Paciente</th></tr>
  </thead>
  <tbody>`;

historial.forEach(item => {
  historialHTML += `
    <tr>
      <td><strong>Fecha:</strong></td>
      <td>${item.fecha_movimiento}</td>
    </tr>
    <tr>
      <td><strong>Paciente:</strong></td>
      <td>${item.nombre_paciente}</td>
    </tr>
    <tr>
      <td><strong>Doctor:</strong></td>
      <td>${item.nombre_doctor}</td>
    </tr>
    <tr>
      <td><strong>Especialidad:</strong></td>
      <td>${item.especialidad}</td>
    </tr>
    <tr>
      <td><strong>Consultorio:</strong></td>
      <td>${item.consultorio}</td>
    </tr>
    <tr>
      <td><strong>Diagnóstico:</strong></td>
      <td>${item.diagnostico}</td>
    </tr>
    <tr><td colspan="2"><hr></td></tr>
  `;
});

historialHTML += `</tbody>
</table>`;


      DivHistorial.innerHTML = historialHTML;
    })
    .catch(error => {
      DivHistorial.innerHTML = `<p>Error: el paciente no tiene historial</p>`;
      console.error(error);
    });
}






document.getElementById("btn-atendidas").addEventListener("click", () => {
  mostrarCitasAtendidas(todasLasCitas);
});

document.getElementById("btn-pendientes").addEventListener("click", () => {
  mostrarCitasPendientes(todasLasCitas);
});




</script>

    
</body>
</html>
