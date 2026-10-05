<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-enter">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="section-title">Income & expenses</div>
    <div class="d-flex gap-2 flex-wrap">
      <button class="btn btn-success fw-semibold" onclick="openIncomeModal()"><i class="bi bi-plus-lg me-1"></i>Add Income</button>
      <button class="btn btn-danger fw-semibold" onclick="openExpenseModal()"><i class="bi bi-dash-lg me-1"></i>Add Expense</button>
      <button class="btn btn-outline-secondary" onclick="exportFinanceCSV()"><i class="bi bi-file-earmark-arrow-down me-1"></i>Export CSV</button>
      <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
  </div>

  <!-- Filters Row -->
  <div class="d-flex gap-2 flex-wrap mb-3 align-items-center">
    <select class="form-select w-auto" id="fin-filter-month" onchange="onMonthChange()">
      <?php
        $cur_y = intval(date('Y'));
        $cur_m = intval(date('n'));
        for ($i = 0; $i < 12; $i++) {
            $ts = mktime(0, 0, 0, $cur_m - $i, 1, $cur_y);
            $val = date('Y-m', $ts);
            $txt = date('F Y', $ts);
            echo "<option value=\"{$val}\">{$txt}</option>";
        }
      ?>
    </select>

    <select class="form-select w-auto" id="fin-filter-hotel" onchange="loadFinance(1)">
      <option value="">All hotels</option>
      <?php foreach ($hotels as $h): ?>
        <option value="<?= $h->id ?>"><?= htmlspecialchars($h->name) ?></option>
      <?php endforeach; ?>
    </select>

    <input type="date" class="form-control w-auto" id="fin-filter-from" title="From date" onchange="onDateRangeChange()" />
    <span class="text-muted small">to</span>
    <input type="date" class="form-control w-auto" id="fin-filter-to" title="To date" onchange="onDateRangeChange()" />

    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFinanceFilters()">Reset</button>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="stat-card">
        <div class="text-muted small">Total income</div>
        <div class="fs-3 fw-bold text-success" id="fin-total-income"><?= ($settings->currency ?? '₹') . '0' ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card">
        <div class="text-muted small">Total expenses</div>
        <div class="fs-3 fw-bold text-danger" id="fin-total-expense"><?= ($settings->currency ?? '₹') . '0' ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card">
        <div class="text-muted small">Net profit / loss</div>
        <div class="fs-3 fw-bold" id="fin-net-balance"><?= ($settings->currency ?? '₹') . '0' ?></div>
      </div>
    </div>
  </div>

  <!-- Last 6 Months Chart -->
  <div class="stat-card mb-3 no-print">
    <h6 class="fw-semibold mb-3">Last 6 months</h6>
    <div style="height: 240px; position: relative;">
      <canvas id="fin-chart"></canvas>
    </div>
  </div>

  <!-- Filter & Tabs Bar -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <ul class="nav nav-pills" id="financePills">
      <li class="nav-item">
        <button class="nav-link active" id="tab-btn-all" onclick="switchFinanceTab('all')">All Transactions</button>
      </li>
      <li class="nav-item">
        <button class="nav-link" id="tab-btn-income" onclick="switchFinanceTab('income')">Income Only</button>
      </li>
      <li class="nav-item">
        <button class="nav-link" id="tab-btn-expense" onclick="switchFinanceTab('expense')">Expenses Only</button>
      </li>
    </ul>

    <div class="d-flex gap-2 flex-wrap align-items-center">
      <input class="form-control form-control-sm" style="max-width:200px" id="fin-filter-q" placeholder="Search category / party..." />
      <button type="button" class="btn btn-sm btn-primary" onclick="loadFinance(1)"><i class="bi bi-search"></i> Search</button>
    </div>
  </div>

  <!-- Table Container -->
  <div class="stat-card table-wrap">
    <table class="table align-middle" id="finance-print-table">
      <thead>
        <tr>
          <th>Type</th>
          <th>Date</th>
          <th>Category</th>
          <th>Party / Description</th>
          <th>Hotel</th>
          <th>Mode</th>
          <th>Amount</th>
          <th class="no-print">Action</th>
        </tr>
      </thead>
      <tbody id="finance-tbody">
        <tr><td colspan="8" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading financial records...</td></tr>
      </tbody>
    </table>
  </div>

  <!-- Pagination Host -->
  <div id="finance-pagination" class="no-print"></div>
