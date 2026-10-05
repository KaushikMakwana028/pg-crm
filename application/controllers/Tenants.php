<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tenants extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    private function _sync_room_occupancy($room_id)
    {
        if (empty($room_id)) return;
        $room = $this->General_model->getOne('rooms', ['id' => $room_id]);
        if (!$room || $room->status === 'Maintenance') return;

        $count = $this->General_model->getCount('users', [
            'room_id' => $room_id,
            'role'    => 0,
            'status'  => 1
        ]);

        $new_status = ($count >= intval($room->capacity)) ? 'Occupied' : 'Available';
        $this->General_model->update('rooms', ['id' => $room_id], ['status' => $new_status]);
    }

    /**
     * Ensure all rent invoices from start month up to current month (or vacate month) exist
     */
    private function _sync_tenant_rent_bills($tenant, $preferred_cycle = null)
    {
        if (empty($tenant) || floatval($tenant->rent_amount ?? 0) <= 0) {
            return;
        }

        $check_in_ts = !empty($tenant->check_in) ? strtotime($tenant->check_in) : time();

        // 1. Check if tenant already has recorded rent bills in DB
        $this->db->select_min('year', 'min_year');
        $this->db->where('user_id', $tenant->id);
        $min_yr_row = $this->db->get('rents')->row();

        if (!empty($min_yr_row) && !empty($min_yr_row->min_year)) {
            $start_year = intval($min_yr_row->min_year);
            $this->db->select_min('month', 'min_month');
            $this->db->where('user_id', $tenant->id);
            $this->db->where('year', $start_year);
            $min_m_row = $this->db->get('rents')->row();
            $start_month = intval($min_m_row->min_month);
        } else {
            if ($preferred_cycle === 'next') {
                $start_month = intval(date('n', strtotime('+1 month', $check_in_ts)));
                $start_year  = intval(date('Y', strtotime('+1 month', $check_in_ts)));
            } else {
                $start_month = intval(date('n', $check_in_ts));
                $start_year  = intval(date('Y', $check_in_ts));
            }
        }

        // 2. Determine end year & month
        // For active occupants: till the current month and year
        // For vacated occupants: till the vacate month/year (or current month if earlier)
        $cur_month = intval(date('n'));
        $cur_year  = intval(date('Y'));

        if (!empty($tenant->vacate_date) && ($tenant->status == 0)) {
            $vacate_ts = strtotime($tenant->vacate_date);
            $v_m = intval(date('n', $vacate_ts));
            $v_y = intval(date('Y', $vacate_ts));
            if (($v_y < $cur_year) || ($v_y === $cur_year && $v_m < $cur_month)) {
                $end_month = $v_m;
                $end_year  = $v_y;
            } else {
                $end_month = $cur_month;
                $end_year  = $cur_year;
            }
        } else {
            $end_month = $cur_month;
            $end_year  = $cur_year;
        }

        // If start is in future, cap at start
        if (($start_year > $end_year) || ($start_year === $end_year && $start_month > $end_month)) {
            $end_month = $start_month;
            $end_year  = $start_year;
        }

        $due_day = $this->settings ? intval($this->settings->rent_due_date) : 10;

        $y = $start_year;
        $m = $start_month;

        while (($y < $end_year) || ($y === $end_year && $m <= $end_month)) {
            $exists = $this->General_model->getOne('rents', [
                'user_id' => $tenant->id,
                'month'   => $m,
                'year'    => $y
            ]);

            if (!$exists) {
                $max_day = cal_days_in_month(CAL_GREGORIAN, $m, $y);
                $due_date = sprintf('%04d-%02d-%02d', $y, $m, min($due_day, $max_day));

                $this->General_model->insert('rents', [
                    'user_id'  => $tenant->id,
                    'hotel_id' => $tenant->hotel_id,
                    'room_id'  => $tenant->room_id,
                    'month'    => $m,
                    'year'     => $y,
                    'due_date' => $due_date,
                    'amount'   => $tenant->rent_amount,
                    'status'   => 'Unpaid'
                ]);
            }

            $m++;
            if ($m > 12) {
                $m = 1;
                $y++;
            }
        }
    }

    /**
     * AJAX list for active tenants with pagination and filters
     */
    public function list_ajax()
    {
        $this->output->set_content_type('application/json');

        $page        = max(1, intval($this->input->get('page', TRUE) ?: 1));
        $per_page    = max(1, min(100, intval($this->input->get('per_page', TRUE) ?: 10)));
        $offset      = ($page - 1) * $per_page;
        $q           = trim((string)$this->input->get('q', TRUE));
        $hotel_id    = $this->input->get('hotel_id', TRUE);
        $rent_status = $this->input->get('rent_status', TRUE);

        $cur_month = intval(date('n'));
        $cur_year  = intval(date('Y'));

        // Query setup
        $this->db->from('users u');
        $this->db->join("rents cur_rent", "cur_rent.user_id = u.id AND cur_rent.month = {$cur_month} AND cur_rent.year = {$cur_year}", 'left');
        $this->db->where('u.role', 0);
        $this->db->where('u.status', 1);

        if (!empty($q)) {
            $this->db->group_start();
            $this->db->like('u.name', $q);
            $this->db->or_like('u.phone', $q);
            $this->db->or_like('u.email', $q);
            $this->db->group_end();
        }

        if (!empty($hotel_id)) {
            $this->db->where('u.hotel_id', intval($hotel_id));
        }

        if ($rent_status === 'Paid') {
            $this->db->where('(cur_rent.status = "Paid" OR cur_rent.id IS NULL)', NULL, FALSE);
        } elseif ($rent_status === 'Unpaid') {
            $this->db->where('cur_rent.status', 'Unpaid');
        }

        $total = $this->db->count_all_results('', FALSE);

        $this->db->select('u.*, h.name as hotel_name, r.number as room_number, r.capacity as room_capacity, COALESCE(cur_rent.status, "Paid") as rent_status');
        $this->db->join('hotels h', 'h.id = u.hotel_id', 'left');
        $this->db->join('rooms r', 'r.id = u.room_id', 'left');
        $this->db->order_by('u.id', 'DESC');
        $this->db->limit($per_page, $offset);
        $tenants = $this->db->get()->result();

        $total_pages = ceil($total / $per_page) ?: 1;

        $this->output->set_output(json_encode([
            'success'    => TRUE,
            'data'       => $tenants,
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => $total_pages
            ]
        ]));
    }

    /**
     * AJAX list for vacated tenants with pagination and filters
     */
    public function vacated_ajax()
    {
        $this->output->set_content_type('application/json');

        $page     = max(1, intval($this->input->get('page', TRUE) ?: 1));
        $per_page = max(1, min(100, intval($this->input->get('per_page', TRUE) ?: 10)));
        $offset   = ($page - 1) * $per_page;
        $q        = trim((string)$this->input->get('q', TRUE));
        $hotel_id = $this->input->get('hotel_id', TRUE);

        $this->db->from('users u');
        $this->db->where('u.role', 0);
        $this->db->group_start();
        $this->db->where('u.status', 0);
        $this->db->or_where('u.vacate_date IS NOT NULL', NULL, FALSE);
        $this->db->group_end();

        if (!empty($q)) {
            $this->db->group_start();
            $this->db->like('u.name', $q);
            $this->db->or_like('u.phone', $q);
            $this->db->or_like('u.email', $q);
            $this->db->group_end();
        }

        if (!empty($hotel_id)) {
            $this->db->where('u.hotel_id', intval($hotel_id));
        }

        $total = $this->db->count_all_results('', FALSE);

        $this->db->select('u.*, h.name as hotel_name, r.number as room_number');
        $this->db->join('hotels h', 'h.id = u.hotel_id', 'left');
        $this->db->join('rooms r', 'r.id = u.room_id', 'left');
        $this->db->order_by('u.vacate_date', 'DESC');
        $this->db->order_by('u.id', 'DESC');
        $this->db->limit($per_page, $offset);
        $vacated = $this->db->get()->result();

        $total_pages = ceil($total / $per_page) ?: 1;

        $this->output->set_output(json_encode([
            'success'    => TRUE,
            'data'       => $vacated,
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => $total_pages
            ]
        ]));
    }

    /**
     * View active tenants list
     */
    public function index()
    {
        $q           = trim($this->input->get('q', TRUE) ?? '');
        $hotel_id    = $this->input->get('hotel_id', TRUE);
        $room_id     = $this->input->get('room_id', TRUE);
        $rent_status = $this->input->get('rent_status', TRUE);

        $this->db->select('u.*, h.name as hotel_name, r.number as room_number, r.capacity as room_capacity');
        $this->db->from('users u');
        $this->db->join('hotels h', 'h.id = u.hotel_id', 'left');
        $this->db->join('rooms r', 'r.id = u.room_id', 'left');
        $this->db->where('u.role', 0);
        $this->db->where('u.status', 1);

        if (!empty($q)) {
            $this->db->group_start();
            $this->db->like('u.name', $q);
            $this->db->or_like('u.phone', $q);
            $this->db->or_like('u.email', $q);
            $this->db->group_end();
        }

        if (!empty($hotel_id)) {
            $this->db->where('u.hotel_id', $hotel_id);
        }

        if (!empty($room_id)) {
            $this->db->where('u.room_id', $room_id);
        }

        $this->db->order_by('u.id', 'DESC');
        $tenants_raw = $this->db->get()->result();

        $cur_month = intval(date('n'));
        $cur_year  = intval(date('Y'));

        $tenants = [];
        foreach ($tenants_raw as $t) {
            $rent = $this->General_model->getOne('rents', [
                'user_id' => $t->id,
                'month'   => $cur_month,
                'year'    => $cur_year
            ]);

            $st = 'Paid';
            if ($rent) {
                $st = $rent->status;
            } else {
                $unpaid = $this->General_model->getOne('rents', [
                    'user_id' => $t->id,
                    'status'  => 'Unpaid'
                ]);
                $st = $unpaid ? 'Unpaid' : 'Paid';
            }

            if (!empty($rent_status) && $st !== $rent_status) {
                continue;
            }

            $t->rent_status = $st;
            $tenants[] = $t;
        }

        $hotels = $this->General_model->getAll('hotels');

        $data = [
            'user'        => $this->current_user,
            'settings'    => $this->settings,
            'active_page' => 'tenants',
            'page_title'  => 'Tenants - ' . ($this->settings->crm_name ?? 'StayFlow CRM'),
            'tenants'     => $tenants,
            'hotels'      => $hotels,
            'filters'     => [
                'q'           => $q,
                'hotel_id'    => $hotel_id,
                'room_id'     => $room_id,
                'rent_status' => $rent_status
            ]
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('tenants_view', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * View single tenant detail
     */
    public function detail($id = NULL)
    {
        $id = intval($id);
        if (!$id) {
            redirect(base_url('tenants'));
        }

        $this->db->select('u.*, h.name as hotel_name, r.number as room_number');
        $this->db->from('users u');
        $this->db->join('hotels h', 'h.id = u.hotel_id', 'left');
        $this->db->join('rooms r', 'r.id = u.room_id', 'left');
        $this->db->where('u.id', $id);
        $this->db->where('u.role', 0);
        $tenant = $this->db->get()->row();

        if (!$tenant) {
            redirect(base_url('tenants'));
        }

        $this->_sync_tenant_rent_bills($tenant);

        $this->db->where('user_id', $id);
        $this->db->order_by('year', 'DESC');
        $this->db->order_by('month', 'DESC');
        $rents = $this->db->get('rents')->result();

        $total_paid = 0;
        $total_pending = 0;
        foreach ($rents as $r) {
            if ($r->status === 'Paid') {
                $total_paid += floatval($r->amount);
            } else {
                $total_pending += floatval($r->amount);
            }
        }

        $hotels = $this->General_model->getAll('hotels');

        $data = [
            'user'          => $this->current_user,
            'settings'      => $this->settings,
            'active_page'   => 'tenants',
            'page_title'    => htmlspecialchars($tenant->name) . ' - Tenant Details',
            'tenant'        => $tenant,
            'rents'         => $rents,
            'total_paid'    => $total_paid,
            'total_pending' => $total_pending,
            'hotels'        => $hotels
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('tenant_detail_view', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * View vacated tenant history
     */
    public function vacated()
    {
        $this->db->select('u.*, h.name as hotel_name, r.number as room_number');
        $this->db->from('users u');
        $this->db->join('hotels h', 'h.id = u.hotel_id', 'left');
        $this->db->join('rooms r', 'r.id = u.room_id', 'left');
        $this->db->where('u.role', 0);
        $this->db->group_start();
        $this->db->where('u.status', 0);
        $this->db->or_where('u.vacate_date IS NOT NULL', NULL, FALSE);
        $this->db->group_end();
        $this->db->order_by('u.vacate_date', 'DESC');
        $tenants = $this->db->get()->result();

        $data = [
            'user'        => $this->current_user,
            'settings'    => $this->settings,
            'active_page' => 'vacated',
            'page_title'  => 'Vacated Tenant History - ' . ($this->settings->crm_name ?? 'StayFlow CRM'),
            'tenants'     => $tenants
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('vacated_view', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * Save tenant (Add / Edit) with uploads
     */
    public function save()
    {
        $this->output->set_content_type('application/json');

        $id   = $this->input->post('id', TRUE);
        $name = trim($this->input->post('name', TRUE));

        if (empty($name)) {
            $this->output->set_output(json_encode([
                'success' => FALSE,
                'message' => 'Tenant name is required.'
            ]));
            return;
        }

        $hotel_id = $this->input->post('hotel_id', TRUE);
        $room_id  = $this->input->post('room_id', TRUE);

        $tenant_data = [
            'name'            => $name,
            'phone'           => trim($this->input->post('phone', TRUE) ?? ''),
            'email'           => trim($this->input->post('email', TRUE) ?? ''),
            'dob'             => $this->input->post('dob', TRUE) ?: null,
            'emergency_name'  => trim($this->input->post('emergency_name', TRUE) ?? ''),
            'emergency_phone' => trim($this->input->post('emergency_phone', TRUE) ?? ''),
            'aadhar'          => trim($this->input->post('aadhar', TRUE) ?? ''),
            'pan'             => trim($this->input->post('pan', TRUE) ?? ''),
            'occupation'      => $this->input->post('occupation', TRUE) ?: 'Student',
            'company'         => trim($this->input->post('company', TRUE) ?? ''),
            'address'         => trim($this->input->post('address', TRUE) ?? ''),
            'rent_amount'     => floatval($this->input->post('rent_amount', TRUE) ?: 0),
            'deposit'         => floatval($this->input->post('deposit', TRUE) ?: 0),
            'deposit_status'  => $this->input->post('deposit_status', TRUE) ?: 'Pending',
            'check_in'        => $this->input->post('check_in', TRUE) ?: date('Y-m-d'),
            'hotel_id'        => !empty($hotel_id) ? intval($hotel_id) : null,
            'room_id'         => !empty($room_id) ? intval($room_id) : null,
            'notes'           => trim($this->input->post('notes', TRUE) ?? ''),
            'role'            => 0, // 0 = Tenant/User
            'status'          => 1  // 1 = Active
        ];

        // Validate target room capacity if room is assigned
        if (!empty($tenant_data['room_id'])) {
            $target_room = $this->General_model->getOne('rooms', ['id' => $tenant_data['room_id']]);
            if ($target_room) {
                $cur_occ = $this->General_model->getCount('users', [
                    'room_id' => $tenant_data['room_id'],
                    'role'    => 0,
                    'status'  => 1
                ]);

                // If editing existing active tenant who is currently in this room, discount them
                if (!empty($id)) {
                    $existing = $this->General_model->getOne('users', ['id' => intval($id), 'role' => 0]);
                    if ($existing && intval($existing->room_id) === intval($tenant_data['room_id']) && intval($existing->status) === 1) {
                        $cur_occ--;
                    }
                }

                if ($cur_occ >= intval($target_room->capacity)) {
                    $this->output->set_output(json_encode([
                        'success' => FALSE,
                        'message' => "Room {$target_room->number} is already full! (Capacity: {$target_room->capacity}, Occupied: {$cur_occ}). Please choose another room."
                    ]));
                    return;
                }
            }
        }

        if (!is_dir('./uploads/profiles/')) mkdir('./uploads/profiles/', 0777, true);
        if (!is_dir('./uploads/aadhar/'))   mkdir('./uploads/aadhar/', 0777, true);
        if (!is_dir('./uploads/pan/'))      mkdir('./uploads/pan/', 0777, true);
        if (!is_dir('./uploads/police/'))   mkdir('./uploads/police/', 0777, true);

        // Enforce 2MB size limit on all uploads
        foreach (['photo', 'aadhar_file', 'pan_file', 'police_verification_file'] as $field) {
            if (!empty($_FILES[$field]['name']) && $_FILES[$field]['size'] > 2097152) {
                $field_label = ($field === 'photo') ? 'Profile photo' : (($field === 'aadhar_file') ? 'Aadhar document' : (($field === 'pan_file') ? 'PAN document' : 'Police verification document'));
                $this->output->set_output(json_encode([
                    'success' => FALSE,
                    'message' => $field_label . ' size cannot exceed 2MB.'
                ]));
                return;
            }
        }

        // Upload Profile Photo (Max 2MB)
        if (!empty($_FILES['photo']['name'])) {
            $cfg['upload_path']   = './uploads/profiles/';
            $cfg['allowed_types'] = 'gif|jpg|jpeg|png|webp';
            $cfg['max_size']      = 2048; // 2MB
            $cfg['encrypt_name']  = TRUE;
            $this->load->library('upload', $cfg, 'photo_upload');
            if ($this->photo_upload->do_upload('photo')) {
                $up = $this->photo_upload->data();
                $tenant_data['profile_image'] = 'uploads/profiles/' . $up['file_name'];
            }
        }

        // Upload Aadhar (Max 2MB)
        if (!empty($_FILES['aadhar_file']['name'])) {
            $cfg2['upload_path']   = './uploads/aadhar/';
            $cfg2['allowed_types'] = 'gif|jpg|jpeg|png|pdf|webp';
            $cfg2['max_size']      = 2048; // 2MB
            $cfg2['encrypt_name']  = TRUE;
            $this->load->library('upload', $cfg2, 'aadhar_upload');
            if ($this->aadhar_upload->do_upload('aadhar_file')) {
                $up = $this->aadhar_upload->data();
                $tenant_data['aadhar_file'] = 'uploads/aadhar/' . $up['file_name'];
            }
        }

        // Upload PAN (Max 2MB)
        if (!empty($_FILES['pan_file']['name'])) {
            $cfg3['upload_path']   = './uploads/pan/';
            $cfg3['allowed_types'] = 'gif|jpg|jpeg|png|pdf|webp';
            $cfg3['max_size']      = 2048; // 2MB
            $cfg3['encrypt_name']  = TRUE;
            $this->load->library('upload', $cfg3, 'pan_upload');
            if ($this->pan_upload->do_upload('pan_file')) {
                $up = $this->pan_upload->data();
                $tenant_data['pan_file'] = 'uploads/pan/' . $up['file_name'];
            }
        }

        // Upload Police Verification (Optional, Max 2MB)
        if (!empty($_FILES['police_verification_file']['name'])) {
            $cfg4['upload_path']   = './uploads/police/';
            $cfg4['allowed_types'] = 'gif|jpg|jpeg|png|pdf|webp';
            $cfg4['max_size']      = 2048; // 2MB
            $cfg4['encrypt_name']  = TRUE;
            $this->load->library('upload', $cfg4, 'police_upload');
            if ($this->police_upload->do_upload('police_verification_file')) {
                $up = $this->police_upload->data();
                $tenant_data['police_verification_file'] = 'uploads/police/' . $up['file_name'];
            }
        }

        $old_room_id = null;
        if (!empty($id)) {
            $existing = $this->General_model->getOne('users', ['id' => intval($id), 'role' => 0]);
            if ($existing) {
                $old_room_id = $existing->room_id;
            }
            $this->General_model->update('users', ['id' => intval($id)], $tenant_data);
            $tenant_id = intval($id);
            $msg = 'Tenant updated successfully.';
        } else {
            $tenant_id = $this->General_model->insert('users', $tenant_data);
            $msg = 'Tenant added successfully.';

            // Auto-generate all rent invoices from start month up to current month
            if (floatval($tenant_data['rent_amount']) > 0) {
                $tenant_obj = (object) array_merge($tenant_data, ['id' => $tenant_id]);
                $rent_cycle = $this->input->post('rent_cycle', TRUE) ?: 'next';
                $this->_sync_tenant_rent_bills($tenant_obj, $rent_cycle);
            }

            // If deposit was marked Paid at creation, record in incomes
            if ($tenant_data['deposit_status'] === 'Paid' && floatval($tenant_data['deposit']) > 0) {
                $this->General_model->insert('incomes', [
                    'hotel_id'      => $tenant_data['hotel_id'],
                    'date'          => $tenant_data['check_in'] ?: date('Y-m-d'),
                    'category'      => 'Security Deposit',
                    'amount'        => $tenant_data['deposit'],
                    'received_from' => $tenant_data['name'],
                    'description'   => 'Security Deposit received from ' . $tenant_data['name'],
                    'payment_mode'  => 'Cash'
                ]);
            }
        }

        if ($old_room_id && $old_room_id != $tenant_data['room_id']) {
            $this->_sync_room_occupancy($old_room_id);
        }
        if (!empty($tenant_data['room_id'])) {
            $this->_sync_room_occupancy($tenant_data['room_id']);
        }

        $this->output->set_output(json_encode([
            'success'   => TRUE,
            'message'   => $msg,
            'tenant_id' => $tenant_id
        ]));
    }

    /**
     * Mark security deposit as paid and record income
     */
    public function pay_deposit()
    {
        $this->output->set_content_type('application/json');

        $tenant_id    = intval($this->input->post('tenant_id', TRUE));
        $tenant       = $this->General_model->getOne('users', ['id' => $tenant_id, 'role' => 0]);
        if (!$tenant) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Tenant not found.']));
            return;
        }

        $paid_date    = $this->input->post('paid_date', TRUE) ?: date('Y-m-d');
        $payment_mode = $this->input->post('payment_mode', TRUE) ?: 'Cash';
        $reference    = trim($this->input->post('reference', TRUE) ?? '');
        $remarks      = trim($this->input->post('remarks', TRUE) ?? '');
        $amount       = floatval($this->input->post('amount', TRUE) ?: $tenant->deposit);

        $this->General_model->update('users', ['id' => $tenant_id], [
            'deposit_status' => 'Paid'
        ]);

        $this->General_model->insert('incomes', [
            'hotel_id'      => $tenant->hotel_id,
            'date'          => $paid_date,
            'category'      => 'Security Deposit',
            'amount'        => $amount,
            'received_from' => $tenant->name,
            'description'   => "Security Deposit from {$tenant->name}" . ($reference ? " (Ref: {$reference})" : "") . ($remarks ? " - {$remarks}" : ""),
            'payment_mode'  => $payment_mode
        ]);

        $this->output->set_output(json_encode([
            'success' => TRUE,
            'message' => 'Security deposit marked as Paid successfully.'
        ]));
    }

    /**
     * Return / Refund security deposit
     */
    public function refund_deposit()
    {
        $this->output->set_content_type('application/json');

        $tenant_id     = intval($this->input->post('tenant_id', TRUE));
        $tenant        = $this->General_model->getOne('users', ['id' => $tenant_id, 'role' => 0]);
        if (!$tenant) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Tenant not found.']));
            return;
        }

        $refund_date   = $this->input->post('refund_date', TRUE) ?: date('Y-m-d');
        $refund_amount = floatval($this->input->post('refund_amount', TRUE) ?: $tenant->deposit);
        $payment_mode  = $this->input->post('payment_mode', TRUE) ?: 'Cash';
        $reference     = trim($this->input->post('reference', TRUE) ?? '');
        $remarks       = trim($this->input->post('remarks', TRUE) ?? '');

        $this->General_model->update('users', ['id' => $tenant_id], [
            'deposit_status'        => 'Refunded',
            'deposit_refund_date'   => $refund_date,
            'deposit_refund_amount' => $refund_amount
        ]);

        // Record as expense transaction
        $this->General_model->insert('expenses', [
            'hotel_id'     => $tenant->hotel_id,
            'date'         => $refund_date,
            'category'     => 'Security Deposit Refund',
            'amount'       => $refund_amount,
            'paid_to'      => $tenant->name,
            'payment_mode' => $payment_mode,
            'description'  => "Security Deposit returned to {$tenant->name}" . ($reference ? " (Ref: {$reference})" : "") . ($remarks ? " - {$remarks}" : "")
        ]);

        $this->output->set_output(json_encode([
            'success' => TRUE,
            'message' => 'Security deposit of ' . ($this->settings->currency ?? '₹') . number_format($refund_amount, 2) . ' returned successfully.'
        ]));
    }

    /**
     * Mark tenant as vacated (and optionally return security deposit)
     */
    public function vacate()
    {
        $this->output->set_content_type('application/json');

        $tenant_id      = intval($this->input->post('tenant_id', TRUE));
        $vacate_date    = $this->input->post('vacate_date', TRUE) ?: date('Y-m-d');
        $return_deposit = intval($this->input->post('return_deposit', TRUE) ?: 0);
        $refund_amount  = floatval($this->input->post('refund_amount', TRUE) ?: 0);
        $payment_mode   = $this->input->post('payment_mode', TRUE) ?: 'Cash';
        $remarks        = trim($this->input->post('remarks', TRUE) ?? '');

        $tenant = $this->General_model->getOne('users', ['id' => $tenant_id, 'role' => 0]);
        if (!$tenant) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Tenant not found.']));
            return;
        }

        $update_data = [
            'status'      => 0,
            'vacate_date' => $vacate_date
        ];

        // If returning deposit immediately upon vacate
        if ($return_deposit && $tenant->deposit_status === 'Paid') {
            $actual_refund = ($refund_amount > 0) ? $refund_amount : floatval($tenant->deposit);
            $update_data['deposit_status']        = 'Refunded';
            $update_data['deposit_refund_date']   = $vacate_date;
            $update_data['deposit_refund_amount'] = $actual_refund;

            $this->General_model->insert('expenses', [
                'hotel_id'     => $tenant->hotel_id,
                'date'         => $vacate_date,
                'category'     => 'Security Deposit Refund',
                'amount'       => $actual_refund,
                'paid_to'      => $tenant->name,
                'payment_mode' => $payment_mode,
                'description'  => "Security Deposit returned to {$tenant->name} upon checkout" . ($remarks ? " - {$remarks}" : "")
            ]);
        }

        $this->General_model->update('users', ['id' => $tenant_id], $update_data);

        if (!empty($tenant->room_id)) {
            $this->_sync_room_occupancy($tenant->room_id);
        }

        $this->output->set_output(json_encode([
            'success' => TRUE,
            'message' => 'Tenant marked as vacated successfully.' . ($return_deposit ? ' Security deposit returned.' : '')
        ]));
    }

    /**
     * Transfer room
     */
    public function transfer()
    {
        $this->output->set_content_type('application/json');

        $tenant_id    = intval($this->input->post('tenant_id', TRUE));
        $new_hotel_id = intval($this->input->post('hotel_id', TRUE));
        $new_room_id  = intval($this->input->post('room_id', TRUE));

        $tenant = $this->General_model->getOne('users', ['id' => $tenant_id, 'role' => 0]);
        if (!$tenant) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Tenant not found.']));
            return;
        }

        $new_room = $this->General_model->getOne('rooms', ['id' => $new_room_id]);
        if (!$new_room) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Target room not found.']));
            return;
        }

        $cur_occ = $this->General_model->getCount('users', [
            'room_id' => $new_room_id,
            'role'    => 0,
            'status'  => 1
        ]);

        if ($cur_occ >= intval($new_room->capacity) && $tenant->room_id != $new_room_id) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Target room is already full!']));
            return;
        }

        $old_room = $tenant->room_id ? $this->General_model->getOne('rooms', ['id' => $tenant->room_id]) : null;
        $old_num  = $old_room ? $old_room->number : 'None';

        $note_entry = "\nTransferred from " . $old_num . " to " . $new_room->number . " on " . date('Y-m-d');
        $updated_notes = trim(($tenant->notes ?? '') . $note_entry);

        $this->General_model->update('users', ['id' => $tenant_id], [
            'hotel_id' => $new_hotel_id,
            'room_id'  => $new_room_id,
            'notes'    => $updated_notes
        ]);

        if ($tenant->room_id) {
            $this->_sync_room_occupancy($tenant->room_id);
        }
        $this->_sync_room_occupancy($new_room_id);

        $this->output->set_output(json_encode([
            'success' => TRUE,
            'message' => 'Tenant transferred successfully.'
        ]));
    }

    /**
     * Delete tenant
     */
    public function delete($id = NULL)
    {
        $this->output->set_content_type('application/json');

        $id = intval($id);
        $tenant = $this->General_model->getOne('users', ['id' => $id, 'role' => 0]);
        if (!$tenant) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Tenant not found.']));
            return;
        }

        $room_id = $tenant->room_id;
        $this->General_model->delete('users', ['id' => $id, 'role' => 0]);

        if ($room_id) {
            $this->_sync_room_occupancy($room_id);
        }

        $this->output->set_output(json_encode([
            'success' => TRUE,
            'message' => 'Tenant deleted successfully.'
        ]));
    }

    /**
     * JSON list for search/API
     */
    public function list()
    {
        $this->output->set_content_type('application/json');
        $q = trim($this->input->get('q', TRUE) ?? '');

        $this->db->select('u.id, u.name, u.phone, u.email, h.name as hotel_name, r.number as room_number');
        $this->db->from('users u');
        $this->db->join('hotels h', 'h.id = u.hotel_id', 'left');
        $this->db->join('rooms r', 'r.id = u.room_id', 'left');
        $this->db->where('u.role', 0);
        $this->db->where('u.status', 1);

        if (!empty($q)) {
            $this->db->group_start();
            $this->db->like('u.name', $q);
            $this->db->or_like('u.phone', $q);
            $this->db->group_end();
        }

        $res = $this->db->get()->result();
        $this->output->set_output(json_encode(['success' => TRUE, 'data' => $res]));
    }
}
