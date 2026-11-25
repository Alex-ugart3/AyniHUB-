/**
 * AyniHub - lógica del frontend.
 * Consume el backend Laravel por fetch (JSON + token CSRF):
 *  - navegación por secciones (hash), idioma ES/QU, carrusel
 *  - registro / login / logout
 *  - catálogo y CRUD de productos (el servidor valida y autoriza)
 * Todo texto de usuario se inserta con textContent (previene XSS).
 */
(function () {
  "use strict";
  const $ = (s, root = document) => root.querySelector(s),
    $$ = (s, root = document) => Array.from(root.querySelectorAll(s));
  const csrf = $('meta[name="csrf-token"]').content,
    BASE = $('meta[name="base-url"]').content;
  let user = window.AYNI_USER || null,
    selectedRole = { login: "comprador", reg: "comprador" },
    category = "todos",
    slide = 0;
  let lang = "es";
  try { lang = localStorage.getItem("ayni_lang") || "es"; } catch (e) {}
  const t = (k, vars) => {
    const all = window.AyniHub?.data?.i18n || {};
    let out = all[lang]?.[k] ?? all.es?.[k] ?? k;
    if (vars) Object.entries(vars).forEach(([a, b]) => (out = out.split("{" + a + "}").join(b)));
    return out;
  };
  const toast = (msg) => {
    const el = $("#toastGlobal");
    if (!el) return;
    el.textContent = msg;
    el.classList.add("show");
    setTimeout(() => el.classList.remove("show"), 3000);
  };
  const request = async (url, method = "GET", data = null) => {
    const opts = {
      method,
      headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf, "X-Lang": lang },
    };
    if (data) {
      opts.headers["Content-Type"] = "application/json";
      opts.body = JSON.stringify(data);
    }
    const r = await fetch(BASE + url, opts);
    let body = {};
    try {
      body = await r.json();
    } catch (e) {}
    if (!r.ok) {
      if (r.status === 419) throw new Error(t("e_session"));
      if (r.status === 401) throw new Error(body.message || t("e_login"));
      if (r.status === 403 && (!body.message || /unauthorized/i.test(body.message))) throw new Error(t("e_forbidden"));
      throw new Error(Object.values(body.errors || {}).flat()[0] || body.message || t("e_generic"));
    }
    return body;
  };
  function updateUserUI() {
    const pill = $("#userPill");
    pill.style.display = user ? "inline-flex" : "none";
    pill.textContent = user ? `${user.name} · ${t("rol_" + user.role)}` : "";
    $("#navLogin").hidden = !!user;
    $("#navRegister").hidden = !!user;
    $("#btnLogout").hidden = !user;
    $("#btnNuevoProducto").hidden = !user || user.role === "comprador";
    const nav = $("#navPanel");
    nav.hidden = !user;
    if (user)
      nav.textContent = t("nav_panel_" + user.role);
  }
  function showSection(name, focus = false) {
    const valid = [
      "Inicio",
      "Entrevista",
      "ComoFunciona",
      "Catalogo",
      "Login",
      "Registro",
      "Panel",
    ];
    if (!valid.includes(name)) name = "Inicio";
    if (name === "Panel" && !user) {
      location.hash = "#Login";
      return;
    }
    $$(".section").forEach((s) =>
      s.classList.toggle("active", s.id === "sec-" + name),
    );
    $$("[data-route]").forEach((a) =>
      a.getAttribute("data-route") === name
        ? a.setAttribute("aria-current", "page")
        : a.removeAttribute("aria-current"),
    );
    window.scrollTo(0, 0);
    if (focus) $("#sec-" + name)?.focus({ preventScroll: true });
    $("#navLinks").classList.remove("open");
    $("#menuToggle").setAttribute("aria-expanded", "false");
    if (name === "Panel") loadPanel().catch((e) => toast(e.message));
  }
  function navigate() {
    showSection(location.hash.replace("#", "") || "Inicio");
  }
  function applyLang(l, silent) {
    const all = window.AyniHub?.data?.i18n;
    const dict = all?.[l];
    if (!dict) return;
    lang = l;
    try { localStorage.setItem("ayni_lang", l); } catch (e) {}
    document.documentElement.lang = l;
    ["es", "qu"].forEach((x) => {
      const b = $("#btn-" + x);
      if (b) {
        b.classList.toggle("active", x === l);
        b.setAttribute("aria-pressed", String(x === l));
      }
    });
    $$("[data-i18n]").forEach((n) => {
      const v = dict[n.dataset.i18n] ?? all.es[n.dataset.i18n];
      if (v) n.innerHTML = v;
    });
    $$("[data-i18n-placeholder]").forEach((n) => {
      const v = dict[n.dataset.i18nPlaceholder] ?? all.es[n.dataset.i18nPlaceholder];
      if (v) n.placeholder = v;
    });
    updateUserUI();
    if (silent) return;
    loadProducts().catch(() => {});
    if (user && location.hash === "#Panel") loadPanel().catch(() => {});
  }
  let productsById = {};
  function renderProducts(list) {
    productsById = Object.fromEntries(list.map((p) => [p.id, p]));
    const grid = $("#catGrid");
    grid.innerHTML = "";
    if (!list.length) {
      grid.innerHTML = "";
      grid.append(el("p", t("sinProductos"), "empty-msg"));
      return;
    }
    list.forEach((p) => {
      const card = document.createElement("article");
      card.className = "prod-card show";
      card.dataset.category = p.category;
      const image = document.createElement("img");
      image.src = BASE + "/" + (p.image || "images/papa1.jpg");
      image.alt = p.name;
      image.loading = "lazy";
      const wrap = document.createElement("div");
      wrap.className = "prod-img-wrap";
      wrap.append(image);
      const h = document.createElement("h3");
      h.textContent = p.name;
      const producer = document.createElement("p");
      producer.className = "who";
      producer.textContent = p.producer_name;
      const loc = document.createElement("p");
      loc.className = "loc";
      loc.textContent = "📍 " + p.location;
      const price = document.createElement("p");
      price.className = "price";
      price.textContent = "S/ " + Number(p.price).toFixed(2) + " / " + t("u_" + p.unit);
      card.append(wrap, h, producer, loc, price);
      if (user && user.role === "comprador") {
        const buy = actionButton(t("pedir"), "js-order", p.id);
        buy.classList.replace("btn-ghost", "btn-primary");
        card.append(buy);
      }
      if (
        user &&
        (user.role === "gestor" ||
          (user.role === "productor" && Number(p.user_id) === Number(user.id)))
      ) {
        const actions = document.createElement("div");
        actions.className = "card-actions";
        actions.append(
          actionButton(t("editar"), "js-edit", p.id),
          actionButton(t("eliminar"), "js-delete", p.id),
        );
        card.append(actions);
      }
      grid.append(card);
    });
  }
  function actionButton(label, cls, id) {
    const b = document.createElement("button");
    b.type = "button";
    b.className = "btn btn-ghost btn-small " + cls;
    b.dataset.id = id;
    b.textContent = label;
    return b;
  }
  async function loadProducts() {
    const list = await request(
      "/productos" +
        (category !== "todos"
          ? "?category=" + encodeURIComponent(category)
          : ""),
    );
    renderProducts(list);
  }
  function roleButtons() {
    $$("[data-role]").forEach((b) =>
      b.addEventListener("click", () => {
        const group = b.dataset.roleGroup;
        selectedRole[group] = b.dataset.role;
        $$(`[data-role-group="${group}"]`).forEach((x) => {
          x.classList.toggle("active", x === b);
          x.setAttribute("aria-pressed", String(x === b));
        });
        if (group === "reg")
          $("#regUbicWrap").hidden = selectedRole.reg === "comprador";
      }),
    );
  }
  function showError(form, msg) {
    let box = $(".form-error", form);
    if (!box) {
      box = document.createElement("p");
      box.className = "form-error";
      box.style.color = "#b42318";
      form.prepend(box);
    }
    box.textContent = msg;
    box.hidden = !msg;
    box.setAttribute("role", "alert");
  }
  /** Tras ingresar/registrarse: ir a la sección indicada por el servidor y recargar. */
  function goAfterAuth(res) {
    const target = /^#[A-Za-z]+$/.test(res?.redirect || "") ? res.redirect : "#Catalogo";
    history.replaceState(null, "", location.pathname + target);
    location.reload();
  }
  async function initAuth() {
    roleButtons();
    $("#formLogin").addEventListener("submit", async (e) => {
      e.preventDefault();
      const f = e.currentTarget;
      const btn = $("button[type=submit]", f);
      showError(f, "");
      btn.disabled = true;
      try {
        const res = await request("/login", "POST", {
          contact: $("#loginUser").value.trim(),
          password: $("#loginPass").value,
          role: selectedRole.login,
        });
        $("#loginPass").value = "";
        goAfterAuth(res);
      } catch (err) {
        showError(f, err.message);
        btn.disabled = false;
      }
    });
    $("#formRegistro").addEventListener("submit", async (e) => {
      e.preventDefault();
      const f = e.currentTarget;
      const btn = $("button[type=submit]", f);
      showError(f, "");
      btn.disabled = true;
      try {
        const res = await request("/registro", "POST", {
          name: $("#regNombre").value.trim(),
          contact: $("#regContacto").value.trim(),
          location: $("#regUbicacion").value.trim(),
          password: $("#regPass").value,
          role: selectedRole.reg,
        });
        $("#regPass").value = "";
        goAfterAuth(res);
      } catch (err) {
        showError(f, err.message);
        btn.disabled = false;
      }
    });
    $("#btnLogout").addEventListener("click", async () => {
      try {
        await request("/logout", "POST");
        history.replaceState(null, "", location.pathname + "#Inicio");
        location.reload();
      } catch (e) {
        toast(e.message);
      }
    });
  }
  function openProductForm(p = null) {
    if (!user || !["productor", "gestor"].includes(user.role)) {
      toast(t("toastLoginProd"));
      location.hash = "#Login";
      return;
    }
    const f = $("#formProducto");
    f.reset();
    $("#fpId").value = p?.id || "";
    if (p) {
      $("#fpNombre").value = p.name;
      $("#fpCat").value = p.category;
      $("#fpProductor").value = p.producer_name;
      $("#fpPrecio").value = p.price;
      $("#fpUnidad").value = p.unit;
      $("#fpUbicacion").value = p.location;
      $("#fpDesc").value = p.description || "";
    } else {
      $("#fpProductor").value = user.name;
      $("#fpUbicacion").value = user.location || "";
    }
    $("#fpTitulo").textContent = p ? t("editFormTitle") : t("addProdTitle");
    f.hidden = false;
    $("#fpNombre").focus();
  }
  async function initProducts() {
    $$("[data-cat]").forEach((b) =>
      b.addEventListener("click", async () => {
        category = b.dataset.cat;
        $$(".cat-tab[data-cat]").forEach((x) =>
          x.setAttribute("aria-pressed", String(x === b)),
        );
        await loadProducts();
      }),
    );
    $("#btnNuevoProducto").addEventListener("click", () => openProductForm());
    $("#btnCancelarProducto").addEventListener(
      "click",
      () => ($("#formProducto").hidden = true),
    );
    $("#formProducto").addEventListener("submit", async (e) => {
      e.preventDefault();
      const id = $("#fpId").value;
      const data = {
        name: $("#fpNombre").value.trim(),
        category: $("#fpCat").value,
        producer_name: $("#fpProductor").value.trim(),
        price: $("#fpPrecio").value,
        unit: $("#fpUnidad").value,
        location: $("#fpUbicacion").value.trim(),
        description: $("#fpDesc").value.trim(),
      };
      try {
        await request(
          id ? "/productos/" + id : "/productos",
          id ? "PUT" : "POST",
          data,
        );
        $("#formProducto").hidden = true;
        await loadProducts();
        toast(id ? t("toastActualizado") : t("toastGuardado"));
      } catch (err) {
        showError($("#formProducto"), err.message);
      }
    });
    $("#catGrid").addEventListener("click", async (e) => {
      const order = e.target.closest(".js-order");
      if (order) return placeOrder(order.dataset.id);
      const edit = e.target.closest(".js-edit"),
        del = e.target.closest(".js-delete");
      if (edit) {
        openProductForm(await request("/productos/" + edit.dataset.id));
      }
      if (del && confirm(t("confirmEliminar"))) {
        try {
          await request("/productos/" + del.dataset.id, "DELETE");
          await loadProducts();
          toast(t("toastEliminado"));
        } catch (err) {
          toast(err.message);
        }
      }
    });
  }

  /* ---------- Pedidos y panel de gestión ---------- */
  const KEYS = ["pendiente", "confirmado", "enviado", "entregado", "cancelado"];
  const STATUS = new Proxy({}, { get: (_, k) => t("st_" + String(k)) });
  const FINAL = ["entregado", "cancelado"];
  let panelTab = "pedidos";
  const el = (tag, text, cls) => {
    const n = document.createElement(tag);
    if (text !== undefined && text !== null) n.textContent = text;
    if (cls) n.className = cls;
    return n;
  };
  const money = (v) => "S/ " + Number(v).toFixed(2);
  const fdate = (d) => new Date(d).toLocaleDateString("es-PE", { day: "2-digit", month: "short", year: "numeric" });

  async function placeOrder(id) {
    const p = productsById[id];
    if (!p) return;
    const raw = prompt(t("askQty", { name: p.name, unit: t("u_" + p.unit) }), "1");
    if (raw === null) return;
    const quantity = parseInt(raw, 10);
    if (!Number.isInteger(quantity) || quantity < 1) return toast(t("errQty"));
    try {
      await request("/pedidos", "POST", { product_id: Number(id), quantity });
      toast(t("toastOrder"));
      location.hash = "#Panel";
    } catch (err) {
      toast(err.message);
    }
  }

  function allowedStatuses(o) {
    if (user.role === "gestor") return KEYS;
    if (FINAL.includes(o.status)) return [];
    if (user.role === "productor") return ["confirmado", "enviado", "entregado", "cancelado"];
    return o.status === "pendiente" ? ["cancelado"] : [];
  }

  async function changeStatus(id, status) {
    if (status === "cancelado" && !confirm(t("confirmCancel"))) return loadOrders();
    try {
      await request(`/pedidos/${id}/estado`, "PATCH", { status });
      toast(t("toastStatus") + " " + STATUS[status]);
    } catch (err) {
      toast(err.message);
    }
    await loadOrders();
    loadStats().catch(() => {});
  }

  function table(headers) {
    const t = el("table", null, "panel-table");
    const tr = el("tr");
    headers.forEach((h) => tr.append(el("th", h)));
    const thead = el("thead");
    thead.append(tr);
    t.append(thead);
    return t;
  }

  function renderOrders(list) {
    const box = $("#panelTable");
    box.replaceChildren();
    if (!list.length) return box.append(el("p", t("noOrders"), "empty-msg"));
    const tbl = table(["#", t("thDate"), t("thProduct"), t("thQty"), t("thTotal"), user.role === "comprador" ? t("thProducer") : t("thBuyer"), t("thStatus"), t("thAction")]);
    const body = el("tbody");
    list.forEach((o) => {
      const tr = el("tr");
      tr.append(el("td", o.id), el("td", fdate(o.created_at)));
      const prod = el("td", o.product_name);
      prod.append(el("small", money(o.unit_price) + " / " + o.unit));
      tr.append(prod, el("td", o.quantity), el("td", money(o.total)));
      const who = user.role === "comprador" ? o.producer?.name || "—" : o.user?.name || "—";
      const whoTd = el("td", who);
      if (user.role !== "comprador" && o.user?.contact) whoTd.append(el("small", o.user.contact));
      tr.append(whoTd, td_badge(o.status));
      const act = el("td");
      const opts = allowedStatuses(o);
      if (user.role === "comprador") {
        if (opts.length) {
          const b = el("button", t("cancelar"), "btn btn-ghost btn-small");
          b.type = "button";
          b.addEventListener("click", () => changeStatus(o.id, "cancelado"));
          act.append(b);
        } else act.append("—");
      } else if (opts.length) {
        const sel = el("select");
        sel.setAttribute("aria-label", t("ariaStatus") + " " + o.id);
        [...new Set([o.status, ...opts])].forEach((k) => {
          const op = el("option", STATUS[k]);
          op.value = k;
          op.selected = k === o.status;
          sel.append(op);
        });
        sel.addEventListener("change", () => changeStatus(o.id, sel.value));
        act.append(sel);
      } else act.append("—");
      tr.append(act);
      body.append(tr);
    });
    tbl.append(body);
    box.append(tbl);
  }
  function td_badge(status) {
    const td = el("td");
    td.append(el("span", STATUS[status], "st-badge " + status));
    return td;
  }

  function renderUsers(list) {
    const box = $("#panelTable");
    box.replaceChildren();
    const tbl = table(["#", t("thName"), t("thContact"), t("thRole"), t("thLocation"), t("thRegistered")]);
    const body = el("tbody");
    list.forEach((u) => {
      const tr = el("tr");
      tr.append(el("td", u.id), el("td", u.name), el("td", u.contact), el("td", t("rol_" + u.role)), el("td", u.location || "—"), el("td", fdate(u.created_at)));
      body.append(tr);
    });
    tbl.append(body);
    box.append(tbl);
  }

  async function loadOrders() {
    const st = $("#orderFilter").value;
    renderOrders(await request("/pedidos" + (st ? "?status=" + encodeURIComponent(st) : "")));
  }
  /* ---------- Dashboard (KPIs + gráficos SVG, sin librerías externas) ---------- */
  const SVG_NS = "http://www.w3.org/2000/svg";
  const svg = (tag, attrs = {}, text) => {
    const n = document.createElementNS(SVG_NS, tag);
    Object.entries(attrs).forEach(([k, v]) => n.setAttribute(k, v));
    if (text !== undefined) n.textContent = text;
    return n;
  };
  const chartCard = (title) => {
    const c = el("section", null, "chart-card");
    c.append(el("h4", title));
    return c;
  };

  /** Barras verticales: ventas de los últimos 14 días. */
  function chartDaily(days) {
    const card = chartCard(t("c_daily"));
    const W = 560, H = 190, pad = 26, max = Math.max(...days.map((d) => d.total), 1);
    const bw = (W - pad * 2) / days.length;
    const g = svg("svg", { viewBox: `0 0 ${W} ${H}`, class: "chart-svg", role: "img", "aria-label": t("c_daily") });
    days.forEach((d, i) => {
      const h = Math.round(((H - pad * 2) * d.total) / max);
      const x = pad + i * bw + 3;
      const bar = svg("rect", { x, y: H - pad - h, width: Math.max(bw - 6, 2), height: h, rx: 3, class: "bar" });
      bar.append(svg("title", {}, `${d.date}: ${money(d.total)}`));
      g.append(bar);
      if (i % 2 === 0) g.append(svg("text", { x: x + (bw - 6) / 2, y: H - 8, class: "axis", "text-anchor": "middle" }, d.date.slice(8) + "/" + d.date.slice(5, 7)));
    });
    g.append(svg("text", { x: pad, y: 14, class: "axis" }, money(max)));
    card.append(g);
    return card;
  }

  /** Barras horizontales (reutilizable): lista de {label, value}. */
  function chartBars(title, rows, fmt = (v) => v) {
    const card = chartCard(title);
    if (!rows.length || rows.every((r) => !r.value)) {
      card.append(el("p", t("c_empty"), "empty-msg"));
      return card;
    }
    const max = Math.max(...rows.map((r) => r.value), 1);
    const list = el("ul", null, "hbars");
    rows.forEach((r) => {
      const li = el("li");
      const head = el("div", null, "hbar-head");
      head.append(el("span", r.label), el("strong", fmt(r.value)));
      const track = el("div", null, "hbar-track");
      const fill = el("div", null, "hbar-fill " + (r.cls || ""));
      fill.style.width = Math.max((r.value / max) * 100, 2) + "%";
      track.append(fill);
      li.append(head, track);
      list.append(li);
    });
    card.append(list);
    return card;
  }

  async function loadStats() {
    const d = await request("/panel/dashboard");
    const box = $("#panelStats");
    box.replaceChildren();
    const card = (icon, n, label) => {
      const c = el("div", null, "stat-card");
      c.append(el("em", icon), el("strong", n), el("span", label));
      box.append(c);
    };
    const k = d.kpi;
    if (d.role === "gestor") card("📦", k.products, t("s_products"));
    if (d.role === "productor") card("📦", k.products, t("s_myproducts"));
    card("🧾", k.orders, t("s_orders"));
    card("⏳", k.pending, t("s_pending"));
    card("💰", money(k.sales), t("s_sales"));
    card("📈", money(k.average), t("s_avg"));
    if (d.role === "gestor") card("👥", k.producers + " / " + k.buyers, t("s_people"));

    const charts = $("#dashCharts");
    charts.replaceChildren();
    charts.append(chartDaily(d.sales_daily));
    charts.append(
      chartBars(t("c_status"), KEYS.map((s) => ({ label: STATUS[s], value: Number(d.by_status[s] || 0), cls: s })))
    );
    charts.append(
      chartBars(t("c_top"), d.top_products.map((p) => ({ label: p.product_name, value: Number(p.ventas) })), money)
    );
    charts.append(
      chartBars(
        t("c_category"),
        Object.entries(d.by_category).map(([c, v]) => ({ label: t("cat_" + c), value: Number(v) })),
        money
      )
    );
  }
  async function loadPanel() {
    const gestor = user.role === "gestor";
    $("#h-panel").textContent = t("panelH_" + user.role);
    $("#panelSub").textContent = t("panelSub_" + user.role);
    $("#tabUsuarios").hidden = !gestor;
    $("#linkProductos").hidden = !gestor;
    if (!gestor) panelTab = "pedidos";
    $("#orderFilter").hidden = panelTab !== "pedidos";
    if (panelTab === "pedidos") loadStats().catch((e) => toast(e.message));
    $("#dashCharts").hidden = $("#panelStats").hidden = $("#dashTableTitle").hidden = panelTab !== "pedidos";
    if (panelTab === "usuarios") renderUsers(await request("/panel/usuarios"));
    else await loadOrders();
  }
  function initPanel() {
    $("#orderFilter").addEventListener("change", () => loadOrders().catch((e) => toast(e.message)));
    $$("[data-ptab]").forEach((b) =>
      b.addEventListener("click", () => {
        panelTab = b.dataset.ptab;
        $$("[data-ptab]").forEach((x) => x.setAttribute("aria-pressed", String(x === b)));
        loadPanel().catch((e) => toast(e.message));
      }),
    );
  }
  function initVisual() {
    window.addEventListener("hashchange", navigate);
    navigate();
    $("#menuToggle").addEventListener("click", () => {
      const open = $("#navLinks").classList.toggle("open");
      $("#menuToggle").setAttribute("aria-expanded", String(open));
    });
    $$("[data-lang]").forEach((b) =>
      b.addEventListener("click", () => applyLang(b.dataset.lang)),
    );
    $$("[data-close-modal]").forEach((b) =>
      b.addEventListener("click", () =>
        $("#infoOverlay").classList.remove("open"),
      ),
    );
    $("#infoOverlay").addEventListener("click", (e) => {
      if (e.target.id === "infoOverlay")
        e.currentTarget.classList.remove("open");
    });
    $$("#heroCarousel .dot").forEach((b) =>
      b.addEventListener("click", () => setSlide(Number(b.dataset.slide))),
    );
    setInterval(() => setSlide((slide + 1) % 3), 4500);
  }
  function setSlide(i) {
    slide = i;
    $$("#heroCarousel .carousel-img").forEach((x, k) =>
      x.classList.toggle("show", k === i),
    );
    $$("#heroCarousel .dot").forEach((x, k) =>
      x.classList.toggle("active", k === i),
    );
  }
  document.addEventListener("DOMContentLoaded", async () => {
    updateUserUI();
    initVisual();
    if (lang !== "es") applyLang(lang, true);
    initAuth();
    initProducts();
    initPanel();
    try {
      await loadProducts();
    } catch (e) {
      toast(e.message);
    }
  });
})();
