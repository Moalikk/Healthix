const API_BASE = "http://" + (window.location.hostname || "127.0.0.1") + ":8000/api";
// Nama halaman. Kalau file diganti nama, ubah di sini saja.
const PAGES = {
  login: "login.html",
  citizen: "citizen-dashboard.html",
  staff: "staff-dashboard.html",
};

const TOKEN_KEY = "quickcare_token";
const USER_KEY = "quickcare_user";

function getToken() {
  try { return localStorage.getItem(TOKEN_KEY); } catch { return null; }
}

function getUser() {
  try { return JSON.parse(localStorage.getItem(USER_KEY)); } catch { return null; }
}

function saveSession(result) {
  try {
    localStorage.setItem(TOKEN_KEY, result.token);
    localStorage.setItem(USER_KEY, JSON.stringify(result.user));
  } catch {}
}

function clearSession() {
  try {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
  } catch {}
}

function goTo(page) {
  window.location.href = encodeURI(page);
}

// Fungsi dasar: semua request lewat sini.
async function apiRequest(path, { method = "GET", body = null, auth = true } = {}) {
  const headers = { "Accept": "application/json" };
  if (body) headers["Content-Type"] = "application/json";
  if (auth && getToken()) headers["Authorization"] = "Bearer " + getToken();

  let response;
  try {
    response = await fetch(API_BASE + path, {
      method,
      headers,
      body: body ? JSON.stringify(body) : null,
    });
  } catch {
    throw new Error("Cannot reach the server. Is 'php artisan serve' running?");
  }

  const result = await response.json().catch(() => ({}));

  if (response.status === 401 && auth) {
    // Token tidak berlaku lagi -> login ulang
    clearSession();
    goTo(PAGES.login);
    throw new Error("Please log in again.");
  }

  if (!response.ok) {
    // Laravel mengirim detail validasi di result.errors
    const firstError = result.errors ? Object.values(result.errors)[0][0] : null;
    throw new Error(firstError || result.message || "Request failed");
  }

  return result;
}

// ---- Auth ----
const register = (data) => apiRequest("/register", { method: "POST", body: data, auth: false });
const login = (data) => apiRequest("/login", { method: "POST", body: data, auth: false });
const logout = () => apiRequest("/logout", { method: "POST" });

// ---- Data ----
const getDepartments = () => apiRequest("/departments", { auth: false });
const getMyAppointments = () => apiRequest("/appointments");
const createAppointment = (data) => apiRequest("/appointments", { method: "POST", body: data });

// ---- Staff ----
// date (opsional, YYYY-MM-DD): tandai dokter yang cuti pada tanggal itu
const getDoctors = (departmentId, date) =>
  apiRequest("/doctors?department_id=" + departmentId + (date ? "&date=" + date : ""), { auth: false });
const getStaffAppointments = () => apiRequest("/staff/appointments");
const staffConfirm = (id, data) => apiRequest("/staff/appointments/" + id + "/confirm", { method: "PATCH", body: data });
const staffDecline = (id, note) => apiRequest("/staff/appointments/" + id + "/decline", { method: "PATCH", body: { staff_note: note } });
const staffCall = (id) => apiRequest("/staff/appointments/" + id + "/call", { method: "PATCH" });
const staffComplete = (id) => apiRequest("/staff/appointments/" + id + "/complete", { method: "PATCH" });

// ---- Saran departemen (chat) ----
const suggestDepartment = (message) => apiRequest("/suggest-department", { method: "POST", body: { message } });

// ---- Notifikasi ----
const getNotifications = () => apiRequest("/notifications");
const markNotificationRead = (id) => apiRequest("/notifications/" + id + "/read", { method: "PATCH" });
const markAllNotificationsRead = () => apiRequest("/notifications/read-all", { method: "PATCH" });