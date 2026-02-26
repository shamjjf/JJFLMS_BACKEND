// ─────────────────────────────────────────────────────────────────────────────
// src/hooks/useAppState.js (UPDATED — uses Laravel API backend)
//
// Drop-in replacement for the original useAppState.js.
// Fetches all data from the Laravel API and keeps local state in sync.
//
// MIGRATION STEPS:
//   1. Copy src/services/api.js to your project
//   2. Replace your existing src/hooks/useAppState.js with this file
//   3. Install axios: npm install axios
//   4. Set REACT_APP_API_URL=http://localhost:8000/api in your .env
// ─────────────────────────────────────────────────────────────────────────────

import { useState, useEffect, useCallback } from "react";
import * as api from "../services/api";

export function useAppState() {
  const [user, setUser] = useState(null);
  const [activeTab, setActiveTab] = useState("dashboard");
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [loading, setLoading] = useState(true);

  const [leaves, setLeaves] = useState([]);
  const [holidays, setHolidays] = useState([]);
  const [employees, setEmployees] = useState([]);
  const [balances, setBalances] = useState({});

  // ── Load all data after login ───────────────────────────────────────────────
  const fetchAllData = useCallback(async () => {
    try {
      const [leavesData, holidaysData, employeesData, balancesData] =
        await Promise.all([
          api.getLeaves(),
          api.getHolidays(),
          api.getEmployees(),
          api.getBalances(),
        ]);

      setLeaves(leavesData);
      setHolidays(holidaysData);
      setEmployees(employeesData);
      setBalances(balancesData);
    } catch (err) {
      console.error("Failed to fetch data:", err);
    }
  }, []);

  // ── Check for existing session on mount ─────────────────────────────────────
  useEffect(() => {
    const token = localStorage.getItem("auth_token");
    if (token) {
      api
        .getUser()
        .then((userData) => {
          setUser(userData);
          return fetchAllData();
        })
        .catch(() => {
          localStorage.removeItem("auth_token");
        })
        .finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, [fetchAllData]);

  // ── Auth ────────────────────────────────────────────────────────────────────
  const login = async (selectedUser) => {
    try {
      // Use the demo quick-login (loginAs) matching the frontend's role-card flow
      const userData = await api.loginAs(selectedUser.id);
      setUser(userData);
      setActiveTab("dashboard");
      await fetchAllData();
    } catch (err) {
      console.error("Login failed:", err);
      alert("Login failed. Please check the backend is running.");
    }
  };

  const logout = async () => {
    try {
      await api.logout();
    } catch {
      // Ignore errors
    }
    setUser(null);
    setActiveTab("dashboard");
    setLeaves([]);
    setHolidays([]);
    setEmployees([]);
    setBalances({});
  };

  // ── Leave actions ────────────────────────────────────────────────────────────
  const submitLeave = async (form) => {
    try {
      const result = await api.applyLeave({
        leaveType: form.leaveType,
        startDate: form.startDate,
        endDate: form.endDate,
        reason: form.reason,
      });

      if (result.success) {
        // Refresh leaves and balances
        const [leavesData, balancesData] = await Promise.all([
          api.getLeaves(),
          api.getBalances(),
        ]);
        setLeaves(leavesData);
        setBalances(balancesData);
      }

      return result;
    } catch (err) {
      const message =
        err.response?.data?.message || "Failed to submit leave application.";
      alert(message);
      return { success: false, message };
    }
  };

  const cancelLeave = async (id) => {
    try {
      await api.cancelLeave(id);
      // Update local state
      setLeaves((prev) =>
        prev.map((l) => (l.id === id ? { ...l, status: "cancelled" } : l))
      );
    } catch (err) {
      console.error("Cancel failed:", err);
      alert(err.response?.data?.message || "Failed to cancel leave.");
    }
  };

  const reviewLeave = async (leaveId, action, comment, reviewerId) => {
    try {
      await api.reviewLeave(leaveId, action, comment);
      // Refresh leaves and balances (balance changes on approval)
      const [leavesData, balancesData] = await Promise.all([
        api.getLeaves(),
        api.getBalances(),
      ]);
      setLeaves(leavesData);
      setBalances(balancesData);
    } catch (err) {
      console.error("Review failed:", err);
      alert(err.response?.data?.message || "Failed to review leave.");
    }
  };

  // ── Holiday actions ──────────────────────────────────────────────────────────
  const addHoliday = async (holidayData) => {
    try {
      const newHoliday = await api.addHoliday(holidayData);
      setHolidays((prev) => [...prev, newHoliday]);
    } catch (err) {
      console.error("Add holiday failed:", err);
      alert(err.response?.data?.message || "Failed to add holiday.");
    }
  };

  const deleteHoliday = async (id) => {
    try {
      await api.deleteHoliday(id);
      setHolidays((prev) => prev.filter((h) => h.id !== id));
    } catch (err) {
      console.error("Delete holiday failed:", err);
      alert(err.response?.data?.message || "Failed to delete holiday.");
    }
  };

  // ── Derived values ───────────────────────────────────────────────────────────
  const pendingCount = leaves.filter((l) => l.status === "pending").length;

  return {
    // State
    user,
    activeTab,
    sidebarOpen,
    leaves,
    holidays,
    employees,
    balances,
    pendingCount,
    loading, // NEW: use this to show a loading spinner on initial load

    // Navigation
    setActiveTab,
    setSidebarOpen,

    // Auth
    login,
    logout,

    // Leave actions
    submitLeave,
    cancelLeave,
    reviewLeave,

    // Holiday actions
    addHoliday,
    deleteHoliday,
  };
}
