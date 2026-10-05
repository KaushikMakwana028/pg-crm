<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
  .profile-avatar-wrapper {
    position: relative;
    width: 140px;
    height: 140px;
    margin: 0 auto 12px;
  }

  .profile-avatar-circle {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    border: 4px solid var(--card);
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    box-shadow: 0 10px 25px rgba(99, 102, 241, 0.22);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 54px;
    font-weight: 700;
    user-select: none;
    position: relative;
  }

  .profile-avatar-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
  }

  .camera-badge-action {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid var(--card);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    z-index: 5;
  }

  .camera-badge-action:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 18px rgba(99, 102, 241, 0.4);
    color: #ffffff;
  }

  .camera-badge-action i {
    font-size: 18px;
  }

  .security-notice-box {
    background: rgba(99, 102, 241, 0.05);
    border: 1px solid rgba(99, 102, 241, 0.15);
    border-radius: 14px;
    padding: 14px 16px;
    text-align: left;
  }

  body.dark .security-notice-box {
    background: rgba(99, 102, 241, 0.1);
    border-color: rgba(99, 102, 241, 0.25);
  }

  .btn-save-profile-custom {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 10px 24px;
    font-weight: 600;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.25);
    transition: all 0.2s ease;
  }

  .btn-save-profile-custom:hover {
    filter: brightness(1.08);
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.35);
  }

  .btn-save-profile-custom:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    transform: none;
  }
</style>

