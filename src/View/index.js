
async function loadSession() {
  try {
    const res = await fetch("../Controller/check_session.php", { cache: "no-store" });
    if (!res.ok) throw new Error("HTTP " + res.status);
    const data = await res.json();

    // No autenticado → login
    if (!data?.authenticated) {
      window.location.href = "login.html";
      return;
    }

    // Admin → dashboard
    if (data.role === "administrador") {
      window.location.href = "dashboard.html";
      return;
    }

    // Poblar campos
    setText("f-id", data.id ?? "—");
    setText("f-email", data.email ?? "—");
    setText("f-role", data.role ?? "usuario");

    // Estado
    const isActive = !!data.active;
    const stateEl = byId("f-state");
    stateEl.textContent = isActive ? "Activo" : "Bloqueado";
    stateEl.classList.remove("pill--active", "pill--blocked");
    stateEl.classList.add(isActive ? "pill--active" : "pill--blocked");

    // Banner
    const banner = byId("banner");
    banner.classList.remove("hidden", "notice--ok", "notice--warn");
    if (!isActive) {
      banner.classList.add("notice--warn");
      banner.textContent = "Tu usuario se encuentra bloqueado. Si creés que es un error, contactá al administrador.";
      disable("btn-edit");
      disable("btn-pass");
    } else {
      banner.classList.add("notice--ok");
      banner.textContent = "Sesión válida.";
    }

    // Footer (opcional)
    setText("foot-role", data.role ? `Rol: ${data.role}` : "");
  } catch (err) {
    // Falla de verificación → login
    window.location.href = "login.html";
  }
}

/* ----------------- helpers ----------------- */
function byId(id) { return document.getElementById(id); }
function setText(id, txt) { const el = byId(id); if (el) el.textContent = txt; }
function disable(id) { const el = byId(id); if (el) el.disabled = true; }

/* init */
document.addEventListener("DOMContentLoaded", loadSession);
