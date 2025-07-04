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
  <title>Sistema de Recepcionista</title>
  <link rel="icon" href="../imagenes/kaguyafondo.jpg" type="image/png">
  <link rel="stylesheet" href="../css/mainRecepcionista.css">
</head>
<body>

  <!-- Navbar -->
    <div class="navbar">
      <a href="mainRecepcionistahtml.php" class="color">
        <div class="hospital-name">Hospital Ajedrezado</div>
      </a>
      <div class="profile-button">
        <a href="perfilRecepcionista.html">
          <img src="../imagenes/sesion.png" alt="Perfil">
        </a>
      </div>
    </div>

  <div class="welcome">
    <h1>Bienvenido al sistema de Recepcionista</h1>
  </div>

  <div class="creaPerfil">
    <h4>Cree un perfil</h4>

    <form action="../php/registroRecep.php" method="post">
      <!-- Primera fila -->
      <div class="fila">
        <div class="caja">
          <h5>Tipo de usuario</h5>
          <select id="tipoUsuario" name="tipoUsuario">
            <option value="paciente">Paciente</option>
            <option value="empleado">Empleado</option>
          </select>
        </div>
        <div class="caja">
          <h5>Nombre</h5>
          <input type="text" id="nombre" name="nombre" placeholder="Ingrese el nombre" required>
        </div>
        <div class="caja">
          <h5>Apellido Paterno</h5>
          <input type="text" id="apellidoPaterno" name="apellidoPaterno" placeholder="Ingrese el apellido paterno" required>
        </div>
        <div class="caja">
          <h5>Apellido Materno</h5>
          <input type="text" id="apellidoMaterno" name="apellidoMaterno" placeholder="Ingrese el apellido materno" required>
        </div>
        <div class="caja">
          <h5>Fecha de Nacimiento</h5>
          <input type="date" id="fechaDeNacimiento" name="fechaDeNacimiento" required>
        </div>
      </div>

      <!-- Segunda fila -->
      <div class="fila">
        <div class="caja">
          <h5>Teléfono</h5>
          <input type="tel" id="telefono" name="telefono" placeholder="Ingrese el teléfono" required>
        </div>
        <div class="caja">
          <h5>Nombre de Usuario</h5>
          <input type="text" id="nombreDeUsuario" name="nombreDeUsuario" placeholder="Ingrese el nombre de usuario" required>
        </div>
        <div class="caja">
          <h5>Correo Electrónico</h5>
          <input type="email" id="correo" name="correo" placeholder="Ingrese el correo" required>
        </div>
        <div class="caja">
          <h5>Contraseña</h5>
          <input type="password" id="contrasena" name="contrasena" placeholder="Ingrese la contraseña" required>
        </div>
        <div class="caja">
          <h5>Confirmar contraseña</h5>
          <input type="password" id="contrasena2" name="contrasena2" placeholder="Confirme la contraseña" required>
        </div>
      </div>

      <!-- Tercera fila: sólo si empleado -->
      <div class="fila">
        <div class="caja" id="cont-tipoEmpleado" style="display: none;">
          <h5>¿Qué tipo de empleado?</h5>
          <select id="tipoEmpleado" name="tipoEmpleado">
            <option value="">-- Seleccione --</option>
            <option value="Recepcionista">Recepcionista</option>
            <option value="Doctor">Doctor</option>
          </select>
        </div>

        <div class="caja" id="sueldo" style="display: none;">
          <h5>Sueldo:</h5>
          <input type="number" id="sueldo" name="sueldo" placeholder="Ingrese el sueldo del empleado">
        </div>

        <div class="caja" id="rfc" style="display: none;">
          <h5>RFC:</h5>
          <input type="text" id="rfc" name="rfc" placeholder="Ingrese el RFC del empleado">
        </div>
      </div>
        <div class="fila">  
          <!-- createPerfil.html (fragmento modificado) -->
          <div class="caja" id="cont-especialidad" style="display: none;">
              <h5>¿Qué especialidad?</h5>
              <select id="especialidad" name="especialidad">
                <option value="">-- Seleccione --</option>
                <option value="Cardiología">Cardiología</option>
                <option value="Dermatología">Dermatología</option>
                <option value="Ginecología">Ginecología</option>
                <option value="Medicina General">Medicina General</option>
                <option value="Nefrología">Nefrología</option>
                <option value="Nutriología">Nutriología</option>
                <option value="Oftalmología">Oftalmología</option>
                <option value="Oncología">Oncología</option>
                <option value="Ortopedia">Ortopedia</option>
                <option value="Pediatría">Pediatría</option>
              </select>
          </div>

          <div class="caja" id="cont-cedula" style="display: none;">
            <h5>Cedula del doctor</h5>
            <input type="text" id="cedulaDoctor" name="cedulaDoctor" placeholder="Ingrese la cédula del doctor">
          </div>

          <button type="submit" class="boton">Crear Perfil</button>

        </div>

        
      </div>
    </form>
  </div>

 <div class="funcionExtraP">
    <div class="funcionExtra">
      <h4>Agendar una cita</h4>
      <a href="agendar.html"><button class="boton">Agendar</button></a>
    </div>
    <div class="funcionExtra">
      <h4>Cancelar una cita</h4>
      <a href="cancelar.html"><button class="boton">Cancelar</button></a>
    </div>
    <div class="funcionExtra">
      <h4>Cobrar medicamentos</h4>
      <a href="cobrar.html"><button class="boton">Cobrar</button></a>
    </div>
    <div class="funcionExtra">
      <h4>Dar de baja un Doctor</h4>
      <a href="darBajaDoctorhtml.php"><button class="boton">Dar de baja</button></a>
    </div>
  </div>

  <script>
    
    const tipoUsuario  = document.getElementById('tipoUsuario');
    const contTipoEmp   = document.getElementById('cont-tipoEmpleado');
    const tipoEmpleado  = document.getElementById('tipoEmpleado');
    const sueldo        = document.getElementById('sueldo');
    const rfc          = document.getElementById('rfc');
    const cedulaDoctor = document.getElementById('cont-cedula');

    const contEsp = document.getElementById('cont-especialidad');

    // Mostrar/ocultar "¿Qué tipo de empleado?"
    tipoUsuario.addEventListener('change', () => {
      if (tipoUsuario.value === 'doctor') {
        contTipoEmp.style.display = 'flex';
      } else {
        contTipoEmp.style.display = 'none';
        // ocultar también especialidad en caso de cambio atrás
        contEsp.style.display = 'none';
        cedulaDoctor.style.display = 'none';
        tipoEmpleado.value = '';
      }
    });

    // Mostrar/ocultar "¿Qué especialidad?"
    tipoEmpleado.addEventListener('change', () => {
      if (tipoEmpleado.value === 'Doctor') {
        contEsp.style.display = 'flex';
        cedulaDoctor.style.display = 'flex';
      } else {
        contEsp.style.display = 'none';
        cedulaDoctor.style.display = 'none';
      }
    });


    tipoUsuario.addEventListener('change', () => {
      if (tipoUsuario.value === 'empleado') {
        contTipoEmp.style.display = 'flex';
        sueldo.style.display = 'flex';
        rfc.style.display = 'flex';
      } else {
        contTipoEmp.style.display = 'none';
        sueldo.style.display = 'none';
        rfc.style.display = 'none';
        tipoEmpleado.value = '';
      }
    });
  </script>
</body>
</html>
