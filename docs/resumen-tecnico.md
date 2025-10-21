# Resumen Técnico Interno - Sistema de Login (Grupo 18)

Este documento está pensado como guía interna para el equipo. Describe cómo funciona el código, qué decisiones de diseño se tomaron y por qué.

## 1. Arquitectura general
- **Patrón MVC simplificado**: la carpeta `View/` contiene los recursos estáticos, `Controller/` concentra la lógica de negocio y `Model/` expone la conexión a la base y el script SQL. Esta separación permite iterar en la UI sin tocar la capa de datos y viceversa.
- **Enrutamiento plano**: cada acción importante se resuelve con un archivo PHP dedicado (por ejemplo `login.php`, `register.php`). La decisión responde a mantener el código accesible para el cursado, evitando frameworks que agreguen curva de aprendizaje extra.

## 2. Mapa de archivos y justificación

### `src/main.php`
- **Función**: punto de entrada que redirige al login. Simplifica la navegación cuando el proyecto se monta en la raíz del servidor embebido.
- **Decisión**: mantenerlo mínimo evita lógicas duplicadas y permite que cualquier cambio en la ruta del login se haga en un único lugar.

### Carpeta `src/Model`
- `conexion.php`: instancia `PDO` con los parámetros configurables. Se eligió PDO porque permite parametrizar consultas y cambiar de motor con mínimos ajustes.
- `db.sql`: contiene la estructura y los datos semilla. Mantenerlo versionado facilita levantar el entorno desde cero y ofrece usuarios de prueba coherentes con la demo.

### Carpeta `src/Controller`
- `login.php`: recibe credenciales, valida formatos, consulta usuarios y endurece la sesión. Fue diseñado proceduralmente para que los compañeros familiarizados con PHP básico puedan seguir el flujo sin clases intermedias.
- `register.php`: encapsula el alta de usuarios; valida email, ID y fuerza de contraseña antes de insertar. Mantenerlo separado evita condicionar la lógica de login con casos de registro.
- `admin_get_users.php`: arma la respuesta JSON con usuarios y logs. Se decidió devolver todo en una sola consulta + agregados en PHP para simplificar el render del dashboard y evitar depender de funciones avanzadas del motor SQL.
- `admin_update_user.php`: procesa bloqueos, desbloqueos y eliminación. Incluye validaciones para impedir que un admin se auto-bloquee o modifique a otros admins; esto evita estados inconsistentes durante las pruebas.
- `check_session.php`: confirma que la sesión sea válida y, opcionalmente, que el rol sea administrador. Se expuso como endpoint independiente porque tanto el dashboard como otras vistas futuras pueden reutilizarlo.
- `logs.php`: helper que registra cada evento relevante en la tabla `logs`. Se mantuvo como archivo separado para poder reutilizarlo desde cualquier controlador sin duplicar código.
- `session.php`: centraliza `start_session()` y `json_response()`. Así, cada endpoint incluye el mismo endurecimiento de cookies y formato de respuesta.
- `update_profile.php` y `update_password.php`: manejan la edición de datos personales y cambio de contraseña. Se construyeron con validaciones exhaustivas para demostrar buenas prácticas en manejo de formularios sensibles.

### Carpeta `src/View`
- `index.html`: landing con botones hacia login y registro; sirve de introducción para la demo presencial.
- `login.html`: vista que concentra tanto el formulario de inicio de sesión como el registro (mostrado bajo demanda). Se diseñó minimalista para enfocarse en la lógica de validación.
- `index.js`: maneja la navegación desde la landing y prepara listeners básicos; se mantuvo pequeño para no mezclarlo con los scripts de autenticación.
- `script.js`: orquesta el registro e inicio de sesión vía `fetch`. El JavaScript puro facilita que cualquier integrante entienda el flujo sin depender de frameworks y permite compartir validaciones entre ambos formularios.
- `dashboard.html`: interfaz de administración con tablas para usuarios y logs. Mantenerlo en HTML facilita que cualquiera del equipo ajuste el maquetado sin modificar PHP.
- `dashboard.js`: maneja la carga de usuarios/logs y las acciones administrativas. Separarlo de `script.js` evita mezclar responsabilidades del login con la administración.
- `dashboard.css`: estilos específicos del panel para no sobrecargar la hoja principal.
- `style.css` e `index.css`: capas de estilos genéricos para las vistas públicas (landing, login, registro). Separamos archivos para que los cambios cosméticos no afecten al dashboard.

