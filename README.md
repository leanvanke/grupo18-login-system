# Sistema de Login - Grupo 18 (UNSO)

Aplicación web desarrollada para el Trabajo Práctico de **Programación de Sistemas** (Tecnicatura en Ciberseguridad, UNSO). El objetivo es demostrar un flujo completo de autenticación con registro de usuarios, administración y persistencia en base de datos.

## Entregables solicitados por la cátedra
- Wireframe / prototipo de pantallas.
- Código fuente completo (PHP, HTML, CSS, JS).
- Script SQL con la estructura y datos de la base (`src/Model/db.sql`).
- Archivo README con instrucciones de uso (este documento).
- Video en formato MP4 con el _elevator pitch_ del producto seleccionado.

> **Nota:** este repositorio incluye todo el código y el script SQL. El wireframe y el video deben adjuntarse en el Campus Virtual según las indicaciones del docente.

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

Opcionalmente, contar con un entorno LAMP/WAMP ya configurado facilita el despliegue.

---

## Configuración y puesta en marcha
1. **Obtener el código**
   - Utilizar la carpeta entregada junto con el TP (archivo `.zip` o copia física).
   - Descomprimirla y abrirla en tu entorno de preferencia.

2. **Crear la base de datos**
   - Abrir MySQL y crear una base (por ejemplo `db2`).
   - Importar el script `src/Model/db.sql` desde phpMyAdmin o la CLI:
     ```bash
     mysql -u root -p db2 < src/Model/db.sql
     ```
   - El script crea las tablas `users` y `logs` e inserta usuarios de ejemplo.

3. **Configurar la conexión**
   - Editar `src/Model/conexion.php` con el nombre de base, usuario, contraseña y puerto correspondientes a tu entorno.

4. **Levantar el servidor PHP**
   - Desde la carpeta `src` ejecutar:
     ```bash
     php -S localhost:8000
     ```
   - O copiar el directorio `src/` dentro de `htdocs` de XAMPP y acceder mediante `http://localhost/src/View/login.html`.

5. **Acceder a la aplicación**
   - Abrir `http://localhost:8000/View/login.html` en el navegador.
   - Probar con los usuarios precargados en la base o registrar uno nuevo.

### Credenciales de ejemplo
En el script SQL se incluyen cuentas de referencia:
- Administrador: `id=29127`, contraseña `Luna-2015!`
- Usuario bloqueado: `id=29`, contraseña `Luna-2015!`
- Usuario activo: `id=1213`, contraseña `Luna-2015!`

Se recomienda cambiar estas claves en un entorno real y mantenerlas como demostración únicamente.

---

## Estructura del proyecto
```
src/
├── Controller/          # Endpoints PHP (login, registro, administración)
├── Model/               # Conexión PDO y script SQL
├── View/                # HTML, CSS y JS del frontend
└── main.php             # Redirección inicial al login
```

### Endpoints relevantes
| Archivo | Descripción |
|---------|-------------|
| `Controller/register.php` | Alta de usuarios con validaciones y password hash. |
| `Controller/login.php` | Inicio de sesión, chequeo de estado e intentos. |
| `Controller/admin_get_users.php` | Listado de usuarios y logs (solo admin). |
| `Controller/admin_update_user.php` | Acciones administrativas: bloquear, desbloquear, eliminar. |
| `Controller/check_session.php` | Valida la sesión actual para proteger vistas. |
| `Controller/update_profile.php`, `update_password.php` | Endpoints auxiliares para extensión futura. |

El frontend consume estos endpoints con `fetch` (ver `View/script.js` y `View/dashboard.js`).

---

## Buenas prácticas aplicadas
- **Patrón MVC ligero:** separación entre vistas, controladores y modelo de datos.
- **Seguridad:**
  - Contraseñas almacenadas con `password_hash`.
  - Control de intentos fallidos y bloqueo temporal.
  - Regeneración de sesión tras login y cookies endurecidas.
  - Validación de roles y autorización en endpoints sensibles.
- **Mantenibilidad:** uso de `json_response()` para respuestas homogéneas y `add_log()` para centralizar la auditoría.
- **Escalabilidad:** el uso de PDO con consultas preparadas previene inyecciones y facilita migrar a otros motores.

---

## Pruebas recomendadas
1. Registrar un nuevo usuario (role `usuario`) y verificar su aparición en la base.
2. Intentar loguearse con una contraseña incorrecta para observar el mensaje de error.
3. Iniciar sesión como administrador, bloquear a un usuario y revisar que el estado se actualiza y que se registra el log.
4. Intentar ingresar con un usuario bloqueado para comprobar la respuesta `423 Usuario bloqueado`.

---

## Integrantes
- Leandro Van Kemenade
- Franco Sisti
- Emanuel Esquivel
- Rodrigo Franco

---

## Licencia
Proyecto académico. Puede reutilizarse con fines educativos citando a los autores.
