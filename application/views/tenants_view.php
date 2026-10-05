<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="page-enter">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="section-title" id="tenants-title-count">Tenants</div>
    <div class="d-flex gap-2">
      <div class="btn-group">
        <button class="btn btn-outline-secondary btn-sm active" id="btn-view-table" onclick="toggleTenantView('table')" title="Table View"><i class="bi bi-table"></i></button>
        <button class="btn btn-outline-secondary btn-sm" id="btn-view-card" onclick="toggleTenantView('card')" title="Card View"><i class="bi bi-grid"></i></button>
      </div>
      <button class="quick-btn" onclick="openTenantModal()"><i class="bi bi-person-plus me-1"></i>Add Tenant</button>
    </div>
  </div>

  <!-- AJAX Filter Bar -->
  <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
    <input class="form-control" style="max-width:240px" id="tenant-filter-q" placeholder="Search name / phone / email..." />
    <select class="form-select w-auto" id="tenant-filter-hotel" onchange="loadTenants(1)">
      <option value="">All Hotels</option>
      <?php foreach ($hotels as $h): ?>
        <option value="<?= $h->id ?>"><?= htmlspecialchars($h->name) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-select w-auto" id="tenant-filter-status" onchange="loadTenants(1)">
      <option value="">All Rent Status</option>
      <option value="Paid">Paid</option>
      <option value="Unpaid">Unpaid</option>
    </select>
    <button type="button" class="btn btn-primary" onclick="loadTenants(1)"><i class="bi bi-search"></i> Filter</button>
    <button type="button" class="btn btn-outline-secondary" onclick="resetTenantFilters()">Reset</button>
  </div>

  <!-- TABLE VIEW -->
  <div id="tenant-table-view" class="stat-card table-wrap">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Photo</th>
          <th>Name</th>
          <th>Room</th>
          <th>Hotel</th>
          <th>Monthly Rent</th>
          <th>Deposit</th>
          <th>Rent Status</th>
        </tr>
      </thead>
      <tbody id="tenant-table-tbody">
        <tr><td colspan="7" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading tenants...</td></tr>
      </tbody>
    </table>
  </div>

  <!-- CARD VIEW -->
  <div id="tenant-card-view" class="row g-3 d-none">
    <!-- Populated via AJAX -->
  </div>

  <!-- Pagination Host -->
  <div id="tenant-pagination"></div>
</div>

