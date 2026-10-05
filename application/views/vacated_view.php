<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-enter">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="section-title" id="vacated-title-count">Vacated Tenant History</div>
  </div>

  <!-- Search Filter Bar -->
  <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
    <input class="form-control" style="max-width:260px" id="vacated-filter-q" placeholder="Search name / phone / email..." />
    <button type="button" class="btn btn-primary" onclick="loadVacated(1)"><i class="bi bi-search"></i> Filter</button>
    <button type="button" class="btn btn-outline-secondary" onclick="resetVacatedFilters()">Reset</button>
  </div>

  <div class="stat-card table-wrap">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Photo</th>
          <th>Name</th>
          <th>Hotel / Room</th>
          <th>Check-in Date</th>
          <th>Check-out (Vacate) Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="vacated-tbody">
        <tr><td colspan="6" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading vacated history...</td></tr>
      </tbody>
    </table>
  </div>

  <!-- Pagination Host -->
  <div id="vacated-pagination"></div>
</div>

<script>
  let searchVacatedTimer = null;
  document.getElementById('vacated-filter-q').addEventListener('input', () => {
    clearTimeout(searchVacatedTimer);
    searchVacatedTimer = setTimeout(() => {
      loadVacated(1);
    }, 350);
  });

  function resetVacatedFilters() {
    document.getElementById('vacated-filter-q').value = '';
    loadVacated(1);
  }

  async function loadVacated(page = 1) {
    const q = document.getElementById('vacated-filter-q').value.trim();
    const tbody = document.getElementById('vacated-tbody');

    try {
      const res = await apiGet('tenants/vacated_ajax', { page, q, per_page: 10 });
      if (!res.success) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Failed to load vacated history.</td></tr>`;
        return;
      }

      const tenants = res.data || [];
      const total = res.pagination.total;
      document.getElementById('vacated-title-count').textContent = `Vacated Tenant History (${total})`;

      if (!tenants.length) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center text-muted py-5">
              <i class="bi bi-box-arrow-right fs-1 text-muted d-block mb-2"></i>
              No vacated tenants found.
            </td>
          </tr>
        `;
        document.getElementById('vacated-pagination').innerHTML = '';
        return;
      }

      tbody.innerHTML = tenants.map(t => {
        const avatarHtml = t.profile_image ?
          `<img src="${BASE_URL + t.profile_image}" alt="${t.name}" />` :
          (t.name ? t.name.charAt(0).toUpperCase() : 'T');

        return `
          <tr>
            <td><div class="avatar sm">${avatarHtml}</div></td>
            <td class="fw-semibold">
              ${t.name}
              <div class="small text-muted">${t.phone || '-'}</div>
            </td>
            <td>${t.hotel_name || ''} • Room ${t.room_number || '-'}</td>
            <td><i class="bi bi-calendar-check me-1 text-success"></i>${t.check_in || '-'}</td>
            <td><span class="badge text-bg-secondary"><i class="bi bi-box-arrow-right me-1"></i>${t.vacate_date || '-'}</span></td>
            <td>
              <a href="${BASE_URL}tenants/detail/${t.id}" class="btn btn-sm btn-outline-primary">View Details</a>
            </td>
          </tr>
        `;
      }).join('');

      document.getElementById('vacated-pagination').innerHTML = renderPagination(res.pagination.total, res.pagination.page, res.pagination.per_page, 'loadVacated');

    } catch (err) {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Error loading data.</td></tr>`;
    }
  }

  // Initial load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => loadVacated(1));
  } else {
    loadVacated(1);
  }
</script>
