<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-enter">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="section-title" id="hotels-title-count">Hotels / PG Properties</div>
    <button class="quick-btn" onclick="openHotelModal()"><i class="bi bi-plus-lg me-1"></i>Add Hotel</button>
  </div>

  <!-- Search Filter Bar -->
  <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
    <input class="form-control" style="max-width:280px" id="hotel-search-q" placeholder="Search property name / city / phone..." />
    <button type="button" class="btn btn-primary" onclick="loadHotels(1)"><i class="bi bi-search"></i> Search</button>
    <button type="button" class="btn btn-outline-secondary" onclick="resetHotelSearch()">Reset</button>
  </div>

  <div class="row g-3" id="hotels-grid-container">
    <div class="col-12 text-center py-5 text-muted">
      <span class="spinner-border spinner-border-sm me-2"></span>Loading properties...
    </div>
  </div>

  <!-- Pagination Host -->
  <div id="hotels-pagination"></div>
</div>

<script>
  let hotelSearchTimer = null;
  document.getElementById('hotel-search-q').addEventListener('input', () => {
    clearTimeout(hotelSearchTimer);
    hotelSearchTimer = setTimeout(() => {
      loadHotels(1);
    }, 350);
  });

  function resetHotelSearch() {
    document.getElementById('hotel-search-q').value = '';
    loadHotels(1);
  }

  async function loadHotels(page = 1) {
    const q = document.getElementById('hotel-search-q').value.trim();
    const grid = document.getElementById('hotels-grid-container');

    try {
      const res = await apiGet('hotels/list_ajax', { page, q, per_page: 9 });
      if (!res.success) {
        grid.innerHTML = `<div class="col-12 text-center text-danger py-4">Failed to load properties.</div>`;
        return;
      }

      const hotels = res.data || [];
      const total = res.pagination.total;
      document.getElementById('hotels-title-count').textContent = `Hotels / PG Properties (${total})`;

      if (!hotels.length) {
        grid.innerHTML = `
          <div class="col-12">
            <div class="empty-state">
              <i class="bi bi-building fs-1 text-muted d-block mb-2"></i>
              No properties found matching your search.
            </div>
          </div>
        `;
        document.getElementById('hotels-pagination').innerHTML = '';
        return;
      }

      grid.innerHTML = hotels.map(h => `
        <div class="col-md-6 col-xl-4">
          <div class="stat-card hotel-card card-lift">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <h5 class="fw-bold mb-1 text-truncate">${h.name}</h5>
              ${h.code ? `<span class="badge text-bg-light border">${h.code}</span>` : ''}
            </div>
            <div class="text-muted small mb-2 text-truncate"><i class="bi bi-geo-alt"></i> ${h.address ? h.address + ', ' : ''}${h.city || ''}</div>
            <div class="small mb-3 text-muted">
              <i class="bi bi-telephone"></i> ${h.phone || '-'} • <strong>${h.total_rooms || 0}</strong> rooms • <strong>${h.total_tenants || 0}</strong> active tenants
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <a href="${BASE_URL}rooms?hotel_id=${h.id}" class="btn btn-sm btn-primary">View Rooms</a>
              <button class="btn btn-sm btn-outline-secondary" onclick='openHotelModal(${JSON.stringify(h)})'>Edit</button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteHotel(${h.id})">Delete</button>
            </div>
          </div>
        </div>
      `).join('');

      document.getElementById('hotels-pagination').innerHTML = renderPagination(res.pagination.total, res.pagination.page, res.pagination.per_page, 'loadHotels');

    } catch (err) {
      console.error(err);
      grid.innerHTML = `<div class="col-12 text-center text-danger py-4">Error loading properties.</div>`;
    }
  }

  // Initial load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => loadHotels(1));
  } else {
    loadHotels(1);
  }

  function openHotelModal(h = null) {
    const isEdit = !!h;
    h = h || {};
    openModal(`
      <div class="modal-header"><h5 class="modal-title fw-bold">${isEdit ? 'Edit' : 'Add'} Hotel / Property</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="f-hotel-form">
          <input type="hidden" name="id" value="${h.id || ''}">
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label fw-semibold">Property Name <span class="text-danger">*</span></label><input class="form-control" name="name" value="${h.name || ''}" placeholder="e.g. Sunrise PG" required></div>
            <div class="col-md-6"><label class="form-label fw-semibold">Property Code</label><input class="form-control" name="code" value="${h.code || ''}" placeholder="SUN-001"></div>
            <div class="col-md-8"><label class="form-label fw-semibold">Address</label><input class="form-control" name="address" value="${h.address || ''}" placeholder="Street, landmark"></div>
            <div class="col-md-4"><label class="form-label fw-semibold">City</label><input class="form-control" name="city" value="${h.city || ''}" placeholder="City"></div>
            <div class="col-md-6"><label class="form-label fw-semibold">Phone</label><input class="form-control" name="phone" value="${h.phone || ''}" placeholder="+91 9876543210"></div>
            <div class="col-12"><label class="form-label fw-semibold">Description</label><textarea class="form-control" name="description" placeholder="Notes or description...">${h.description || ''}</textarea></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary px-4 fw-semibold" id="btn-save-h">Save Property</button>
      </div>`, () => {
      document.getElementById('btn-save-h').onclick = async () => {
        const form = document.getElementById('f-hotel-form');
        const fd = new FormData(form);
        const res = await apiPost('hotels/save', fd);
        if (res.success) {
          toast(res.message);
          closeModal();
          loadHotels(1);
        } else {
          swalAlert('Error', res.message, 'error');
        }
      };
    });
  }

  async function deleteHotel(id) {
    const conf = await swalConfirm('Delete Property?', 'Are you sure you want to delete this property? Associated rooms will be affected.');
    if (!conf.isConfirmed) return;

    const res = await apiGet('hotels/delete/' + id);
    if (res.success) {
      toast(res.message);
      loadHotels(1);
    } else {
      swalAlert('Error', res.message, 'error');
    }
  }
</script>
