
let profileState = {
  email: "",
  name: "",
  birth_date: "",
};

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
    profileState = {
      email: data.email ?? "",
      name: data.name ?? "",
      birth_date: data.birth_date ?? "",
    };

    setInput("f-email", profileState.email);
    setInput("f-name", profileState.name);
    setInput("f-birth", profileState.birth_date);
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
  const editBtn = byId("btn-edit");
  const cancelBtn = byId("btn-cancel-edit");
  const messageEl = byId("profile-message");
  if (!editBtn) return;

  const fields = [
    { id: "f-email", name: "email" },
    { id: "f-name", name: "name" },
    { id: "f-birth", name: "birth_date" },
  ];

  let editing = false;

  const setInputsDisabled = (disabled) => {
    fields.forEach(({ id }) => {
      const input = byId(id);
      if (input && "disabled" in input) {
        input.disabled = disabled;
      }
    }); 
  };

  const restoreValues = () => {
    setInput("f-email", profileState.email ?? "");
    setInput("f-name", profileState.name ?? "");
    setInput("f-birth", profileState.birth_date ?? "");
  };

  const showMessage = (msg, ok = true) => {
    if (!messageEl) return;
    messageEl.textContent = msg;
    messageEl.classList.remove(
      "hidden",
      "profile-message--ok",
      "profile-message--error"
    );
    if (!msg) {
      messageEl.classList.add("hidden");
      return;
    }
    messageEl.classList.add(ok ? "profile-message--ok" : "profile-message--error");
  };

  const setEditing = (active) => {
    editing = active;
    setInputsDisabled(!active);
    if (active) {
      editBtn.textContent = "Guardar cambios";
      cancelBtn?.classList.remove("hidden");
      cancelBtn?.removeAttribute("disabled");
      const firstInput = byId(fields[0].id);
      firstInput?.focus();
      firstInput?.select?.();
    } else {
      editBtn.textContent = "Editar perfil";
      cancelBtn?.classList.add("hidden");
      cancelBtn?.removeAttribute("disabled");
    }
  };

  setInputsDisabled(true);

  editBtn.addEventListener("click", async () => {
    if (!editing) {
      showMessage("", true);
      setEditing(true);
      return;
    }

    const values = fields.reduce((acc, { id, name }) => {
      const input = byId(id);
      acc[name] = (input?.value ?? "").trim();
      return acc;
    }, {});

    if (!values.email || !values.name) {
      showMessage("Completá el email y el nombre.", false);
      setInputsDisabled(false);
      return;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(values.email)) {
      showMessage("Ingresá un email válido.", false);
      setInputsDisabled(false);
      return;
    }

    const birth = values.birth_date;
    if (birth && !/^\d{4}-\d{2}-\d{2}$/.test(birth)) {
      showMessage(
        "Ingresá una fecha de nacimiento válida (AAAA-MM-DD).",
        false
      );
      setInputsDisabled(false);
      return;
    }

    const payload = new FormData();
    payload.append("email", values.email);
    payload.append("name", values.name);
    payload.append("birth_date", birth);

    showMessage("Guardando cambios…", true);
    editBtn.disabled = true;
    cancelBtn?.setAttribute("disabled", "true");
    setInputsDisabled(true);

    try {
      const res = await fetch("../Controller/update_profile.php", {
        method: "POST",
        body: payload,
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data?.success) {
        throw new Error(data?.message || "No se pudieron guardar los cambios.");
      }

      profileState = {
        email: data.profile?.email ?? values.email,
        name: data.profile?.name ?? values.name,
        birth_date: data.profile?.birth_date ?? birth,
      };

      restoreValues();
      showMessage(data.message || "Perfil actualizado correctamente.", true);
      setEditing(false);
    } catch (err) {
      showMessage(err.message || "Error inesperado al actualizar el perfil.", false);
      setInputsDisabled(false);
    } finally {
      editBtn.disabled = false;
      cancelBtn?.removeAttribute("disabled");
    }
  });

  cancelBtn?.addEventListener("click", () => {
    restoreValues();
    showMessage("", true);
    setEditing(false);
  });
}
/* init */
document.addEventListener("DOMContentLoaded", () => {
  loadSession();
  setupEditProfile();
  setupChangePassword();
});

function setupChangePassword() {
  const form = document.getElementById("change-pass-form");
  const toggleBtn = document.getElementById("btn-pass");
  const cancelBtn = document.getElementById("cancel-pass");
  const messageEl = document.getElementById("pass-message");
  if (!form || !toggleBtn) return;

  const defaultLabel = (toggleBtn.textContent || "¿Cambiar contraseña?").trim();
  const openLabel = "Ocultar cambio de contraseña";

  const setExpanded = (expanded) => {
    toggleBtn.setAttribute("aria-expanded", expanded ? "true" : "false");
    toggleBtn.textContent = expanded ? openLabel : defaultLabel;
  };

  toggleBtn.setAttribute("aria-controls", "change-pass-form");
  form.classList.add("hidden");
  setExpanded(false);

  const disableForm = (disabled) => {
    [...form.elements].forEach((el) => { if ("disabled" in el) el.disabled = disabled; });
    toggleBtn.disabled = disabled;
  };

  const showMessage = (msg, ok = true) => {
    if (!messageEl) return;
    messageEl.textContent = msg;
    messageEl.classList.remove("hidden", "pass-message--ok", "pass-message--error");
    if (!msg) { messageEl.classList.add("hidden"); return; }
    messageEl.classList.add(ok ? "pass-message--ok" : "pass-message--error");
  };

  toggleBtn.addEventListener("click", () => {
    const isHidden = form.classList.toggle("hidden");
    setExpanded(!isHidden);
    showMessage("", true);
    if (!isHidden) form.scrollIntoView({ behavior: "smooth", block: "center" });
  });

  cancelBtn?.addEventListener("click", () => {
    form.reset();
    form.classList.add("hidden");
    setExpanded(false);
    showMessage("", true);
  });

  form.addEventListener("submit", async (ev) => {
    ev.preventDefault();
    const payload = new FormData(form);
    showMessage("Procesando…", true);
    disableForm(true);
    try {
      const res = await fetch("../Controller/update_password.php", { method: "POST", body: payload });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data?.success) throw new Error(data?.message || "No se pudo actualizar la contraseña.");

      showMessage(data.message || "Contraseña actualizada correctamente.", true);
      form.reset();
      form.classList.add("hidden");
      setExpanded(false);
    } catch (err) {
      showMessage(err.message || "Error inesperado.", false);
    } finally {
      disableForm(false);
    }
  });
}