</div>

<script>
  let currentFinTab = 'all';
  let currentFinPage = 1;
  let finSearchTimer = null;
  let finChartInstance = null;
  let currentFinItems = [];

  document.getElementById('fin-filter-q').addEventListener('input', () => {
    clearTimeout(finSearchTimer);
    finSearchTimer = setTimeout(() => {
      loadFinance(1);
    }, 350);
  });

  function onMonthChange() {
    // When month dropdown is picked, clear date range inputs so month filter takes effect
    document.getElementById('fin-filter-from').value = '';
    document.getElementById('fin-filter-to').value = '';
    loadFinance(1);
  }

  function onDateRangeChange() {
    // When custom date range is used, deselect month selector or keep it
    loadFinance(1);
  }

  function resetFinanceFilters() {
    document.getElementById('fin-filter-month').selectedIndex = 0;
    document.getElementById('fin-filter-hotel').value = '';
    document.getElementById('fin-filter-from').value = '';
    document.getElementById('fin-filter-to').value = '';
    document.getElementById('fin-filter-q').value = '';
    loadFinance(1);
  }

  function switchFinanceTab(tab) {
    currentFinTab = tab;
    document.getElementById('tab-btn-all').classList.toggle('active', tab === 'all');
    document.getElementById('tab-btn-income').classList.toggle('active', tab === 'income');
    document.getElementById('tab-btn-expense').classList.toggle('active', tab === 'expense');
    loadFinance(1);
  }

  async function loadFinance(page = 1) {
    currentFinPage = page;
    const month     = document.getElementById('fin-filter-month').value;
    const hotel_id  = document.getElementById('fin-filter-hotel').value;
    const date_from = document.getElementById('fin-filter-from').value;
    const date_to   = document.getElementById('fin-filter-to').value;
    const q         = document.getElementById('fin-filter-q').value.trim();
    const tbody     = document.getElementById('finance-tbody');

    try {
      const res = await apiGet('finance/list_ajax', {
        page,
        type: currentFinTab,
        month,
        hotel_id,
        date_from,
        date_to,
        q,
        per_page: 15
      });

      if (!res.success) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Error loading financial records.</td></tr>`;
        return;
      }

      currentFinItems = res.data || [];
      const sum = res.summary || { total_income: 0, total_expense: 0, net_profit: 0 };

      // Update Summary Cards
      document.getElementById('fin-total-income').textContent = money(sum.total_income);
      document.getElementById('fin-total-expense').textContent = money(sum.total_expense);
      const netEl = document.getElementById('fin-net-balance');
      netEl.textContent = money(sum.net_profit);
      netEl.className = 'fs-3 fw-bold ' + (sum.net_profit >= 0 ? 'text-success' : 'text-danger');

      // Update Chart
      if (res.chart) {
        renderFinChart(res.chart);
      }

      // Render Rows
      if (!currentFinItems.length) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-wallet2 fs-1 d-block mb-2 text-muted"></i>No transactions found for the selected filter.</td></tr>`;
        document.getElementById('finance-pagination').innerHTML = '';
        return;
      }

      tbody.innerHTML = currentFinItems.map(x => {
        const isInc = (x.tx_type === 'income');
        const badge = isInc ? '<span class="badge badge-paid">Income</span>' : '<span class="badge badge-unpaid">Expense</span>';
        const amtColor = isInc ? 'text-success' : 'text-danger';
        const billBadge = x.bill_file ? `<a href="${BASE_URL + x.bill_file}" target="_blank" class="badge text-bg-light border text-primary text-decoration-none ms-1" title="View attached bill receipt"><i class="bi bi-paperclip me-1"></i>Bill</a>` : '';
        const partyDesc = x.party ? `<strong>${x.party}</strong> ${x.description ? '<span class="text-muted small">(' + x.description + ')</span>' : ''} ${billBadge}` : `${x.description || '-'} ${billBadge}`;

        return `
          <tr>
            <td>${badge}</td>
            <td>${x.date || '-'}</td>
            <td><span class="badge text-bg-light border">${x.category || 'General'}</span></td>
            <td>${partyDesc}</td>
            <td>${x.hotel_name || '-'}</td>
            <td>${x.payment_mode || 'Cash'}</td>
            <td class="fw-bold ${amtColor}">${money(x.amount)}</td>
            <td class="no-print">
              <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteFinItem('${x.tx_type}', ${x.id})">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        `;
      }).join('');

      document.getElementById('finance-pagination').innerHTML = renderPagination(res.pagination.total, res.pagination.page, res.pagination.per_page, 'loadFinance');

    } catch (err) {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Error loading financial records.</td></tr>`;
    }
  }

  function renderFinChart(chartData) {
    const ctx = document.getElementById('fin-chart');
    if (!ctx) return;

    if (finChartInstance) {
      finChartInstance.destroy();
    }

    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const textColor = isDark ? '#cbd5e1' : '#475569';
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

    finChartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: chartData.labels,
        datasets: [
          {
            label: 'Income',
            data: chartData.incomes,
            backgroundColor: '#10b981',
            borderRadius: 6,
            maxBarThickness: 32
          },
          {
            label: 'Expense',
            data: chartData.expenses,
            backgroundColor: '#ef4444',
            borderRadius: 6,
            maxBarThickness: 32
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: { color: textColor, font: { family: 'Poppins', size: 12 } }
          }
        },
        scales: {
          x: {
            ticks: { color: textColor, font: { family: 'Poppins', size: 11 } },
            grid: { display: false }
          },
          y: {
            ticks: {
              color: textColor,
              font: { family: 'Poppins', size: 11 },
              callback: val => CURRENCY + Number(val).toLocaleString('en-IN')
            },
            grid: { color: gridColor }
          }
        }
      }
    });
  }

  // Export CSV function
  async function exportFinanceCSV() {
    const month     = document.getElementById('fin-filter-month').value;
    const hotel_id  = document.getElementById('fin-filter-hotel').value;
    const date_from = document.getElementById('fin-filter-from').value;
    const date_to   = document.getElementById('fin-filter-to').value;
    const q         = document.getElementById('fin-filter-q').value.trim();

    try {
      // Fetch all items matching the filter for CSV
      const res = await apiGet('finance/list_ajax', {
        page: 1,
        per_page: 5000,
        type: currentFinTab,
        month,
        hotel_id,
        date_from,
        date_to,
        q
      });

      const list = (res.success && res.data) ? res.data : currentFinItems;
      if (!list.length) {
        swalAlert('No Data', 'No transactions found to export.', 'info');
        return;
      }

      const rows = [
        ['Type', 'Date', 'Hotel', 'Category', 'Party', 'Description', 'Payment Mode', 'Amount']
      ];

      list.forEach(x => {
        rows.push([
          x.tx_type === 'income' ? 'Income' : 'Expense',
          x.date || '',
          x.hotel_name || '',
          x.category || '',
          x.party || '',
          x.description || '',
          x.payment_mode || 'Cash',
          x.amount || 0
        ]);
      });

      const csvContent = rows.map(r => r.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\r\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `StayFlow_Income_Expense_${todayISO()}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      toast('CSV exported successfully.');

    } catch (e) {
      console.error(e);
      swalAlert('Export Failed', 'An error occurred while exporting CSV.', 'error');
    }
  }

  async function deleteFinItem(type, id) {
    const conf = await swalConfirm('Delete Transaction?', 'Are you sure you want to delete this financial record?');
    if (!conf.isConfirmed) return;

    const endpoint = (type === 'income') ? 'finance/delete_income/' + id : 'finance/delete_expense/' + id;
    const res = await apiGet(endpoint);
    if (res.success) {
      toast(res.message);
      loadFinance(currentFinPage);
    } else {
      swalAlert('Error', res.message, 'error');
    }
  }

  function openIncomeModal() {
    openModal(`
      <div class="modal-header"><h5 class="modal-title fw-bold text-success"><i class="bi bi-plus-circle me-1"></i>Add Income</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="f-income-form">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Hotel / Property</label>
              <select class="form-select" name="hotel_id">
                <option value="">Select Hotel</option>
                <?php foreach ($hotels as $h): ?>
                  <option value="<?= $h->id ?>"><?= htmlspecialchars($h->name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control" name="amount" required placeholder="0.00">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Category</label>
              <select class="form-select" name="category">
                <option value="Rent">Rent</option>
                <option value="Security Deposit">Security Deposit</option>
                <option value="Laundry">Laundry</option>
                <option value="Food / Mess">Food / Mess</option>
                <option value="Electricity">Electricity</option>
                <option value="Maintenance Recovery">Maintenance Recovery</option>
                <option value="Other">Other Income</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Date</label>
              <input type="date" class="form-control" name="date" value="${todayISO()}">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Received From</label>
              <input class="form-control" name="received_from" placeholder="e.g. Tenant name or party">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Payment Mode</label>
              <select class="form-select" name="payment_mode">
                <option value="Cash">Cash</option>
                <option value="UPI">UPI</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Cheque">Cheque</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Description / Notes</label>
              <textarea class="form-control" name="description" placeholder="Optional notes..."></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success fw-semibold px-4" id="btn-save-inc">Save Income</button>
      </div>`, () => {
      document.getElementById('btn-save-inc').onclick = async () => {
        const form = document.getElementById('f-income-form');
        const fd = new FormData(form);
        const res = await apiPost('finance/add_income', fd);
        if (res.success) {
          toast(res.message);
          closeModal();
          loadFinance(1);
        } else {
          swalAlert('Error', res.message, 'error');
        }
      };
    });
  }

  function openExpenseModal() {
    openModal(`
      <div class="modal-header"><h5 class="modal-title fw-bold text-danger"><i class="bi bi-dash-circle me-1"></i>Add Expense</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="f-expense-form">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Hotel / Property</label>
              <select class="form-select" name="hotel_id">
                <option value="">Select Hotel</option>
                <?php foreach ($hotels as $h): ?>
                  <option value="<?= $h->id ?>"><?= htmlspecialchars($h->name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control" name="amount" required placeholder="0.00">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Category</label>
              <select class="form-select" name="category">
                <option value="Electricity Bill">Electricity Bill</option>
                <option value="Water Bill">Water Bill</option>
                <option value="Internet / Wi-Fi">Internet / Wi-Fi</option>
                <option value="Maintenance & Repairs">Maintenance & Repairs</option>
                <option value="Cleaning / Housekeeping">Cleaning / Housekeeping</option>
                <option value="Staff Salary">Staff Salary</option>
                <option value="Groceries / Food">Groceries / Food</option>
                <option value="Property Rent">Property Rent</option>
                <option value="Other">Other Expense</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Date</label>
              <input type="date" class="form-control" name="date" value="${todayISO()}">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Paid To</label>
              <input class="form-control" name="paid_to" placeholder="e.g. Vendor or person name">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Payment Mode</label>
              <select class="form-select" name="payment_mode">
                <option value="Cash">Cash</option>
                <option value="UPI">UPI</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Cheque">Cheque</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Upload Bill / Receipt <span class="text-muted fw-normal small">(Optional)</span></label>
              <input type="file" class="form-control" name="bill_file" accept="image/*,.pdf">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Description / Notes</label>
              <textarea class="form-control" name="description" placeholder="Optional notes..."></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger fw-semibold px-4" id="btn-save-exp">Save Expense</button>
      </div>`, () => {
      document.getElementById('btn-save-exp').onclick = async () => {
        const form = document.getElementById('f-expense-form');
        const fd = new FormData(form);
        const res = await apiPost('finance/add_expense', fd);
        if (res.success) {
          toast(res.message);
          closeModal();
          loadFinance(1);
        } else {
          swalAlert('Error', res.message, 'error');
        }
      };
    });
  }

  // Initial Load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => loadFinance(1));
  } else {
    loadFinance(1);
  }
</script>
