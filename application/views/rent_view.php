<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-enter">
  <div class="d-flex justify-content-between flex-wrap gap-2 mb-3 align-items-center">
    <div class="section-title">Rent Collection</div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <select class="form-select w-auto" id="rent-month-year" onchange="loadRent(1)">
        <option value="all" <?= ($selected_month_year === 'all') ? 'selected' : '' ?>>All Months (All Time)</option>
        <?php for ($i = 0; $i < 12; $i++): ?>
          <?php
            $t = strtotime("-$i months");
            $val = date('Y-n', $t);
            $label = date('F Y', $t);
            $sel = ($selected_month_year === $val) ? 'selected' : '';
          ?>
          <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
        <?php endfor; ?>
      </select>
      <!-- <button class="quick-btn" onclick="generateRentBills()"><i class="bi bi-receipt me-1"></i>Generate Rent</button> -->
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-muted small">Expected</div><div class="fs-4 fw-bold" id="stat-expected"><?= ($settings->currency ?? '₹') . number_format($expected_amt ?? 0, 0) ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-muted small">Collected</div><div class="fs-4 fw-bold text-success" id="stat-collected"><?= ($settings->currency ?? '₹') . number_format($collected_amt ?? 0, 0) ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-muted small">Pending</div><div class="fs-4 fw-bold text-danger" id="stat-pending"><?= ($settings->currency ?? '₹') . number_format(max(0, ($expected_amt ?? 0) - ($collected_amt ?? 0)), 0) ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-muted small">Collection Rate</div><div class="fs-4 fw-bold" id="stat-rate"><?= intval($collection_rate ?? 0) ?>%</div></div></div>
  </div>

  <div class="progress mb-3" style="height:12px">
    <div class="progress-bar bg-success" id="stat-progress-bar" style="width:<?= intval($collection_rate ?? 0) ?>%"></div>
  </div>

  <!-- Filter & Tabs Bar -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <ul class="nav nav-pills" id="rentPills">
        <li class="nav-item">
          <button class="nav-link" id="tab-btn-all" onclick="switchRentTab('All')">
            <i class="bi bi-collection me-1"></i>All
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link active" id="tab-btn-unpaid" onclick="switchRentTab('Unpaid')">
            <i class="bi bi-clock-history me-1"></i>Unpaid
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" id="tab-btn-paid" onclick="switchRentTab('Paid')">
            <i class="bi bi-check2-circle me-1"></i>Paid
          </button>
        </li>
      </ul>

      <button class="btn btn-sm btn-outline-danger fw-semibold" id="btn-see-unpaid" onclick="seeAllUnpaid()">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>See All Unpaid
      </button>
    </div>

    <div class="d-flex gap-2">
      <input class="form-control form-control-sm" style="max-width:240px" id="rent-filter-q" placeholder="Search tenant / room..." />
      <button type="button" class="btn btn-sm btn-primary" onclick="loadRent(1)"><i class="bi bi-search"></i> Search</button>
    </div>
  </div>

  <!-- TABLE CONTENT -->
  <div class="stat-card table-wrap">
    <table class="table align-middle">
      <thead id="rent-table-thead">
        <tr>
          <th>Tenant</th><th>Room</th><th>Hotel</th><th>Month</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Action</th>
        </tr>
      </thead>
      <tbody id="rent-table-tbody">
        <tr><td colspan="8" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading rent records...</td></tr>
      </tbody>
    </table>
  </div>

  <!-- Pagination Host -->
  <div id="rent-pagination"></div>
</div>

