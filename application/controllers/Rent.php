<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rent extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $selected_my = $this->input->get('month_year', TRUE) ?: date('Y-n');
        $parts = explode('-', $selected_my);
        $year  = intval($parts[0] ?? date('Y'));
        $month = intval($parts[1] ?? date('n'));

        $this->db->select('r.*, u.name as tenant_name, u.phone as tenant_phone, h.name as hotel_name, rm.number as room_number');
        $this->db->from('rents r');
        $this->db->join('users u', 'u.id = r.user_id', 'left');
        $this->db->join('hotels h', 'h.id = u.hotel_id', 'left');
        $this->db->join('rooms rm', 'rm.id = u.room_id', 'left');

        if ($selected_my !== 'all') {
            $parts = explode('-', $selected_my);
            $year  = intval($parts[0] ?? date('Y'));
            $month = intval($parts[1] ?? date('n'));
            $this->db->where('r.month', $month);
            $this->db->where('r.year', $year);
        }
        $this->db->order_by('r.year', 'DESC');
        $this->db->order_by('r.month', 'DESC');
        $this->db->order_by('r.id', 'DESC');
        $all_rents = $this->db->get()->result();

        $unpaid_rents = [];
        $paid_rents   = [];
        $expected_amt = 0;
        $collected_amt = 0;

        foreach ($all_rents as $r) {
            $expected_amt += floatval($r->amount);
            if ($r->status === 'Paid') {
                $collected_amt += floatval($r->amount);
                $paid_rents[] = $r;
            } else {
                $unpaid_rents[] = $r;
            }
        }
        $collection_rate = $expected_amt ? round(($collected_amt / $expected_amt) * 100) : 0;

        $data = [
            'user'                => $this->current_user,
            'settings'            => $this->settings,
            'active_page'         => 'rent',
            'page_title'          => 'Rent Collection - ' . ($this->settings->crm_name ?? 'StayFlow CRM'),
            'selected_month_year' => $selected_my,
            'expected_amt'        => $expected_amt,
            'collected_amt'       => $collected_amt,
            'collection_rate'     => $collection_rate,
            'unpaid_rents'        => $unpaid_rents,
            'paid_rents'          => $paid_rents,
            'active_tab'          => $this->input->get('tab', TRUE) ?: 'unpaid'
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('rent_view', $data);
        $this->load->view('layout/footer', $data);
    }

    public function list()
    {
        $this->output->set_content_type('application/json');

        $status = $this->input->get('status', TRUE);
        $month  = $this->input->get('month', TRUE);
        $year   = $this->input->get('year', TRUE);

        $this->db->select('r.*, u.name as tenant_name, u.phone as tenant_phone, h.name as hotel_name, rm.number as room_number');
        $this->db->from('rents r');
        $this->db->join('users u', 'u.id = r.user_id', 'left');
        $this->db->join('hotels h', 'h.id = u.hotel_id', 'left');
        $this->db->join('rooms rm', 'rm.id = u.room_id', 'left');

        if (!empty($status)) {
            $this->db->where('r.status', $status);
        }
        if (!empty($month)) {
            $this->db->where('r.month', intval($month));
        }
        if (!empty($year)) {
            $this->db->where('r.year', intval($year));
        }

        $this->db->order_by('r.year', 'DESC');
        $this->db->order_by('r.month', 'DESC');
        $rents = $this->db->get()->result();

        $this->output->set_output(json_encode(['success' => TRUE, 'data' => $rents]));
    }

    public function list_ajax()
    {
        $this->output->set_content_type('application/json');

        $page     = max(1, intval($this->input->get('page', TRUE) ?: 1));
        $per_page = max(1, min(100, intval($this->input->get('per_page', TRUE) ?: 10)));
        $offset   = ($page - 1) * $per_page;
        $status   = $this->input->get('status', TRUE);
        $my       = $this->input->get('month_year', TRUE) ?: date('Y-n');
        $q        = trim((string)$this->input->get('q', TRUE));

        $parts = explode('-', $my);
        $year  = intval($parts[0] ?? date('Y'));
        $month = intval($parts[1] ?? date('n'));

        // Query setup
        $this->db->from('rents r');
        $this->db->join('users u', 'u.id = r.user_id', 'left');
        $this->db->join('hotels h', 'h.id = u.hotel_id', 'left');
        $this->db->join('rooms rm', 'rm.id = u.room_id', 'left');

        if ($my !== 'all') {
            $parts = explode('-', $my);
            $year  = intval($parts[0] ?? date('Y'));
            $month = intval($parts[1] ?? date('n'));
            $this->db->where('r.month', $month);
            $this->db->where('r.year', $year);
        }

        if (!empty($status) && strtolower($status) !== 'all') {
            $this->db->where('r.status', $status);
        }
        if (!empty($q)) {
            $this->db->group_start();
            $this->db->like('u.name', $q);
            $this->db->or_like('u.phone', $q);
            $this->db->or_like('rm.number', $q);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results('', FALSE);

        $this->db->select('r.*, u.name as tenant_name, u.phone as tenant_phone, h.name as hotel_name, rm.number as room_number');
        $this->db->order_by('r.year', 'DESC');
        $this->db->order_by('r.month', 'DESC');
        $this->db->order_by('r.id', 'DESC');
        $this->db->limit($per_page, $offset);
        $rents = $this->db->get()->result();

        // Calculate summary
        $this->db->select("
            COALESCE(SUM(amount), 0) as expected,
            COALESCE(SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END), 0) as collected
        ");
        $this->db->from('rents');
        if ($my !== 'all') {
            $parts = explode('-', $my);
            $year  = intval($parts[0] ?? date('Y'));
            $month = intval($parts[1] ?? date('n'));
            $this->db->where('month', $month);
            $this->db->where('year', $year);
        }
        $summary = $this->db->get()->row();

        $expected  = floatval($summary->expected ?? 0);
        $collected = floatval($summary->collected ?? 0);
        $pending   = max(0, $expected - $collected);
        $rate      = $expected > 0 ? round(($collected / $expected) * 100) : 0;

        $total_pages = ceil($total / $per_page) ?: 1;

        $this->output->set_output(json_encode([
            'success'    => TRUE,
            'data'       => $rents,
            'summary'    => [
                'expected'   => $expected,
                'collected'  => $collected,
                'pending'    => $pending,
                'rate'       => $rate
            ],
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => $total_pages
            ]
        ]));
    }

    public function pay()
    {
        $this->output->set_content_type('application/json');

        $rent_id = intval($this->input->post('rent_id', TRUE));
        $rent = $this->General_model->getOne('rents', ['id' => $rent_id]);

        if (!$rent) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Rent record not found.']));
            return;
        }

        $paid_date    = $this->input->post('paid_date', TRUE) ?: date('Y-m-d');
        $payment_mode = $this->input->post('payment_mode', TRUE) ?: 'Cash';
        $reference    = trim($this->input->post('reference', TRUE) ?? '');
        $remarks      = trim($this->input->post('remarks', TRUE) ?? '');

        $update_data = [
            'status'       => 'Paid',
            'paid_date'    => $paid_date,
            'payment_mode' => $payment_mode,
            'reference'    => $reference,
            'remarks'      => $remarks
        ];

        // Process uploaded receipt slip if payment mode is not Cash
        if ($payment_mode !== 'Cash' && !empty($_FILES['receipt_file']['name'])) {
            $cfg_rcpt['upload_path']   = './uploads/rents/';
            $cfg_rcpt['allowed_types'] = 'gif|jpg|jpeg|png|pdf|webp';
            $cfg_rcpt['max_size']      = 5120; // 5MB
            $cfg_rcpt['encrypt_name']  = TRUE;
            $this->load->library('upload', $cfg_rcpt, 'receipt_upload');
            if ($this->receipt_upload->do_upload('receipt_file')) {
                $up = $this->receipt_upload->data();
                $update_data['receipt_file'] = 'uploads/rents/' . $up['file_name'];
            }
        }

        $this->General_model->update('rents', ['id' => $rent_id], $update_data);

        $tenant = $this->General_model->getOne('users', ['id' => $rent->user_id]);
        $tenant_name = $tenant ? $tenant->name : 'Tenant';
        $hotel_id = $tenant ? $tenant->hotel_id : null;

        $month_names = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
        $m_text = $month_names[$rent->month] ?? $rent->month;

        $this->General_model->insert('incomes', [
            'hotel_id'      => $hotel_id,
            'date'          => $paid_date,
            'category'      => 'Rent',
            'amount'        => $rent->amount,
            'description'   => "Rent for {$m_text} {$rent->year} - {$tenant_name}",
            'received_from' => $tenant_name,
            'payment_mode'  => $payment_mode
        ]);

        $this->output->set_output(json_encode(['success' => TRUE, 'message' => 'Payment recorded successfully.']));
    }

    public function generate()
    {
        $this->output->set_content_type('application/json');

        $month = intval($this->input->post('month', TRUE) ?: date('n'));
        $year  = intval($this->input->post('year', TRUE) ?: date('Y'));

        $setting = $this->settings;
        $due_day = $setting ? intval($setting->rent_due_date) : 10;
        $due_date = sprintf('%04d-%02d-%02d', $year, $month, min($due_day, cal_days_in_month(CAL_GREGORIAN, $month, $year)));

        $tenants = $this->General_model->getAll('users', [
            'role'   => 0,
            'status' => 1
        ]);

        $generated_count = 0;
        foreach ($tenants as $t) {
            $exists = $this->General_model->getOne('rents', [
                'user_id' => $t->id,
                'month'   => $month,
                'year'    => $year
            ]);

            if (!$exists) {
                $this->General_model->insert('rents', [
                    'user_id'   => $t->id,
                    'hotel_id'  => $t->hotel_id,
                    'room_id'   => $t->room_id,
                    'month'     => $month,
                    'year'      => $year,
                    'due_date'  => $due_date,
                    'amount'    => $t->rent_amount,
                    'status'    => 'Unpaid'
                ]);
                $generated_count++;
            }
        }

        $this->output->set_output(json_encode([
            'success' => TRUE,
            'message' => "Generated {$generated_count} rent invoice(s) for {$month}/{$year}."
        ]));
    }

    /**
     * Create an individual rent bill/invoice for a tenant
     */
    public function add_bill()
    {
        $this->output->set_content_type('application/json');

        $user_id = intval($this->input->post('user_id', TRUE));
        $tenant  = $this->General_model->getOne('users', ['id' => $user_id, 'role' => 0]);
        if (!$tenant) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Tenant not found.']));
            return;
        }

        $month    = intval($this->input->post('month', TRUE) ?: date('n'));
        $year     = intval($this->input->post('year', TRUE) ?: date('Y'));
        $amount   = floatval($this->input->post('amount', TRUE) ?: $tenant->rent_amount);
        $due_day  = $this->settings ? intval($this->settings->rent_due_date) : 10;
        $due_date = $this->input->post('due_date', TRUE) ?: sprintf('%04d-%02d-%02d', $year, $month, min($due_day, cal_days_in_month(CAL_GREGORIAN, $month, $year)));

        $exists = $this->General_model->getOne('rents', [
            'user_id' => $user_id,
            'month'   => $month,
            'year'    => $year
        ]);

        if ($exists) {
            $month_names = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
            $m_str = $month_names[$month] ?? $month;
            $this->output->set_output(json_encode([
                'success' => FALSE,
                'message' => "A rent bill already exists for {$m_str} {$year} for this tenant."
            ]));
            return;
        }

        $rent_id = $this->General_model->insert('rents', [
            'user_id'  => $user_id,
            'hotel_id' => $tenant->hotel_id,
            'room_id'  => $tenant->room_id,
            'month'    => $month,
            'year'     => $year,
            'due_date' => $due_date,
            'amount'   => $amount,
            'status'   => 'Unpaid'
        ]);

        $this->output->set_output(json_encode([
            'success' => TRUE,
            'message' => 'Rent bill created successfully.',
            'rent_id' => $rent_id
        ]));
    }

    /**
     * Get single rent receipt details
     */
    public function receipt($id = NULL)
    {
        $this->output->set_content_type('application/json');

        $id = intval($id);
        $this->db->select('r.*, u.name as tenant_name, u.phone as tenant_phone, u.email as tenant_email, h.name as hotel_name, h.address as hotel_address, rm.number as room_number');
        $this->db->from('rents r');
        $this->db->join('users u', 'u.id = r.user_id', 'left');
        $this->db->join('hotels h', 'h.id = u.hotel_id', 'left');
        $this->db->join('rooms rm', 'rm.id = u.room_id', 'left');
        $this->db->where('r.id', $id);
        $rent = $this->db->get()->row();

        if (!$rent) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Rent receipt record not found.']));
            return;
        }

        $this->output->set_output(json_encode(['success' => TRUE, 'data' => $rent]));
    }
}
