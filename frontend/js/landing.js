// QuickCare - tombol "Book a Visit / Request a Visit" di halaman depan
// diarahkan ke Citizen Portal. Kalau belum login, portal otomatis
// membawa pengguna ke login.html.
// Dimuat di quickcare-index.html SETELAH <script> bawaan halaman.

document.addEventListener("click", (event) => {
  if (!event.target.closest(".open-booking")) return;
  event.preventDefault();
  event.stopImmediatePropagation(); // hentikan modal demo bawaan
  window.location.href = "citizen-dashboard.html";
}, true);