<script>
  let currentTenantPage = 1;
  let activeTenantView = 'table';
  let searchDebounceTimer = null;

  document.getElementById('tenant-filter-q').addEventListener('input', () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
      loadTenants(1);
    }, 350);
  });

  function resetTenantFilters() {
    document.getElementById('tenant-filter-q').value = '';
    document.getElementById('tenant-filter-hotel').value = '';
    document.getElementById('tenant-filter-status').value = '';
    loadTenants(1);
  }

  function toggleTenantView(view) {
    activeTenantView = view;
    if (view === 'card') {
      document.getElementById('tenant-table-view').classList.add('d-none');
      document.getElementById('tenant-card-view').classList.remove('d-none');
      document.getElementById('btn-view-card').classList.add('active');
      document.getElementById('btn-view-table').classList.remove('active');
    } else {
      document.getElementById('tenant-table-view').classList.remove('d-none');
      document.getElementById('tenant-card-view').classList.add('d-none');
      document.getElementById('btn-view-table').classList.add('active');
      document.getElementById('btn-view-card').classList.remove('active');
    }
  }

  async function loadTenants(page = 1) {
    currentTenantPage = page;
    const q = document.getElementById('tenant-filter-q').value.trim();
    const hotel_id = document.getElementById('tenant-filter-hotel').value;
    const rent_status = document.getElementById('tenant-filter-status').value;

    const tbody = document.getElementById('tenant-table-tbody');
    const cardContainer = document.getElementById('tenant-card-view');

    try {
      const res = await apiGet('tenants/list_ajax', { page, q, hotel_id, rent_status, per_page: 10 });
      if (!res.success) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Failed to load data.</td></tr>`;
        return;
      }

      const tenants = res.data || [];
      const total = res.pagination.total;
      document.getElementById('tenants-title-count').textContent = `Tenants (${total})`;

      if (!tenants.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-people fs-1 d-block mb-2 text-muted"></i>No active tenants match your filter.</td></tr>`;
        cardContainer.innerHTML = `<div class="col-12"><div class="empty-state">No active tenants match your filter.</div></div>`;
        document.getElementById('tenant-pagination').innerHTML = '';
        return;
      }

      // Render Table Rows
      tbody.innerHTML = tenants.map(t => {
        const rentBadgeClass = (t.rent_status === 'Paid') ? 'badge-paid' : 'badge-unpaid';
        const depositBadgeClass = (t.deposit_status === 'Paid') ? 'badge-paid' : 'badge-pending';
        const avatarHtml = t.profile_image ?
          `<img src="${BASE_URL + t.profile_image}" alt="${t.name}" />` :
          (t.name ? t.name.charAt(0).toUpperCase() : 'T');

        return `
          <tr style="cursor:pointer" onclick="location.href='${BASE_URL}tenants/detail/${t.id}'">
            <td><div class="avatar sm">${avatarHtml}</div></td>
            <td class="fw-semibold">
              ${t.name}
              <div class="small text-muted">${t.phone || '-'}</div>
            </td>
            <td>${t.room_number ? 'Room ' + t.room_number : '-'}</td>
            <td>${t.hotel_name || '-'}</td>
            <td>${money(t.rent_amount)}</td>
            <td><span class="badge ${depositBadgeClass}">${t.deposit_status || 'Pending'}</span></td>
            <td><span class="badge ${rentBadgeClass}">${t.rent_status || 'Paid'}</span></td>
          </tr>
        `;
      }).join('');

      // Render Cards
      cardContainer.innerHTML = tenants.map(t => {
        const rentBadgeClass = (t.rent_status === 'Paid') ? 'badge-paid' : 'badge-unpaid';
        const avatarHtml = t.profile_image ?
          `<img src="${BASE_URL + t.profile_image}" alt="${t.name}" />` :
          (t.name ? t.name.charAt(0).toUpperCase() : 'T');

        return `
          <div class="col-md-6 col-xl-4">
            <div class="stat-card tenant-card card-lift" style="cursor:pointer" onclick="location.href='${BASE_URL}tenants/detail/${t.id}'">
              <div class="d-flex gap-3">
                <div class="avatar lg">${avatarHtml}</div>
                <div class="flex-grow-1 overflow-hidden">
                  <div class="fw-semibold text-truncate">${t.name}</div>
                  <div class="small text-muted text-truncate">${t.hotel_name || ''} • Room ${t.room_number || '-'}</div>
                  <div class="mt-1">
                    <span class="fw-semibold">${money(t.rent_amount)}</span>
                    <span class="badge ms-1 ${rentBadgeClass}">${t.rent_status || 'Paid'}</span>
                  </div>
                  <div class="small text-muted mt-1"><i class="bi bi-telephone"></i> ${t.phone || '-'}</div>
                </div>
              </div>
            </div>
          </div>
        `;
      }).join('');

      // Render Pagination
      document.getElementById('tenant-pagination').innerHTML = renderPagination(res.pagination.total, res.pagination.page, res.pagination.per_page, 'loadTenants');

    } catch (err) {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Error loading tenants list.</td></tr>`;
    }
  }

  function checkUrlParams() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('action') === 'add') {
      openTenantModal();
      const assignHotel = params.get('assign_hotel');
      const assignRoom = params.get('assign_room');
      setTimeout(() => {
        if (assignHotel) {
          const hotelSel = document.getElementById('sel-hotel');
          if (hotelSel && hotelSel.tomselect) {
            hotelSel.tomselect.setValue(assignHotel);
          } else if (hotelSel) {
            hotelSel.value = assignHotel;
            hotelSel.dispatchEvent(new Event('change'));
          }
        }
        if (assignRoom) {
          setTimeout(() => {
            const roomSel = document.getElementById('sel-room');
            if (roomSel && roomSel.tomselect) {
              roomSel.tomselect.setValue(assignRoom);
            } else if (roomSel) {
              roomSel.value = assignRoom;
            }
          }, 450);
        }
      }, 400);
    }
  }

  // Initial load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      loadTenants(1);
      checkUrlParams();
    });
  } else {
    loadTenants(1);
    checkUrlParams();
  }

  function openTenantModal() {
    openModal(`
      <div class="modal-header border-bottom pb-3">
        <div>
          <h5 class="modal-title fw-bold mb-0"><i class="bi bi-person-plus-fill text-primary me-2"></i>Add New Tenant</h5>
          <div class="text-muted small">Register a new occupant and assign property, room, and deposit.</div>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" style="background: var(--bg);">
        <form id="f-add-tenant" enctype="multipart/form-data">
          
          <!-- 1. Personal Information -->
          <div class="p-3 mb-3 rounded-3 bg-white dark:bg-card border shadow-sm">
            <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
              <i class="bi bi-person-vcard"></i> 1. Personal Information
            </h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                <input class="form-control" name="name" required placeholder="e.g. John Doe">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Phone Number</label>
                <input class="form-control" name="phone" placeholder="+91 9876543210">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Email Address</label>
                <input type="email" class="form-control" name="email" placeholder="tenant@example.com">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Date of Birth</label>
                <input type="date" class="form-control" name="dob">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Profile Photo</label>
                <input type="file" name="photo" accept="image/*" class="form-control file-limit-2mb">
                <div class="form-text small text-muted">Max 2MB (JPG/PNG)</div>
              </div>
            </div>
          </div>

          <!-- 2. Room & Stay Allocation -->
          <div class="p-3 mb-3 rounded-3 bg-white dark:bg-card border shadow-sm">
            <h6 class="fw-bold text-success mb-3 d-flex align-items-center gap-2">
              <i class="bi bi-door-open"></i> 2. Room & Stay Allocation
            </h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Hotel / Property <span class="text-danger">*</span></label>
                <select class="form-select" name="hotel_id" id="sel-hotel" required>
                  <option value="">Select Hotel / Property</option>
                  <?php foreach ($hotels as $h): ?>
                    <option value="<?= $h->id ?>"><?= htmlspecialchars($h->name) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Room Number <span class="text-danger">*</span></label>
                <select class="form-select" name="room_id" id="sel-room" required>
                  <option value="">First choose a Hotel...</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Check-in Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="check_in" value="${todayISO()}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Monthly Rent (₹) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="rent_amount" placeholder="5000" min="0" required>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Rent Billing Start</label>
                <select class="form-select" name="rent_cycle">
                  <option value="next" selected>Next Month (Month after check-in)</option>
                  <option value="current">Check-in Month (Start billing from check-in month)</option>
                </select>
                <div class="form-text small text-muted">Generates rent bills up to current month.</div>
              </div>
            </div>
          </div>

          <!-- 3. Security Deposit -->
          <div class="p-3 mb-3 rounded-3 bg-white dark:bg-card border shadow-sm">
            <h6 class="fw-bold text-warning mb-3 d-flex align-items-center gap-2">
              <i class="bi bi-shield-lock"></i> 3. Security Deposit
            </h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Security Deposit Amount (₹)</label>
                <input type="number" class="form-control" name="deposit" placeholder="0" min="0">
                <div class="form-text small text-muted">Refundable deposit upon vacating the room.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Deposit Payment Status</label>
                <select class="form-select" name="deposit_status">
                  <option value="Pending" selected>Pending (Not Paid Yet)</option>
                  <option value="Paid">Paid (Collected At Check-in)</option>
                </select>
                <div class="form-text small text-muted">If marked Paid, an income entry is automatically recorded.</div>
              </div>
            </div>
          </div>

          <!-- 4. Occupation & Emergency -->
          <div class="p-3 mb-3 rounded-3 bg-white dark:bg-card border shadow-sm">
            <h6 class="fw-bold text-secondary mb-3 d-flex align-items-center gap-2">
              <i class="bi bi-briefcase"></i> 4. Occupation & Address
            </h6>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-semibold">Occupation</label>
                <select class="form-select" name="occupation">
                  <option value="Student">Student</option>
                  <option value="Working Professional">Working Professional</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Company / College</label>
                <input class="form-control" name="company" placeholder="e.g. TCS or University">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Permanent Address</label>
                <input class="form-control" name="address" placeholder="Permanent Address">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Emergency Contact Person</label>
                <input class="form-control" name="emergency_name" placeholder="Parent / Relative name">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Emergency Contact Phone</label>
                <input class="form-control" name="emergency_phone" placeholder="+91 9876543210">
              </div>
            </div>
          </div>

          <!-- 5. Verification & Documents (With Police Verification) -->
          <div class="p-3 mb-3 rounded-3 bg-white dark:bg-card border shadow-sm">
            <h6 class="fw-bold text-info mb-3 d-flex align-items-center gap-2">
              <i class="bi bi-file-earmark-check"></i> 5. Documents & Police Verification
            </h6>
            <div class="row g-3">
              <!-- Aadhar -->
              <div class="col-md-6">
                <label class="form-label fw-semibold">Aadhar Card Number</label>
                <input class="form-control" name="aadhar" placeholder="12-digit Aadhar number">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Aadhar Document (Max 2MB)</label>
                <input type="file" name="aadhar_file" accept="image/*,application/pdf" class="form-control file-limit-2mb">
              </div>
              
              <!-- PAN -->
              <div class="col-md-6">
                <label class="form-label fw-semibold">PAN Card Number</label>
                <input class="form-control" name="pan" placeholder="10-digit PAN number">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">PAN Document (Max 2MB)</label>
                <input type="file" name="pan_file" accept="image/*,application/pdf" class="form-control file-limit-2mb">
              </div>

              <!-- Police Verification (Optional) -->
              <div class="col-12">
                <div class="p-3 rounded-3 border bg-light-subtle">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-semibold mb-0">
                      <i class="bi bi-shield-check text-success me-1"></i>Police Verification Document
                    </label>
                    <span class="badge bg-secondary-subtle text-secondary border">Optional</span>
                  </div>
                  <div class="text-muted small mb-2">Upload Police Clearance Certificate (PCC), Verification Form, or NOC if available.</div>
                  <input type="file" name="police_verification_file" accept="image/*,application/pdf" class="form-control file-limit-2mb">
                  <div class="form-text small text-muted">Supported formats: PDF, JPG, PNG, WEBP (Max 2MB). Can be uploaded now or later.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- 6. Additional Notes -->
          <div class="p-3 rounded-3 bg-white dark:bg-card border shadow-sm">
            <h6 class="fw-bold text-muted mb-2 d-flex align-items-center gap-2">
              <i class="bi bi-card-text"></i> 6. Additional Remarks / Notes
            </h6>
            <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes regarding the tenant, vehicle number, preferences..."></textarea>
          </div>

        </form>
      </div>
      <div class="modal-footer border-top pt-3 bg-white dark:bg-card">
        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary px-4 fw-semibold shadow-sm" id="btn-save-t">
          <i class="bi bi-check2-circle me-1"></i> Save Tenant
        </button>
      </div>`, () => {

      // Initialize searchable dropdowns for Hotel and Room
      const hotelEl = document.getElementById('sel-hotel');
      const roomEl  = document.getElementById('sel-room');

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

      hotelTs.on('change', async (hid) => {
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
            const free = Math.max(0, cap - occ);
            const isFull = free <= 0;
            roomTs.addOption({
              value: r.id,
              text: isFull ? `Room ${r.number} (Full - 0 of ${cap} beds available)` : `Room ${r.number} (${free} of ${cap} beds available)`,
              disabled: isFull
            });
          });
        }
        roomTs.refreshOptions(false);
      });

      document.getElementById('btn-save-t').onclick = async () => {
        const form = document.getElementById('f-add-tenant');

        // Client-side 2MB validation
        const fileInputs = form.querySelectorAll('.file-limit-2mb');
        for (const fi of fileInputs) {
          if (fi.files && fi.files[0] && fi.files[0].size > 2 * 1024 * 1024) {
            swalAlert('File Too Large', `Selected ${fi.name.replace('_', ' ')} file exceeds 2MB limit. Please choose a file smaller than 2MB.`, 'error');
            return;
          }
        }

        const btn = document.getElementById('btn-save-t');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        try {
          const formData = new FormData(form);
          const res = await apiPost('tenants/save', formData);
          if (res.success) {
            toast(res.message);
            closeModal();
            loadTenants(1);
          } else {
            swalAlert('Error', res.message, 'error');
          }
        } catch (err) {
          swalAlert('Error', 'An error occurred while saving tenant.', 'error');
        } finally {
          btn.disabled = false;
          btn.innerHTML = 'Save Tenant';
        }
      };
    });
  }
</script>