
Instalación de una ambiente de desarrollo.

Instalación en el ambiente de Pruebas
1. Crear una carpeta skyline en el servidor.
2. El dominio debe de estar apuntando a la carpeta skyline/public.
3. Generar una base de datos, se requiere
    nombre de la base de datos:
    usuario:
    contraseña:
4. Crear la cuenta de correo electronico.
5. Copiar el sitio completo, o ejecutar en el servidor composer y bower
6. Dentro de la carpeta
    skyline/app
7. Crear el archivo config_override.php con la configuración del ambiente.
  Usuario de base de datos
  Usuario de correo

Reinstalación.
1. Regenerar la base de datos con perdida de datos.
1.1 Para regenerar desde remoto se debe de conectar al servidor actualizando el archivo local config_override.php
1.2 Ejecutar los scripts de
      php migration.php drop
      php migration.php create
      php migration.php inicialize
2. Respaldar el archivo config_override.php que se encuentra en el raiz de la aplicación.
3. Copiar los componentes al servidor.
4. Restaurar el archivo config_override.php.

Instalación en el ambiente de Producción.
