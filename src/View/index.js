
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
    setInput("f-email", data.email ?? "");
    setInput("f-name", data.name ?? "");
    setInput("f-birth", data.birth_date ?? "");
    setText("f-role", data.role ?? "usuario");

    // Estado
    const isActive = !!data.active;
    const stateEl = byId("f-state");
    stateEl.textContent = isActive ? "Activo" : "Bloqueado";
    stateEl.classList.remove("pill--active", "pill--blocked");
    stateEl.classList.add(isActive ? "pill--active" : "pill--blocked");

    const passBtn = byId("btn-pass");
    if (passBtn) {
      passBtn.disabled = !isActive;
      passBtn.title = isActive ? "Cambiar contraseña" : "Usuario bloqueado";
    }

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
function setText(id, txt) {
  const el = byId(id);
  if (!el) return;
  if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement) {
    el.value = txt ?? "";
  } else {
    el.textContent = txt ?? "";
  }
}
function setInput(id, value) {
  const el = byId(id);
  if (el && "value" in el) el.value = value ?? "";
}
function disable(id) {
  const el = byId(id);
  if (el && "disabled" in el) el.disabled = true;
}

function setupEditProfile() {
  const btn = byId("btn-edit");
  if (!btn) return;
  const editableIds = ["f-email", "f-name", "f-birth"];
  btn.addEventListener("click", () => {
    editableIds.forEach((id, index) => {
      const input = byId(id);
      if (input && "disabled" in input) {
        input.disabled = false;
        if (index === 0) input.focus();
      }
    });
    btn.disabled = true;
  });
}
/* init */
document.addEventListener("DOMContentLoaded", () => {
  loadSession();
  setupEditProfile();
  setupChangePassword();
});

function setupChangePassword() {
  const form = byId("change-pass-form");
  const toggleBtn = byId("btn-pass");
  const cancelBtn = byId("cancel-pass");
  const messageEl = byId("pass-message");

  if (!form || !toggleBtn) return;

  const disableForm = (disabled) => {
    [...form.elements].forEach((el) => {
      if ("disabled" in el) el.disabled = disabled;
    });
    toggleBtn.disabled = disabled;
  };

  const showMessage = (msg, ok) => {
    if (!messageEl) return;
    messageEl.textContent = msg;
    messageEl.classList.remove("hidden", "pass-message--ok", "pass-message--error");
    if (!msg) {
      messageEl.classList.add("hidden");
      return;
    }
    messageEl.classList.add(ok ? "pass-message--ok" : "pass-message--error");
  };

  toggleBtn.addEventListener("click", () => {
    form.classList.toggle("hidden");
    showMessage("", true);
    if (!form.classList.contains("hidden")) {
      form.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  });

  cancelBtn?.addEventListener("click", () => {
    form.reset();
    form.classList.add("hidden");
    showMessage("", true);
  });

  form.addEventListener("submit", async (ev) => {
    ev.preventDefault();
    showMessage("Procesando…", true);
    disableForm(true);

    try {
      const res = await fetch("../Controller/update_password.php", {
        method: "POST",
        body: new FormData(form),
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data?.success) {
        throw new Error(data?.message || "No se pudo actualizar la contraseña.");
      }

      showMessage(data.message || "Contraseña actualizada correctamente.", true);
      form.reset();
    } catch (err) {
      showMessage(err.message || "Error inesperado.", false);
    } finally {
      disableForm(false);
    }
  });
}