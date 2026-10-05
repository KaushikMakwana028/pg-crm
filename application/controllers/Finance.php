<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Finance extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->db->select_sum('amount');
        $inc_query = $this->db->get('incomes')->row();
        $total_income = floatval($inc_query->amount ?? 0);

        $this->db->select_sum('amount');
        $exp_query = $this->db->get('expenses')->row();
        $total_expense = floatval($exp_query->amount ?? 0);

        $this->db->select('i.*, h.name as hotel_name');
        $this->db->from('incomes i');
        $this->db->join('hotels h', 'h.id = i.hotel_id', 'left');
        $this->db->order_by('i.date', 'DESC');
        $incomes = $this->db->get()->result();

        $this->db->select('e.*, h.name as hotel_name');
        $this->db->from('expenses e');
        $this->db->join('hotels h', 'h.id = e.hotel_id', 'left');
        $this->db->order_by('e.date', 'DESC');
        $expenses = $this->db->get()->result();

        $hotels = $this->General_model->getAll('hotels');

        $data = [
            'user'          => $this->current_user,
            'settings'      => $this->settings,
            'active_page'   => 'finance',
            'page_title'    => 'Income & Expense - ' . ($this->settings->crm_name ?? 'StayFlow CRM'),
            'total_income'  => $total_income,
            'total_expense' => $total_expense,
            'incomes'       => $incomes,
            'expenses'      => $expenses,
            'hotels'        => $hotels
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('finance_view', $data);
        $this->load->view('layout/footer', $data);
    }

    public function list_ajax()
    {
        $this->output->set_content_type('application/json');

        $type      = $this->input->get('type', TRUE) ?: 'all';
        $page      = max(1, intval($this->input->get('page', TRUE) ?: 1));
        $per_page  = max(1, min(100, intval($this->input->get('per_page', TRUE) ?: 10)));
        $offset    = ($page - 1) * $per_page;
        $hotel_id  = $this->input->get('hotel_id', TRUE);
        $month     = $this->input->get('month', TRUE); // e.g. 2026-10
        $date_from = $this->input->get('date_from', TRUE);
        $date_to   = $this->input->get('date_to', TRUE);
        $q         = trim((string)$this->input->get('q', TRUE));

        $where_inc = [];
        $where_exp = [];

        if (!empty($hotel_id)) {
            $hid = intval($hotel_id);
            $where_inc[] = "i.hotel_id = {$hid}";
            $where_exp[] = "e.hotel_id = {$hid}";
        }

        // Date range filters take precedence over month filter if provided
        if (!empty($date_from)) {
            $safe_from = $this->db->escape($date_from);
            $where_inc[] = "i.date >= {$safe_from}";
            $where_exp[] = "e.date >= {$safe_from}";
        }
        if (!empty($date_to)) {
            $safe_to = $this->db->escape($date_to);
            $where_inc[] = "i.date <= {$safe_to}";
            $where_exp[] = "e.date <= {$safe_to}";
        }
        if (empty($date_from) && empty($date_to) && !empty($month)) {
            $safe_m = $this->db->escape_like_str($month);
            $where_inc[] = "i.date LIKE '{$safe_m}%'";
            $where_exp[] = "e.date LIKE '{$safe_m}%'";
        }

        if (!empty($q)) {
            $safe_q = $this->db->escape_like_str($q);
            $where_inc[] = "(i.category LIKE '%{$safe_q}%' OR i.received_from LIKE '%{$safe_q}%' OR i.description LIKE '%{$safe_q}%')";
            $where_exp[] = "(e.category LIKE '%{$safe_q}%' OR e.paid_to LIKE '%{$safe_q}%' OR e.description LIKE '%{$safe_q}%')";
        }

        $w_inc_str = !empty($where_inc) ? " WHERE " . implode(' AND ', $where_inc) : "";
        $w_exp_str = !empty($where_exp) ? " WHERE " . implode(' AND ', $where_exp) : "";

        // Filtered Totals
        $tot_inc_q = $this->db->query("SELECT COALESCE(SUM(amount), 0) as tot FROM incomes i {$w_inc_str}")->row();
        $tot_exp_q = $this->db->query("SELECT COALESCE(SUM(amount), 0) as tot FROM expenses e {$w_exp_str}")->row();
        $total_income  = floatval($tot_inc_q->tot ?? 0);
        $total_expense = floatval($tot_exp_q->tot ?? 0);

        $items = [];
        $total = 0;

        if ($type === 'income') {
            $cnt_q = $this->db->query("SELECT COUNT(*) as cnt FROM incomes i {$w_inc_str}")->row();
            $total = intval($cnt_q->cnt ?? 0);
            $p_q   = $this->db->query("SELECT i.id, i.hotel_id, i.date, i.category, i.amount, i.received_from as party, i.description, i.payment_mode, NULL as bill_file, h.name as hotel_name, 'income' as tx_type FROM incomes i LEFT JOIN hotels h ON h.id = i.hotel_id {$w_inc_str} ORDER BY i.date DESC, i.id DESC LIMIT {$offset}, {$per_page}");
            $items = $p_q->result();

        } elseif ($type === 'expense') {
            $cnt_q = $this->db->query("SELECT COUNT(*) as cnt FROM expenses e {$w_exp_str}")->row();
            $total = intval($cnt_q->cnt ?? 0);
            $p_q   = $this->db->query("SELECT e.id, e.hotel_id, e.date, e.category, e.amount, e.paid_to as party, e.description, e.payment_mode, e.bill_file, h.name as hotel_name, 'expense' as tx_type FROM expenses e LEFT JOIN hotels h ON h.id = e.hotel_id {$w_exp_str} ORDER BY e.date DESC, e.id DESC LIMIT {$offset}, {$per_page}");
            $items = $p_q->result();

        } else {
            $sql_inc = "SELECT i.id, i.hotel_id, i.date, i.category, i.amount, i.received_from as party, i.description, i.payment_mode, NULL as bill_file, h.name as hotel_name, 'income' as tx_type FROM incomes i LEFT JOIN hotels h ON h.id = i.hotel_id {$w_inc_str}";
            $sql_exp = "SELECT e.id, e.hotel_id, e.date, e.category, e.amount, e.paid_to as party, e.description, e.payment_mode, e.bill_file, h.name as hotel_name, 'expense' as tx_type FROM expenses e LEFT JOIN hotels h ON h.id = e.hotel_id {$w_exp_str}";

            $union_sql = "({$sql_inc}) UNION ALL ({$sql_exp})";
            $count_query = $this->db->query("SELECT COUNT(*) as cnt FROM ({$union_sql}) as total_tbl");
            $total = intval($count_query->row()->cnt ?? 0);

            $page_query = $this->db->query("{$union_sql} ORDER BY date DESC, id DESC LIMIT {$offset}, {$per_page}");
            $items = $page_query->result();
        }

        // Calculate 6-month historical chart data
        $chart_labels = [];
        $chart_inc    = [];
        $chart_exp    = [];
        $cur_y = intval(date('Y'));
        $cur_m = intval(date('n'));

        for ($i = 5; $i >= 0; $i--) {
            $ts = mktime(0, 0, 0, $cur_m - $i, 1, $cur_y);
            $ym = date('Y-m', $ts);
            $lbl = date('M Y', $ts);
            $chart_labels[] = $lbl;

            $h_cond = !empty($hotel_id) ? "AND hotel_id = " . intval($hotel_id) : "";
            $iq = $this->db->query("SELECT COALESCE(SUM(amount), 0) as tot FROM incomes WHERE date LIKE '{$ym}%' {$h_cond}")->row();
            $eq = $this->db->query("SELECT COALESCE(SUM(amount), 0) as tot FROM expenses WHERE date LIKE '{$ym}%' {$h_cond}")->row();

            $chart_inc[] = floatval($iq->tot ?? 0);
            $chart_exp[] = floatval($eq->tot ?? 0);
        }

        $total_pages = ceil($total / $per_page) ?: 1;

        $this->output->set_output(json_encode([
            'success'    => TRUE,
            'data'       => $items,
            'summary'    => [
                'total_income'  => $total_income,
                'total_expense' => $total_expense,
                'net_profit'    => $total_income - $total_expense
            ],
            'chart'      => [
                'labels'   => $chart_labels,
                'incomes'  => $chart_inc,
                'expenses' => $chart_exp
            ],
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => $total_pages
            ]
        ]));
    }

    public function summary()
    {
        $this->output->set_content_type('application/json');

        $this->db->select_sum('amount');
        $inc_query = $this->db->get('incomes')->row();
        $total_income = floatval($inc_query->amount ?? 0);

        $this->db->select_sum('amount');
        $exp_query = $this->db->get('expenses')->row();
        $total_expense = floatval($exp_query->amount ?? 0);

        $this->output->set_output(json_encode([
            'success'       => TRUE,
            'total_income'  => $total_income,
            'total_expense' => $total_expense,
            'net_profit'    => $total_income - $total_expense
        ]));
    }

    public function add_income()
    {
        $this->output->set_content_type('application/json');

        $hotel_id = $this->input->post('hotel_id', TRUE);
        $amount   = floatval($this->input->post('amount', TRUE));
        $category = $this->input->post('category', TRUE) ?: 'Other';
        $date     = $this->input->post('date', TRUE) ?: date('Y-m-d');

        if ($amount <= 0) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Amount must be greater than zero.']));
            return;
        }

        $data = [
            'hotel_id'      => !empty($hotel_id) ? intval($hotel_id) : null,
            'date'          => $date,
            'category'      => $category,
            'amount'        => $amount,
            'received_from' => trim($this->input->post('received_from', TRUE) ?? ''),
            'description'   => trim($this->input->post('description', TRUE) ?? ''),
            'payment_mode'  => $this->input->post('payment_mode', TRUE) ?: 'Cash'
        ];

        $this->General_model->insert('incomes', $data);
        $this->output->set_output(json_encode(['success' => TRUE, 'message' => 'Income added successfully.']));
    }

    public function add_expense()
    {
        $this->output->set_content_type('application/json');

        $hotel_id = $this->input->post('hotel_id', TRUE);
        $amount   = floatval($this->input->post('amount', TRUE));
        $category = $this->input->post('category', TRUE) ?: 'Other';
        $date     = $this->input->post('date', TRUE) ?: date('Y-m-d');

        if ($amount <= 0) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Amount must be greater than zero.']));
            return;
        }

        $data = [
            'hotel_id'     => !empty($hotel_id) ? intval($hotel_id) : null,
            'date'         => $date,
            'category'     => $category,
            'amount'       => $amount,
            'paid_to'      => trim($this->input->post('paid_to', TRUE) ?? ''),
            'description'  => trim($this->input->post('description', TRUE) ?? ''),
            'payment_mode' => $this->input->post('payment_mode', TRUE) ?: 'Cash'
        ];

        // Upload Bill / Receipt file (Optional)
        if (!empty($_FILES['bill_file']['name'])) {
            $cfg_bill['upload_path']   = './uploads/expenses/';
            $cfg_bill['allowed_types'] = 'gif|jpg|jpeg|png|pdf|webp';
            $cfg_bill['max_size']      = 5120; // 5MB
            $cfg_bill['encrypt_name']  = TRUE;
            $this->load->library('upload', $cfg_bill, 'bill_upload');
            if ($this->bill_upload->do_upload('bill_file')) {
                $up = $this->bill_upload->data();
                $data['bill_file'] = 'uploads/expenses/' . $up['file_name'];
            }
        }

        $this->General_model->insert('expenses', $data);
        $this->output->set_output(json_encode(['success' => TRUE, 'message' => 'Expense added successfully.']));
    }

    public function delete_income($id = NULL)
    {
        $this->output->set_content_type('application/json');
        $id = intval($id);
        $this->General_model->delete('incomes', ['id' => $id]);
        $this->output->set_output(json_encode(['success' => TRUE, 'message' => 'Income record deleted.']));
    }

    public function delete_expense($id = NULL)
    {
        $this->output->set_content_type('application/json');
        $id = intval($id);
        $this->General_model->delete('expenses', ['id' => $id]);
        $this->output->set_output(json_encode(['success' => TRUE, 'message' => 'Expense record deleted.']));
    }
}
