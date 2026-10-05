<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // 1. Hotel & Room Stats
        $total_hotels = $this->General_model->getCount('hotels');
        $total_rooms  = $this->General_model->getCount('rooms');

        // 2. Tenants Stats (role = 0, status = 1)
        $total_tenants = $this->General_model->getCount('users', [
            'role'   => 0,
            'status' => 1
        ]);

        // 3. Occupancy calculation
        $this->db->select_sum('capacity');
        $cap_res = $this->db->get('rooms')->row();
        $total_capacity = intval($cap_res->capacity ?? 0);
        $occupancy_rate = $total_capacity ? round(($total_tenants / $total_capacity) * 100) : 0;

        // 4. Month filter (default to current month: YYYY-MM)
        $selected_my = $this->input->get('month_year', TRUE) ?: date('Y-m');
        $month_stats = $this->_calculate_month_stats($selected_my);

        // 5. Recent Tenants
        $this->db->select('u.*, h.name as hotel_name, r.number as room_number');
        $this->db->from('users u');
        $this->db->join('hotels h', 'h.id = u.hotel_id', 'left');
        $this->db->join('rooms r', 'r.id = u.room_id', 'left');
        $this->db->where('u.role', 0);
        $this->db->where('u.status', 1);
        $this->db->order_by('u.id', 'DESC');
        $this->db->limit(6);
        $recent_tenants = $this->db->get()->result();

        // Month dropdown options: past 12 months up to next 3 months
        $month_options = [];
        $base_time = time();
        for ($i = 3; $i >= -12; $i--) {
            $t = strtotime("$i month", $base_time);
            $val = date('Y-m', $t);
            $month_options[$val] = date('F Y', $t);
        }

        $data = array_merge([
            'user'                => $this->current_user,
            'settings'            => $this->settings,
            'active_page'         => 'dashboard',
            'page_title'          => 'Dashboard - ' . ($this->settings->crm_name ?? 'StayFlow CRM'),
            'total_hotels'        => $total_hotels,
            'total_rooms'         => $total_rooms,
            'total_tenants'       => $total_tenants,
            'occupancy_rate'      => $occupancy_rate,
            'recent_tenants'      => $recent_tenants,
            'selected_month_year' => $month_stats['month_year'],
            'month_label'         => $month_stats['month_label'],
            'month_options'       => $month_options
        ], $month_stats);

        $this->load->view('layout/header', $data);
        $this->load->view('dashboard_view', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * AJAX endpoint to return filtered month statistics
     */
    public function stats_ajax()
    {
        $this->output->set_content_type('application/json');
        $selected_my = $this->input->get('month_year', TRUE) ?: date('Y-m');
        $stats = $this->_calculate_month_stats($selected_my);
        $this->output->set_output(json_encode(['success' => TRUE, 'stats' => $stats]));
    }

    /**
     * Helper to compute month-specific statistics
     */
    private function _calculate_month_stats($selected_my)
    {
        $parts = explode('-', $selected_my);
        $year  = intval($parts[0] ?? date('Y'));
        $month = intval($parts[1] ?? date('n'));
        if ($month < 1 || $month > 12) {
            $month = intval(date('n'));
            $year  = intval(date('Y'));
        }
        $formatted_my = sprintf('%04d-%02d', $year, $month);
        $month_label  = date('F Y', mktime(0, 0, 0, $month, 1, $year));

        // Rents for this month & year
        $this->db->where('month', $month);
        $this->db->where('year', $year);
        $month_rents = $this->db->get('rents')->result();

        $pending_rents_count = 0;
        $paid_amount = 0;
        $total_rent_amount = 0;

        foreach ($month_rents as $r) {
            $total_rent_amount += floatval($r->amount);
            if ($r->status === 'Paid') {
                $paid_amount += floatval($r->amount);
            } else {
                $pending_rents_count++;
            }
        }

        // If no rent records generated yet for that month, fallback to active tenant rent amounts
        if ($total_rent_amount == 0 && count($month_rents) == 0) {
            $this->db->select_sum('rent_amount');
            $this->db->where('role', 0);
            $this->db->where('status', 1);
            $rev_res = $this->db->get('users')->row();
            $monthly_revenue = floatval($rev_res->rent_amount ?? 0);
        } else {
            $monthly_revenue = $total_rent_amount;
        }

        $coll_pct = $total_rent_amount ? round(($paid_amount / $total_rent_amount) * 100) : 0;
        $pending_amount = max(0, $total_rent_amount - $paid_amount);

        // Income for this month
        $this->db->select_sum('amount');
        $this->db->where("DATE_FORMAT(date, '%Y-%m') =", $formatted_my);
        $inc_res = $this->db->get('incomes')->row();
        $total_income = floatval($inc_res->amount ?? 0);

        // Expenses for this month
        $this->db->select_sum('amount');
        $this->db->where("DATE_FORMAT(date, '%Y-%m') =", $formatted_my);
        $exp_res = $this->db->get('expenses')->row();
        $total_expense = floatval($exp_res->amount ?? 0);

        $net_profit = $total_income - $total_expense;

        return [
            'month_year'          => $formatted_my,
            'month_label'         => $month_label,
            'monthly_revenue'     => $monthly_revenue,
            'pending_rents_count' => $pending_rents_count,
            'paid_amount'         => $paid_amount,
            'total_rent_amount'   => $total_rent_amount,
            'pending_amount'      => $pending_amount,
            'coll_pct'            => $coll_pct,
            'total_income'        => $total_income,
            'total_expense'       => $total_expense,
            'net_profit'          => $net_profit,
            'selected_year'       => $year,
            'selected_month'      => $month
        ];
    }
}
