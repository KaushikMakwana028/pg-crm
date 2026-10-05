<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-enter">
  <a href="<?= base_url('tenants') ?>" class="btn btn-link px-0 mb-2 text-decoration-none text-muted">
    <i class="bi bi-arrow-left me-1"></i> Back to Tenants
  </a>

  <!-- Tenant Header Banner -->
  <div class="stat-card mb-4 shadow-sm">
    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
      <div class="d-flex gap-3 align-items-center">
        <div class="avatar lg shadow-sm">
          <?php if (!empty($tenant->profile_image)): ?>
            <img src="<?= base_url($tenant->profile_image) ?>" alt="<?= htmlspecialchars($tenant->name) ?>" />
          <?php else: ?>
            <?= strtoupper(substr($tenant->name, 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <h4 class="fw-bold mb-0"><?= htmlspecialchars($tenant->name) ?></h4>
            <?php if ($tenant->status == 0 || !empty($tenant->vacate_date)): ?>
              <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                <i class="bi bi-box-arrow-right me-1"></i>Vacated (<?= !empty($tenant->vacate_date) ? date('d M Y', strtotime($tenant->vacate_date)) : 'Past' ?>)
              </span>
            <?php else: ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-check-circle me-1"></i>Active Tenant
              </span>
            <?php endif; ?>
          </div>
          <div class="text-muted mt-1">
            <i class="bi bi-door-closed me-1"></i><?= !empty($tenant->room_number) ? 'Room ' . htmlspecialchars($tenant->room_number) : 'No room assigned' ?>
            <span class="mx-1">·</span>
            <i class="bi bi-building me-1"></i><?= htmlspecialchars($tenant->hotel_name ?? 'Property') ?>
          </div>
          <div class="small text-muted mt-1">
            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($tenant->phone ?: '—') ?>
            <span class="mx-2">·</span>
            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($tenant->email ?: '—') ?>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-primary" onclick="showIdCardModal()">
          <i class="bi bi-person-badge me-1"></i>ID Card
        </button>
        <?php if ($tenant->status == 1 && empty($tenant->vacate_date)): ?>
          <!-- <button class="btn btn-outline-secondary" onclick="openTransferModal()">
            <i class="bi bi-arrow-left-right me-1"></i>Transfer Room
          </button> -->
        <?php endif; ?>
        <button class="btn btn-primary" onclick="openEditTenantModal()">
          <i class="bi bi-pencil me-1"></i>Edit
        </button>
        <?php if ($tenant->status == 1 && empty($tenant->vacate_date)): ?>
          <button class="btn btn-outline-danger" onclick="openVacateModal()">
            <i class="bi bi-box-arrow-right me-1"></i>Mark as Vacated
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Key Metrics 4-Cards Row -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="stat-card glass d-flex gap-3 align-items-center">
        <div class="icon" style="background:#6366f1"><i class="bi bi-wallet2"></i></div>
        <div>
          <div class="text-muted small">Monthly Rent</div>
          <div class="fs-5 fw-bold text-primary"><?= ($settings->currency ?? '₹') . number_format($tenant->rent_amount ?? 0, 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stat-card glass d-flex gap-3 align-items-center">
        <div class="icon" style="background:#10b981"><i class="bi bi-cash-stack"></i></div>
        <div>
          <div class="text-muted small">Total Rent Paid</div>
          <div class="fs-5 fw-bold text-success"><?= ($settings->currency ?? '₹') . number_format($total_paid ?? 0, 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stat-card glass d-flex gap-3 align-items-center">
        <div class="icon" style="background:#ef4444"><i class="bi bi-exclamation-triangle"></i></div>
        <div>
          <div class="text-muted small">Pending Rent</div>
          <div class="fs-5 fw-bold text-danger"><?= ($settings->currency ?? '₹') . number_format($total_pending ?? 0, 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stat-card glass d-flex gap-3 align-items-center">
        <div class="icon" style="background:#f59e0b"><i class="bi bi-shield-lock"></i></div>
        <div>
          <div class="text-muted small">Security Deposit</div>
          <div class="fs-5 fw-bold"><?= ($settings->currency ?? '₹') . number_format($tenant->deposit ?? 0, 0) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Dedicated Security Deposit Section / Lifecycle Banner -->
  <div class="mb-4">
    <?php 
      $isVacated = ($tenant->status == 0 || !empty($tenant->vacate_date));
      $depStatus = $tenant->deposit_status ?? 'Pending';
      $depAmount = floatval($tenant->deposit ?? 0);
    ?>

    <?php if ($depStatus === 'Refunded'): ?>
      <!-- State 1: Refunded -->
      <div class="stat-card border-2" style="border-color: #06b6d4 !important; background: rgba(6, 182, 212, 0.08); border-radius: 16px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-grid place-items-center bg-white text-info shadow-sm" style="width: 52px; height: 52px; font-size: 26px;">
              <i class="bi bi-arrow-counterclockwise"></i>
            </div>
            <div>
              <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0 text-dark dark:text-light">Security Deposit Returned</h5>
                <span class="badge bg-info text-dark px-2 py-1 fw-bold"><i class="bi bi-check2-all me-1"></i>REFUNDED</span>
              </div>
              <div class="text-muted small mt-1">
                Amount refunded: <strong><?= ($settings->currency ?? '₹') . number_format($tenant->deposit_refund_amount ?: $depAmount, 2) ?></strong>
                <?php if (!empty($tenant->deposit_refund_date)): ?>
                  · Date: <?= date('d M Y', strtotime($tenant->deposit_refund_date)) ?>
                <?php endif; ?>
                · Deposit returned to tenant upon vacate.
              </div>
            </div>
          </div>
          <div class="text-end">
            <div class="fs-4 fw-bold text-info"><?= ($settings->currency ?? '₹') . number_format($tenant->deposit_refund_amount ?: $depAmount, 0) ?></div>
            <span class="badge bg-light text-secondary border">Settled</span>
          </div>
        </div>
      </div>

    <?php elseif ($depStatus === 'Paid' && $isVacated): ?>
      <!-- State 2: Vacated & Paid (Ready to Return) -->
      <div class="stat-card border-2" style="border-color: #6366f1 !important; background: rgba(99, 102, 241, 0.08); border-radius: 16px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-grid place-items-center bg-white text-primary shadow-sm" style="width: 52px; height: 52px; font-size: 26px;">
              <i class="bi bi-arrow-return-left"></i>
            </div>
            <div>
              <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0 text-primary">Return Security Deposit</h5>
                <span class="badge bg-warning text-dark px-2 py-1 fw-bold">REFUND PENDING</span>
              </div>
              <div class="text-muted small mt-1">
                Tenant has vacated on <?= !empty($tenant->vacate_date) ? date('d M Y', strtotime($tenant->vacate_date)) : 'vacate date' ?>. Security deposit of <strong><?= ($settings->currency ?? '₹') . number_format($depAmount, 0) ?></strong> is held and ready to be returned.
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3">
            <div class="text-end">
              <div class="fs-4 fw-bold text-primary"><?= ($settings->currency ?? '₹') . number_format($depAmount, 0) ?></div>
              <span class="badge bg-primary-subtle text-primary border">Held by Admin</span>
            </div>
            <button class="btn btn-primary fw-bold px-4 py-2 shadow-sm" onclick="openRefundDepositModal()">
              <i class="bi bi-arrow-return-left me-1"></i>Return Deposit
            </button>
          </div>
        </div>
      </div>

    <?php elseif ($depStatus === 'Paid'): ?>
      <!-- State 3: Active & Paid -->
      <div class="stat-card border-2" style="border-color: #10b981 !important; background: rgba(16, 185, 129, 0.08); border-radius: 16px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-grid place-items-center bg-white text-success shadow-sm" style="width: 52px; height: 52px; font-size: 26px;">
              <i class="bi bi-shield-fill-check"></i>
            </div>
            <div>
              <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0 text-success">Security Deposit Paid</h5>
                <span class="badge bg-success px-2 py-1 fw-bold"><i class="bi bi-check-circle me-1"></i>PAID AT CHECK-IN</span>
              </div>
              <div class="text-muted small mt-1">
                Security deposit received and safely secured. Will be returned to tenant when they vacate the room.
              </div>
            </div>
          </div>
          <div class="text-end">
            <div class="fs-4 fw-bold text-success"><?= ($settings->currency ?? '₹') . number_format($depAmount, 0) ?></div>
            <span class="badge bg-success-subtle text-success border border-success-subtle">Secured</span>
          </div>
        </div>
      </div>

    <?php else: ?>
      <!-- State 4: Pending / Not Paid -->
      <div class="stat-card border-2" style="border-color: #f59e0b !important; background: rgba(245, 158, 11, 0.09); border-radius: 16px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-grid place-items-center bg-white text-warning shadow-sm" style="width: 52px; height: 52px; font-size: 26px;">
              <i class="bi bi-shield-fill-exclamation"></i>
            </div>
            <div>
              <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0 text-warning-emphasis">Security Deposit Pending</h5>
                <span class="badge bg-warning text-dark px-2 py-1 fw-bold"><i class="bi bi-clock-history me-1"></i>NOT PAID</span>
              </div>
              <div class="text-muted small mt-1">
                Security deposit of <strong><?= ($settings->currency ?? '₹') . number_format($depAmount, 0) ?></strong> has not been collected yet.
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3">
            <div class="text-end">
              <div class="fs-4 fw-bold text-danger"><?= ($settings->currency ?? '₹') . number_format($depAmount, 0) ?></div>
              <span class="badge bg-warning-subtle text-warning border">Payment Due</span>
            </div>
            <button class="btn btn-warning text-dark fw-bold px-4 py-2 shadow-sm" onclick="openPayDepositModal()">
              <i class="bi bi-cash-stack me-1"></i>Collect / Pay Now
            </button>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Details Row: 2 Balanced Columns -->
  <div class="row g-4 mb-4">
    <!-- Left Column: Personal Information & Stay -->
    <div class="col-lg-6">
      <div class="stat-card p-4">
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 border-bottom pb-2">
          <i class="bi bi-person-lines-fill text-primary"></i> Personal & Stay Details
        </h6>
        <div class="row g-3 small">
          <div class="col-6">
            <span class="text-muted d-block">Check-in Date:</span>
            <strong class="text-dark dark:text-light"><?= !empty($tenant->check_in) ? date('d M Y', strtotime($tenant->check_in)) : '—' ?></strong>
          </div>
          <div class="col-6">
            <span class="text-muted d-block">Status:</span>
            <?php if ($tenant->status == 1): ?>
              <span class="badge bg-success-subtle text-success">Active Occupant</span>
            <?php else: ?>
              <span class="badge bg-secondary-subtle text-secondary">Vacated</span>
            <?php endif; ?>
          </div>
          <?php if (!empty($tenant->vacate_date) || $tenant->status == 0): ?>
            <div class="col-6">
              <span class="text-muted d-block">Check-out (Vacate) Date:</span>
              <strong class="text-danger"><?= !empty($tenant->vacate_date) ? date('d M Y', strtotime($tenant->vacate_date)) : '—' ?></strong>
            </div>
          <?php endif; ?>
          <div class="col-6">
            <span class="text-muted d-block">Date of Birth:</span>
            <strong><?= !empty($tenant->dob) ? date('d M Y', strtotime($tenant->dob)) : '—' ?></strong>
          </div>
          <div class="col-6">
            <span class="text-muted d-block">Occupation:</span>
            <strong><?= htmlspecialchars($tenant->occupation ?: '—') ?></strong>
          </div>
          <div class="col-6">
            <span class="text-muted d-block">Company / College:</span>
            <strong><?= htmlspecialchars($tenant->company ?: '—') ?></strong>
          </div>
          <div class="col-12">
            <span class="text-muted d-block">Permanent Address:</span>
            <strong><?= htmlspecialchars($tenant->address ?: '—') ?></strong>
          </div>
          <div class="col-6">
            <span class="text-muted d-block">Emergency Contact:</span>
            <strong><?= htmlspecialchars($tenant->emergency_name ?: '—') ?></strong>
          </div>
          <div class="col-6">
            <span class="text-muted d-block">Emergency Phone:</span>
            <strong><?= htmlspecialchars($tenant->emergency_phone ?: '—') ?></strong>
          </div>
          <div class="col-12 pt-2 border-top">
            <span class="text-muted d-block mb-1">Notes / Transfer History:</span>
            <div class="p-2 rounded bg-light border text-secondary" style="white-space: pre-wrap; font-size: 0.85rem; max-height: 100px; overflow-y: auto;">
              <?= htmlspecialchars($tenant->notes ?: 'No additional notes.') ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Verification & KYC Documents (with Police Verification) -->
    <div class="col-lg-6">
      <div class="stat-card p-4">
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 border-bottom pb-2">
          <i class="bi bi-file-earmark-check-fill text-success"></i> Document Proofs & Verification
        </h6>
        
        <div class="d-flex flex-column gap-3">
          <!-- Aadhar Card -->
          <div class="p-3 rounded border bg-light-subtle d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold text-dark dark:text-light mb-1">
                <i class="bi bi-person-badge text-primary me-1"></i>Aadhar Card
              </div>
              <div class="small text-muted">Number: <strong><?= htmlspecialchars($tenant->aadhar ?: 'Not provided') ?></strong></div>
            </div>
            <div>
              <?php if (!empty($tenant->aadhar_file)): ?>
                <a href="<?= base_url($tenant->aadhar_file) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-eye me-1"></i>View File
                </a>
              <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary">No upload</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- PAN Card -->
          <div class="p-3 rounded border bg-light-subtle d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold text-dark dark:text-light mb-1">
                <i class="bi bi-credit-card text-success me-1"></i>PAN Card
              </div>
              <div class="small text-muted">Number: <strong><?= htmlspecialchars($tenant->pan ?: 'Not provided') ?></strong></div>
            </div>
            <div>
              <?php if (!empty($tenant->pan_file)): ?>
                <a href="<?= base_url($tenant->pan_file) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                  <i class="bi bi-eye me-1"></i>View File
                </a>
              <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary">No upload</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Police Verification Document -->
          <div class="p-3 rounded border bg-light-subtle d-flex justify-content-between align-items-center">
            <div>
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fw-bold text-dark dark:text-light">
                  <i class="bi bi-shield-check text-info me-1"></i>Police Verification
                </span>
                <span class="badge bg-secondary-subtle text-secondary small">Optional</span>
              </div>
              <div class="small text-muted">
                <?php if (!empty($tenant->police_verification_file)): ?>
                  <span class="text-success"><i class="bi bi-check-circle me-1"></i>Document attached</span>
                <?php else: ?>
                  Police clearance certificate / NOC not uploaded
                <?php endif; ?>
              </div>
            </div>
            <div>
              <?php if (!empty($tenant->police_verification_file)): ?>
                <a href="<?= base_url($tenant->police_verification_file) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                  <i class="bi bi-eye me-1"></i>View Document
                </a>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openEditTenantModal()">
                  <i class="bi bi-upload me-1"></i>Upload
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Rent History Table -->
  <div class="stat-card table-wrap p-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div>
        <h5 class="fw-bold mb-0">Rent Billing History</h5>
        <div class="text-muted small">All past and current rent invoices for <?= htmlspecialchars($tenant->name) ?></div>
      </div>
      <button class="btn btn-sm btn-primary" onclick="openAddBillModal()">
        <i class="bi bi-plus-lg me-1"></i>Add Bill
      </button>
    </div>

    <table class="table align-middle table-hover">
      <thead class="table-light">
        <tr>
          <th>Month</th>
          <th>Due Date</th>
          <th>Amount</th>
          <th>Paid Date</th>
          <th>Status</th>
          <th>Payment Mode</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rents)): ?>
          <?php foreach ($rents as $x): ?>
            <tr>
              <td class="fw-semibold"><?= date('F Y', mktime(0, 0, 0, $x->month, 10, $x->year)) ?></td>
              <td><?= !empty($x->due_date) ? date('d M Y', strtotime($x->due_date)) : '—' ?></td>
              <td class="fw-bold"><?= ($settings->currency ?? '₹') . number_format($x->amount, 0) ?></td>
              <td><?= !empty($x->paid_date) ? date('d M Y', strtotime($x->paid_date)) : '—' ?></td>
              <td>
                <span class="badge <?= $x->status === 'Paid' ? 'badge-paid' : 'badge-unpaid' ?>">
                  <?= $x->status ?>
                </span>
              </td>
              <td>
                <?= htmlspecialchars($x->payment_mode ?: '—') ?>
                <?php if (!empty($x->receipt_file)): ?>
                  <a href="<?= base_url($x->receipt_file) ?>" target="_blank" class="badge text-bg-light border text-primary text-decoration-none ms-1" title="View uploaded receipt slip"><i class="bi bi-paperclip"></i></a>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?php if ($x->status === 'Paid'): ?>
                  <button class="btn btn-sm btn-outline-primary" onclick="showReceiptModal('<?= $x->id ?>')">
                    <i class="bi bi-receipt me-1"></i>Receipt
                  </button>
                <?php else: ?>
                  <button class="btn btn-sm btn-success" onclick="openPayModal('<?= $x->id ?>')">
                    <i class="bi bi-check2 me-1"></i>Mark Paid
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No rent records found for this tenant.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  function showIdCardModal() {
    openModal(`
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Resident ID Card</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body d-flex justify-content-center">
        <div class="id-card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <strong><?= htmlspecialchars($settings->crm_name ?? 'StayFlow CRM') ?></strong>
            <span class="small badge text-bg-warning">RESIDENT ID</span>
          </div>
          <div class="d-flex gap-3 align-items-center">
            <div class="avatar lg">
              <?php if (!empty($tenant->profile_image)): ?>
                <img src="<?= base_url($tenant->profile_image) ?>" alt="Tenant" />
              <?php else: ?>
                <?= strtoupper(substr($tenant->name, 0, 1)) ?>
              <?php endif; ?>
            </div>
            <div>
              <div class="fs-5 fw-bold"><?= htmlspecialchars($tenant->name) ?></div>
              <div>Room <?= htmlspecialchars($tenant->room_number ?? '-') ?></div>
              <div class="small"><?= htmlspecialchars($tenant->hotel_name ?? '') ?></div>
            </div>
          </div>
          <hr class="border-light"/>
          <div class="small">
            <div>Phone: <?= htmlspecialchars($tenant->phone ?: '-') ?></div>
            <div>Check-in: <?= htmlspecialchars($tenant->check_in ?: '-') ?></div>
            <div>ID: #<?= str_pad($tenant->id, 5, '0', STR_PAD_LEFT) ?></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary fw-semibold" onclick="window.print()">
          <i class="bi bi-printer me-1"></i>Print ID Card
        </button>
      </div>`);
  }

  function openTransferModal() {
    openModal(`
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Transfer Room: <?= htmlspecialchars($tenant->name) ?></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label fw-semibold">Target Hotel / Property <span class="text-danger">*</span></label>
        <select class="form-select mb-3" id="tr-h">
          <option value="">Select Hotel</option>
          <?php foreach ($hotels as $h): ?>
            <option value="<?= $h->id ?>" <?= $h->id == $tenant->hotel_id ? 'selected' : '' ?>><?= htmlspecialchars($h->name) ?></option>
          <?php endforeach; ?>
        </select>
        <label class="form-label fw-semibold">Target Room <span class="text-danger">*</span></label>
        <select class="form-select" id="tr-r">
          <option value="">Select Room</option>
        </select>
        <p class="small text-muted mt-2">Previous assignment will be logged in tenant transfer history.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary fw-semibold" id="btn-do-tr">Confirm Transfer</button>
      </div>`, () => {

      const hotelEl = document.getElementById('tr-h');
      const roomEl  = document.getElementById('tr-r');

      const hotelTs = registerTomSelect(new TomSelect(hotelEl, {
        placeholder: 'Search or select hotel...',
        allowEmptyOption: true,
        maxOptions: 200
      }));

      const roomTs = registerTomSelect(new TomSelect(roomEl, {
        placeholder: 'Search or select room...',
        allowEmptyOption: true,
        maxOptions: 300
      }));

      const fillRooms = async (hid) => {
        roomTs.clear();
        roomTs.clearOptions();
        if (!hid) {
          roomTs.refreshOptions(false);
          return;
        }
        const res = await apiGet('rooms/list', { hotel_id: hid });
        const rooms = (res.success && res.data) ? res.data : [];
        if (rooms.length === 0) {
          roomTs.addOption({ value: '', text: 'No rooms available' });
        } else {
          rooms.forEach(r => {
            const cap = parseInt(r.capacity || 1);
            const occ = parseInt(r.occupied_beds || 0);
            const isCurrent = (String(r.id) === String('<?= $tenant->room_id ?>'));
            const free = Math.max(0, cap - occ);
            const isFull = (free <= 0 && !isCurrent);
            roomTs.addOption({
              value: r.id,
              text: isFull ? `Room ${r.number} (Full - 0 of ${cap} beds available)` : `Room ${r.number} (${free + (isCurrent ? 1 : 0)} of ${cap} beds available)` + (isCurrent ? ' [Current]' : ''),
              disabled: isFull
            });
          });
        }
        roomTs.refreshOptions(false);
      };

      if (hotelEl.value) {
        fillRooms(hotelEl.value);
      }

      hotelTs.on('change', (hid) => {
        fillRooms(hid);
      });

      document.getElementById('btn-do-tr').onclick = async () => {
        const hotel_id = hotelEl.value;
        const room_id  = roomEl.value;
        if (!hotel_id || !room_id) {
          swalAlert('Missing Selection', 'Please select both hotel and destination room.', 'warning');
          return;
        }

        const res = await apiPost('tenants/transfer', {
          tenant_id: '<?= $tenant->id ?>',
          hotel_id,
          room_id
        });

        if (res.success) {
          toast(res.message);
          closeModal();
          setTimeout(() => location.reload(), 600);
        } else {
          swalAlert('Transfer Failed', res.message, 'error');
        }
      };
    });
  }

  // Vacate Modal (With optional instant deposit return)
  function openVacateModal() {
    const isDepositPaid = ('<?= $tenant->deposit_status ?>' === 'Paid' && parseFloat('<?= $tenant->deposit ?>' || 0) > 0);

    openModal(`
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-danger"><i class="bi bi-box-arrow-right me-1"></i>Mark Tenant as Vacated</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="f-vacate-tenant">
          <input type="hidden" name="tenant_id" value="<?= $tenant->id ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Vacate (Check-out) Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="vacate_date" id="vac-d" value="${todayISO()}" required>
            <div class="form-text small text-muted">This will release the assigned bed in Room <?= htmlspecialchars($tenant->room_number ?? '') ?> and mark the occupant as vacated.</div>
          </div>

          ${isDepositPaid ? `
            <div class="p-3 mb-3 rounded-3 border bg-warning-subtle text-dark">
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="chk-return-dep" name="return_deposit" value="1" checked onchange="document.getElementById('vacate-dep-details').style.display = this.checked ? 'block' : 'none'">
                <label class="form-check-label fw-bold" for="chk-return-dep">
                  Return Security Deposit Now (<?= ($settings->currency ?? '₹') . number_format($tenant->deposit, 0) ?>)
                </label>
              </div>
              <div class="small text-muted mb-2">Tenant paid <?= ($settings->currency ?? '₹') . number_format($tenant->deposit, 0) ?> deposit at check-in.</div>
              
              <div id="vacate-dep-details">
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label small fw-semibold">Refund Amount</label>
                    <input type="number" step="0.01" class="form-control form-control-sm" name="refund_amount" value="<?= $tenant->deposit ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-semibold">Payment Mode</label>
                    <select class="form-select form-select-sm" name="payment_mode">
                      <option value="Cash">Cash</option>
                      <option value="UPI" selected>UPI</option>
                      <option value="Bank Transfer">Bank Transfer</option>
                      <option value="Cheque">Cheque</option>
                    </select>
                  </div>
                </div>
                <div class="mb-1">
                  <label class="form-label small fw-semibold">Settlement Remarks</label>
                  <input class="form-control form-control-sm" name="refund_remarks" placeholder="e.g. Deposit refunded upon room handover / No damage">
                </div>
              </div>
            </div>
          ` : `
            <div class="alert alert-secondary small mb-0">
              <i class="bi bi-info-circle me-1"></i>Security deposit status is <strong><?= $tenant->deposit_status ?: 'Pending' ?></strong>.
            </div>
          `}
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger fw-semibold px-4" id="btn-do-vac">
          <i class="bi bi-box-arrow-right me-1"></i>Confirm Vacate
        </button>
      </div>`, () => {
      document.getElementById('btn-do-vac').onclick = async () => {
        const form = document.getElementById('f-vacate-tenant');
        const fd = new FormData(form);
        const btn = document.getElementById('btn-do-vac');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

        try {
          const res = await apiPost('tenants/vacate', fd);
          if (res.success) {
            toast(res.message);
            closeModal();
            setTimeout(() => location.reload(), 600);
          } else {
            swalAlert('Error', res.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-arrow-right me-1"></i>Confirm Vacate';
          }
        } catch (err) {
          swalAlert('Error', 'Failed to process vacate.', 'error');
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-box-arrow-right me-1"></i>Confirm Vacate';
        }
      };
    });
  }

  // Return Security Deposit Modal (For Vacated Tenant)
  function openRefundDepositModal() {
    openModal(`
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-primary">
          <i class="bi bi-arrow-return-left me-1"></i>Return Security Deposit
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="f-refund-deposit">
          <input type="hidden" name="tenant_id" value="<?= $tenant->id ?>">
          <div class="p-3 mb-3 rounded-3 bg-light border">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="text-muted small">Tenant Name:</span>
              <strong class="fs-6"><?= htmlspecialchars($tenant->name) ?></strong>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="text-muted small">Original Deposit Collected:</span>
              <span class="fw-bold text-success fs-6"><?= ($settings->currency ?? '₹') . number_format($tenant->deposit, 2) ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small">Check-out Date:</span>
              <span><?= !empty($tenant->vacate_date) ? date('d M Y', strtotime($tenant->vacate_date)) : 'Vacated' ?></span>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Refund Amount (<?= $settings->currency ?? '₹' ?>) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" class="form-control form-control-lg fw-bold text-primary" name="refund_amount" value="<?= $tenant->deposit ?>" required>
            <div class="form-text text-muted small">Adjust amount if deducting for electricity, damage, or unpaid dues.</div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Refund Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="refund_date" value="${todayISO()}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Payment Mode <span class="text-danger">*</span></label>
              <select class="form-select" name="payment_mode" required>
                <option value="Cash">Cash</option>
                <option value="UPI" selected>UPI</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Cheque">Cheque</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Reference Number / Trx ID</label>
            <input class="form-control" name="reference" placeholder="e.g. UPI-REF-9876543210">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Deductions Reason / Settlement Remarks</label>
            <textarea class="form-control" name="remarks" rows="2" placeholder="e.g. Full refund settled upon checkout / No damage"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary fw-bold px-4" id="btn-confirm-refund">
          <i class="bi bi-check2-circle me-1"></i>Confirm Deposit Return
        </button>
      </div>`, () => {
      document.getElementById('btn-confirm-refund').onclick = async () => {
        const form = document.getElementById('f-refund-deposit');
        const fd = new FormData(form);
        const btn = document.getElementById('btn-confirm-refund');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

        try {
          const res = await apiPost('tenants/refund_deposit', fd);
          if (res.success) {
            toast(res.message);
            closeModal();
            setTimeout(() => location.reload(), 600);
          } else {
            swalAlert('Error', res.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirm Deposit Return';
          }
        } catch (err) {
          swalAlert('Error', 'Failed to process refund.', 'error');
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirm Deposit Return';
        }
      };
    });
  }

  // Edit Tenant Modal (Including Police Verification Optional Upload)
  function openEditTenantModal() {
    openModal(`
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Tenant</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" style="background: var(--bg);">
        <form id="f-edit-tenant" enctype="multipart/form-data">
          <input type="hidden" name="id" value="<?= $tenant->id ?>">
          
          <!-- 1. Personal Information -->
          <div class="p-3 mb-3 rounded-3 bg-white dark:bg-card border shadow-sm">
            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person-vcard me-1"></i>Personal Information</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                <input class="form-control" name="name" value="<?= htmlspecialchars($tenant->name) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Phone Number</label>
                <input class="form-control" name="phone" value="<?= htmlspecialchars($tenant->phone ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Email Address</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($tenant->email ?? '') ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Date of Birth</label>
                <input type="date" class="form-control" name="dob" value="<?= $tenant->dob ?? '' ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Profile Photo (Max 2MB)</label>
                <input type="file" name="photo" accept="image/*" class="form-control file-limit-2mb">
              </div>
            </div>
          </div>

          <!-- 2. Stay & Room Allocation -->
          <div class="p-3 mb-3 rounded-3 bg-white dark:bg-card border shadow-sm">
            <h6 class="fw-bold text-success mb-3"><i class="bi bi-door-open me-1"></i>Stay & Room Allocation</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Hotel / Property</label>
                <select class="form-select" name="hotel_id" id="edit-t-hotel">
                  <option value="">Select Hotel</option>
                  <?php foreach ($hotels as $h): ?>
                    <option value="<?= $h->id ?>" <?= $h->id == $tenant->hotel_id ? 'selected' : '' ?>><?= htmlspecialchars($h->name) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Room</label>
                <select class="form-select" name="room_id" id="edit-t-room">
                  <option value="">Select Room</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Monthly Rent (₹)</label>
                <input type="number" class="form-control" name="rent_amount" value="<?= $tenant->rent_amount ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Check-in Date</label>
                <input type="date" class="form-control" name="check_in" value="<?= $tenant->check_in ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Security Deposit (₹)</label>
                <input type="number" class="form-control" name="deposit" value="<?= $tenant->deposit ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Deposit Status</label>
                <select class="form-select" name="deposit_status">
                  <option value="Pending" <?= $tenant->deposit_status === 'Pending' ? 'selected' : '' ?>>Pending (Not Paid)</option>
                  <option value="Paid" <?= $tenant->deposit_status === 'Paid' ? 'selected' : '' ?>>Paid (Held by Admin)</option>
                  <option value="Refunded" <?= $tenant->deposit_status === 'Refunded' ? 'selected' : '' ?>>Refunded (Returned upon Vacate)</option>
                </select>
              </div>
            </div>
          </div>

          <!-- 3. Occupation & Address -->
          <div class="p-3 mb-3 rounded-3 bg-white dark:bg-card border shadow-sm">
            <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-briefcase me-1"></i>Occupation & Address</h6>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-semibold">Occupation</label>
                <select class="form-select" name="occupation">
                  <option value="Student" <?= $tenant->occupation === 'Student' ? 'selected' : '' ?>>Student</option>
                  <option value="Working Professional" <?= $tenant->occupation === 'Working Professional' ? 'selected' : '' ?>>Working Professional</option>
                  <option value="Other" <?= $tenant->occupation === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Company / College</label>
                <input class="form-control" name="company" value="<?= htmlspecialchars($tenant->company ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Permanent Address</label>
                <input class="form-control" name="address" value="<?= htmlspecialchars($tenant->address ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Emergency Contact Person</label>
                <input class="form-control" name="emergency_name" value="<?= htmlspecialchars($tenant->emergency_name ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Emergency Contact Phone</label>
                <input class="form-control" name="emergency_phone" value="<?= htmlspecialchars($tenant->emergency_phone ?? '') ?>">
              </div>
            </div>
          </div>

          <!-- 4. Documents & Police Verification (Optional) -->
          <div class="p-3 mb-3 rounded-3 bg-white dark:bg-card border shadow-sm">
            <h6 class="fw-bold text-info mb-3"><i class="bi bi-file-earmark-check me-1"></i>Documents & Verification</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Aadhar Number</label>
                <input class="form-control" name="aadhar" value="<?= htmlspecialchars($tenant->aadhar ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Aadhar Document (Max 2MB)</label>
                <input type="file" name="aadhar_file" accept="image/*,application/pdf" class="form-control file-limit-2mb">
                <?php if (!empty($tenant->aadhar_file)): ?>
                  <div class="small mt-1"><a href="<?= base_url($tenant->aadhar_file) ?>" target="_blank" class="text-success"><i class="bi bi-check-circle me-1"></i>Current document uploaded</a></div>
                <?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">PAN Number</label>
                <input class="form-control" name="pan" value="<?= htmlspecialchars($tenant->pan ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">PAN Document (Max 2MB)</label>
                <input type="file" name="pan_file" accept="image/*,application/pdf" class="form-control file-limit-2mb">
                <?php if (!empty($tenant->pan_file)): ?>
                  <div class="small mt-1"><a href="<?= base_url($tenant->pan_file) ?>" target="_blank" class="text-success"><i class="bi bi-check-circle me-1"></i>Current document uploaded</a></div>
                <?php endif; ?>
              </div>
              <div class="col-12">
                <div class="p-3 rounded-3 border bg-light-subtle">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-semibold mb-0">
                      <i class="bi bi-shield-check text-info me-1"></i>Police Verification Document
                    </label>
                    <span class="badge bg-secondary-subtle text-secondary border">Optional</span>
                  </div>
                  <div class="text-muted small mb-2">Upload Police Clearance Certificate (PCC) or tenant verification document (Max 2MB).</div>
                  <input type="file" name="police_verification_file" accept="image/*,application/pdf" class="form-control file-limit-2mb">
                  <?php if (!empty($tenant->police_verification_file)): ?>
                    <div class="small mt-1"><a href="<?= base_url($tenant->police_verification_file) ?>" target="_blank" class="text-info"><i class="bi bi-check-circle me-1"></i>Current police verification document attached</a></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- 5. Notes -->
          <div class="p-3 rounded-3 bg-white dark:bg-card border shadow-sm">
            <h6 class="fw-bold text-muted mb-2"><i class="bi bi-card-text me-1"></i>Additional Notes</h6>
            <textarea class="form-control" name="notes" rows="2"><?= htmlspecialchars($tenant->notes ?? '') ?></textarea>
          </div>

        </form>
      </div>
      <div class="modal-footer border-top pt-3 bg-white dark:bg-card">
        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary fw-semibold px-4 shadow-sm" id="btn-save-edit-t">
          <i class="bi bi-check2-circle me-1"></i>Update Tenant
        </button>
      </div>`, () => {

      const hotelEl = document.getElementById('edit-t-hotel');
      const roomEl  = document.getElementById('edit-t-room');

      const hotelTs = registerTomSelect(new TomSelect(hotelEl, {
        placeholder: 'Search or select hotel...',
        allowEmptyOption: true,
        maxOptions: 200
      }));

      const roomTs = registerTomSelect(new TomSelect(roomEl, {
        placeholder: 'Search or select room...',
        allowEmptyOption: true,
        maxOptions: 300
      }));

      const fillRooms = async (hid, targetRoomId) => {
        roomTs.clear();
        roomTs.clearOptions();
        if (!hid) {
          roomTs.refreshOptions(false);
          return;
        }
        const res = await apiGet('rooms/list', { hotel_id: hid });
        const rooms = (res.success && res.data) ? res.data : [];
        if (rooms.length === 0) {
          roomTs.addOption({ value: '', text: 'No rooms in this hotel' });
        } else {
          rooms.forEach(r => {
            const cap = parseInt(r.capacity || 1);
            const occ = parseInt(r.occupied_beds || 0);
            const isCurrent = (String(r.id) === String('<?= $tenant->room_id ?>'));
            const free = Math.max(0, cap - occ);
            const isFull = (free <= 0 && !isCurrent);

            roomTs.addOption({
              value: r.id,
              text: isFull ? `Room ${r.number} (Full - 0 of ${cap} beds available)` : `Room ${r.number} (${free + (isCurrent ? 1 : 0)} of ${cap} beds available)` + (isCurrent ? ' [Current]' : ''),
              disabled: isFull
            });
          });
        }
        if (targetRoomId) {
          roomTs.setValue(targetRoomId);
        }
        roomTs.refreshOptions(false);
      };

      if (hotelEl.value) {
        fillRooms(hotelEl.value, '<?= $tenant->room_id ?>');
      }

      hotelTs.on('change', (hid) => {
        fillRooms(hid, null);
      });

      document.getElementById('btn-save-edit-t').onclick = async () => {
        const form = document.getElementById('f-edit-tenant');

        const fileInputs = form.querySelectorAll('.file-limit-2mb');
        for (const fi of fileInputs) {
          if (fi.files && fi.files[0] && fi.files[0].size > 2 * 1024 * 1024) {
            swalAlert('File Too Large', `Selected ${fi.name.replace('_', ' ')} file exceeds 2MB limit. Please choose a file smaller than 2MB.`, 'error');
            return;
          }
        }

        const btn = document.getElementById('btn-save-edit-t');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        try {
          const formData = new FormData(form);
          const res = await apiPost('tenants/save', formData);
          if (res.success) {
            toast(res.message);
            closeModal();
            setTimeout(() => location.reload(), 600);
          } else {
            swalAlert('Error', res.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Update Tenant';
          }
        } catch (err) {
          swalAlert('Error', 'Failed to update tenant.', 'error');
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Update Tenant';
        }
      };
    });
  }

  function openPayModal(rid) {
    openModal(`
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Record Rent Payment</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="f-pay-rent">
          <input type="hidden" name="rent_id" value="${rid}">
          <label class="form-label fw-semibold">Payment Date</label>
          <input type="date" class="form-control mb-3" name="paid_date" value="${todayISO()}">
          <label class="form-label fw-semibold">Payment Mode</label>
          <select class="form-select mb-3" name="payment_mode" id="td-pay-mode" onchange="toggleTdReceiptUpload(this.value)">
            <option value="UPI" selected>UPI</option>
            <option value="Cash">Cash</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cheque">Cheque</option>
          </select>
          <div id="td-receipt-upload-wrap" class="mb-3">
            <label class="form-label fw-semibold">Upload Receipt <span class="text-muted fw-normal small">(Optional Screenshot / Slip)</span></label>
            <input type="file" class="form-control" name="receipt_file" id="td-receipt-file" accept="image/*,.pdf">
          </div>
          <label class="form-label fw-semibold">Remarks</label>
          <input class="form-control" name="remarks" placeholder="Optional notes...">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success fw-semibold px-4" id="btn-confirm-pay">Confirm Payment</button>
      </div>`, () => {
      document.getElementById('btn-confirm-pay').onclick = async () => {
        const form = document.getElementById('f-pay-rent');
        const formData = new FormData(form);
        const res = await apiPost('rent/pay', formData);
        if (res.success) {
          toast(res.message);
          closeModal();
          setTimeout(() => location.reload(), 600);
        } else {
          swalAlert('Error', res.message, 'error');
        }
      };
    });
  }

  function toggleTdReceiptUpload(mode) {
    const wrap = document.getElementById('td-receipt-upload-wrap');
    const input = document.getElementById('td-receipt-file');
    if (!wrap) return;
    if (mode === 'Cash') {
      wrap.style.display = 'none';
      if (input) input.value = '';
    } else {
      wrap.style.display = 'block';
    }
  }

  async function showReceiptModal(rid) {
    const res = await apiGet('rent/receipt/' + rid);
    if (!res.success || !res.data) {
      swalAlert('Error', 'Unable to load receipt details.', 'error');
      return;
    }
    const rec = res.data;
    const monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
    const mText = monthNames[parseInt(rec.month)] || rec.month;

    openModal(`
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Rent Receipt</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="p-3" id="receipt-print-area">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 class="fw-bold mb-0 text-primary"><?= htmlspecialchars($settings->crm_name ?? 'StayFlow CRM') ?></h4>
              <div class="small text-muted">${rec.hotel_name || ''}</div>
            </div>
            <div class="text-end">
              <span class="badge badge-paid fs-6">PAID</span>
              <div class="small text-muted mt-1">Receipt #${String(rec.id).padStart(6, '0')}</div>
            </div>
          </div>
          <hr/>
          <div class="row g-2 mb-3 small">
            <div class="col-6">
              <div class="text-muted">Tenant Name</div>
              <strong>${rec.tenant_name || '<?= htmlspecialchars($tenant->name) ?>'}</strong>
              <div>Phone: ${rec.tenant_phone || '<?= htmlspecialchars($tenant->phone ?: '-') ?>'}</div>
            </div>
            <div class="col-6 text-end">
              <div class="text-muted">Room & Property</div>
              <strong>Room ${rec.room_number || '-'}</strong>
              <div>${rec.hotel_name || ''}</div>
            </div>
          </div>
          <table class="table table-bordered mb-3">
            <thead class="table-light">
              <tr><th>Description</th><th class="text-end">Amount</th></tr>
            </thead>
            <tbody>
              <tr>
                <td>Rent for <strong>${mText} ${rec.year}</strong></td>
                <td class="text-end fw-bold">${money(rec.amount)}</td>
              </tr>
              <tr>
                <td class="text-muted small">
                  <div>Paid On: <strong>${rec.paid_date || '-'}</strong></div>
                  <div>Payment Mode: <strong>${rec.payment_mode || 'Cash'}</strong></div>
                  ${rec.receipt_file ? `<div>Receipt / Slip: <a href="${BASE_URL + rec.receipt_file}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 mt-1"><i class="bi bi-paperclip me-1"></i>View Uploaded Slip</a></div>` : ''}
                  ${rec.reference ? `<div>Reference / Trx: <strong>${rec.reference}</strong></div>` : ''}
                  ${rec.remarks ? `<div>Remarks: ${rec.remarks}</div>` : ''}
                </td>
                <td class="text-end align-bottom">
                  <div class="small text-muted">Total Paid</div>
                  <h5 class="fw-bold text-success mb-0">${money(rec.amount)}</h5>
                </td>
              </tr>
            </tbody>
          </table>
          <p class="small text-muted text-center mb-0">This is a computer generated payment receipt.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary fw-semibold" onclick="window.print()">
          <i class="bi bi-printer me-1"></i>Print Receipt
        </button>
      </div>`);
  }

  function openAddBillModal() {
    const curMonth = new Date().getMonth() + 1;
    const curYear  = new Date().getFullYear();
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const monthOpts = months.map((m, idx) => `<option value="${idx+1}" ${idx+1 === curMonth ? 'selected' : ''}>${m}</option>`).join('');

    openModal(`
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Add Rent Bill</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="f-add-bill">
          <input type="hidden" name="user_id" value="<?= $tenant->id ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Month</label>
              <select class="form-select" name="month">${monthOpts}</select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Year</label>
              <input type="number" class="form-control" name="year" value="${curYear}">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Amount (<?= $settings->currency ?? '₹' ?>)</label>
              <input type="number" class="form-control" name="amount" value="<?= $tenant->rent_amount ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Due Date</label>
              <input type="date" class="form-control" name="due_date" value="${curYear}-${String(curMonth).padStart(2,'0')}-<?= sprintf('%02d', intval($settings->rent_due_date ?? 10)) ?>">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Remarks</label>
              <input class="form-control" name="remarks" placeholder="Optional remarks...">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary fw-semibold" id="btn-save-bill">Create Bill</button>
      </div>`, () => {
      document.getElementById('btn-save-bill').onclick = async () => {
        const form = document.getElementById('f-add-bill');
        const formData = new FormData(form);
        const res = await apiPost('rent/add_bill', formData);
        if (res.success) {
          toast(res.message);
          closeModal();
          setTimeout(() => location.reload(), 600);
        } else {
          swalAlert('Error', res.message, 'error');
        }
      };
    });
  }

  function openPayDepositModal() {
    openModal(`
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-warning-emphasis">
          <i class="bi bi-shield-check me-1"></i>Collect Security Deposit
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="f-pay-deposit">
          <input type="hidden" name="tenant_id" value="<?= $tenant->id ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Tenant Name</label>
            <input class="form-control" value="<?= htmlspecialchars($tenant->name) ?>" readonly disabled>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Deposit Amount (<?= $settings->currency ?? '₹' ?>) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" class="form-control" name="amount" value="<?= $tenant->deposit ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Payment Date</label>
            <input type="date" class="form-control" name="paid_date" value="${todayISO()}">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Payment Mode</label>
            <select class="form-select" name="payment_mode">
              <option value="Cash">Cash</option>
              <option value="UPI" selected>UPI</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Cheque">Cheque</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Reference Number / Trx ID</label>
            <input class="form-control" name="reference" placeholder="e.g. UPI-9876543210">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Remarks</label>
            <input class="form-control" name="remarks" placeholder="Optional remarks...">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning text-dark fw-bold px-4" id="btn-save-pay-dep">
          <i class="bi bi-check2-circle me-1"></i>Confirm Deposit Payment
        </button>
      </div>`, () => {
      document.getElementById('btn-save-pay-dep').onclick = async () => {
        const form = document.getElementById('f-pay-deposit');
        const fd = new FormData(form);
        const res = await apiPost('tenants/pay_deposit', fd);
        if (res.success) {
          toast(res.message);
          closeModal();
          setTimeout(() => location.reload(), 600);
        } else {
          swalAlert('Error', res.message, 'error');
        }
      };
    });
  }
</script>
