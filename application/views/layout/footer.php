<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
</div> <!-- end #page-content -->
</div> <!-- end .main -->
</div> <!-- end #app-screen -->

<div class="toast-wrap" id="toast-wrap"></div>
<div id="modal-host"></div>

<script>

  // Mobile Hamburger
  document.getElementById('btn-hamburger')?.addEventListener('click', () => {
    const sb = document.getElementById('sidebar');
    sb.classList.toggle('open');
    const ov = document.getElementById('sidebar-overlay');
    if (ov) ov.style.display = sb.classList.contains('open') ? 'block' : 'none';
  });

  document.getElementById('sidebar-overlay')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebar-overlay').style.display = 'none';
  });

  // Dark Mode Switch
  document.getElementById('btn-theme')?.addEventListener('click', async () => {
    const isDark = document.body.classList.toggle('dark');
    const theme = isDark ? 'dark' : 'light';
    document.documentElement.setAttribute('data-bs-theme', theme);
    document.body.setAttribute('data-bs-theme', theme);
    const icon = document.querySelector('#btn-theme i');
    if (icon) icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon';
    await apiPost('settings/save', {
      crm_name: '<?= addslashes($settings->crm_name ?? 'StayFlow CRM') ?>',
      currency: '<?= addslashes($settings->currency ?? '₹') ?>',
      rent_due_date: <?= intval($settings->rent_due_date ?? 10) ?>,
      dark_mode: isDark ? 1 : 0
    });
  });

  // Notifications
  document.getElementById('btn-bell')?.addEventListener('click', async e => {
    e.stopPropagation();
    const drop = document.getElementById('notif-drop');
    const isShow = drop.style.display !== 'block';
    if (isShow) {
      try {
        const res = await apiGet('rooms/list');
        const rooms = res.success ? res.data : [];
        const fullRooms = rooms.filter(r => r.status !== 'Maintenance' && Number(r.occupied_beds || 0) >= Number(r.capacity));
        drop.innerHTML = fullRooms.length ? fullRooms.map(r => `
            <div class="item p-3 border-bottom" onclick="location.href='${BASE_URL}rooms'">
              <div class="small fw-semibold text-danger"><i class="bi bi-info-circle me-1"></i>Room ${r.number} (${r.hotel_name||'PG'}) is full</div>
            </div>
          `).join('') : '<div class="p-3 text-muted small">No current alerts.</div>';
        document.getElementById('notif-count').textContent = fullRooms.length;
      } catch (err) {
        drop.innerHTML = '<div class="p-3 text-muted small">No alerts.</div>';
      }
    }
    drop.style.display = isShow ? 'block' : 'none';
  });

  document.addEventListener('click', () => {
    const drop = document.getElementById('notif-drop');
    if (drop) drop.style.display = 'none';
    const searchBox = document.getElementById('search-results');
    if (searchBox) searchBox.style.display = 'none';
  });

  // Global Search
  const searchInp = document.getElementById('global-search');
  const searchBox = document.getElementById('search-results');
  if (searchInp && searchBox) {
    searchInp.addEventListener('input', async () => {
      const q = searchInp.value.trim().toLowerCase();
      if (!q) {
        searchBox.style.display = 'none';
        return;
      }

      try {
        const [tRes, rRes, hRes] = await Promise.all([
          apiGet('tenants/list', {
            q
          }),
          apiGet('rooms/list'),
          apiGet('hotels/list')
        ]);
        const tenants = tRes.success ? tRes.data : [];
        const rooms = (rRes.success ? rRes.data : []).filter(r => r.number.toLowerCase().includes(q));
        const hotels = (hRes.success ? hRes.data : []).filter(h => h.name.toLowerCase().includes(q) || (h.city || '').toLowerCase().includes(q));

        const items = [];
        tenants.slice(0, 4).forEach(t => items.push({
          title: t.name,
          sub: `Tenant in ${t.hotel_name||''} (Room ${t.room_number||'-'})`,
          url: `${BASE_URL}tenants/detail/${t.id}`
        }));
        rooms.slice(0, 3).forEach(r => items.push({
          title: 'Room ' + r.number,
          sub: `${r.hotel_name||''} (${r.type})`,
          url: `${BASE_URL}rooms`
        }));
        hotels.slice(0, 3).forEach(h => items.push({
          title: h.name,
          sub: `Hotel in ${h.city||''}`,
          url: `${BASE_URL}hotels`
        }));

        if (!items.length) {
          searchBox.innerHTML = '<div class="p-3 text-muted small">No matches found</div>';
        } else {
          searchBox.innerHTML = items.map(m => `
              <div class="item border-bottom" onclick="location.href='${m.url}'">
                <div class="fw-semibold small">${m.title}</div>
                <div class="text-muted small">${m.sub}</div>
              </div>
            `).join('');
        }
        searchBox.style.display = 'block';
      } catch (e) {}
    });
  }
</script>
</body>

</html>