<div class="page-enter">
  <!-- Title Header -->
  <div class="mb-4">
    <h3 class="fw-bold mb-1" style="font-size: 26px;">Profile Settings</h3>
    <p class="text-muted small mb-0">Manage your administrative credentials and personal details.</p>
  </div>

  <form id="profile-main-form" enctype="multipart/form-data">
    <!-- Hidden File Input for Avatar Photo -->
    <input type="file" id="profile_image_input" name="profile_image" accept="image/png, image/jpeg, image/jpg, image/webp" class="d-none">

    <div class="row g-4">
      <!-- LEFT COLUMN: Profile Overview Card -->
      <div class="col-lg-4 col-xl-4">
        <div class="stat-card p-4 text-center h-100 d-flex flex-column justify-content-between">
          <div>
            <!-- Avatar with Camera Badge -->
            <div class="profile-avatar-wrapper">
              <div class="profile-avatar-circle" id="profile-avatar-box">
                <?php $has_pic = !empty($user->profile_image) && file_exists(FCPATH . $user->profile_image); ?>
                <img src="<?= $has_pic ? base_url($user->profile_image) : '' ?>" alt="Admin" id="avatar-preview-img" class="<?= $has_pic ? '' : 'd-none' ?>">
                <span id="avatar-preview-initial" class="<?= $has_pic ? 'd-none' : '' ?>">
                  <?= strtoupper(substr($user->name ?? 'Admin', 0, 1)) ?>
                </span>
              </div>

              <!-- Camera Button -->
              <label for="profile_image_input" class="camera-badge-action" title="Click to change photo">
                <i class="bi bi-camera-fill"></i>
              </label>
            </div>

            <!-- Upload Hint -->
            <div class="text-muted small mt-2 mb-0">
              <i class="bi bi-camera me-1"></i>Click camera to change photo
            </div>
            <div class="text-muted mb-3" style="font-size: 11px;">
              JPG, PNG, WEBP • Max 2MB
            </div>

            <!-- Admin Name & Email Display -->
            <h4 class="fw-bold mb-0 text-body" id="display-card-name"><?= htmlspecialchars($user->name ?? 'Admin') ?></h4>
            <div class="text-muted small mt-1 mb-3" id="display-card-email"><?= htmlspecialchars($user->email ?? 'admin@gmail.com') ?></div>

            <!-- Role & Status Badges -->
            <div class="d-flex justify-content-center gap-2 mb-4">
              <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background:#fef3c7; color:#92400e; font-size:12px;">
                <i class="bi bi-shield-fill me-1"></i>ADMIN
              </span>
              <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background:#d1fae5; color:#065f46; font-size:12px;">
                <i class="bi bi-check-circle-fill me-1"></i>Active
              </span>
            </div>
          </div>

          <!-- Security Notice Box -->
          <div class="security-notice-box mt-3">
            <div class="fw-bold small text-primary mb-1 d-flex align-items-center gap-2">
              <i class="bi bi-info-circle-fill"></i> Security Notice
            </div>
            <p class="text-muted mb-0" style="font-size: 12px; line-height: 1.5;">
              Role permissions and account active status are system-governed and cannot be modified from profile settings.
            </p>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Edit Personal Details & Password Card -->
      <div class="col-lg-8 col-xl-8">
        <div class="stat-card p-4">
          <h5 class="fw-bold mb-4" style="font-size: 19px;">Edit Personal Details</h5>

          <div class="row g-3">
            <!-- Full Name -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" id="input-name" value="<?= htmlspecialchars($user->name ?? '') ?>" placeholder="Admin" required>
            </div>

            <!-- Email Address -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" name="email" id="input-email" value="<?= htmlspecialchars($user->email ?? '') ?>" placeholder="admin@gmail.com" required>
            </div>

            <!-- Phone Number -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Phone Number</label>
              <input type="text" class="form-control" name="phone" id="input-phone" value="<?= htmlspecialchars($user->phone ?? '') ?>" placeholder="+91 9876543210">
            </div>

            <!-- System Role (Read Only) -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small">System Role</label>
              <input type="text" class="form-control" value="1 — Admin (Full System Access)" disabled readonly>
            </div>

            <!-- Address -->
            <div class="col-12">
              <label class="form-label fw-semibold small">Address</label>
              <textarea class="form-control" name="address" id="input-address" rows="3" placeholder="Office / Physical Address"><?= htmlspecialchars($user->address ?? '') ?></textarea>
            </div>

            <!-- Change Password Section (Inline on this same page) -->
            <div class="col-12 mt-4 pt-3 border-top">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold mb-0 text-body">
                  <i class="bi bi-key me-2 text-primary"></i>Change Password
                </h6>
                <span class="badge rounded-pill border small" id="badge-pass-optional">Optional</span>
              </div>
              <p class="text-muted small mb-3">Leave blank if you do not want to change your current password.</p>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold small">New Password</label>
                  <div class="position-relative">
                    <input type="password" class="form-control pe-5" name="password" id="input-password" placeholder="Enter new password">
                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-muted text-decoration-none pe-3" onclick="togglePass('input-password', this)">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small">Confirm New Password</label>
                  <div class="position-relative">
                    <input type="password" class="form-control pe-5" name="confirm_password" id="input-confirm-password" placeholder="Confirm new password">
                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-muted text-decoration-none pe-3" onclick="togglePass('input-confirm-password', this)">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Save Profile Action Button -->
            <div class="col-12 text-end mt-4 pt-3 border-top">
              <button type="submit" class="btn btn-save-profile-custom d-inline-flex align-items-center gap-2" id="btn-save-profile">
                <i class="bi bi-floppy-fill"></i>
                <span>Save Profile</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
  // Password Visibility Toggle
  function togglePass(inputId, btn) {
    const inp = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
      inp.type = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      inp.type = 'password';
      icon.className = 'bi bi-eye';
    }
  }

  // Live Sync of Name on Input
  document.getElementById('input-name').addEventListener('input', function() {
    const val = this.value.trim() || 'Admin';
    document.getElementById('display-card-name').textContent = val;
    const initEl = document.getElementById('avatar-preview-initial');
    if (initEl && !initEl.classList.contains('d-none')) {
      initEl.textContent = val.charAt(0).toUpperCase();
    }
  });

  // Live Sync of Email on Input
  document.getElementById('input-email').addEventListener('input', function() {
    const val = this.value.trim() || 'admin@gmail.com';
    document.getElementById('display-card-email').textContent = val;
  });

  // Photo Selection & 2MB Validation
  const imageInput = document.getElementById('profile_image_input');
  const previewImg = document.getElementById('avatar-preview-img');
  const previewInit = document.getElementById('avatar-preview-initial');

  imageInput.addEventListener('change', function(e) {
    const file = e.target.files && e.target.files[0];
    if (file) {
      if (!file.type.startsWith('image/')) {
        swalAlert('Invalid File', 'Please choose a valid image file (JPG, PNG, WEBP).', 'error');
        imageInput.value = '';
        return;
      }

      if (file.size > 2 * 1024 * 1024) {
        swalAlert('Image Too Large', 'Maximum allowed image size is 2MB. Please choose an image smaller than 2MB.', 'error');
        imageInput.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = function(evt) {
        previewImg.src = evt.target.result;
        previewImg.classList.remove('d-none');
        if (previewInit) previewInit.classList.add('d-none');
        toast('Photo preview ready. Click "Save Profile" to save changes.');
      };
      reader.readAsDataURL(file);
    }
  });

  // Form Submit via AJAX
  const profileForm = document.getElementById('profile-main-form');
  profileForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    const pass = document.getElementById('input-password').value;
    const confirm = document.getElementById('input-confirm-password').value;
    if (pass && pass !== confirm) {
      swalAlert('Password Mismatch', 'Password and Confirm Password do not match!', 'error');
      return;
    }

    const btn = document.getElementById('btn-save-profile');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    try {
      const formData = new FormData(profileForm);
      const res = await apiPost('profile/update', formData);

      if (res.success) {
        toast(res.message || 'Profile updated successfully!');

        // Update Left Card
        if (res.user_name) {
          document.getElementById('display-card-name').textContent = res.user_name;
          const headerName = document.getElementById('header-admin-name');
          if (headerName) headerName.textContent = res.user_name;
          const dropName = document.getElementById('dropdown-admin-name');
          if (dropName) dropName.textContent = res.user_name;
        }

        if (res.user_email) {
          document.getElementById('display-card-email').textContent = res.user_email;
          const dropEmail = document.querySelector('#dropdown-admin-name + div');
          if (dropEmail) dropEmail.textContent = res.user_email;
        }

        // Update Avatar everywhere
        if (res.profile_image) {
          // Profile Card Image
          previewImg.src = res.profile_image;
          previewImg.classList.remove('d-none');
          if (previewInit) previewInit.classList.add('d-none');

          // Header Avatar
          const hImg = document.getElementById('header-avatar-img');
          const hInit = document.getElementById('header-avatar-initial');
          if (hImg) { hImg.src = res.profile_image; hImg.classList.remove('d-none'); }
          if (hInit) hInit.classList.add('d-none');

          // Dropdown Avatar
          const dImg = document.getElementById('dropdown-avatar-img');
          const dInit = document.getElementById('dropdown-avatar-initial');
          if (dImg) { dImg.src = res.profile_image; dImg.classList.remove('d-none'); }
          if (dInit) dInit.classList.add('d-none');
        }

        // Clear password fields
        document.getElementById('input-password').value = '';
        document.getElementById('input-confirm-password').value = '';
        imageInput.value = '';

      } else {
        swalAlert('Error', res.message || 'Failed to update profile.', 'error');
      }
    } catch (err) {
      console.error(err);
      swalAlert('Error', 'An unexpected error occurred while saving profile.', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
    }
  });
</script>