<script>
  let currentRentTab = 'Unpaid';
  let currentRentPage = 1;
  let rentSearchTimer = null;
  const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  function formatMonth(m, y) {
    if (!m || !y) return '-';
    return (monthNames[Number(m)] || m) + ' ' + y;
  }

  function statusBadge(st) {
    if (st === 'Paid') {
      return '<span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>';
    }
    return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Unpaid</span>';
  }

  document.getElementById('rent-filter-q').addEventListener('input', () => {
    clearTimeout(rentSearchTimer);
    rentSearchTimer = setTimeout(() => {
      loadRent(1);
    }, 350);
  });

  function seeAllUnpaid() {
    document.getElementById('rent-month-year').value = 'all';
    switchRentTab('Unpaid');
  }

  function switchRentTab(tab) {
    currentRentTab = tab;
    ['all', 'unpaid', 'paid'].forEach(t => {
      const btn = document.getElementById('tab-btn-' + t);
      if (btn) btn.classList.toggle('active', t.toLowerCase() === tab.toLowerCase());
    });

    if (tab === 'All') {
      document.getElementById('rent-table-thead').innerHTML = `
        <tr><th>Tenant</th><th>Room</th><th>Hotel</th><th>Month</th><th>Amount</th><th>Due / Paid Date</th><th>Status</th><th>Action</th></tr>
      `;
    } else if (tab === 'Unpaid') {
      document.getElementById('rent-table-thead').innerHTML = `
        <tr><th>Tenant</th><th>Room</th><th>Hotel</th><th>Month</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
      `;
    } else {
      document.getElementById('rent-table-thead').innerHTML = `
        <tr><th>Tenant</th><th>Room</th><th>Hotel</th><th>Month</th><th>Amount</th><th>Paid Date</th><th>Mode</th><th>Status</th><th>Action</th></tr>
      `;
    }
    loadRent(1);
  }

  async function loadRent(page = 1) {
    currentRentPage = page;
    const my = document.getElementById('rent-month-year').value;
    const q = document.getElementById('rent-filter-q').value.trim();
    const tbody = document.getElementById('rent-table-tbody');

    try {
      const res = await apiGet('rent/list_ajax', { page, status: currentRentTab, month_year: my, q, per_page: 10 });
      if (!res.success) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Failed to load rent records.</td></tr>`;
        return;
      }

      // Update Summary Cards
      if (res.summary) {
        document.getElementById('stat-expected').textContent = money(res.summary.expected);
        document.getElementById('stat-collected').textContent = money(res.summary.collected);
        document.getElementById('stat-pending').textContent = money(res.summary.pending);
        document.getElementById('stat-rate').textContent = res.summary.rate + '%';
        document.getElementById('stat-progress-bar').style.width = res.summary.rate + '%';
      }

      const rents = res.data || [];

      if (!rents.length) {
        const emptyMsg = (currentRentTab === 'Unpaid') ?
          'All caught up! No pending rent invoices found.' :
          (currentRentTab === 'Paid' ? 'No rent payments recorded yet.' : 'No rent records found.');
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-cash-stack fs-1 text-muted d-block mb-2"></i>${emptyMsg}</td></tr>`;
        document.getElementById('rent-pagination').innerHTML = '';
        return;
      }

      if (currentRentTab === 'All') {
        tbody.innerHTML = rents.map(x => `
          <tr>
            <td class="fw-semibold">
              <a href="${BASE_URL}tenants/detail/${x.user_id}" class="text-decoration-none text-reset">${x.tenant_name || 'Tenant'}</a>
            </td>
            <td>${x.room_number ? 'Room ' + x.room_number : '-'}</td>
            <td>${x.hotel_name || '-'}</td>
            <td><span class="badge bg-secondary-subtle text-body border">${formatMonth(x.month, x.year)}</span></td>
            <td class="fw-semibold ${x.status === 'Paid' ? 'text-success' : 'text-danger'}">${money(x.amount)}</td>
            <td>${x.status === 'Paid' ? (x.paid_date || '-') : (x.due_date || '-')}</td>
            <td>${statusBadge(x.status)}</td>
            <td>
              ${x.status === 'Paid' ?
                `<button class="btn btn-sm btn-outline-primary" onclick='showReceiptModal(${JSON.stringify(x)})'><i class="bi bi-receipt me-1"></i>Receipt</button>` :
                `<button class="btn btn-sm btn-success" onclick="openPayModal(${x.id})"><i class="bi bi-check-lg me-1"></i>Mark as Paid</button>`
              }
            </td>
          </tr>
        `).join('');
      } else if (currentRentTab === 'Unpaid') {
        tbody.innerHTML = rents.map(x => `
          <tr>
            <td class="fw-semibold">
              <a href="${BASE_URL}tenants/detail/${x.user_id}" class="text-decoration-none text-reset">${x.tenant_name || 'Tenant'}</a>
            </td>
            <td>${x.room_number ? 'Room ' + x.room_number : '-'}</td>
            <td>${x.hotel_name || '-'}</td>
            <td><span class="badge bg-secondary-subtle text-body border">${formatMonth(x.month, x.year)}</span></td>
            <td class="fw-semibold text-danger">${money(x.amount)}</td>
            <td><span class="text-danger fw-semibold">${x.due_date || '-'}</span></td>
            <td>${statusBadge(x.status)}</td>
            <td><button class="btn btn-sm btn-success" onclick="openPayModal(${x.id})"><i class="bi bi-check-lg me-1"></i>Mark as Paid</button></td>
          </tr>
        `).join('');
      } else {
        tbody.innerHTML = rents.map(x => `
          <tr>
            <td class="fw-semibold">
              <a href="${BASE_URL}tenants/detail/${x.user_id}" class="text-decoration-none text-reset">${x.tenant_name || 'Tenant'}</a>
            </td>
            <td>${x.room_number ? 'Room ' + x.room_number : '-'}</td>
            <td>${x.hotel_name || '-'}</td>
            <td><span class="badge bg-secondary-subtle text-body border">${formatMonth(x.month, x.year)}</span></td>
            <td class="fw-semibold text-success">${money(x.amount)}</td>
            <td>${x.paid_date || '-'}</td>
            <td>
              <span class="badge bg-light text-dark border">${x.payment_mode || 'Cash'}</span>
              ${x.receipt_file ? `<a href="${BASE_URL + x.receipt_file}" target="_blank" class="badge text-bg-light border text-primary text-decoration-none ms-1" title="View uploaded receipt slip"><i class="bi bi-paperclip"></i></a>` : ''}
            </td>
            <td>${statusBadge(x.status)}</td>
            <td><button class="btn btn-sm btn-outline-primary" onclick='showReceiptModal(${JSON.stringify(x)})'><i class="bi bi-receipt me-1"></i>Receipt</button></td>
          </tr>
        `).join('');
      }

      document.getElementById('rent-pagination').innerHTML = renderPagination(res.pagination.total, res.pagination.page, res.pagination.per_page, 'loadRent');

    } catch (err) {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Error loading rent list.</td></tr>`;
    }
  }

  // Initial load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => loadRent(1));
  } else {
    loadRent(1);
  }

  async function generateRentBills() {
    const my = document.getElementById('rent-month-year').value;
    const [year, month] = my.split('-');
    const conf = await swalConfirm('Generate Invoices?', `Do you want to generate rent bills for all active tenants for this month?`);
    if (!conf.isConfirmed) return;

    const res = await apiPost('rent/generate_monthly', { month, year });
    if (res.success) {
      toast(res.message);
      loadRent(1);
    } else {
      swalAlert('Info', res.message, 'info');
    }
  }

  function openPayModal(rid) {
    openModal(`
      <div class="modal-header"><h5 class="modal-title fw-bold">Record Rent Payment</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="f-pay-rent">
          <input type="hidden" name="rent_id" value="${rid}">
          <label class="form-label fw-semibold">Payment Date</label>
          <input type="date" class="form-control mb-2" name="paid_date" value="${todayISO()}">
          <label class="form-label fw-semibold">Payment Mode</label>
          <select class="form-select mb-2" name="payment_mode" id="rent-payment-mode" onchange="toggleRentPayReceipt(this.value)">
            <option value="UPI" selected>UPI</option>
            <option value="Cash">Cash</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cheque">Cheque</option>
          </select>
          <div id="rent-receipt-upload-wrap" class="mb-2">
            <label class="form-label fw-semibold">Upload Receipt <span class="text-muted fw-normal small">(Optional Screenshot / Slip)</span></label>
            <input type="file" class="form-control" name="receipt_file" id="rent-receipt-file" accept="image/*,.pdf">
          </div>
          <label class="form-label fw-semibold">Notes / Remarks</label>
          <textarea class="form-control" name="remarks" placeholder="Optional notes..."></textarea>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success px-4 fw-semibold" id="btn-save-pay">Confirm & Record</button>
      </div>`, () => {
      document.getElementById('btn-save-pay').onclick = async () => {
        const form = document.getElementById('f-pay-rent');
        const fd = new FormData(form);
        const res = await apiPost('rent/pay', fd);
        if (res.success) {
          toast(res.message);
          closeModal();
          loadRent(currentRentPage);
        } else {
          swalAlert('Error', res.message, 'error');
        }
      };
    });
  }

  function toggleRentPayReceipt(mode) {
    const wrap = document.getElementById('rent-receipt-upload-wrap');
    const input = document.getElementById('rent-receipt-file');
    if (!wrap) return;
    if (mode === 'Cash') {
      wrap.style.display = 'none';
      if (input) input.value = '';
    } else {
      wrap.style.display = 'block';
    }
  }

  function showReceiptModal(x) {
    openModal(`
      <div class="modal-header"><h5 class="modal-title fw-bold">Rent Receipt</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body p-4">
        <div class="p-4 border rounded-3 bg-body" id="print-receipt-box">
          <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
            <div>
              <h4 class="fw-bold mb-0 text-primary"><?= htmlspecialchars($settings->crm_name ?? 'StayFlow CRM') ?></h4>
              <div class="text-muted small">Rent Payment Receipt</div>
            </div>
            <div class="text-end">
              <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">PAID</span>
              <div class="small text-muted mt-1">Receipt #${x.id}</div>
            </div>
          </div>
          <table class="table table-sm table-borderless">
            <tr><td>Tenant Name:</td><td><strong>${x.tenant_name}</strong></td></tr>
            <tr><td>Room / Property:</td><td>Room ${x.room_number || '-'} (${x.hotel_name || ''})</td></tr>
            <tr><td>Rent Month:</td><td>${x.month}/${x.year}</td></tr>
            <tr><td>Amount Paid:</td><td><strong class="text-success">${money(x.amount)}</strong></td></tr>
            <tr><td>Payment Date:</td><td>${x.paid_date || '-'}</td></tr>
            <tr><td>Payment Mode:</td><td>${x.payment_mode || 'Cash'}</td></tr>
            ${x.receipt_file ? `<tr><td>Receipt Slip:</td><td><a href="${BASE_URL + x.receipt_file}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-paperclip me-1"></i>View Uploaded Slip</a></td></tr>` : ''}
            ${x.reference ? `<tr><td>Reference:</td><td>${x.reference}</td></tr>` : ''}
          </table>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary" onclick="window.print()">Print Receipt</button></div>`);
  }
</script>
