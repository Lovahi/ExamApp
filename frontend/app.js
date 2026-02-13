// app.js
const API_URL = 'http://localhost:8080/api.php';

const tbody = document.getElementById('tbody');
const statusEl = document.getElementById('status');
const reloadBtn = document.getElementById('reloadBtn');
const addForm = document.getElementById('addForm');
const nameInput = document.getElementById('nameInput');

function setStatus(msg) {
  statusEl.textContent = msg;
}

function escapeHtml(str) {
  return str.replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[c]));
}

async function loadItems() {
  setStatus('Cargando items...');
  tbody.innerHTML = `<tr><td colspan="4" class="muted">Cargando...</td></tr>`;

  try {
    const res = await fetch(API_URL, { method: 'GET' });
    const data = await res.json();

    if (!res.ok || !data.ok) {
      throw new Error(data.error || 'Error desconocido');
    }

    const items = data.items || [];
    if (items.length === 0) {
      tbody.innerHTML = `<tr><td colspan="4" class="muted">No hay items.</td></tr>`;
      setStatus('Sin datos.');
      return;
    }

    tbody.innerHTML = items.map((it) => `
      <tr>
        <td>${it.id}</td>
        <td>${escapeHtml(it.name)}</td>
        <td>${escapeHtml(it.created_at)}</td>
        <td class="actions">
          <button data-del="${it.id}" class="secondary">Borrar</button>
        </td>
      </tr>
    `).join('');

    setStatus(`Cargados ${items.length} item(s).`);
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" class="muted">Error cargando datos.</td></tr>`;
    setStatus(`Error: ${err.message}`);
  }
}

async function addItem(name) {
  setStatus('Añadiendo...');
  const res = await fetch(API_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name })
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.ok) {
    throw new Error(data.error || 'No se pudo añadir');
  }
}

async function deleteItem(id) {
  setStatus(`Borrando #${id}...`);
  const res = await fetch(`${API_URL}?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.ok) {
    throw new Error(data.error || 'No se pudo borrar');
  }
}

reloadBtn.addEventListener('click', loadItems);

addForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const name = nameInput.value.trim();
  if (!name) return;

  try {
    await addItem(name);
    nameInput.value = '';
    await loadItems();
  } catch (err) {
    setStatus(`Error: ${err.message}`);
  }
});

tbody.addEventListener('click', async (e) => {
  const btn = e.target.closest('button[data-del]');
  if (!btn) return;

  const id = btn.getAttribute('data-del');
  try {
    await deleteItem(id);
    await loadItems();
  } catch (err) {
    setStatus(`Error: ${err.message}`);
  }
});

// Cargar al abrir
loadItems();
