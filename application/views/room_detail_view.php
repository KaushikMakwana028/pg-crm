<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="page-enter">
  <!-- Back navigation -->
  <div class="mb-3">
    <a href="<?= base_url('rooms') ?>" class="btn btn-link text-decoration-none px-0 text-muted">
      <i class="bi bi-arrow-left me-1"></i> Back to Rooms
    </a>
  </div>

  <!-- Room Header & Stat Card -->
  <div class="stat-card mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
          <h3 class="fw-bold mb-0">Room <?= htmlspecialchars($room->number) ?></h3>
          <?php
            $badgeClass = ($room->status === 'Available') ? 'badge-available' : (($room->status === 'Occupied') ? 'badge-occupied' : 'badge-maintenance');
          ?>
          <span class="badge <?= $badgeClass ?> fs-6"><?= htmlspecialchars($room->status) ?></span>
        </div>
        <div class="text-muted mb-2">
          <i class="bi bi-building me-1"></i><?= htmlspecialchars($room->hotel_name ?? 'Hotel') ?>
          <?php if (!empty($room->hotel_address)): ?>
            <span class="mx-1">·</span> <span class="small"><?= htmlspecialchars($room->hotel_address) ?></span>
          <?php endif; ?>
        </div>
        <div class="fw-semibold">
          <span class="text-primary"><?= intval($room->occupied_beds) ?></span> / <?= intval($room->capacity) ?> Occupants
          <span class="mx-1">·</span>
          <span class="<?= intval($room->free_beds) > 0 ? 'text-success' : 'text-danger' ?>">
            <?= intval($room->free_beds) ?> <?= intval($room->free_beds) === 1 ? 'bed' : 'beds' ?> free
          </span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" onclick="openAssignTenantModal()">
          <i class="bi bi-person-plus me-1"></i>Assign Tenant
        </button>
        <button class="btn btn-outline-secondary" onclick="openEditRoomModal()">
          <i class="bi bi-pencil me-1"></i>Edit Room
        </button>
        <button class="btn btn-outline-danger" onclick="deleteCurrentRoom(<?= $room->id ?>)">
          <i class="bi bi-trash me-1"></i>Delete Room
        </button>
      </div>
    </div>

    <!-- Capacity Bar Breakdown -->
    <div class="mt-4 pt-3 border-top">
      <?php
        $cap = max(1, intval($room->capacity));
        $occ = intval($room->occupied_beds);
        $pct = min(100, round(($occ / $cap) * 100));
      ?>
      <div class="d-flex justify-content-between small text-muted mb-1">
        <span>Capacity Utilization</span>
        <span><?= $pct ?>% Full (<?= $occ ?> of <?= $cap ?> beds occupied)</span>
      </div>
      <div class="progress" style="height: 10px;">
        <div class="progress-bar <?= ($room->free_beds == 0) ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= $pct ?>%"></div>
      </div>
    </div>
  </div>

  <!-- Current Tenants List -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Current Tenants (<?= count($tenants) ?>)</h5>
    <?php if (intval($room->free_beds) > 0): ?>
      <span class="badge bg-success-subtle text-success border border-success-subtle">
        <i class="bi bi-check-circle me-1"></i><?= $room->free_beds ?> Bed<?= $room->free_beds > 1 ? 's' : '' ?> Available
      </span>
    <?php else: ?>
      <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
        <i class="bi bi-exclamation-circle me-1"></i>Room Full
      </span>
    <?php endif; ?>
  </div>

  <?php if (!empty($tenants)): ?>
    <div class="row g-3">
      <?php foreach ($tenants as $t): ?>
        <div class="col-md-6 col-xl-4">
          <div class="stat-card h-100 d-flex flex-column justify-content-between card-lift">
            <div>
              <div class="d-flex gap-3 align-items-start mb-3">
                <div class="avatar" style="width:48px;height:48px;font-size:1.1rem;flex-shrink:0;">
                  <?php if (!empty($t->profile_image)): ?>
                    <img src="<?= base_url($t->profile_image) ?>" alt="<?= htmlspecialchars($t->name) ?>" />
                  <?php else: ?>
                    <?= strtoupper(substr($t->name, 0, 1)) ?>
                  <?php endif; ?>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                  <h6 class="fw-bold mb-1 text-truncate"><?= htmlspecialchars($t->name) ?></h6>
                  <div class="text-muted small text-truncate">
                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($t->phone) ?>
                  </div>
                  <div class="text-muted small text-truncate">
                    <i class="bi bi-calendar-check me-1"></i>Joined: <?= !empty($t->check_in) ? date('d M Y', strtotime($t->check_in)) : '-' ?>
                  </div>
                </div>
              </div>

              <!-- Rent & Deposit Status Badges -->
              <div class="p-2 rounded bg-light mb-3" style="border: 1px solid var(--border);">
                <div class="d-flex justify-content-between align-items-center small mb-1">
                  <span class="text-muted">Monthly Rent:</span>
                  <span class="fw-bold"><?= ($settings->currency ?? '₹') . number_format($t->rent_amount, 0) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center small mb-1">
                  <span class="text-muted">This Month Rent:</span>
                  <?php if ($t->rent_status === 'Paid'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>
                  <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Unpaid</span>
                  <?php endif; ?>
                </div>
                <div class="d-flex justify-content-between align-items-center small">
                  <span class="text-muted">Security Deposit:</span>
                  <?php if (($t->deposit_status ?? '') === 'Paid'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>
                  <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div>
              <a href="<?= base_url('tenants/detail/' . $t->id) ?>" class="btn btn-sm btn-outline-primary w-100">
                <i class="bi bi-person me-1"></i>View Profile
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="stat-card text-center py-5">
      <i class="bi bi-person-x fs-1 text-muted d-block mb-2"></i>
      <h6 class="fw-semibold">No tenants assigned to this room yet</h6>
      <p class="text-muted small mb-3">You can assign an existing tenant to this room or register a new tenant.</p>
      <button class="btn btn-primary btn-sm" onclick="openAssignTenantModal()">
        <i class="bi bi-person-plus me-1"></i>Assign Tenant Now
      </button>
    </div>
  <?php endif; ?>
</div>

<script>
  const currentRoom = <?= json_encode($room) ?>;
  const allHotels = <?= json_encode($hotels) ?>;
  const allActiveTenants = <?= json_encode($all_active_tenants) ?>;

  function escapeHtmlSafe(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function openEditRoomModal() {
    const hotelOptions = (allHotels || []).map(h => 
      `<option value="${h.id}" ${h.id == currentRoom.hotel_id ? 'selected' : ''}>${escapeHtmlSafe(h.name)}</option>`
    ).join('');

    openModal(`
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Edit Room ${escapeHtmlSafe(currentRoom.number)}</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="f-room-edit-form">
          <input type="hidden" name="id" value="${currentRoom.id}">
          <div class="mb-3">
            <label class="form-label fw-semibold">Hotel / Property <span class="text-danger">*</span></label>
            <select class="form-select" name="hotel_id" required>
              ${hotelOptions}
            </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Room Number <span class="text-danger">*</span></label>
              <input class="form-control" name="number" value="${escapeHtmlSafe(currentRoom.number)}" required />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Capacity (Total Beds) <span class="text-danger">*</span></label>
              <input class="form-control" type="number" min="1" name="capacity" value="${currentRoom.capacity || 1}" required />
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select" name="status">
              <option value="Available" ${currentRoom.status === 'Available' ? 'selected' : ''}>Available</option>
              <option value="Occupied" ${currentRoom.status === 'Occupied' ? 'selected' : ''}>Occupied</option>
              <option value="Maintenance" ${currentRoom.status === 'Maintenance' ? 'selected' : ''}>Maintenance</option>
            </select>
          </div>
          <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="btn-save-room">Save Changes</button>
          </div>
        </form>
      </div>
    `, () => {
      const form = document.getElementById('f-room-edit-form');
      if (form) {
        form.onsubmit = async (e) => {
          e.preventDefault();
          const btn = document.getElementById('btn-save-room');
          btn.disabled = true;
          btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

          try {
            const fd = new FormData(e.target);
            const res = await apiPost('rooms/save', fd);
            if (res.success) {
              toast(res.message || 'Room updated successfully.', 'success');
              closeModal();
              setTimeout(() => location.reload(), 400);
            } else {
              swalAlert('Error', res.message || 'Failed to update room.', 'error');
              btn.disabled = false;
              btn.innerHTML = 'Save Changes';
            }
          } catch (err) {
            swalAlert('Error', 'Failed to update room.', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Save Changes';
          }
        };
      }
    });
  }

  function openAssignTenantModal() {
    if (parseInt(currentRoom.free_beds || 0) <= 0) {
      swalAlert('Room Full', 'This room has reached its maximum capacity. Please increase the capacity or transfer existing occupants before assigning more tenants.', 'warning');
      return;
    }

    let tenantOptions = '';
    if (allActiveTenants && allActiveTenants.length > 0) {
      tenantOptions = allActiveTenants.map(t => 
        `<option value="${t.id}">${escapeHtmlSafe(t.name)} (${t.phone || 'No phone'})</option>`
      ).join('');
    }

    openModal(`
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Assign Tenant to Room ${escapeHtmlSafe(currentRoom.number)}</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        ${tenantOptions ? `
          <div class="mb-4">
            <h6 class="fw-semibold mb-2 text-primary"><i class="bi bi-person-check me-1"></i>Option 1: Assign Existing Tenant</h6>
            <p class="text-muted small mb-2">Select an active tenant to assign or transfer to this room.</p>
            <form id="f-assign-tenant-form">
              <input type="hidden" name="hotel_id" value="${currentRoom.hotel_id}">
              <input type="hidden" name="room_id" value="${currentRoom.id}">
              <div class="mb-3">
                <label class="form-label fw-semibold">Select Tenant *</label>
                <select class="form-select" name="tenant_id" required>
                  <option value="">-- Choose Tenant --</option>
                  ${tenantOptions}
                </select>
              </div>
              <button type="submit" class="btn btn-primary w-100" id="btn-assign-existing">
                <i class="bi bi-box-arrow-in-right me-1"></i>Assign Selected Tenant
              </button>
            </form>
          </div>
          <hr class="my-3" />
        ` : `
          <div class="alert alert-info small mb-3">
            <i class="bi bi-info-circle me-1"></i>No other unassigned or transferable active tenants currently found.
          </div>
        `}

        <div>
          <h6 class="fw-semibold mb-2 text-success"><i class="bi bi-person-plus me-1"></i>Option 2: Register & Assign New Tenant</h6>
          <p class="text-muted small mb-3">Create a brand new tenant record with this room pre-selected.</p>
          <a href="<?= base_url('tenants') ?>?assign_hotel=${currentRoom.hotel_id}&assign_room=${currentRoom.id}&action=add" class="btn btn-outline-success w-100">
            <i class="bi bi-plus-circle me-1"></i>+ Register New Tenant in Room ${escapeHtmlSafe(currentRoom.number)}
          </a>
        </div>
      </div>
    `, () => {
      const assignForm = document.getElementById('f-assign-tenant-form');
      if (assignForm) {
        assignForm.onsubmit = async (e) => {
          e.preventDefault();
          const btn = document.getElementById('btn-assign-existing');
          btn.disabled = true;
          btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Assigning...';

          try {
            const fd = new FormData(e.target);
            const res = await apiPost('tenants/transfer', fd);
            if (res.success) {
              toast(res.message || 'Tenant assigned successfully.', 'success');
              closeModal();
              setTimeout(() => location.reload(), 400);
            } else {
              swalAlert('Error', res.message || 'Failed to assign tenant.', 'error');
              btn.disabled = false;
              btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i>Assign Selected Tenant';
            }
          } catch (err) {
            swalAlert('Error', 'Failed to assign tenant.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i>Assign Selected Tenant';
          }
        };
      }
    });
  }

  async function deleteCurrentRoom(roomId) {
    const conf = await swalConfirm('Delete Room ' + (currentRoom.number || '') + '?', 'Are you sure you want to delete this room? This action cannot be undone.', 'Yes, Delete Room');
    if (!conf.isConfirmed) return;

    try {
      const res = await apiPost('rooms/delete/' + roomId);
      if (res.success) {
        toast(res.message || 'Room deleted successfully.', 'success');
        setTimeout(() => {
          window.location.href = '<?= base_url('rooms') ?>';
        }, 500);
      } else {
        swalAlert('Error', res.message || 'Failed to delete room.', 'error');
      }
    } catch (err) {
      swalAlert('Error', 'Failed to delete room.', 'error');
    }
  }
</script>
