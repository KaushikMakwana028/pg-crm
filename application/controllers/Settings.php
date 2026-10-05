<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data = [
            'user'        => $this->current_user,
            'settings'    => $this->settings,
            'active_page' => 'settings',
            'page_title'  => 'Settings - ' . ($this->settings->crm_name ?? 'StayFlow CRM')
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('settings_view', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * Save Branding & Rent settings (and auto-update due dates for all tenants)
     */
    public function save()
    {
        $this->output->set_content_type('application/json');

        $due_day = max(1, min(31, intval($this->input->post('rent_due_date', TRUE) ?: 10)));
        $data = [
            'rent_due_date' => $due_day
        ];

        if ($this->input->post('crm_name') !== NULL) {
            $data['crm_name'] = trim($this->input->post('crm_name', TRUE));
        }
        if ($this->input->post('currency') !== NULL) {
            $data['currency'] = trim($this->input->post('currency', TRUE));
        }
        if ($this->input->post('dark_mode') !== NULL) {
            $data['dark_mode'] = intval($this->input->post('dark_mode', TRUE) ?: 0);
        }

        $this->General_model->update('settings', ['id' => 1], $data);

        // Auto-update due date for all unpaid rents to match this new due day
        $unpaid_rents = $this->General_model->getAll('rents', ['status' => 'Unpaid']);
        $updated_count = 0;
        foreach ($unpaid_rents as $ur) {
            $m = intval($ur->month);
            $y = intval($ur->year);
            $days_in_m = cal_days_in_month(CAL_GREGORIAN, $m, $y);
            $new_due_date = sprintf('%04d-%02d-%02d', $y, $m, min($due_day, $days_in_m));
            if ($ur->due_date !== $new_due_date) {
                $this->General_model->update('rents', ['id' => $ur->id], ['due_date' => $new_due_date]);
                $updated_count++;
            }
        }

        $msg = "Rent billing policy saved successfully.";
        if ($updated_count > 0) {
            $msg .= " Updated {$updated_count} unpaid rent bill(s) to the {$due_day}th of the month.";
        }

        $this->output->set_output(json_encode([
            'success' => TRUE,
            'message' => $msg,
            'data'    => $data
        ]));
    }

    /**
     * Save Admin Profile from Settings
     */
    public function save_profile()
    {
        $this->output->set_content_type('application/json');

        $name  = trim($this->input->post('name', TRUE) ?? '');
        $phone = trim($this->input->post('phone', TRUE) ?? '');
        $email = trim($this->input->post('email', TRUE) ?? '');
        $pass  = trim($this->input->post('password', TRUE) ?? '');

        if (empty($name) || empty($email)) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Name and email are required.']));
            return;
        }

        // Check if email already in use by another user
        $existing = $this->db->where('email', $email)->where('id !=', $this->current_user->id)->get('users')->row();
        if ($existing) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Email address is already in use by another user.']));
            return;
        }

        $update_data = [
            'name'  => $name,
            'phone' => $phone,
            'email' => $email
        ];

        if (!empty($pass)) {
            $update_data['password'] = password_hash($pass, PASSWORD_BCRYPT);
        }

        $this->General_model->update('users', ['id' => $this->current_user->id], $update_data);

        // Update session
        $this->session->set_userdata('user_name', $name);
        $this->session->set_userdata('user_email', $email);

        $this->output->set_output(json_encode([
            'success' => TRUE,
            'message' => 'Admin profile updated successfully.'
        ]));
    }

    /**
     * Export All Data JSON
     */
    public function export_json()
    {
        $backup = [
            'exported_at' => date('Y-m-d H:i:s'),
            'hotels'      => $this->General_model->getAll('hotels'),
            'rooms'       => $this->General_model->getAll('rooms'),
            'users'       => $this->General_model->getAll('users'),
            'rents'       => $this->General_model->getAll('rents'),
            'incomes'     => $this->General_model->getAll('incomes'),
            'expenses'    => $this->General_model->getAll('expenses'),
            'settings'    => $this->General_model->getAll('settings')
        ];

        $json = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $filename = 'StayFlow_Backup_' . date('Y-m-d_H-i-s') . '.json';

        $this->output
            ->set_content_type('application/json')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_output($json);
    }

    /**
     * Import Data JSON
     */
    public function import_json()
    {
        $this->output->set_content_type('application/json');

        if (empty($_FILES['backup_file']['tmp_name'])) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'No backup JSON file uploaded.']));
            return;
        }

        $content = file_get_contents($_FILES['backup_file']['tmp_name']);
        $data = json_decode($content, true);

        if (!$data || !is_array($data)) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Invalid backup JSON file format.']));
            return;
        }

        $this->db->trans_start();

        // Restore tables if present
        $tables = ['hotels', 'rooms', 'rents', 'incomes', 'expenses'];
        foreach ($tables as $tbl) {
            if (isset($data[$tbl]) && is_array($data[$tbl])) {
                $this->db->empty_table($tbl);
                foreach ($data[$tbl] as $row) {
                    $this->db->insert($tbl, $row);
                }
            }
        }

        // Restore tenants in users (keep admin safe)
        if (isset($data['users']) && is_array($data['users'])) {
            $this->db->where('role', 0)->delete('users');
            foreach ($data['users'] as $u) {
                if ($u['role'] == 0) {
                    $this->db->insert('users', $u);
                }
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Import failed during database transaction.']));
        } else {
            $this->output->set_output(json_encode(['success' => TRUE, 'message' => 'Data imported successfully!']));
        }
    }

    /**
     * Clear All Data (Keeps Admin Account)
     */
    public function clear_all_data()
    {
        $this->output->set_content_type('application/json');

        $this->db->trans_start();
        $this->db->empty_table('rents');
        $this->db->empty_table('incomes');
        $this->db->empty_table('expenses');
        $this->db->where('role', 0)->delete('users');
        $this->db->empty_table('rooms');
        $this->db->empty_table('hotels');
        $this->db->trans_complete();

        $this->output->set_output(json_encode([
            'success' => TRUE,
            'message' => 'All CRM operational data has been cleared. Admin account preserved.'
        ]));
    }
}
