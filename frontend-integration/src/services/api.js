// ─────────────────────────────────────────────────────────────────────────────
// src/services/api.js
// API service layer — replace mock data with real Laravel backend calls.
//
// SETUP:
//   1. Install axios:  npm install axios
//   2. Copy this file to src/services/api.js
//   3. Update your .env:  REACT_APP_API_URL=http://localhost:8000/api
//   4. Replace useAppState.js with the updated version (useAppState.api.js)
// ─────────────────────────────────────────────────────────────────────────────

import axios from "axios";

const API_BASE = process.env.REACT_APP_API_URL || "http://localhost:8000/api";

// ── Axios instance with default config ──────────────────────────────────────
const api = axios.create({
  baseURL: API_BASE,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

// ── Token management ────────────────────────────────────────────────────────
let authToken = localStorage.getItem("auth_token") || null;

export function setToken(token) {
  authToken = token;
  if (token) {
    localStorage.setItem("auth_token", token);
    api.defaults.headers.common["Authorization"] = `Bearer ${token}`;
  } else {
    localStorage.removeItem("auth_token");
    delete api.defaults.headers.common["Authorization"];
  }
}

// Restore token on load
if (authToken) {
  api.defaults.headers.common["Authorization"] = `Bearer ${authToken}`;
}

// ── Response interceptor for auth errors ────────────────────────────────────
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      setToken(null);
      window.location.reload();
    }
    return Promise.reject(error);
  }
);

// ─────────────────────────────────────────────────────────────────────────────
// AUTH
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Login with email + password
 */
export async function login(email, password) {
  const { data } = await api.post("/login", { email, password });
  setToken(data.token);
  return data.user;
}

/**
 * Quick demo login by user ID (matches the frontend's role-card login)
 */
export async function loginAs(userId) {
  const { data } = await api.post("/login-as", { user_id: userId });
  setToken(data.token);
  return data.user;
}

/**
 * Logout and revoke token
 */
export async function logout() {
  try {
    await api.post("/logout");
  } catch {
    // Token may already be invalid
  }
  setToken(null);
}

/**
 * Get current authenticated user
 */
export async function getUser() {
  const { data } = await api.get("/user");
  return data.user;
}

// ─────────────────────────────────────────────────────────────────────────────
// DASHBOARD
// ─────────────────────────────────────────────────────────────────────────────

export async function getDashboard() {
  const { data } = await api.get("/dashboard");
  return data;
}

// ─────────────────────────────────────────────────────────────────────────────
// EMPLOYEES
// ─────────────────────────────────────────────────────────────────────────────

export async function getEmployees(params = {}) {
  const { data } = await api.get("/employees", { params });
  return data.employees;
}

export async function getDepartments() {
  const { data } = await api.get("/departments");
  return data.departments;
}

// ─────────────────────────────────────────────────────────────────────────────
// LEAVE TYPES
// ─────────────────────────────────────────────────────────────────────────────

export async function getLeaveTypes() {
  const { data } = await api.get("/leave-types");
  return data.leaveTypes;
}

export async function updateLeaveType(id, updates) {
  const { data } = await api.put(`/leave-types/${id}`, updates);
  return data.leaveType;
}

// ─────────────────────────────────────────────────────────────────────────────
// LEAVE REQUESTS
// ─────────────────────────────────────────────────────────────────────────────

export async function getLeaves(params = {}) {
  const { data } = await api.get("/leaves", { params });
  return data.leaves;
}

export async function applyLeave(form) {
  const { data } = await api.post("/leaves", form);
  return data;
}

export async function cancelLeave(id) {
  const { data } = await api.put(`/leaves/${id}/cancel`);
  return data;
}

export async function reviewLeave(id, action, comment) {
  const { data } = await api.put(`/leaves/${id}/review`, { action, comment });
  return data;
}

// ─────────────────────────────────────────────────────────────────────────────
// LEAVE BALANCES
// ─────────────────────────────────────────────────────────────────────────────

export async function getBalances(params = {}) {
  const { data } = await api.get("/balances", { params });
  return data.balances;
}

// ─────────────────────────────────────────────────────────────────────────────
// HOLIDAYS
// ─────────────────────────────────────────────────────────────────────────────

export async function getHolidays(params = {}) {
  const { data } = await api.get("/holidays", { params });
  return data.holidays;
}

export async function addHoliday(holidayData) {
  const { data } = await api.post("/holidays", holidayData);
  return data.holiday;
}

export async function deleteHoliday(id) {
  const { data } = await api.delete(`/holidays/${id}`);
  return data;
}

// ─────────────────────────────────────────────────────────────────────────────
// REPORTS
// ─────────────────────────────────────────────────────────────────────────────

export async function getEmployeeReport(params = {}) {
  const { data } = await api.get("/reports/employee", { params });
  return data.report;
}

export async function getDepartmentReport() {
  const { data } = await api.get("/reports/department");
  return data.report;
}

export async function getMonthlyReport(params = {}) {
  const { data } = await api.get("/reports/monthly", { params });
  return data;
}

// ── Default export for convenience ──────────────────────────────────────────
export default api;
