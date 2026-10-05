<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-enter">
  <div class="section-title mb-3">Occupancy Analytics</div>
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card">
        <div class="text-muted small">Total Capacity</div>
        <div class="fs-4 fw-bold"><?= intval($total_capacity ?? 0) ?> beds</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div class="text-muted small">Occupied Beds</div>
        <div class="fs-4 fw-bold text-danger"><?= intval($total_occupied ?? 0) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div class="text-muted small">Available Beds</div>
        <div class="fs-4 fw-bold text-success"><?= intval($total_available ?? 0) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div class="text-muted small">Occupancy Rate</div>
        <div class="fs-4 fw-bold text-primary"><?= intval($occupancy_rate ?? 0) ?>%</div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="stat-card">
        <h6 class="fw-semibold mb-3">Bed Allocation Distribution</h6>
        <div style="height: 220px; max-height: 220px; position: relative;" class="d-flex justify-content-center">
          <canvas id="occ-chart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="stat-card table-wrap">
        <h6 class="fw-semibold mb-3">Room Status Breakdown</h6>
        <table class="table align-middle">
          <thead><tr><th>Room</th><th>Hotel</th><th>Type</th><th>Occupancy</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (!empty($rooms)): ?>
              <?php foreach ($rooms as $r): ?>
                <tr>
                  <td class="fw-semibold">Room <?= htmlspecialchars($r->number) ?></td>
                  <td><?= htmlspecialchars($r->hotel_name ?? '') ?></td>
                  <td><?= htmlspecialchars($r->type) ?></td>
                  <td><?= intval($r->occupied_beds ?? 0) ?> / <?= intval($r->capacity) ?></td>
                  <td><span class="badge <?= $r->status === 'Available' ? 'badge-available' : ($r->status === 'Occupied' ? 'badge-occupied' : 'badge-maintenance') ?>"><?= $r->status ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-center text-muted py-3">No rooms configured.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('occ-chart');
    if (ctx) {
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Occupied Beds', 'Available Beds'],
          datasets: [{
            data: [<?= intval($total_occupied ?? 0) ?>, <?= intval($total_available ?? 0) ?>],
            backgroundColor: ['#ef4444', '#10b981'],
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '70%',
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                boxWidth: 12,
                padding: 14,
                font: { family: 'Poppins', size: 12 }
              }
            }
          }
        }
      });
    }
  });
</script>
