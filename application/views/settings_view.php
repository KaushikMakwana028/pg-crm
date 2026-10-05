<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-enter">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1">Rent Billing Policy</h4>
      <div class="text-muted">Configure your standard monthly rent due date and automatic billing rule for all tenants.</div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Main Policy Card -->
    <div class="col-lg-7">
      <div class="stat-card shadow-sm p-4">
        <form id="f-rent-policy">
          <!-- Policy Header -->
          <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div class="stat-card icon shadow-sm" style="width: 46px; height: 46px; border-radius: 12px; background: linear-gradient(135deg, #f59e0b, #d97706);">
              <i class="bi bi-calendar2-week fs-5"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-0">Monthly Rent Due Date</h5>
              <div class="text-muted small">Standard calendar day of the month when rent bills are due</div>
            </div>
          </div>

          <!-- Due Day Input -->
          <div class="mb-4">
            <label class="form-label fw-bold mb-2">Default Rent Due Day (1 – 31) <span class="text-danger">*</span></label>
            <div class="row align-items-center g-3 mb-2">
              <div class="col-sm-5">
                <div class="input-group input-group-lg">
                  <span class="input-group-text bg-body-secondary"><i class="bi bi-calendar3 text-warning"></i></span>
                  <input type="number" class="form-control fw-bold text-center fs-4" name="rent_due_date" id="set-rent-day" value="<?= intval($settings->rent_due_date ?? 10) ?>" min="1" max="31" required oninput="onDueDayChange(this.value)">
                  <span class="input-group-text bg-body-secondary fw-semibold">th</span>
                </div>
              </div>
              <div class="col-sm-7">
                <div class="text-muted small">
                  Rent bills will fall due on the <strong id="lbl-due-day" class="text-primary"><?= intval($settings->rent_due_date ?? 10) ?>th</strong> of every calendar month.
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Presets -->
          <div class="mb-4">
            <label class="form-label small text-muted fw-semibold mb-2">Quick Select Popular Days</label>
            <div class="d-flex gap-2 flex-wrap">
              <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1" onclick="selectDueDay(1)">1st (Start of month)</button>
              <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1" onclick="selectDueDay(5)">5th of month</button>
              <button type="button" class="btn btn-sm btn-outline-primary px-3 py-1 fw-semibold" onclick="selectDueDay(10)">10th (Standard)</button>
              <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1" onclick="selectDueDay(15)">15th (Mid-month)</button>
            </div>
          </div>

          <!-- Automatic Synchronization Notice -->
          <div class="p-3 rounded-3 bg-warning-subtle border border-warning-subtle mb-4">
            <div class="d-flex gap-3 align-items-start">
              <i class="bi bi-arrow-repeat text-warning fs-4 mt-1 flex-shrink-0"></i>
              <div>
                <div class="fw-semibold text-dark dark:text-light">Automatic Invoice Synchronization</div>
                <div class="small text-muted mt-1" style="line-height: 1.6;">
                  When you save this policy, <strong>all unpaid rent bills</strong> across tenants automatically align their due dates to this day of the month.
                </div>
              </div>
            </div>
          </div>

          <!-- Save Button -->
          <div class="pt-3 border-top d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-lg px-5 fw-semibold shadow-sm" id="btn-save-policy">
              <i class="bi bi-check2-circle me-1"></i> Save Billing Policy
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right Column: Current Policy Summary & Guidelines -->
    <div class="col-lg-5">
      <!-- Active Policy Summary Card -->
      <div class="stat-card p-4 mb-4 shadow-sm">
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
          <i class="bi bi-shield-check text-success fs-5"></i> Current Policy Summary
        </h6>
        <div class="p-3 rounded-3 border bg-body-tertiary">
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <span class="text-muted small">Standard Rent Due Date</span>
            <span class="fw-bold text-primary fs-6" id="card-due-day"><?= intval($settings->rent_due_date ?? 10) ?>th of each month</span>
          </div>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <span class="text-muted small">Billing Frequency</span>
            <span class="badge bg-secondary-subtle text-secondary">Monthly Recurring</span>
          </div>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <span class="text-muted small">Tenant Scope</span>
            <span class="badge bg-success-subtle text-success">All Active Tenants</span>
          </div>
          <div class="d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Currency</span>
            <span class="fw-semibold text-body"><?= htmlspecialchars($settings->currency ?? '₹') ?> (Configured)</span>
          </div>
        </div>
      </div>

      <!-- Guidelines Card -->
      <div class="stat-card p-4 shadow-sm">
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-muted">
          <i class="bi bi-lightbulb text-warning fs-5"></i> Billing Best Practices
        </h6>
        <ul class="text-muted small ps-3 mb-0" style="line-height: 1.8;">
          <li>Most hostels and PGs set the rent due date between the <strong>1st and 10th</strong> of each month to align with tenant salary cycles.</li>
          <li>Grace periods are usually provided until the 10th before applying late reminders.</li>
          <li>All future generated monthly invoices will inherit this default due day automatically.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
  function selectDueDay(day) {
    document.getElementById('set-rent-day').value = day;
    onDueDayChange(day);
  }

  function onDueDayChange(day) {
    const val = parseInt(day) || 10;
    const clamped = Math.max(1, Math.min(31, val));
    document.getElementById('lbl-due-day').textContent = clamped + 'th';
    document.getElementById('card-due-day').textContent = clamped + 'th of each month';
  }

  // Save Rent Policy Form
  document.getElementById('f-rent-policy').onsubmit = async e => {
    e.preventDefault();
    const btn = document.getElementById('btn-save-policy');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving Policy...';

    const fd = new FormData(e.target);
    const res = await apiPost('settings/save', fd);
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save Billing Policy';

    if (res.success) {
      toast(res.message, 'success');
      setTimeout(() => location.reload(), 600);
    } else {
      swalAlert('Error', res.message, 'error');
    }
  };
</script>
