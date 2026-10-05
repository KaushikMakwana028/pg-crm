<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - <?= htmlspecialchars($settings->crm_name ?? 'StayFlow CRM') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --primary: #6366f1;
      --secondary: #8b5cf6;
      --success: #10b981;
      --danger: #ef4444;
    }

    * {
      box-sizing: border-box;
    }

    html,
    body {
      height: 100%;
      margin: 0;
      font-family: 'Poppins', sans-serif;
    }

    .login-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      background:
        radial-gradient(900px 500px at 10% -10%, rgba(99, 102, 241, .35), transparent 50%),
        radial-gradient(700px 400px at 100% 100%, rgba(139, 92, 246, .3), transparent 50%),
        linear-gradient(135deg, #0f172a, #1e1b4b);
    }

    .login-card {
      width: 100%;
      max-width: 440px;
      background: rgba(255, 255, 255, .12);
      border: 1px solid rgba(255, 255, 255, .18);
      backdrop-filter: blur(18px);
      border-radius: 24px;
      padding: 36px 32px;
      color: #fff;
      box-shadow: 0 30px 80px rgba(0, 0, 0, .35);
      animation: fadeUp .6s ease;
    }

    .logo-orb {
      width: 72px;
      height: 72px;
      border-radius: 20px;
      margin: 0 auto 16px;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      box-shadow: 0 12px 30px rgba(99, 102, 241, .45);
      animation: float 3s ease-in-out infinite;
      font-size: 32px;
    }

    @keyframes float {

      0%,
      100% {
        transform: translateY(0)
      }

      50% {
        transform: translateY(-8px)
      }
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

    .login-card h1 {
      font-size: 26px;
      font-weight: 700;
      text-align: center;
    }

    .login-card p.sub {
      text-align: center;
      color: #c7d2fe;
      margin-bottom: 24px;
    }

    .login-card .form-control {
      background: rgba(255, 255, 255, .12);
      border: 1px solid rgba(255, 255, 255, .2);
      color: #fff;
      border-radius: 12px;
      padding: 12px 14px;
    }

    .login-card .form-control::placeholder {
      color: #c7d2fe;
    }

    .login-card .form-control:focus {
      box-shadow: 0 0 0 .2rem rgba(99, 102, 241, .35);
      background: rgba(255, 255, 255, .18);
      color: #fff;
    }

    .btn-primary-grad {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border: 0;
      color: #fff;
      font-weight: 600;
      border-radius: 12px;
      padding: 12px 16px;
    }

    .btn-primary-grad:hover {
      color: #fff;
      filter: brightness(1.08);
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
      background: #ffffff;
      color: #0f172a;
      border: 1px solid rgba(0, 0, 0, 0.08);
      border-left: 4px solid var(--danger);
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

    .crm-toast.success { border-left-color: var(--success) !important; }
    .crm-toast.warning { border-left-color: #f59e0b !important; }
    .crm-toast.danger, .crm-toast.error { border-left-color: var(--danger) !important; }

    @keyframes crmToastIn {
      from { opacity: 0; transform: translateY(-8px) scale(0.97); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .crm-toast.dismissing {
      opacity: 0 !important;
      transform: translateY(-6px) scale(0.97) !important;
    }
  </style>
</head>

<body>
  <div class="login-wrap">
    <div class="login-card">
      <div class="logo-orb"><i class="bi bi-buildings"></i></div>
      <h1><?= htmlspecialchars($settings->crm_name ?? 'StayFlow CRM') ?></h1>
      <p class="sub">PG & Hostel Management</p>
      <form id="login-form">
        <label class="form-label text-white">Email Address</label>
        <input type="email" class="form-control mb-3" id="login-email" placeholder="admin@gmail.com" value="admin@gmail.com" autocomplete="email" required />
        <label class="form-label text-white">Password</label>
        <input type="password" class="form-control mb-3" id="login-pass" placeholder="••••••••" value="123456" autocomplete="current-password" required />
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="remember-me" checked />
          <label class="form-check-label text-white" for="remember-me">Remember me (Permanent Session)</label>
        </div>
        <button class="btn btn-primary-grad w-100" type="submit" id="btn-login-submit">Sign In</button>
      </form>
    </div>
  </div>

  <div class="toast-wrap" id="toast-wrap"></div>

  <script>
    const BASE_URL = '<?= base_url() ?>';

    function toast(msg, type = 'danger') {
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
        el.classList.add('dismissing');
        setTimeout(() => el.remove(), 220);
      });

      wrap.appendChild(el);

      let timer = setTimeout(() => {
        el.classList.add('dismissing');
        setTimeout(() => el.remove(), 220);
      }, 3200);

      el.addEventListener('mouseenter', () => clearTimeout(timer));
      el.addEventListener('mouseleave', () => {
        timer = setTimeout(() => {
          el.classList.add('dismissing');
          setTimeout(() => el.remove(), 220);
        }, 1200);
      });
    }

    document.getElementById('login-form').onsubmit = async e => {
      e.preventDefault();
      const email = document.getElementById('login-email').value.trim();
      const password = document.getElementById('login-pass').value;
      const btn = document.getElementById('btn-login-submit');

      btn.disabled = true;
      btn.textContent = 'Signing in...';

      const fd = new FormData();
      fd.append('email', email);
      fd.append('password', password);

      try {
        const res = await fetch(BASE_URL + 'auth/login', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        });
        const data = await res.json();
        btn.disabled = false;
        btn.textContent = 'Sign In';

        if (data.success) {
          window.location.href = BASE_URL + 'dashboard';
        } else {
          toast(data.message || 'Invalid credentials');
        }
      } catch (err) {
        btn.disabled = false;
        btn.textContent = 'Sign In';
        toast('Connection failed. Please check network.');
      }
    };
  </script>
</body>

</html>