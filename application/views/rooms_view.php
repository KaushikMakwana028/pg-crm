<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-enter">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="section-title" id="rooms-title-count">Rooms</div>
    <button class="quick-btn" onclick="openRoomModal()"><i class="bi bi-door-open me-1"></i>Add Room</button>
  </div>

  <!-- AJAX Filter Bar -->
  <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
    <input class="form-control" style="max-width:220px" id="room-filter-q" placeholder="Search room / hotel..." />
    <select class="form-select w-auto" id="room-filter-hotel" onchange="loadRooms(1)">
      <option value="">All Hotels</option>
      <?php foreach ($hotels as $h): ?>
        <option value="<?= $h->id ?>" <?= (string)$h->id === (string)($filters['hotel_id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($h->name) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-select w-auto" id="room-filter-status" onchange="loadRooms(1)">
      <option value="">All Status</option>
      <option value="Available" <?= ($filters['status'] ?? '') === 'Available' ? 'selected' : '' ?>>Available</option>
      <option value="Occupied" <?= ($filters['status'] ?? '') === 'Occupied' ? 'selected' : '' ?>>Occupied</option>
      <option value="Maintenance" <?= ($filters['status'] ?? '') === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
    </select>
    <button type="button" class="btn btn-primary" onclick="loadRooms(1)"><i class="bi bi-search"></i> Filter</button>
    <button type="button" class="btn btn-outline-secondary" onclick="resetRoomFilters()">Reset</button>
  </div>

  <!-- Rooms Cards Grid -->
  <div class="row g-3" id="rooms-grid-container">
    <div class="col-12 text-center py-5 text-muted">
      <span class="spinner-border spinner-border-sm me-2"></span>Loading rooms...
    </div>
  </div>

  <!-- Pagination Host -->
  <div id="rooms-pagination"></div>
</div>

<script>
  let roomSearchTimer = null;
  document.getElementById('room-filter-q').addEventListener('input', () => {
    clearTimeout(roomSearchTimer);
    roomSearchTimer = setTimeout(() => {
      loadRooms(1);
    }, 350);
  });

  function resetRoomFilters() {
    document.getElementById('room-filter-q').value = '';
    document.getElementById('room-filter-hotel').value = '';
    document.getElementById('room-filter-status').value = '';
    loadRooms(1);
  }

  async function loadRooms(page = 1) {
    const q = document.getElementById('room-filter-q').value.trim();
    const hotel_id = document.getElementById('room-filter-hotel').value;
    const status = document.getElementById('room-filter-status').value;
    const grid = document.getElementById('rooms-grid-container');

    try {
      const res = await apiGet('rooms/list_ajax', { page, q, hotel_id, status, per_page: 12 });
      if (!res.success) {
        grid.innerHTML = `<div class="col-12 text-center text-danger py-4">Failed to load rooms.</div>`;
        return;
      }

      const rooms = res.data || [];
      const total = res.pagination.total;
      document.getElementById('rooms-title-count').textContent = `Rooms (${total})`;

      if (!rooms.length) {
        grid.innerHTML = `
          <div class="col-12">
            <div class="empty-state">
              <i class="bi bi-door-open fs-1 text-muted d-block mb-2"></i>
              No rooms match your filter.
            </div>
          </div>
        `;
        document.getElementById('rooms-pagination').innerHTML = '';
        return;
      }

      grid.innerHTML = rooms.map(r => {
        const occ = parseInt(r.occupied_beds || 0);
        const cap = parseInt(r.capacity || 1);
        const available = Math.max(0, cap - occ);
        const pct = cap ? Math.min(100, Math.round((occ / cap) * 100)) : 0;
        const statusBadgeClass = (r.status === 'Available') ? 'badge-available' : ((r.status === 'Occupied') ? 'badge-occupied' : 'badge-maintenance');

        return `
          <div class="col-md-6 col-xl-4">
            <div class="stat-card room-card card-lift">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <a href="<?= base_url('rooms/detail/') ?>${r.id}" class="text-decoration-none text-reset">
                    <h5 class="mb-1 fw-bold">Room ${r.number} <i class="bi bi-box-arrow-up-right fs-6 text-muted"></i></h5>
                  </a>
                  <div class="text-muted small"><i class="bi bi-building me-1"></i>${r.hotel_name || '-'}</div>
                </div>
                <span class="badge ${statusBadgeClass}">${r.status}</span>
              </div>
              <div class="mt-3 p-2 rounded bg-light" style="border: 1px solid var(--border);">
                <div class="d-flex justify-content-between small mb-1">
                  <span class="text-muted">Total Capacity:</span>
                  <span class="fw-bold">${cap} ${cap === 1 ? 'Bed' : 'Beds'}</span>
                </div>
                <div class="d-flex justify-content-between small mb-1">
                  <span class="text-muted">Occupied:</span>
                  <span class="fw-semibold text-danger">${occ} ${occ === 1 ? 'Bed' : 'Beds'}</span>
                </div>
                <div class="d-flex justify-content-between small">
                  <span class="text-muted">Available:</span>
                  <span class="fw-bold ${available > 0 ? 'text-success' : 'text-danger'}">${available} ${available === 1 ? 'Bed' : 'Beds'}</span>
                </div>
              </div>
              <div class="progress mt-3 mb-3" style="height: 8px;">
                <div class="progress-bar ${available === 0 ? 'bg-danger' : 'bg-primary'}" style="width:${pct}%"></div>
              </div>
              <div class="d-flex gap-2">
                <a href="<?= base_url('rooms/detail/') ?>${r.id}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Details</a>
                <button class="btn btn-sm btn-outline-secondary" onclick='openRoomModal(${JSON.stringify(r)})'><i class="bi bi-pencil me-1"></i>Edit</button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteRoom(${r.id})"><i class="bi bi-trash me-1"></i>Delete</button>
              </div>
            </div>
          </div>
        `;
      }).join('');

      document.getElementById('rooms-pagination').innerHTML = renderPagination(res.pagination.total, res.pagination.page, res.pagination.per_page, 'loadRooms');

    } catch (err) {
      console.error(err);
      grid.innerHTML = `<div class="col-12 text-center text-danger py-4">Error loading rooms.</div>`;
    }
  }

  // Initial load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => loadRooms(1));
  } else {
    loadRooms(1);
  }

  function openRoomModal(r = null) {
    const isEdit = !!r;
    r = r || {};
    openModal(`
      <div class="modal-header"><h5 class="modal-title fw-bold">${isEdit ? 'Edit' : 'Add'} Room</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="f-room-form">
          <input type="hidden" name="id" value="${r.id || ''}">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Hotel / Property <span class="text-danger">*</span></label>
              <select class="form-select" name="hotel_id" id="rm-modal-hotel" required>
                <?php foreach ($hotels as $h): ?>
                  <option value="<?= $h->id ?>" ${r.hotel_id == '<?= $h->id ?>' ? 'selected' : ''}><?= htmlspecialchars($h->name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Room Number <span class="text-danger">*</span></label>
              <input class="form-control" name="number" value="${r.number || ''}" placeholder="e.g. A101" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Capacity (Total Beds) <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="capacity" value="${r.capacity || 1}" min="1" required placeholder="e.g. 3">
              <div class="form-text small text-muted">Total number of beds available for tenants.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Status</label>
              <select class="form-select" name="status">
                <option value="Available" ${r.status==='Available'?'selected':''}>Available</option>
                <option value="Occupied" ${r.status==='Occupied'?'selected':''}>Occupied</option>
                <option value="Maintenance" ${r.status==='Maintenance'?'selected':''}>Maintenance</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary px-4 fw-semibold" id="btn-save-rm">Save Room</button>
      </div>`, () => {
      document.getElementById('btn-save-rm').onclick = async () => {
        const form = document.getElementById('f-room-form');
        const fd = new FormData(form);
        const res = await apiPost('rooms/save', fd);
        if (res.success) {
          toast(res.message);
          closeModal();
          loadRooms(1);
        } else {
          swalAlert('Error', res.message, 'error');
        }
      };
    });
  }

  async function deleteRoom(id) {
    const conf = await swalConfirm('Delete Room?', 'Are you sure you want to delete this room? Any tenant assignments should be checked.');
    if (!conf.isConfirmed) return;

    const res = await apiGet('rooms/delete/' + id);
    if (res.success) {
      toast(res.message);
      loadRooms(1);
    } else {
      swalAlert('Error', res.message, 'error');
    }
  }
</script>