## 3. Flujo de autenticación
1. El formulario en `View/login.html` envía las credenciales mediante `fetch` a `Controller/login.php`.
2. `login.php` valida el request, consulta la tabla `users` con PDO y compara la contraseña. Se admite compatibilidad con contraseñas en texto plano para usuarios antiguos, pero por defecto se almacenan con `password_hash`.
3. Si la combinación es válida y el usuario está activo, se crea la sesión endurecida (`session.php` define cookies `httponly`, `secure` cuando aplica y `samesite=Lax`).
4. Cada intento genera un registro en `logs` a través de `add_log()`, lo que facilita auditoría y control de intentos.

### Por qué así
- Usar PDO con consultas preparadas evita inyección SQL y es estándar en PHP moderno.
- El manejo explícito de sesiones permite reforzar seguridad sin depender de la configuración global del servidor.
- El registro de intentos fallidos se usa para aplicar un rate limit simple (`too_many_attempts`) que mira los últimos 10 minutos.

## 4. Registro de usuarios
- `Controller/register.php` valida el payload (campos obligatorios, formato de email, fuerza de contraseña) antes de tocar la base.
- Se verifica la unicidad de ID y correo con consultas `SELECT 1` y se captura el error `1062` como fallback.
- El password se guarda con `password_hash` y se deja el campo `active` en 1 por defecto.

### Decisiones
- Se prefirió delegar las validaciones críticas al backend aunque el frontend (`View/script.js`) hace una verificación previa para dar feedback inmediato.
- El hash de contraseñas es obligatorio para producción. Mantener la compatibilidad con texto plano permite reutilizar datos de pruebas sin bloquear el demo.

## 5. Panel administrativo
- `View/dashboard.html` carga `dashboard.js`, que al inicializar consulta `check_session.php` para asegurarse de que la sesión corresponde a un administrador.
- `admin_get_users.php` devuelve todos los usuarios con sus logs asociados, ya en el formato esperado por la vista (estado, email, role, lista de eventos).
- Las acciones de bloqueo/desbloqueo/eliminación llaman a `admin_update_user.php`, que impone reglas de negocio: un administrador no puede modificarse a sí mismo ni a otro administrador.

### Justificación
- Entregar un panel único para administración simplifica la demo y reduce rutas. Dado que es un TP, el volumen de datos es bajo y traer todos los usuarios en una sola consulta es aceptable.
- Los logs se agrupan en PHP porque MariaDB 10.4 no admite JSON nativo; hacerlo en la aplicación mantiene compatibilidad con motores básicos.

## 6. Persistencia y esquema
- La base se inicializa con `src/Model/db.sql`. Incluye:
  - Tabla `users`: campos mínimos (id, name, email, password, role, birth_date, active, created_at).
  - Tabla `logs`: referencia a `users.id`, `ts` automático y descripción del evento.
- La clave primaria de `users` es `id`. Para la demo se usa `AUTO_INCREMENT`, pero el código acepta IDs personalizados porque llegan como string desde el formulario.

### Razones
- Mantener el esquema chico y relacional facilita entender el modelo en clase.
- Se incluyeron datos de ejemplo para mostrar distintos estados: activo, bloqueado y rol administrador.

## 7. Seguridad y buenas prácticas
- `session_regenerate_id(true)` tras el login mitiga fijación de sesión.
- Las cookies de sesión se limitan a HTTPS cuando está disponible y se marcan `httponly`.
- El guardado de logs centralizado (`logs.php`) facilita ampliar a más eventos sin duplicar código.
- Los endpoints administrativos verifican el rol antes de ejecutar acciones y responden en JSON con códigos HTTP apropiados (403, 404, 429...).

## 8. Consideraciones para futuros cambios
- Migrar `too_many_attempts` a una tabla dedicada si se requiere persistir el rate limit entre reinicios del servidor.
- Completar los endpoints `update_profile.php` y `update_password.php` según evolucione el alcance del TP.
- Incorporar tokens CSRF si se expone públicamente; en el contexto académico se priorizó mantener el stack liviano.

---
**Contacto del equipo**: cualquier duda, coordinar vía grupo interno de la materia.
