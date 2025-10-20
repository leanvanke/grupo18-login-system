// === Verificar autenticación ===
async function checkAuth() {
    try {
        const res = await fetch("../Controller/check_session.php");
        if (!res.ok) throw new Error(`Error ${res.status}`);
        const data = await res.json();
        if (!data.authenticated || !data.role || data.role !== "administrador") {
            window.location.href = "index.html";
            return false;
        }
        return true;
    } catch (err) {
        logsContainer.innerHTML = `<div class="error">Error de autenticación: ${err.message}</div>`;
        return false;
    }
}

// === Elementos del DOM ===
const logsContainer = document.getElementById("logs-container");
const searchInput = document.getElementById("search-input");
const statusFilter = document.getElementById("status-filter");

// === Cargar logs desde el backend ===
async function fetchLogs() {
    try {
        const res = await fetch("../Controller/admin_get_users.php");
        if (!res.ok) throw new Error(`Error ${res.status}: ${res.statusText}`);
        return await res.json();
    } catch (err) {
        logsContainer.innerHTML = `<div class="error">Error de conexión con el servidor: ${err.message}</div>`;
        return [];
    }
}

// === Actualizar usuario (bloquear, desbloquear, eliminar) ===
async function updateUser(id, action) {
    try {
        logsContainer.innerHTML = `<div class="spinner">Cargando...</div>`;
        const res = await fetch("../Controller/admin_update_user.php", {
            method: "POST",
            body: new URLSearchParams({ id, action })
        });
    
        let data;
        try {
            data = await res.json();
        } catch (parseErr) {
            data = null;
        }

        if (!res.ok) {
            const message = data && data.message ? data.message : `Error ${res.status}: ${res.statusText}`;
            logsContainer.innerHTML = `<div class="error">${message}</div>`;
            setTimeout(() => renderLogs(), 2000);
            return false;
        }
        
        if (!data || !data.success) {
            const message = data && data.message ? data.message : "Error al ejecutar la acción";
            logsContainer.innerHTML = `<div class="error">${message}</div>`;
            setTimeout(() => renderLogs(), 2000);
            return false;
        }

        const successMessage = data.message ? data.message : `Acción realizada con éxito: ${action}`;
        logsContainer.innerHTML = `<div class="success">${successMessage}</div>`;
        setTimeout(() => renderLogs(), 2000);
        return true;
    } catch (err) {
        logsContainer.innerHTML = `<div class="error">Error de conexión con el servidor: ${err.message}</div>`;
        setTimeout(() => renderLogs(), 2000);
        return false;
    }
}

// === Renderizar tabla ===
async function renderLogs() {
    if (!(await checkAuth())) return;
    logsContainer.innerHTML = `<div class="spinner">Cargando...</div>`;

    const allLogs = await fetchLogs();
    if (!allLogs.length && logsContainer.innerHTML.includes("Cargando...")) {
        logsContainer.innerHTML = `<div>No se encontraron usuarios registrados.</div>`;
        return;
    }

    const searchTerm = searchInput.value.toLowerCase();
    const statusTerm = statusFilter.value;

    logsContainer.innerHTML = "";

    const filtered = allLogs.filter(item => {
        const matchSearch = item.id.toLowerCase().includes(searchTerm) ||
                           item.email.toLowerCase().includes(searchTerm);
        const matchStatus = statusTerm === "todos" || item.estado === statusTerm;
        return matchSearch && matchStatus;
    });

    filtered.forEach((user, index) => {
        const row = document.createElement("div");
        row.classList.add("table-row");
        if (user.estado === "bloqueado") row.classList.add("row-blocked");

        row.innerHTML = `
            <span>${index + 1}</span>
            <span>${user.id} (${user.role})</span>
            <span class="status-${user.estado}">${user.estado}</span>
            <span>${user.email}</span>
            <span><button class="btn-logs" data-id="${user.id}">Ver Logs</button></span>
        `;

        const actionCol = document.createElement("span");

        const blockBtn = document.createElement("button");
        blockBtn.setAttribute("aria-label", user.estado === "activo" ? "Bloquear usuario" : "Desbloquear usuario");
        if (user.estado === "activo") {
            blockBtn.textContent = "🔒";
            blockBtn.className = "btn-block";
            blockBtn.title = "Bloquear usuario";
            blockBtn.onclick = async () => {
                blockBtn.disabled = true;
                if (await updateUser(user.id, "block")) renderLogs();
                blockBtn.disabled = false;
            };
        } else {
            blockBtn.textContent = "🔓";
            blockBtn.className = "btn-unblock";
            blockBtn.title = "Desbloquear usuario";
            blockBtn.onclick = async () => {
                blockBtn.disabled = true;
                if (await updateUser(user.id, "unblock")) renderLogs();
                blockBtn.disabled = false;
            };
        }

        const deleteBtn = document.createElement("button");
        deleteBtn.textContent = "❌";
        deleteBtn.className = "btn-delete";
        deleteBtn.title = "Eliminar usuario";
        deleteBtn.setAttribute("aria-label", "Eliminar usuario");
        deleteBtn.onclick = async () => {
            if (confirm(`¿Eliminar usuario ${user.id}?`)) {
                deleteBtn.disabled = true;
                if (await updateUser(user.id, "delete")) renderLogs();
                deleteBtn.disabled = false;
            }
        };

        actionCol.appendChild(blockBtn);
        actionCol.appendChild(deleteBtn);
        row.appendChild(actionCol);

        logsContainer.appendChild(row);

        const logsBtn = row.querySelector(".btn-logs");
        logsBtn.onclick = () => {
            const logsDiv = document.createElement("div");
            logsDiv.className = "logs-details";
            if (user.logs && user.logs.length) {
                logsDiv.innerHTML = `<h3>Logs de ${user.id}</h3>
                    <ul>${user.logs.map(log => `<li>${log.fecha}: ${log.estado}</li>`).join("")}</ul>`;
            } else {
                logsDiv.innerHTML = `<p>No hay logs disponibles para ${user.id}</p>`;
            }
            logsContainer.appendChild(logsDiv);
            setTimeout(() => logsDiv.remove(), 5000);
        };
    });
}

// === Eventos ===
searchInput.addEventListener("input", renderLogs);
statusFilter.addEventListener("change", renderLogs);
renderLogs();

