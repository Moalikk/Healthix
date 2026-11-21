(function () {
  // 1. Wajib login sebagai staff
  const user = getUser();
  if (!getToken() || !user) { goTo(PAGES.login); return; }
  if (user.role !== "staff") { goTo(PAGES.citizen); return; }

  const $ = (id) => document.getElementById(id);
  const esc = (v) => String(v ?? "").replace(/[&<>"']/g, c =>
    ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

  let requests = [];

  // 2. Profil + tombol logout
  document.querySelector(".profile-name").textContent = user.name;
  document.querySelector(".profile-role").textContent = "Hospital staff";
  document.querySelector(".avatar").textContent =
    user.name.split(" ").map(w => w[0]).join("").slice(0, 2).toUpperCase();

  const logoutBtn = document.createElement("button");
  logoutBtn.className = "btn btn-light";
  logoutBtn.textContent = "Log out";
  logoutBtn.addEventListener("click", async () => {
    try { await logout(); } catch {}
    clearSession();
    goTo(PAGES.login);
  });
  const profileBox = document.querySelector(".topbar .profile");
  const rightSide = document.createElement("div");
  rightSide.style.cssText = "display:flex;align-items:center;gap:12px";
  profileBox.before(rightSide);
  rightSide.append(profileBox, logoutBtn);

  // 3. Rapikan teks "demo" yang sudah tidak berlaku
  document.querySelectorAll("#appointments .pill, #bottlenecks .pill").forEach(p => p.textContent = "LIVE DATA");
  document.querySelector("#appointments .muted:last-child").textContent =
    "Actions here update the database. Citizens see the new status on their dashboard.";
  const released = $("released-slots");
  if (released) released.style.display = "none";

  const slotStat = $("slotCount").closest(".stat");
  slotStat.querySelector(".stat-label").textContent = "In queue today";
  slotStat.querySelector(".stat-foot").textContent = "Confirmed or being called";
  $("statusFilter").innerHTML =
    '<option value="all">All statuses</option>' +
    ["pending", "confirmed", "called", "completed", "declined", "missed"]
      .map(s => `<option value="${s}">${s}</option>`).join("");

  // 4. Tampilan satu permohonan
  const PILL = { pending: "pill-amber", confirmed: "pill-green", called: "pill", completed: "pill-green", declined: "pill-red", missed: "pill-red" };

  function actionsFor(r) {
    if (r.status === "pending") {
      return `<button class="btn btn-primary" data-act="confirm" data-id="${r.id}">Confirm</button>
              <button class="btn btn-danger" data-act="decline" data-id="${r.id}">Decline</button>`;
    }
    if (r.status === "confirmed") return `<button class="btn btn-primary" data-act="call" data-id="${r.id}">Call patient</button>`;
    if (r.status === "called") return `<button class="btn btn-primary" data-act="complete" data-id="${r.id}">Mark completed</button>`;
    return "";
  }

  function rowHTML(r) {
    const urgent = r.priority === "urgent";
    const queue = r.queue_number ? ` · Queue #${r.queue_number}` : "";
    return `<article class="queue-row ${urgent ? "priority" : ""}">
      <span class="${urgent ? "priority-icon" : "avatar"}">${urgent ? "⚑" : "QC"}</span>
      <div class="queue-copy">
        <strong>#${r.id} · ${esc(r.department_name)} <span class="pill ${PILL[r.status] || ""}">${esc(r.status)}</span></strong>
        <p>Citizen: ${esc(r.patient_name)} · Date: ${esc(r.appointment_date)}${queue}<br>
        Complaint: ${esc(r.complaint || "-")}${r.staff_note ? "<br>Note: " + esc(r.staff_note) : ""}</p>
        <div class="actions">${actionsFor(r)}</div>
      </div></article>`;
  }

  // 5. Gambar ulang semua bagian halaman
  function renderStaff() {
    const active = (r) => !["completed", "declined", "missed"].includes(r.status);
    const urgentList = requests.filter(r => r.priority === "urgent" && active(r));

    $("reviewCount").textContent = urgentList.length;
    $("pendingCount").textContent = requests.filter(r => r.status === "pending").length;
    $("slotCount").textContent = requests.filter(r => ["confirmed", "called"].includes(r.status)).length;

    $("priorityList").innerHTML = urgentList.length
      ? urgentList.map(rowHTML).join("")
      : '<div class="empty">No urgent patients right now.</div>';

    const search = $("searchRequests").value.trim().toLowerCase();
    const filter = $("statusFilter").value;
    const filtered = requests.filter(r =>
      (!search || (r.id + " " + r.department_name + " " + r.patient_name).toLowerCase().includes(search)) &&
      (filter === "all" || r.status === filter));
    $("requestList").innerHTML = filtered.length
      ? filtered.map(rowHTML).join("")
      : '<div class="empty">No requests found.</div>';

    // Ringkasan per departemen (permohonan yang masih aktif)
    const counts = {};
    requests.filter(active).forEach(r => counts[r.department_name] = (counts[r.department_name] || 0) + 1);
    document.querySelectorAll("#bottlenecks .metric-row").forEach(el => el.remove());
    const rows = Object.entries(counts).map(([d, n]) => `<div class="metric-row"><span>${esc(d)}</span><strong>${n}</strong></div>`).join("")
      || '<div class="metric-row"><span>No active requests</span><strong>0</strong></div>';
    $("bottlenecks").insertAdjacentHTML("beforeend", rows);
  }

  async function reload(showMessage) {
    try {
      requests = (await getStaffAppointments()).data;
      renderStaff();
      if (showMessage) toast("List refreshed.");
    } catch (err) { toast(err.message); }
  }

  // 6. Dialog konfirmasi / penolakan
  const dialog = document.createElement("dialog");
  dialog.style.cssText = "border:0;border-radius:18px;padding:24px;max-width:380px;width:92%;box-shadow:0 20px 50px rgba(0,0,0,.25)";
  dialog.innerHTML = `<form method="dialog" id="actForm">
      <h3 id="dlgTitle" style="margin:0 0 14px"></h3>
      <div id="dlgDoctorBox"><label style="font-size:11px;font-weight:800">Assign doctor</label>
        <select id="dlgDoctor" style="width:100%;padding:10px;margin:6px 0 12px;border:1.5px solid var(--line);border-radius:12px"></select>
        <label style="display:flex;gap:8px;align-items:center;font-size:12px;margin-bottom:12px"><input type="checkbox" id="dlgUrgent"> Mark as urgent (staff decision)</label></div>
      <div id="dlgWarning" style="display:none;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:12px;padding:10px 12px;font-size:12px;margin-bottom:12px"></div>
      <label id="dlgNoteLabel" style="font-size:11px;font-weight:800"></label>
      <textarea id="dlgNote" rows="3" style="width:100%;padding:10px;margin:6px 0 14px;border:1.5px solid var(--line);border-radius:12px"></textarea>
      <div class="actions" style="justify-content:flex-end">
        <button type="button" class="btn btn-light" id="dlgCancel">Cancel</button>
        <button type="submit" class="btn btn-primary" id="dlgOk">OK</button></div></form>`;
  document.body.append(dialog);
  $("dlgCancel").addEventListener("click", () => dialog.close());

  let pending = null; // { mode, id }

  async function openDialog(mode, id) {
    const r = requests.find(x => x.id === id);
    pending = { mode, id };
    $("dlgNote").value = "";
    $("dlgUrgent").checked = false;
    const isConfirm = mode === "confirm";
    $("dlgTitle").textContent = (isConfirm ? "Confirm request #" : "Decline request #") + id;
    $("dlgDoctorBox").style.display = isConfirm ? "block" : "none";
    $("dlgNoteLabel").textContent = isConfirm ? "Note for the citizen (optional)" : "Reason (required)";
    $("dlgNote").required = !isConfirm;
    $("dlgOk").textContent = isConfirm ? "Confirm" : "Decline";

    $("dlgWarning").style.display = "none";
    $("dlgOk").disabled = false;

    if (isConfirm) {
      try {
        // Kirim tanggal kunjungan supaya dokter yang cuti ditandai
        const doctors = (await getDoctors(r.department_id, r.appointment_date)).data;
        const available = doctors.filter(d => d.is_available);

        $("dlgDoctor").innerHTML = doctors.map(d =>
          `<option value="${d.id}" ${d.is_available ? "" : "disabled"}>${esc(d.name)}${d.specialization ? " · " + esc(d.specialization) : ""}${d.is_available ? "" : " — on leave (" + esc(d.leave_reason) + ")"}</option>`
        ).join("");

        if (doctors.length === 0) {
          $("dlgWarning").textContent = "This department has no active doctor. You cannot confirm this request. Please decline it with a reason.";
        } else if (available.length === 0) {
          $("dlgWarning").textContent = "No doctor is available on " + r.appointment_date + ". You cannot confirm this request. Please decline it and ask the citizen to choose another date.";
        } else {
          $("dlgDoctor").value = available[0].id;
        }

        if (available.length === 0) {
          $("dlgWarning").style.display = "block";
          $("dlgOk").disabled = true;
        }
      } catch (err) { toast(err.message); return; }
    }
    dialog.showModal();
  }

  $("actForm").addEventListener("submit", async () => {
    const { mode, id } = pending;
    const note = $("dlgNote").value.trim();
    try {
      if (mode === "confirm") {
        await staffConfirm(id, {
          doctor_id: Number($("dlgDoctor").value),
          priority: $("dlgUrgent").checked ? "urgent" : "routine",
          staff_note: note || null,
        });
        toast("Request confirmed. Queue number assigned.");
      } else {
        await staffDecline(id, note);
        toast("Request declined.");
      }
    } catch (err) { toast(err.message); }
    reload();
  });

  // 7. Tombol di daftar permohonan
  document.addEventListener("click", async (event) => {
    const btn = event.target.closest("[data-act]");
    if (!btn) return;
    const id = Number(btn.dataset.id);
    const act = btn.dataset.act;
    if (act === "confirm" || act === "decline") return openDialog(act, id);
    try {
      if (act === "call") { await staffCall(id); toast("Patient called. They have 10 minutes."); }
      if (act === "complete") { await staffComplete(id); toast("Marked as completed."); }
    } catch (err) { toast(err.message); }
    reload();
  });

  // Cari, filter, refresh (dipasang setelah milik halaman, jadi hasil akhirnya milik kita)
  $("searchRequests").addEventListener("input", renderStaff);
  $("statusFilter").addEventListener("change", renderStaff);
  $("refreshButton").addEventListener("click", () => reload(true));

  reload();

  // Segarkan otomatis tiap 10 detik (hanya saat tab terlihat dan dialog tertutup)
  setInterval(() => { if (!document.hidden && !dialog.open) reload(); }, 10000);
  document.addEventListener("visibilitychange", () => { if (!document.hidden) reload(); });
})();