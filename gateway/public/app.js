const $ = (sel) => document.querySelector(sel);

const api = {
  async get(url) {
    const r = await fetch(url);
    const data = await r.json();
    if (!r.ok) throw new Error(data?.error || "Request failed");
    return data;
  },
  async post(url, body) {
    const r = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    });
    const data = await r.json();
    if (!r.ok) throw new Error(data?.error || "Request failed");
    return data;
  },
};

function setMsg(el, text, kind = "info") {
  el.textContent = text || "";
  el.style.color = kind === "error" ? "var(--danger)" : kind === "ok" ? "var(--ok)" : "var(--text)";
}

function formatBytes(bytes) {
  const n = Number(bytes || 0);
  if (n < 1024) return `${n} B`;
  const kb = n / 1024;
  if (kb < 1024) return `${kb.toFixed(1)} KB`;
  const mb = kb / 1024;
  if (mb < 1024) return `${mb.toFixed(1)} MB`;
  const gb = mb / 1024;
  return `${gb.toFixed(1)} GB`;
}

function badge(status) {
  const cls = status === "available" ? "ok" : "bad";
  return `<span class="badge ${cls}">${status}</span>`;
}

function bindTabs() {
  const tabs = document.querySelectorAll(".tab");
  const panels = {
    physical: $("#tab-physical"),
    digital: $("#tab-digital"),
    admin: $("#tab-admin"),
  };

  tabs.forEach(btn => {
    btn.addEventListener("click", () => {
      tabs.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      Object.values(panels).forEach(p => p.classList.remove("active"));
      panels[btn.dataset.tab].classList.add("active");
    });
  });
}

async function searchByInventory() {
  const inv = $("#invInput").value.trim();
  const msg = $("#physicalMsg");
  setMsg(msg, "");

  if (!inv) return setMsg(msg, "Введи инвентарный номер", "error");

  try {
    const data = await api.get(`/api/physical/books?inventory_number=${encodeURIComponent(inv)}`);
    renderPhysical(data.book ? [data.book] : []);
    if (!data.ok) setMsg(msg, data.message, "error");
  } catch (e) {
    setMsg(msg, e.message, "error");
  }
}

async function searchByAuthor() {
  const author = $("#authorInput").value.trim();
  const msg = $("#physicalMsg");
  setMsg(msg, "");

  if (!author) return setMsg(msg, "Введи автора", "error");

  try {
    const data = await api.get(`/api/physical/books?author_name=${encodeURIComponent(author)}`);
    renderPhysical(data.books || []);
    if (!data.ok) setMsg(msg, data.message, "error");
  } catch (e) {
    setMsg(msg, e.message, "error");
  }
}

function renderPhysical(books) {
  const tbody = $("#physicalTable tbody");
  tbody.innerHTML = "";

  if (!books.length) {
    tbody.innerHTML = `<tr><td colspan="7" class="muted">Ничего не найдено</td></tr>`;
    return;
  }

  for (const b of books) {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${b.inventory_number}</td>
      <td>${b.title}</td>
      <td>${b.author}</td>
      <td>${b.year}</td>
      <td>${b.location}</td>
      <td>${badge(b.status)}</td>
      <td><button data-inv="${b.inventory_number}" class="ghost pick">В форму</button></td>
    `;
    tbody.appendChild(tr);
  }

  tbody.querySelectorAll(".pick").forEach(btn => {
    btn.addEventListener("click", () => {
      $("#loanInv").value = btn.dataset.inv;
      $("#loanReader").focus();
    });
  });
}

async function loanBook() {
  const inv = $("#loanInv").value.trim();
  const reader = $("#loanReader").value.trim();
  const msg = $("#loanMsg");
  setMsg(msg, "");

  if (!inv || !reader) return setMsg(msg, "Нужны инв. № и чит. билет", "error");

  try {
    const data = await api.post("/api/physical/loan", { inventory_number: inv, reader_card: reader });
    setMsg(msg, data.result.message, data.ok ? "ok" : "error");
    await searchByInventory(); // refresh view
  } catch (e) {
    setMsg(msg, e.message, "error");
  }
}


async function loadDigital() {
  const msg = $("#digitalMsg");
  setMsg(msg, "");

  try {
    const data = await api.get("/api/digital/resources");
    renderDigital(data.resources || []);
  } catch (e) {
    setMsg(msg, e.message, "error");
  }
}

function renderDigital(resources) {
  const tbody = $("#digitalTable tbody");
  tbody.innerHTML = "";

  if (!resources.length) {
    tbody.innerHTML = `<tr><td colspan="7" class="muted">Пусто. Запусти seed: <code>npm run seed</code></td></tr>`;
    return;
  }

  for (const r of resources) {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${r.title}</td>
      <td>${r.author}</td>
      <td><span class="badge">${r.format}</span></td>
      <td>${formatBytes(r.fileSize)}</td>
      <td>${(r.tags || []).map(t => `<span class="badge">${t}</span>`).join(" ")}</td>
      <td>${r.downloadCount || 0}</td>
      <td><button data-id="${r._id}">Скачать</button></td>
    `;
    tbody.appendChild(tr);
  }

  tbody.querySelectorAll("button[data-id]").forEach(btn => {
    btn.addEventListener("click", () => download(btn.dataset.id));
  });
}

async function download(resourceId) {
  const msg = $("#digitalMsg");
  setMsg(msg, "");
  const userId = ($("#userId").value.trim() || "anonymous");

  try {
    const data = await api.post("/api/digital/download", { resourceId, userId });
    setMsg(msg, `Лог записан. Ссылка: ${data.downloadUrl}`, "ok");
    await loadDigital();
  } catch (e) {
    setMsg(msg, e.message, "error");
  }
}

function initAdminFrame() {
  // При локальном запуске легаси доступен отдельно (см. URL ниже).
  // При локальном запуске: legacy находится на http://127.0.0.1:8080
  $("#reportFrame").src = "http://127.0.0.1:8080/report.php?type=overdue";
}

function boot() {
  $("#year").textContent = new Date().getFullYear();

  bindTabs();

  $("#invBtn").addEventListener("click", searchByInventory);
  $("#authorBtn").addEventListener("click", searchByAuthor);
  $("#loanBtn").addEventListener("click", loanBook);

  $("#refreshDigital").addEventListener("click", loadDigital);

    // Первая загрузка
  $("#invInput").value = "LIB-2024-001";
  searchByInventory();
  loadDigital();
  initAdminFrame();
}

boot();
