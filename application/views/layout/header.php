<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= !empty($settings->dark_mode) ? 'dark' : 'light' ?>">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title ?? ($settings->crm_name ?? 'StayFlow CRM')) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <style>
    /* SweetAlert & Pagination Styles */
    .swal2-popup {
      font-family: 'Poppins', sans-serif !important;
      border-radius: 20px !important;
      padding: 24px !important;
    }

    .swal2-title {
      font-size: 20px !important;
      font-weight: 700 !important;
    }

    .swal2-confirm {
      background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
      border-radius: 10px !important;
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3) !important;
    }

    .swal2-cancel {
      border-radius: 10px !important;
    }

    body.dark .swal2-popup {
      background: #1e293b !important;
      color: #f1f5f9 !important;
      border: 1px solid #334155 !important;
    }

    body.dark .swal2-title,
    body.dark .swal2-html-container {
      color: #f1f5f9 !important;
    }

    .pagination .page-link {
      color: var(--text);
      background-color: var(--card);
      border-color: var(--border);
      padding: 6px 12px;
      font-size: 13px;
      font-weight: 500;
      transition: all 0.2s ease;
      cursor: pointer;
    }

    .pagination .page-link:hover {
      background-color: rgba(99, 102, 241, 0.12);
      border-color: var(--primary);
      color: var(--primary);
    }

    .pagination .page-item.active .page-link {
      background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
      border-color: transparent !important;
      color: #fff !important;
      box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
    }

    .pagination .page-item.disabled .page-link {
      background-color: transparent;
      border-color: transparent;
      color: var(--muted);
      cursor: default;
    }

    :root {
      --primary: #6366f1;
      --secondary: #8b5cf6;
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
      --bg: #f8fafc;
      --card: #ffffff;
      --sidebar: #1e1b4b;
      --sidebar-text: #c7d2fe;
      --text: #0f172a;
      --muted: #64748b;
      --border: #e2e8f0;
      --shadow: 0 10px 30px rgba(99, 102, 241, 0.08);
      --topbar-h: 64px;
      --sidebar-w: 260px;
      --sidebar-mini: 80px;
    }

    body.dark,
    [data-bs-theme="dark"] {
      --primary: #6366f1;
      --secondary: #8b5cf6;
      --bg: #090e1a;
      --card: #131b2e;
      --sidebar: #080c16;
      --sidebar-text: #cbd5e1;
      --text: #f8fafc;
      --muted: #94a3b8;
      --border: #243049;
      --shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      --bs-body-color: #f8fafc;
      --bs-body-bg: #090e1a;
      --bs-emphasis-color: #ffffff;
      --bs-secondary-color: #94a3b8;
      --bs-tertiary-color: #64748b;
      --bs-secondary-bg: #1c263d;
      --bs-tertiary-bg: #131b2e;
      --bs-border-color: #243049;
    }

    /* =========================================================
       COMPREHENSIVE DARK MODE SYSTEM - HIGH CONTRAST & READABLE
       ========================================================= */
    body.dark {
      background: var(--bg) !important;
      color: var(--text) !important;
    }

    /* Headings, titles, labels and text */
    body.dark h1,
    body.dark h2,
    body.dark h3,
    body.dark h4,
    body.dark h5,
    body.dark h6,
    body.dark .section-title,
    body.dark strong,
    body.dark b,
    body.dark .text-body {
      color: #f8fafc !important;
    }

    /* Fix .text-muted across all pages (Dashboard, Profile, Tables, Cards, Receipts) */
    body.dark .text-muted,
    body.dark [class*="text-muted"],
    body.dark small.text-muted,
    body.dark .text-secondary {
      --bs-text-opacity: 1 !important;
      color: #94a3b8 !important;
    }

    /* Fix .text-dark and .text-dark-emphasis (prevent black text on dark background) */
    body.dark .text-dark,
    body.dark .text-dark-emphasis,
    body.dark .text-black,
    body.dark .text-black-50 {
      color: #f8fafc !important;
    }

    /* Links */
    body.dark a:not(.btn):not(.nav-link-item):not(.dropdown-item) {
      color: #a5b4fc;
    }

    body.dark a:not(.btn):not(.nav-link-item):not(.dropdown-item):hover {
      color: #c7d2fe;
    }

    body.dark a.text-reset {
      color: #f8fafc !important;
    }

    /* Stat Cards & Lift Cards */
    body.dark .stat-card {
      background: var(--card) !important;
      border: 1px solid var(--border) !important;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35) !important;
      color: var(--text) !important;
    }

    body.dark .stat-card:hover {
      box-shadow: 0 14px 35px rgba(0, 0, 0, 0.5) !important;
      border-color: rgba(99, 102, 241, 0.4) !important;
    }

    body.dark .glass {
      background: linear-gradient(135deg, rgba(19, 27, 46, 0.95), rgba(30, 27, 75, 0.55)) !important;
      border: 1px solid rgba(99, 102, 241, 0.25) !important;
      backdrop-filter: blur(12px);
    }

    body.dark .card-lift {
      background: var(--card) !important;
      border: 1px solid var(--border) !important;
    }

    body.dark .card-lift:hover {
      background: #19233c !important;
      border-color: rgba(99, 102, 241, 0.4) !important;
    }

    /* Topbar & Header */
    body.dark .topbar {
      background: #0d1527 !important;
      border-bottom: 1px solid var(--border) !important;
      color: #f8fafc !important;
    }

    body.dark #top-crm-name,
    body.dark #header-admin-name {
      color: #f8fafc !important;
    }

    body.dark .user-dropdown-btn {
      color: #f8fafc !important;
    }

    body.dark #btn-theme,
    body.dark #btn-bell {
      border-color: var(--border) !important;
      color: #cbd5e1 !important;
      background: rgba(255, 255, 255, 0.04);
    }

    body.dark #btn-theme:hover,
    body.dark #btn-bell:hover {
      background: rgba(99, 102, 241, 0.2) !important;
      color: #ffffff !important;
      border-color: var(--primary) !important;
    }

    /* Search Input */
    body.dark .search-wrap input {
      background: #070b14 !important;
      border-color: var(--border) !important;
      color: #f8fafc !important;
    }

    body.dark .search-wrap input::placeholder {
      color: #64748b !important;
    }

    body.dark .search-wrap i {
      color: #64748b !important;
    }

    body.dark .search-results {
      background: var(--card) !important;
      border-color: var(--border) !important;
      box-shadow: 0 14px 35px rgba(0, 0, 0, 0.55) !important;
    }

    body.dark .search-results .item:hover {
      background: #090e1a !important;
    }

    body.dark .search-results .item .fw-semibold {
      color: #f8fafc !important;
    }

    /* Form Controls, Inputs, Textareas, Selects */
    body.dark .form-label {
      color: #e2e8f0 !important;
      font-weight: 500;
    }

    body.dark .form-control,
    body.dark .form-select,
    body.dark textarea {
      background-color: #090e1a !important;
      border: 1px solid var(--border) !important;
      color: #f8fafc !important;
    }

    body.dark .form-control:focus,
    body.dark .form-select:focus,
    body.dark textarea:focus {
      background-color: #0e1526 !important;
      border-color: var(--primary) !important;
      color: #ffffff !important;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25) !important;
    }

    body.dark .form-control::placeholder,
    body.dark textarea::placeholder,
    body.dark input::placeholder {
      color: #64748b !important;
      opacity: 1 !important;
    }

    body.dark .form-control:disabled,
    body.dark .form-control[readonly],
    body.dark .form-select:disabled,
    body.dark .bg-body-secondary {
      background-color: #162035 !important;
      border-color: var(--border) !important;
      color: #94a3b8 !important;
      opacity: 0.95 !important;
    }

    /* Tables */
    body.dark table.table {
      color: var(--text) !important;
      border-color: var(--border) !important;
    }

    body.dark .table> :not(caption)>*>* {
      background-color: transparent !important;
      color: var(--text) !important;
      border-color: var(--border) !important;
    }

    body.dark .table thead tr th {
      color: #cbd5e1 !important;
      background-color: rgba(7, 11, 20, 0.6) !important;
      border-bottom: 2px solid var(--border) !important;
      font-weight: 600;
    }

    body.dark .table tbody tr:hover {
      background-color: rgba(99, 102, 241, 0.08) !important;
    }

    /* Badges */
    body.dark .badge-available,
    body.dark .badge-paid {
      background: rgba(16, 185, 129, 0.16) !important;
      color: #34d399 !important;
      border: 1px solid rgba(16, 185, 129, 0.35) !important;
    }

    body.dark .badge-occupied,
    body.dark .badge-unpaid {
      background: rgba(239, 68, 68, 0.16) !important;
      color: #f87171 !important;
      border: 1px solid rgba(239, 68, 68, 0.35) !important;
    }

    body.dark .badge-maintenance,
    body.dark .badge-pending {
      background: rgba(245, 158, 11, 0.16) !important;
      color: #fbbf24 !important;
      border: 1px solid rgba(245, 158, 11, 0.35) !important;
    }

    body.dark .badge.text-bg-secondary {
      background-color: #1e293b !important;
      color: #cbd5e1 !important;
      border: 1px solid #334155 !important;
    }

    body.dark .badge.text-bg-light,
    body.dark #badge-pass-optional {
      background-color: #162035 !important;
      color: #cbd5e1 !important;
      border: 1px solid var(--border) !important;
    }

    /* Modals */
    body.dark .modal-content {
      background-color: var(--card) !important;
      border: 1px solid var(--border) !important;
      color: var(--text) !important;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.65) !important;
    }

    body.dark .modal-header,
    body.dark .modal-footer {
      border-color: var(--border) !important;
    }

    body.dark .modal-title {
      color: #f8fafc !important;
    }

    body.dark .btn-close {
      filter: invert(1) grayscale(100%) brightness(200%) !important;
    }

    /* Dropdowns */
    body.dark .dropdown-menu {
      background-color: var(--card) !important;
      border: 1px solid var(--border) !important;
      box-shadow: 0 14px 35px rgba(0, 0, 0, 0.55) !important;
    }

    body.dark .dropdown-item {
      color: #e2e8f0 !important;
    }

    body.dark .dropdown-item:hover,
    body.dark .dropdown-item:focus {
      background: rgba(99, 102, 241, 0.2) !important;
      color: #ffffff !important;
    }

    body.dark .dropdown-divider {
      border-color: var(--border) !important;
    }

    /* Notifications Dropdown */
    body.dark .notif-drop {
      background: var(--card) !important;
      border: 1px solid var(--border) !important;
      box-shadow: 0 14px 35px rgba(0, 0, 0, 0.55) !important;
    }

    body.dark .notif-drop .item {
      border-bottom: 1px solid var(--border) !important;
    }

    body.dark .notif-drop .item:hover {
      background: #090e1a !important;
    }

    /* Pagination */
    body.dark .pagination .page-link {
      background-color: var(--card) !important;
      border-color: var(--border) !important;
      color: #cbd5e1 !important;
    }

    body.dark .pagination .page-link:hover {
      background-color: rgba(99, 102, 241, 0.22) !important;
      border-color: var(--primary) !important;
      color: #ffffff !important;
    }

    body.dark .pagination .page-item.active .page-link {
      background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
      color: #ffffff !important;
      border-color: transparent !important;
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4) !important;
    }

    body.dark .pagination .page-item.disabled .page-link {
      background-color: transparent !important;
      border-color: transparent !important;
      color: #475569 !important;
    }

    /* Buttons */
    body.dark .btn-outline-secondary {
      border-color: #334155 !important;
      color: #cbd5e1 !important;
      background: rgba(255, 255, 255, 0.02);
    }

    body.dark .btn-outline-secondary:hover {
      background-color: #1e293b !important;
      border-color: #475569 !important;
      color: #ffffff !important;
    }

    body.dark .btn-outline-primary {
      border-color: var(--primary) !important;
      color: #a5b4fc !important;
    }

    body.dark .btn-outline-primary:hover {
      background-color: var(--primary) !important;
      color: #ffffff !important;
    }

    body.dark .btn-outline-danger {
      border-color: var(--danger) !important;
      color: #fca5a5 !important;
    }

    body.dark .btn-outline-danger:hover {
      background-color: var(--danger) !important;
      color: #ffffff !important;
    }

    /* Nav Pills */
    body.dark .nav-pills .nav-link {
      color: #94a3b8 !important;
    }

    body.dark .nav-pills .nav-link:hover {
      color: #f1f5f9 !important;
      background-color: rgba(99, 102, 241, 0.12) !important;
    }

    body.dark .nav-pills .nav-link.active {
      background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
      color: #ffffff !important;
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3) !important;
    }

    /* Progress & Empty State */
    body.dark .progress {
      background-color: #162035 !important;
    }

    body.dark .empty-state {
      color: #94a3b8 !important;
    }

    body.dark .empty-state i {
      color: #64748b !important;
    }

    /* Borders & Utilities */
    body.dark .border,
    body.dark .border-top,
    body.dark .border-bottom,
    body.dark .border-start,
    body.dark .border-end {
      border-color: var(--border) !important;
    }

    body.dark .bg-light {
      background-color: #090e1a !important;
      color: #e2e8f0 !important;
    }

    body.dark .hover-bg:hover {
      background-color: rgba(99, 102, 241, 0.12) !important;
    }

    body.dark hr {
      border-color: var(--border) !important;
      opacity: 0.5;
    }

    body.dark .security-notice-box {
      background: rgba(99, 102, 241, 0.12) !important;
      border: 1px solid rgba(99, 102, 241, 0.28) !important;
    }

    body.dark .security-notice-box p {
      color: #cbd5e1 !important;
    }

    * {
      box-sizing: border-box;
    }

    html,
    body {
      height: 100%;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--text);
      margin: 0;
    }

    .app-shell {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: var(--sidebar-w);
      background: var(--sidebar);
      color: var(--sidebar-text);
      position: fixed;
      inset: 0 auto 0 0;
      z-index: 1040;
      display: flex;
      flex-direction: column;
      transition: width .25s ease, transform .25s ease;
      overflow: hidden;
    }

    .sidebar .brand {
      height: var(--topbar-h);
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0 20px;
      font-weight: 700;
      color: #fff;
      font-size: 18px;
      border-bottom: 1px solid rgba(255, 255, 255, .08);
      white-space: nowrap;
      text-decoration: none;
    }

    .sidebar .brand i {
      color: #a5b4fc;
      font-size: 22px;
    }

    .sidebar nav {
      padding: 16px 12px;
      overflow-y: auto;
      flex: 1;
    }

    .nav-link-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 11px 14px;
      border-radius: 12px;
      color: var(--sidebar-text);
      text-decoration: none;
      font-weight: 500;
      margin-bottom: 4px;
      cursor: pointer;
      white-space: nowrap;
      transition: .2s;
    }

    .nav-link-item:hover {
      background: rgba(99, 102, 241, .25);
      color: #fff;
    }

    .nav-link-item.active {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: #fff;
    }

    .nav-link-item i {
      font-size: 18px;
      width: 22px;
      text-align: center;
    }

    .main {
      margin-left: var(--sidebar-w);
      flex: 1;
      min-width: 0;
      transition: margin-left .25s ease;
    }

    .topbar {
      height: var(--topbar-h);
      background: var(--card);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 20px;
      position: sticky;
      top: 0;
      z-index: 1030;
      gap: 12px;
    }

    .content {
      padding: 24px;
      animation: fadeIn .35s ease;
    }

    .page-enter {
      animation: fadeUp .35s ease;
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(16px)
      }

      to {
        opacity: 1;
        transform: none
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0
      }

      to {
        opacity: 1
      }
    }

    .stat-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 18px 18px 16px;
      box-shadow: var(--shadow);
      transition: transform .2s ease, box-shadow .2s ease;
      position: relative;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 40px rgba(99, 102, 241, 0.15);
    }

    .stat-card .icon,
    .icon,
    .icon-box {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      color: #fff;
      font-size: 20px;
      flex-shrink: 0;
      line-height: 1 !important;
      text-align: center !important;
    }

    .stat-card .icon i,
    .icon i,
    .icon-box i,
    .rounded-circle i,
    .place-items-center i,
    .d-grid.place-items-center i {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      line-height: 1 !important;
      vertical-align: 0 !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    .stat-card .icon i::before,
    .icon i::before,
    .icon-box i::before,
    .rounded-circle i::before,
    .place-items-center i::before,
    .d-grid.place-items-center i::before,
    .bi::before,
    [class^="bi-"]::before,
    [class*=" bi-"]::before {
      display: inline-block !important;
      vertical-align: 0 !important;
      line-height: 1 !important;
      margin: 0 !important;
    }

    .rounded-circle.d-grid.place-items-center,
    .rounded-circle.place-items-center,
    .place-items-center {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      line-height: 1 !important;
      flex-shrink: 0;
      text-align: center !important;
    }

    .glass {
      background: linear-gradient(135deg, rgba(99, 102, 241, .12), rgba(139, 92, 246, .08));
      border: 1px solid rgba(99, 102, 241, .15);
      backdrop-filter: blur(10px);
    }

    .card-lift {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: var(--shadow);
      transition: .2s;
    }

    .card-lift:hover {
      transform: translateY(-4px);
    }

    .badge-available {
      background: #d1fae5;
      color: #065f46;
    }

    .badge-occupied {
      background: #fee2e2;
      color: #991b1b;
    }

    .badge-maintenance {
      background: #fef3c7;
      color: #92400e;
    }

    .badge-paid {
      background: #d1fae5;
      color: #065f46;
    }

    .badge-unpaid {
      background: #fee2e2;
      color: #991b1b;
    }

    .badge-pending {
      background: #fef3c7;
      color: #92400e;
    }

    .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      display: grid;
      place-items: center;
      font-weight: 600;
      color: #fff;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      overflow: hidden;
      flex-shrink: 0;
    }

    .avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .avatar.lg {
      width: 84px;
      height: 84px;
      font-size: 28px;
    }

    .avatar.sm {
      width: 34px;
      height: 34px;
      font-size: 13px;
    }

    .user-dropdown-btn {
      padding: 4px 10px;
      border-radius: 999px;
      transition: background 0.2s ease;
    }

    .user-dropdown-btn:hover,
    .user-dropdown-btn:focus,
    .user-dropdown-btn[aria-expanded="true"] {
      background: rgba(99, 102, 241, 0.08);
    }

    body.dark .user-dropdown-btn:hover,
    body.dark .user-dropdown-btn:focus,
    body.dark .user-dropdown-btn[aria-expanded="true"] {
      background: rgba(255, 255, 255, 0.08);
    }


    .search-wrap {
      position: relative;
      flex: 1;
      max-width: 420px;
    }

    .search-wrap input {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 999px;
      padding-left: 40px;
      color: var(--text);
    }

    .search-results {
      position: absolute;
      top: 110%;
      left: 0;
      right: 0;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 14px;
      box-shadow: var(--shadow);
      display: none;
      max-height: 320px;
      overflow: auto;
      z-index: 20;
    }

    .search-results .item {
      padding: 10px 14px;
      cursor: pointer;
    }

    .search-results .item:hover {
      background: var(--bg);
    }

    .notif-drop {
      position: absolute;
      right: 0;
      top: 120%;
      width: 340px;
      max-width: 90vw;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 14px;
      box-shadow: var(--shadow);
      display: none;
      max-height: 400px;
      overflow: auto;
      z-index: 30;
    }

    .toast-wrap {
      position: fixed;
      top: 18px;
      right: 20px;
      z-index: 99999;
      display: flex;
      flex-direction: column;
      gap: 8px;
      pointer-events: none;
    }

    .crm-toast {
      pointer-events: auto;
      min-width: 240px;
      max-width: 440px;
      width: fit-content;
      background: var(--card);
      color: var(--text);
      border: 1px solid var(--border);
      border-left: 4px solid var(--primary);
      border-radius: 12px;
      padding: 10px 18px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
      font-size: 0.88rem;
      font-weight: 500;
      line-height: 1.4;
      cursor: pointer;
      user-select: none;
      animation: crmToastIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .crm-toast:hover {
      box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.15);
    }

    .crm-toast.success {
      border-left-color: var(--success) !important;
    }

    .crm-toast.danger,
    .crm-toast.error {
      border-left-color: var(--danger) !important;
    }

    .crm-toast.warning {
      border-left-color: var(--warning) !important;
    }

    .crm-toast.info {
      border-left-color: var(--primary) !important;
    }

    @keyframes crmToastIn {
      from {
        opacity: 0;
        transform: translateY(-8px) scale(0.97);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .crm-toast.dismissing {
      opacity: 0 !important;
      transform: translateY(-6px) scale(0.97) !important;
    }

    body.dark .crm-toast,
    [data-bs-theme="dark"] .crm-toast {
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
      border-color: var(--border) !important;
    }

    .overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, .45);
      z-index: 1035;
    }

    .id-card {
      width: 360px;
      max-width: 100%;
      border-radius: 18px;
      overflow: hidden;
      background: linear-gradient(160deg, #1e1b4b, #4338ca);
      color: #fff;
      padding: 20px;
    }

    .table-wrap {
      overflow-x: auto;
    }

    table.table {
      color: var(--text);
    }

    .table> :not(caption)>*>* {
      background: transparent;
      color: var(--text);
      border-color: var(--border);
    }

    .form-control,
    .form-select {
      border-radius: 10px;
      border-color: var(--border);
      background: var(--card);
      color: var(--text);
    }

    .form-control:focus,
    .form-select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 .2rem rgba(99, 102, 241, .2);
      background: var(--card);
      color: var(--text);
    }

    .modal-content {
      background: var(--card);
      color: var(--text);
      border-radius: 18px;
      border: 1px solid var(--border);
    }

    .section-title {
      font-weight: 700;
      font-size: 22px;
    }

    .quick-btn {
      border: 0;
      border-radius: 12px;
      padding: 10px 14px;
      font-weight: 600;
      color: #fff;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .quick-btn:hover {
      color: #fff;
      filter: brightness(1.08);
    }

    .empty-state {
      text-align: center;
      padding: 48px 16px;
      color: var(--muted);
    }

    .progress {
      height: 10px;
      border-radius: 999px;
      background: var(--border);
    }

    .doc-thumb {
      width: 120px;
      height: 80px;
      object-fit: cover;
      border-radius: 10px;
      border: 1px solid var(--border);
      cursor: pointer;
    }

    .hamburger {
      display: none;
      border: 0;
      background: transparent;
      color: var(--text);
      font-size: 22px;
    }

    @media (max-width: 1024px) {
      .sidebar {
        width: var(--sidebar-mini);
      }

      .sidebar .brand span,
      .sidebar .nav-text {
        display: none;
      }

      .sidebar .nav-link-item {
        justify-content: center;
        padding: 12px;
      }

      .main {
        margin-left: var(--sidebar-mini);
      }
    }

    @media (max-width: 767.98px) {
      .hamburger {
        display: inline-flex;
      }

      .sidebar {
        transform: translateX(-100%);
        width: var(--sidebar-w);
      }

      .sidebar.open {
        transform: none;
      }

      .sidebar .brand span,
      .sidebar .nav-text {
        display: inline;
      }

      .sidebar .nav-link-item {
        justify-content: flex-start;
        padding: 11px 14px;
      }

      .main {
        margin-left: 0;
      }

      .content {
        padding: 16px;
      }

      .search-wrap {
        display: none;
      }
    }

    .print-only {
      display: none;
    }

    @media print {

      .sidebar,
      .topbar,
      .no-print,
      .modal-header,
      .modal-footer {
        display: none !important;
      }

      .main {
        margin: 0 !important;
      }

      .modal-dialog {
        max-width: 100% !important;
        margin: 0 !important;
      }

      .modal-content {
        border: 0 !important;
        box-shadow: none !important;
      }

      .print-only {
        display: block;
      }

      /* TomSelect Custom Styling */
      .ts-wrapper {
        position: relative;
      }

      .ts-control {
        border-radius: 10px !important;
        padding: 9px 14px !important;
        border: 1px solid var(--border) !important;
        background: var(--card) !important;
        color: var(--text) !important;
        font-family: inherit !important;
        font-size: 14px !important;
        min-height: 42px !important;
        box-shadow: none !important;
      }

      .ts-control input {
        color: var(--text) !important;
        font-family: inherit !important;
        font-size: 14px !important;
      }

      .ts-dropdown {
        border-radius: 12px !important;
        border: 1px solid var(--border) !important;
        background: var(--card) !important;
        color: var(--text) !important;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25) !important;
        z-index: 1070 !important;
        font-size: 14px !important;
        padding: 4px 0 !important;
      }

      .ts-dropdown .option {
        padding: 8px 14px !important;
        color: var(--text) !important;
        cursor: pointer !important;
      }

      .ts-dropdown .option.active {
        background-color: rgba(99, 102, 241, 0.12) !important;
        color: var(--primary) !important;
      }

      .ts-dropdown .option[data-disabled="true"],
      .ts-dropdown .option.disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
        background: transparent !important;
      }

      .ts-control.focus {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 .2rem rgba(99, 102, 241, 0.25) !important;
      }

      body.dark .ts-control {
        background: #090e1a !important;
        border-color: #243049 !important;
        color: #f8fafc !important;
      }

      body.dark .ts-control input {
        color: #f8fafc !important;
      }

      body.dark .ts-control input::placeholder {
        color: #64748b !important;
      }

      body.dark .ts-control.focus {
        border-color: #818cf8 !important;
        box-shadow: 0 0 0 .2rem rgba(99, 102, 241, 0.35) !important;
      }

      body.dark .ts-dropdown {
        background: #131b2e !important;
        border-color: #243049 !important;
        color: #f8fafc !important;
        box-shadow: 0 14px 35px rgba(0, 0, 0, 0.6) !important;
      }

      body.dark .ts-dropdown .option {
        color: #e2e8f0 !important;
      }

      body.dark .ts-dropdown .option.active {
        background-color: rgba(99, 102, 241, 0.25) !important;
        color: #ffffff !important;
      }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
  <script>
    const BASE_URL = '<?= base_url() ?>';
    const CURRENCY = '<?= $settings->currency ?? '₹' ?>';
    let activeModalInstance = null;

    function toast(msg, type = 'success') {
      let wrap = document.getElementById('toast-wrap');
      if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'toast-wrap';
        wrap.className = 'toast-wrap';
        document.body.appendChild(wrap);
      }

      if (type === 'error') type = 'danger';

      const el = document.createElement('div');
      el.className = `crm-toast ${type}`;
      el.innerHTML = msg;

      el.addEventListener('click', () => {
        dismissToast(el);
      });

      wrap.appendChild(el);

      let timer = setTimeout(() => {
        dismissToast(el);
      }, 3200);

      el.addEventListener('mouseenter', () => clearTimeout(timer));
      el.addEventListener('mouseleave', () => {
        timer = setTimeout(() => dismissToast(el), 1200);
      });
    }

    function dismissToast(el) {
      if (!el || el.classList.contains('dismissing')) return;
      el.classList.add('dismissing');
      setTimeout(() => {
        if (el.parentNode) el.remove();
      }, 220);
    }

    function swalAlert(title, text = '', icon = 'success') {
      return Swal.fire({
        title: title,
        text: text,
        icon: icon === 'danger' ? 'error' : icon,
        confirmButtonColor: '#6366f1'
      });
    }

    function swalConfirm(title, text = '', confirmText = 'Yes, Delete', cancelText = 'Cancel') {
      return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        reverseButtons: true
      });
    }

    function renderPagination(totalRecords, currentPage, perPage, onPageClickFn) {
      const totalPages = Math.ceil(totalRecords / perPage) || 1;
      currentPage = Math.max(1, Math.min(currentPage, totalPages));

      if (totalRecords <= 0) {
        return '<div class="text-muted small py-2 text-center">No records to display.</div>';
      }

      const startRecord = (currentPage - 1) * perPage + 1;
      const endRecord = Math.min(currentPage * perPage, totalRecords);

      let items = [];
      if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) items.push(i);
      } else {
        if (currentPage <= 3) {
          items = [1, 2, 3, '...', totalPages];
        } else if (currentPage >= totalPages - 2) {
          items = [1, '...', totalPages - 2, totalPages - 1, totalPages];
        } else {
          items = [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages];
        }
      }

      let html = `
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-3 border-top">
        <div class="text-muted small">
          Showing <strong>${startRecord}</strong> to <strong>${endRecord}</strong> of <strong>${totalRecords}</strong> entries
        </div>
        <ul class="pagination pagination-sm mb-0 align-items-center gap-1">
      `;

      if (currentPage > 1) {
        html += `<li class="page-item"><button class="page-link rounded-2" onclick="${onPageClickFn}(${currentPage - 1})" title="Previous"><i class="bi bi-chevron-left"></i></button></li>`;
      } else {
        html += `<li class="page-item disabled"><span class="page-link rounded-2 opacity-50"><i class="bi bi-chevron-left"></i></span></li>`;
      }

      items.forEach(it => {
        if (it === '...') {
          html += `<li class="page-item disabled"><span class="page-link border-0 bg-transparent text-muted px-2">...</span></li>`;
        } else if (it === currentPage) {
          html += `<li class="page-item active"><span class="page-link rounded-2 fw-bold">${it}</span></li>`;
        } else {
          html += `<li class="page-item"><button class="page-link rounded-2" onclick="${onPageClickFn}(${it})">${it}</button></li>`;
        }
      });

      if (currentPage < totalPages) {
        html += `<li class="page-item"><button class="page-link rounded-2" onclick="${onPageClickFn}(${currentPage + 1})" title="Next"><i class="bi bi-chevron-right"></i></button></li>`;
      } else {
        html += `<li class="page-item disabled"><span class="page-link rounded-2 opacity-50"><i class="bi bi-chevron-right"></i></span></li>`;
      }

      html += `</ul></div>`;
      return html;
    }

    const money = n => CURRENCY + Number(n || 0).toLocaleString('en-IN');
    const todayISO = () => new Date().toISOString().slice(0, 10);

    function escapeHtml(str) {
      if (str === null || str === undefined) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    let activeTomSelectInstances = [];

    function registerTomSelect(ts) {
      if (ts) activeTomSelectInstances.push(ts);
      return ts;
    }

    function destroyTomSelects() {
      activeTomSelectInstances.forEach(ts => {
        try {
          ts.destroy();
        } catch (e) {}
      });
      activeTomSelectInstances = [];
      document.querySelectorAll('.ts-dropdown').forEach(el => el.remove());
    }

    function openModal(inner, after) {
      closeModal();
      destroyTomSelects();
      let host = document.getElementById('modal-host');
      if (!host) {
        host = document.createElement('div');
        host.id = 'modal-host';
        document.body.appendChild(host);
      }
      host.innerHTML = `<div class="modal fade" id="crm-modal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">${inner}</div></div></div>`;
      const el = document.getElementById('crm-modal');
      activeModalInstance = new bootstrap.Modal(el);
      el.addEventListener('hidden.bs.modal', () => {
        destroyTomSelects();
        host.innerHTML = '';
        activeModalInstance = null;
      });
      activeModalInstance.show();
      if (after) setTimeout(after, 60);
    }

    function closeModal() {
      destroyTomSelects();
      if (activeModalInstance) {
        try {
          activeModalInstance.hide();
        } catch {}
      }
      const host = document.getElementById('modal-host');
      if (host) host.innerHTML = '';
      activeModalInstance = null;
    }

    function buildApiUrl(endpoint) {
      const cleanEndpoint = (endpoint || '').replace(/^\/+/, '');
      return new URL(BASE_URL + cleanEndpoint, window.location.origin);
    }

    async function apiGet(endpoint, params = {}) {
      try {
        const url = buildApiUrl(endpoint);
        Object.keys(params).forEach(k => {
          if (params[k] !== undefined && params[k] !== null && params[k] !== '') {
            url.searchParams.append(k, params[k]);
          }
        });
        const res = await fetch(url.toString(), {
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        if (res.redirected && res.url.includes('auth/login')) {
          window.location.href = BASE_URL + 'auth/login';
          return {
            success: false,
            message: 'Session expired'
          };
        }
        return await res.json();
      } catch (err) {
        console.error('apiGet error on [' + endpoint + ']:', err);
        throw err;
      }
    }

    async function apiPost(endpoint, data = {}) {
      try {
        const url = buildApiUrl(endpoint);
        let body;
        if (data instanceof FormData) {
          body = data;
        } else {
          body = new FormData();
          Object.keys(data).forEach(k => {
            if (data[k] !== undefined && data[k] !== null) {
              body.append(k, data[k]);
            }
          });
        }
        const res = await fetch(url.toString(), {
          method: 'POST',
          body,
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        if (res.redirected && res.url.includes('auth/login')) {
          window.location.href = BASE_URL + 'auth/login';
          return {
            success: false,
            message: 'Session expired'
          };
        }
        return await res.json();
      } catch (err) {
        console.error('apiPost error on [' + endpoint + ']:', err);
        throw err;
      }
    }
  </script>
</head>

<body class="<?= !empty($settings->dark_mode) ? 'dark' : '' ?>" data-bs-theme="<?= !empty($settings->dark_mode) ? 'dark' : 'light' ?>">

  <div id="app-screen">
    <div class="overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
      <a href="<?= base_url('dashboard') ?>" class="brand">
        <i class="bi bi-buildings"></i>
        <span id="brand-name"><?= htmlspecialchars($settings->crm_name ?? 'StayFlow') ?></span>
      </a>
      <nav>
        <a href="<?= base_url('dashboard') ?>" class="nav-link-item <?= ($active_page ?? '') === 'dashboard' ? 'active' : '' ?>">
          <i class="bi bi-grid-1x2"></i><span class="nav-text">Dashboard</span>
        </a>
        <a href="<?= base_url('hotels') ?>" class="nav-link-item <?= ($active_page ?? '') === 'hotels' ? 'active' : '' ?>">
          <i class="bi bi-building"></i><span class="nav-text">Hotels</span>
        </a>
        <a href="<?= base_url('rooms') ?>" class="nav-link-item <?= ($active_page ?? '') === 'rooms' ? 'active' : '' ?>">
          <i class="bi bi-door-open"></i><span class="nav-text">Rooms</span>
        </a>
        <a href="<?= base_url('tenants') ?>" class="nav-link-item <?= ($active_page ?? '') === 'tenants' ? 'active' : '' ?>">
          <i class="bi bi-people"></i><span class="nav-text">Tenants</span>
        </a>
        <a href="<?= base_url('vacated') ?>" class="nav-link-item <?= ($active_page ?? '') === 'vacated' ? 'active' : '' ?>">
          <i class="bi bi-box-arrow-right"></i><span class="nav-text">Vacated History</span>
        </a>
        <a href="<?= base_url('rent') ?>" class="nav-link-item <?= ($active_page ?? '') === 'rent' ? 'active' : '' ?>">
          <i class="bi bi-cash-stack"></i><span class="nav-text">Rent</span>
        </a>
        <a href="<?= base_url('finance') ?>" class="nav-link-item <?= ($active_page ?? '') === 'finance' ? 'active' : '' ?>">
          <i class="bi bi-graph-up-arrow"></i><span class="nav-text">Income & Expense</span>
        </a>
        <a href="<?= base_url('occupancy') ?>" class="nav-link-item <?= ($active_page ?? '') === 'occupancy' ? 'active' : '' ?>">
          <i class="bi bi-pie-chart"></i><span class="nav-text">Occupancy</span>
        </a>
        <a href="<?= base_url('settings') ?>" class="nav-link-item <?= ($active_page ?? '') === 'settings' ? 'active' : '' ?>">
          <i class="bi bi-gear"></i><span class="nav-text">Settings</span>
        </a>
      </nav>
    </aside>

    <div class="main">
      <header class="topbar">
        <div class="d-flex align-items-center gap-2">
          <button class="hamburger" id="btn-hamburger"><i class="bi bi-list"></i></button>
          <strong id="top-crm-name"><?= htmlspecialchars($settings->crm_name ?? 'StayFlow CRM') ?></strong>
        </div>
        <div class="search-wrap" id="search-wrap">
          <i class="bi bi-search" style="position:absolute;left:14px;top:10px;color:var(--muted)"></i>
          <input class="form-control" id="global-search" placeholder="Search tenants, rooms, hotels..." autocomplete="off" />
          <div class="search-results" id="search-results"></div>
        </div>
        <div class="d-flex align-items-center gap-2 position-relative">
          <button class="btn btn-sm btn-outline-secondary rounded-pill" id="btn-theme" title="Toggle theme">
            <i class="bi <?= !empty($settings->dark_mode) ? 'bi-sun' : 'bi-moon' ?>"></i>
          </button>
          <!-- Notification Bell (Disabled/Commented out per request) -->
          <!--
          <button class="btn btn-sm position-relative" id="btn-bell">
            <i class="bi bi-bell"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notif-count">0</span>
          </button>
          <div class="notif-drop" id="notif-drop"></div>
          -->

          <!-- Admin User Dropdown -->
          <div class="dropdown ms-1">
            <button class="btn d-flex align-items-center gap-2 border-0 bg-transparent text-reset user-dropdown-btn" type="button" id="adminDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="avatar sm shadow-sm" id="header-avatar">
                <?php $has_img = !empty($user->profile_image) && file_exists(FCPATH . $user->profile_image); ?>
                <img src="<?= $has_img ? base_url($user->profile_image) : '' ?>" alt="Admin" id="header-avatar-img" class="<?= $has_img ? '' : 'd-none' ?>" />
                <span id="header-avatar-initial" class="<?= $has_img ? 'd-none' : '' ?>"><?= strtoupper(substr($user->name ?? 'Admin', 0, 1)) ?></span>
              </div>
              <span class="d-none d-md-inline fw-semibold text-reset" id="header-admin-name"><?= htmlspecialchars($user->name ?? 'Admin') ?></span>
              <i class="bi bi-chevron-down small text-muted"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-4 py-2" aria-labelledby="adminDropdownBtn" style="min-width: 220px; z-index: 1060;">
              <li class="px-3 py-2 border-bottom mb-1">
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar sm flex-shrink-0" id="dropdown-avatar">
                    <img src="<?= $has_img ? base_url($user->profile_image) : '' ?>" alt="Admin" id="dropdown-avatar-img" class="<?= $has_img ? '' : 'd-none' ?>" />
                    <span id="dropdown-avatar-initial" class="<?= $has_img ? 'd-none' : '' ?>"><?= strtoupper(substr($user->name ?? 'Admin', 0, 1)) ?></span>
                  </div>
                  <div class="overflow-hidden">
                    <div class="fw-bold text-truncate" id="dropdown-admin-name" style="font-size: 14px;"><?= htmlspecialchars($user->name ?? 'Admin') ?></div>
                    <div class="small text-muted text-truncate" style="font-size: 12px;"><?= htmlspecialchars($user->email ?? 'admin@gmail.com') ?></div>
                  </div>
                </div>
              </li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 fw-medium" href="<?= base_url('profile') ?>">
                  <i class="bi bi-person text-primary fs-6"></i>
                  <span>Profile</span>
                </a>
              </li>
              <li>
                <hr class="dropdown-divider my-1 opacity-50">
              </li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 fw-medium text-danger" href="<?= base_url('auth/logout') ?>">
                  <i class="bi bi-box-arrow-right fs-6"></i>
                  <span>Logout</span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </header>

      <div class="content" id="page-content">