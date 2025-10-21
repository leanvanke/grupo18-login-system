# Sistema de Login - Grupo 18 (UNSO)

Aplicación desarrollada para el Trabajo Práctico de **Programación de Sistemas** (Tecnicatura en Ciberseguridad, UNSO).  
El objetivo es demostrar un flujo completo de autenticación con registro de usuarios, administración y persistencia en base de datos.

---

## Entregables solicitados por la cátedra
- Prototipo de pantallas (`mockup/`).  
- Código fuente completo (PHP, HTML, CSS, JS).  
- Script SQL con la estructura y datos de la base (`src/Model/db.sql`).  
- Archivo README con instrucciones de uso (este documento).  
- Video en formato MP4 con el _elevator pitch_ del producto seleccionado (`video/Video Elevator Pitch.mp4`).  

---

## Funcionalidades principales
- Registro de usuarios con validaciones de integridad y contraseña robusta.  
- Inicio de sesión con control de bloqueos, intentos fallidos y sesiones PHP reforzadas.  
- Perfiles diferenciados de **usuario** y **administrador**.  
- Panel administrativo para visualizar usuarios, consultar logs y ejecutar acciones (bloquear, desbloquear, eliminar).  
- Registro de eventos de seguridad (logs) persistidos en la base.  
- Frontend responsivo sencillo que consume los endpoints PHP mediante `fetch`.

---

## Requisitos previos
- Tener instalado **XAMPP** con Apache y MySQL activos.  
- PHP incluido en XAMPP (no requiere instalación adicional).  
- Navegador (Chrome, Edge, Firefox, etc.) para acceder a la interfaz.  

---

## Configuración y puesta en marcha
1. **Obtener el código**
   - Usar la carpeta entregada junto con el TP (archivo `.zip`).
   - Descomprimirla y abrirla dentro de la carpeta `htdocs` de XAMPP, por ejemplo:  
     ```
     C:\xampp\htdocs\grupo18-login-system
     ```

2. **Iniciar XAMPP**
   - Abrir el panel de control de XAMPP.
   - Encender los módulos **Apache** y **MySQL**.

3. **Crear la base de datos**
   - Entrar a phpMyAdmin desde [http://localhost/phpmyadmin](http://localhost/phpmyadmin).  
   - Crear una nueva base (por ejemplo `db`).
   - Importar el script `src/Model/db.sql` (se encuentra dentro del proyecto).  
   - Este script crea las tablas `users` y `logs` e inserta usuarios de ejemplo.

4. **Configurar la conexión**
   - Editar el archivo `src/Model/conexion.php` con los datos de conexión correctos (nombre de base, usuario, contraseña y puerto, si aplica).

5. **Acceder a la aplicación**
   - Con Apache y MySQL encendidos, abrir en el navegador:  
     ```
     http://localhost/grupo18-login-system/src/main.php
     ```
   - Probar con los usuarios precargados o registrar uno nuevo.

---

### Credenciales de ejemplo
- **Administrador:** `id = 1`, contraseña `Contraseña123!`  
- **Usuario bloqueado:** `id = 2`, contraseña `Contraseña123!`  
- **Usuario activo:** `id = 3`, contraseña `Contraseña123!`  

> Se recomienda cambiar estas claves en un entorno real y mantenerlas solo para demostración.

---

