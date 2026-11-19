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

  loadDepartments();
  loadAppointments();
})();