(function () {
  // 1. Wajib login
  const user = getUser();
  if (!getToken() || !user) {
    goTo(PAGES.login);
    return;
  }

  // 2. Tampilkan nama user yang login
  const nameEl = document.querySelector(".profile-name");
  const avatarEl = document.querySelector(".avatar");
  if (nameEl) nameEl.textContent = user.name;
  if (avatarEl) avatarEl.textContent = user.name.split(" ").map(w => w[0]).join("").slice(0, 2).toUpperCase();

  // 3. Tombol logout (disisipkan di sebelah tombol notifikasi)
  const logoutBtn = document.createElement("button");
  logoutBtn.className = "icon-btn";
  logoutBtn.title = "Log out";
  logoutBtn.textContent = "⎋";
  logoutBtn.addEventListener("click", async () => {
    try { await logout(); } catch {}
    clearSession();
    goTo(PAGES.login);
  });
  const notifBtn = document.getElementById("notificationButton");
  if (notifBtn) notifBtn.after(logoutBtn);

  // 4. Form booking: nama/email dari akun, jam dihilangkan (diatur staff)
  const nameInput = document.getElementById("demoName");
  const emailInput = document.getElementById("demoEmail");
  nameInput.value = user.name;
  emailInput.value = user.email;
  nameInput.readOnly = true;
  emailInput.readOnly = true;

  const timeSelect = document.getElementById("demoTime");
  timeSelect.required = false;
  timeSelect.closest(".field").style.display = "none";

  // 5. Isi pilihan departemen dari database
  async function loadDepartments() {
    const select = document.getElementById("demoDepartment");
    try {
      const result = await getDepartments();
      select.innerHTML = '<option value="">Choose a department</option>';
      result.data.forEach(d => {
        const opt = document.createElement("option");
        opt.value = d.id;
        opt.textContent = d.name + " · " + d.hospital_name;
        select.appendChild(opt);
      });
    } catch (err) {
      showToast(err.message);
    }
  }

  // 6. Tampilkan permohonan milik user dari database
  const STATUS_LABEL = {
    pending: ["pill-pending", "◷ Waiting for staff review"],
    confirmed: ["pill-confirmed", "● Confirmed"],
    called: ["pill-confirmed", "● Called"],
    completed: ["pill-confirmed", "✓ Completed"],
    declined: ["pill-pending", "✕ Declined"],
    missed: ["pill-pending", "✕ Missed"],
  };

  async function loadAppointments() {
    const list = document.getElementById("appointmentList");
    try {
      const result = await getMyAppointments();
      const items = result.data;

      document.getElementById("appointmentCount").textContent = items.length;
      document.getElementById("pendingCount").textContent = items.filter(a => a.status === "pending").length;

      if (items.length === 0) {
        list.innerHTML = '<p class="modal-note">You have no appointment requests yet.</p>';
        return;
      }

      list.innerHTML = "";
      items.forEach(a => {
        const d = new Date(a.appointment_date + "T12:00:00");
        const [pillClass, pillText] = STATUS_LABEL[a.status] || ["pill-pending", a.status];
        const queue = a.queue_number ? " · Queue #" + a.queue_number : "";

        const card = document.createElement("article");
        card.className = "appointment";
        card.innerHTML =
          '<div class="datebox"><strong></strong><span></span></div>' +
          '<div class="appt-main"><strong></strong><span></span></div>' +
          '<span class="pill"></span>';
        card.querySelector(".datebox strong").textContent = d.getDate();
        card.querySelector(".datebox span").textContent = d.toLocaleString("en", { month: "short" }).toUpperCase();
        card.querySelector(".appt-main strong").textContent = a.department_name;
        card.querySelector(".appt-main span").textContent = "Request #" + a.id + queue;
        const pill = card.querySelector(".pill");
        pill.classList.add(pillClass);
        pill.textContent = pillText;
        list.appendChild(card);
      });
    } catch (err) {
      showToast(err.message);
    }
  }

  // 7. Kirim form ke API (menggantikan handler demo bawaan halaman)
  // Listener ini dipasang di fase capture supaya jalan lebih dulu,
  // lalu stopImmediatePropagation() mencegah handler demo ikut berjalan.
  document.addEventListener("submit", async (event) => {
    if (event.target.id !== "bookingForm") return;
    event.preventDefault();
    event.stopImmediatePropagation();

    const button = event.target.querySelector('button[type="submit"]');
    button.disabled = true;

    try {
      await createAppointment({
        department_id: Number(document.getElementById("demoDepartment").value),
        appointment_date: document.getElementById("demoDate").value,
        complaint: document.getElementById("demoNote").value || null,
      });
      event.target.reset();
      nameInput.value = user.name;
      emailInput.value = user.email;
      closeBooking();
      showToast("Request sent. Staff will review it soon.");
      await loadAppointments();
    } catch (err) {
      showToast(err.message);
    } finally {
      button.disabled = false;
    }
  }, true);

  // Teks "demo only" di form sudah tidak benar lagi
  document.querySelectorAll("#bookingForm .modal-note").forEach(p => {
    if (p.textContent.includes("Demo only")) {
      p.textContent = "Your request is sent to the hospital staff for review. This is not a confirmed appointment yet.";
    }
  });
  document.querySelector("#bookingForm button[type=submit]").textContent = "Send request";

  // 8. Chat: kirim keluhan ke backend, tampilkan saran departemen
  function openBookingFor(departmentId, complaint) {
    openBooking(); // fungsi bawaan halaman
    document.getElementById("demoDepartment").value = String(departmentId);
    if (complaint) document.getElementById("demoNote").value = complaint;
  }

  function addSuggestionBubble(data, question) {
    const bubble = document.createElement("div");
    bubble.className = "chat-bubble bot";

    const text = document.createElement("div");
    text.textContent = data.confidence === "low"
      ? data.reason + " (" + data.hospital_name + ")"
      : "Suggested: " + data.department_name + " (" + data.hospital_name + "). " + data.reason + ".";
    const note = document.createElement("div");
    note.style.cssText = "font-size:10px;opacity:.75;margin-top:4px";
    note.textContent = data.disclaimer;

    const button = document.createElement("button");
    button.type = "button";
    button.className = "btn btn-primary";
    button.style.cssText = "margin-top:8px;padding:8px 12px";
    button.textContent = "Book " + data.department_name + " →";
    button.addEventListener("click", () => openBookingFor(data.department_id, question));

    bubble.append(text, note, button);
    document.getElementById("chatMessages").appendChild(bubble);
    document.getElementById("chatMessages").scrollTop = 1e6;
  }

  async function sendChatMessage() {
    const input = document.getElementById("chatQuestion");
    const sendBtn = document.getElementById("sendChat");
    const question = input.value.trim();
    const lang = document.getElementById("chatLanguage").value;

    if (question.length < 3) { showToast("Please describe your question or complaint."); input.focus(); return; }

    addChatBubble(question, "user"); // fungsi bawaan halaman
    input.value = "";
    sendBtn.disabled = true;

    try {
      const { data } = await suggestDepartment(question);

      if (data.emergency) {
        // Teks darurat bawaan halaman sudah tersedia dalam 5 bahasa
        addChatBubble(lang === "en" ? data.reason : demoHealthReply("chest pain", lang) + " (112)", "bot");
      } else {
        addSuggestionBubble(data, question);
      }
    } catch (err) {
      addChatBubble("Sorry, I could not process that: " + err.message, "bot");
    } finally {
      sendBtn.disabled = false;
    }
  }

  // Dipasang di fase capture supaya menggantikan handler demo bawaan halaman
  document.addEventListener("click", (event) => {
    if (!event.target.closest("#sendChat")) return;
    event.stopImmediatePropagation();
    sendChatMessage();
  }, true);

  document.addEventListener("keydown", (event) => {
    if (event.target.id !== "chatQuestion" || event.key !== "Enter") return;
    event.preventDefault();
    event.stopImmediatePropagation();
    sendChatMessage();
  }, true);

  // Label kolom catatan sekarang berisi keluhan singkat
  const noteLabel = document.querySelector('label[for="demoNote"]');
  if (noteLabel) noteLabel.textContent = "Your complaint (short)";

  // Buka form dengan departemen terpilih lewat link: citizen-dashboard.html?department=2
  function openFromUrl() {
    const id = new URLSearchParams(location.search).get("department");
    if (id) openBookingFor(id, "");
  }

  // 9. Notifikasi dari backend + lencana di ikon lonceng
  const NOTICE_ICONS = {
    "Request confirmed": "✓",
    "Request declined": "✕",
    "It's your turn": "!",
    "Moved to the end of the queue": "↻",
    "Request marked as missed": "✕",
    "Visit completed": "✓",
  };

  const bell = document.getElementById("notificationButton");
  bell.style.position = "relative";
  const badge = document.createElement("span");
  badge.style.cssText = "position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;padding:0 5px;" +
    "border-radius:9px;background:#dc2626;color:#fff;font-size:10px;font-weight:800;" +
    "display:none;align-items:center;justify-content:center";
  bell.append(badge);

  const noticePill = document.querySelector("#notifications .pill");
  if (noticePill) noticePill.textContent = "LIVE";
  const noticeNote = document.querySelector("#notifications > .modal-note");
  if (noticeNote) noticeNote.textContent = "Updates appear here when staff confirm, decline or call your request.";

  function formatTime(value) {
    const date = new Date(String(value).replace(" ", "T"));
    return isNaN(date) ? "" : date.toLocaleString("en-GB", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" });
  }

  async function loadNotifications() {
    const panel = document.getElementById("notificationList");
    try {
      const result = await getNotifications();

      badge.textContent = result.unread_count > 99 ? "99+" : result.unread_count;
      badge.style.display = result.unread_count > 0 ? "flex" : "none";

      if (result.data.length === 0) {
        panel.innerHTML = '<p class="modal-note">No notifications yet.</p>';
        return;
      }

      panel.innerHTML = "";
      result.data.forEach(n => {
        const item = document.createElement("article");
        item.className = "notification-item";
        item.innerHTML = '<span class="notice-icon"></span><div><strong></strong><p></p><p style="margin-top:4px;font-size:10px"></p></div>';
        item.querySelector(".notice-icon").textContent = NOTICE_ICONS[n.title] || "ⓘ";
        item.querySelector("strong").textContent = n.title;
        const lines = item.querySelectorAll("p");
        lines[0].textContent = n.message;
        lines[1].textContent = formatTime(n.created_at);

        if (!n.read_at) {
          // Belum dibaca: disorot, klik untuk menandai terbaca
          item.style.cssText = "border-color:var(--primary);background:var(--primary-light);cursor:pointer";
          item.addEventListener("click", async () => {
            try { await markNotificationRead(n.id); } catch {}
            loadNotifications();
          });
        }
        panel.appendChild(item);
      });
    } catch (err) {
      // Diam saja: fungsi ini juga dipanggil otomatis tiap 30 detik
    }
  }

  // Klik lonceng: gulir ke daftar notifikasi, lalu tandai semua terbaca
  document.addEventListener("click", (event) => {
    if (!event.target.closest("#notificationButton")) return;
    event.stopImmediatePropagation();
    document.getElementById("notifications").scrollIntoView({ behavior: "smooth" });
    setTimeout(async () => {
      try { await markAllNotificationsRead(); } catch {}
      loadNotifications();
    }, 1500);
  }, true);

  // Segarkan otomatis tiap 30 detik (status permohonan + notifikasi)
  setInterval(() => { loadAppointments(); loadNotifications(); }, 30000);

  loadDepartments().then(openFromUrl);
  loadAppointments();
  loadNotifications();
})();