# Sistema de Login - Grupo 18 (UNSO)

Aplicación web desarrollada para el Trabajo Práctico de **Programación de Sistemas** (Tecnicatura en Ciberseguridad, UNSO).  
El objetivo es demostrar un flujo completo de autenticación con registro de usuarios, administración y persistencia en base de datos.

---

## Entregables solicitados por la cátedra
- Wireframe / prototipo de pantallas.  
- Código fuente completo (PHP, HTML, CSS, JS).  
- Script SQL con la estructura y datos de la base (`src/Model/db.sql`).  
- Archivo README con instrucciones de uso (este documento).  
- Video en formato MP4 con el _elevator pitch_ del producto seleccionado.

> **Nota:** este repositorio incluye todo el código y el script SQL.  
> El wireframe y el video deben adjuntarse en el Campus Virtual según las indicaciones del docente.

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
- PHP 8.1 o superior (CLI o servidor embebido).  
- Servidor MySQL/MariaDB (por ejemplo XAMPP o Docker).  
- Extensión PDO MySQL habilitada.  
- Navegador moderno (Chrome, Firefox, Edge) para interactuar con la interfaz.  

> Opcionalmente, contar con un entorno LAMP/WAMP ya configurado facilita el despliegue.

---

## Configuración y puesta en marcha
1. **Obtener el código**
   - Usar la carpeta entregada junto con el TP (archivo `.zip` o copia física).
   - Descomprimirla y abrirla en tu entorno de preferencia.

2. **Crear la base de datos**
   - Abrir MySQL y crear una base (por ejemplo `db2`).
   - Importar el script `src/Model/db.sql` desde phpMyAdmin o la CLI:
     ```bash
     mysql -u root -p db2 < src/Model/db.sql
     ```
   - El script crea las tablas `users` y `logs` e inserta usuarios de ejemplo.

3. **Configurar la conexión**
   - Editar `src/Model/conexion.php` con el nombre de base, usuario, contraseña y puerto de tu entorno.

4. **Levantar el servidor PHP**
   - Desde la carpeta `src` ejecutar:
     ```bash
     php -S localhost:8000
     ```
   - O copiar el directorio `src/` dentro de `htdocs` de XAMPP y acceder mediante  
     `http://localhost/src/View/login.html`.

5. **Acceder a la aplicación**
   - Abrir `http://localhost:8000/View/login.html` en el navegador.
   - Probar con los usuarios precargados o registrar uno nuevo.

### Credenciales de ejemplo
- **Administrador:** `id = 29127`, contraseña `Luna-2015!`  
- **Usuario bloqueado:** `id = 29`, contraseña `Luna-2015!`  
- **Usuario activo:** `id = 1213`, contraseña `Luna-2015!`  

> Se recomienda cambiar estas claves en un entorno real y mantenerlas solo para demostración.

---

## Estructura del proyecto