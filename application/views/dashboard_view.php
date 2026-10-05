<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="page-enter">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
      <div class="section-title">Dashboard</div>
      <div class="text-muted">Welcome back, <?= htmlspecialchars($user->name ?? 'Admin') ?></div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <!-- Month & Year Filter Selector with Visible Dropdown Icons -->
      <div class="d-flex align-items-center gap-2 p-1 px-3 rounded-pill border shadow-sm" style="background:var(--card); border-color:var(--border)!important;">
        <i class="bi bi-calendar3 text-primary"></i>
        <!-- Month Select with Chevron Icon -->
        <div class="d-inline-flex align-items-center position-relative">
          <select class="form-select form-select-sm border-0 shadow-none fw-bold" id="dash-select-month" style="width: auto; background: transparent; color: var(--text); cursor: pointer; padding-right: 18px; appearance: none; -webkit-appearance: none; -moz-appearance: none;" onchange="onDashboardDateSelect()">
            <?php 
              $all_months = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
              ];
              foreach ($all_months as $m_num => $m_name): 
            ?>
              <option value="<?= sprintf('%02d', $m_num) ?>" <?= intval($selected_month ?? date('n')) === $m_num ? 'selected' : '' ?>><?= $m_name ?></option>
            <?php endforeach; ?>
          </select>
          <i class="bi bi-chevron-down text-primary small position-absolute end-0" style="pointer-events: none; font-size: 0.7rem;"></i>
        </div>

        <span class="text-muted opacity-50">/</span>

        <!-- Year Select with Chevron Icon -->
        <div class="d-inline-flex align-items-center position-relative">
          <select class="form-select form-select-sm border-0 shadow-none fw-bold" id="dash-select-year" style="width: auto; background: transparent; color: var(--text); cursor: pointer; padding-right: 18px; appearance: none; -webkit-appearance: none; -moz-appearance: none;" onchange="onDashboardDateSelect()">
            <?php 
              $curr_yr = intval(date('Y'));
              for ($yr = $curr_yr - 3; $yr <= $curr_yr + 3; $yr++): 
            ?>
              <option value="<?= $yr ?>" <?= intval($selected_year ?? $curr_yr) === $yr ? 'selected' : '' ?>><?= $yr ?></option>
            <?php endfor; ?>
          </select>
          <i class="bi bi-chevron-down text-primary small position-absolute end-0" style="pointer-events: none; font-size: 0.7rem;"></i>
        </div>
      </div>

      <a href="<?= base_url('hotels') ?>" class="quick-btn"><i class="bi bi-building-add me-1"></i>Hotels</a>
      <a href="<?= base_url('rooms') ?>" class="quick-btn"><i class="bi bi-door-open me-1"></i>Rooms</a>
      <a href="<?= base_url('tenants') ?>" class="quick-btn"><i class="bi bi-person-plus me-1"></i>Tenants</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
      <div class="stat-card glass d-flex gap-3 align-items-center card-lift">
        <div class="icon" style="background:#6366f1"><i class="bi bi-building"></i></div>
        <div>
          <div class="text-muted small">Total Hotels</div>
          <div class="fs-4 fw-bold"><?= intval($total_hotels ?? 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card glass d-flex gap-3 align-items-center card-lift">
        <div class="icon" style="background:#8b5cf6"><i class="bi bi-door-closed"></i></div>
        <div>
          <div class="text-muted small">Total Rooms</div>
          <div class="fs-4 fw-bold"><?= intval($total_rooms ?? 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card glass d-flex gap-3 align-items-center card-lift">
        <div class="icon" style="background:#10b981"><i class="bi bi-people"></i></div>
        <div>
          <div class="text-muted small">Total Tenants</div>
          <div class="fs-4 fw-bold"><?= intval($total_tenants ?? 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card glass d-flex gap-3 align-items-center card-lift">
        <div class="icon" style="background:#f59e0b"><i class="bi bi-pie-chart"></i></div>
        <div>
          <div class="text-muted small">Occupancy Rate</div>
          <div class="fs-4 fw-bold"><?= intval($occupancy_rate ?? 0) ?>%</div>
        </div>
      </div>
    </div>

    <!-- Month Specific Cards -->
    <div class="col-6 col-xl-3">
      <div class="stat-card glass d-flex gap-3 align-items-center card-lift">
        <div class="icon" style="background:#6366f1"><i class="bi bi-wallet2"></i></div>
        <div>
          <div class="text-muted small">Monthly Revenue</div>
          <div class="fs-4 fw-bold" id="dash-revenue"><?= ($settings->currency ?? '₹') . number_format($monthly_revenue ?? 0, 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card glass d-flex gap-3 align-items-center card-lift">
        <div class="icon" style="background:#ef4444"><i class="bi bi-exclamation-circle"></i></div>
        <div>
          <div class="text-muted small">Pending Rent Bills</div>
          <div class="fs-4 fw-bold" id="dash-pending-count"><?= intval($pending_rents_count ?? 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card glass d-flex gap-3 align-items-center card-lift">
        <div class="icon" style="background:#10b981"><i class="bi bi-graph-up"></i></div>
        <div>
          <div class="text-muted small">Total Income</div>
          <div class="fs-4 fw-bold" id="dash-income"><?= ($settings->currency ?? '₹') . number_format($total_income ?? 0, 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card glass d-flex gap-3 align-items-center card-lift">
        <div class="icon" style="background:#ef4444"><i class="bi bi-graph-down-arrow"></i></div>
        <div>
          <div class="text-muted small">Total Expenses</div>
          <div class="fs-4 fw-bold" id="dash-expenses"><?= ($settings->currency ?? '₹') . number_format($total_expense ?? 0, 0) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <!-- Rent Collection Progress Card -->
      <div class="stat-card glass mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="fw-semibold mb-0">
            Rent Collection • <span id="dash-month-label"><?= htmlspecialchars($month_label ?? date('F Y')) ?></span>
          </h6>
          <span class="fw-bold" id="dash-coll-pct"><?= intval($coll_pct ?? 0) ?>%</span>
        </div>
        <div class="progress mt-2 mb-2" style="height: 10px;">
          <div class="progress-bar bg-success" id="dash-coll-bar" style="width:<?= intval($coll_pct ?? 0) ?>%"></div>
        </div>
        <div class="d-flex justify-content-between text-muted small">
          <span id="dash-paid-amount">Collected: <?= ($settings->currency ?? '₹') . number_format($paid_amount ?? 0, 0) ?></span>
          <span id="dash-pending-amount">Pending: <?= ($settings->currency ?? '₹') . number_format($pending_amount ?? 0, 0) ?></span>
        </div>
      </div>

      <!-- Income vs Expenses Bar Chart -->
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-semibold mb-0">Income vs Expenses Overview</h6>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="dash-chart-badge"><?= htmlspecialchars($month_label ?? date('F Y')) ?></span>
        </div>
        <div style="height: 220px; position: relative;">
          <canvas id="dash-chart"></canvas>
        </div>
      </div>
    </div>

    <!-- Recent Tenants Column -->
    <div class="col-lg-4">
      <div class="stat-card h-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-semibold mb-0">Recent Tenants</h6>
          <a href="<?= base_url('tenants') ?>" class="small text-decoration-none">View All</a>
        </div>
        <?php if (!empty($recent_tenants)): ?>
          <?php foreach ($recent_tenants as $t): ?>
            <a href="<?= base_url('tenants/detail/' . $t->id) ?>" class="d-flex align-items-center gap-3 mb-3 p-2 rounded text-decoration-none text-reset hover-bg card-lift">
              <div class="avatar sm">
                <?php if (!empty($t->profile_image)): ?>
                  <img src="<?= base_url($t->profile_image) ?>" alt="<?= htmlspecialchars($t->name) ?>" />
                <?php else: ?>
                  <?= strtoupper(substr($t->name, 0, 1)) ?>
                <?php endif; ?>
              </div>
              <div class="flex-grow-1 overflow-hidden">
                <div class="fw-semibold small text-truncate"><?= htmlspecialchars($t->name) ?></div>
                <div class="text-muted small text-truncate"><?= htmlspecialchars($t->hotel_name ?? 'PG') ?> • Room <?= htmlspecialchars($t->room_number ?? '-') ?></div>
              </div>
              <div class="fw-semibold small text-nowrap"><?= ($settings->currency ?? '₹') . number_format($t->rent_amount, 0) ?></div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state py-4">
            <i class="bi bi-people fs-2 text-muted mb-2 d-block"></i>
            <div class="text-muted small">No tenants added yet.</div>
            <a href="<?= base_url('tenants') ?>" class="btn btn-sm btn-outline-primary mt-2">+ Add First Tenant</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
  let dashChartInstance = null;
  const currencySymbol = '<?= $settings->currency ?? '₹' ?>';

  function formatMoney(amount) {
    return currencySymbol + Math.round(amount).toLocaleString('en-IN');
  }

  document.addEventListener('DOMContentLoaded', () => {
    initDashboardChart(
      <?= floatval($total_income ?? 0) ?>,
      <?= floatval($total_expense ?? 0) ?>,
      <?= floatval(($total_income ?? 0) - ($total_expense ?? 0)) ?>
    );
  });

  function initDashboardChart(income, expense, netProfit) {
    const ctx = document.getElementById('dash-chart');
    if (!ctx) return;

    if (dashChartInstance) {
      dashChartInstance.destroy();
    }

    dashChartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Total Income', 'Total Expenses', 'Net Profit'],
        datasets: [{
          label: `Amount (${currencySymbol})`,
          data: [income, expense, netProfit],
          backgroundColor: ['#10b981', '#ef4444', '#6366f1'],
          borderRadius: 8,
          borderSkipped: false,
          maxBarThickness: 45
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return formatMoney(context.raw);
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: '#94a3b8', font: { weight: '500' } }
          },
          y: {
            grid: { color: 'rgba(148, 163, 184, 0.1)' },
            ticks: {
              color: '#94a3b8',
              callback: function(value) {
                return currencySymbol + value.toLocaleString('en-IN');
              }
            }
          }
        }
      }
    });
  }

  function onDashboardDateSelect() {
    const m = document.getElementById('dash-select-month').value;
    const y = document.getElementById('dash-select-year').value;
    changeDashboardMonth(y + '-' + m);
  }

  async function changeDashboardMonth(selectedMonth) {
    // Update browser URL query string without reloading
    const newUrl = new URL(window.location.href);
    newUrl.searchParams.set('month_year', selectedMonth);
    window.history.replaceState({}, '', newUrl);

    // Visual loading state
    const revEl = document.getElementById('dash-revenue');
    const pendEl = document.getElementById('dash-pending-count');
    const incEl = document.getElementById('dash-income');
    const expEl = document.getElementById('dash-expenses');

    [revEl, pendEl, incEl, expEl].forEach(el => {
      if (el) el.style.opacity = '0.5';
    });

    try {
      const res = await apiGet('dashboard/stats_ajax', { month_year: selectedMonth });
      if (res.success && res.stats) {
        const s = res.stats;

        // Update cards
        if (revEl) revEl.textContent = formatMoney(s.monthly_revenue);
        if (pendEl) pendEl.textContent = s.pending_rents_count;
        if (incEl) incEl.textContent = formatMoney(s.total_income);
        if (expEl) expEl.textContent = formatMoney(s.total_expense);

        // Update rent collection section
        document.getElementById('dash-month-label').textContent = s.month_label;
        document.getElementById('dash-chart-badge').textContent = s.month_label;
        document.getElementById('dash-coll-pct').textContent = s.coll_pct + '%';
        document.getElementById('dash-coll-bar').style.width = s.coll_pct + '%';
        document.getElementById('dash-paid-amount').textContent = 'Collected: ' + formatMoney(s.paid_amount);
        document.getElementById('dash-pending-amount').textContent = 'Pending: ' + formatMoney(s.pending_amount);

        // Update chart
        if (dashChartInstance) {
          dashChartInstance.data.datasets[0].data = [
            parseFloat(s.total_income || 0),
            parseFloat(s.total_expense || 0),
            parseFloat(s.net_profit || 0)
          ];
          dashChartInstance.update();
        }
      } else {
        toast('Failed to load month statistics.', 'error');
      }
    } catch (err) {
      console.error(err);
      toast('Error fetching data for selected month.', 'error');
    } finally {
      [revEl, pendEl, incEl, expEl].forEach(el => {
        if (el) el.style.opacity = '1';
      });
    }
  }
</script>