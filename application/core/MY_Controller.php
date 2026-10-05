<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    public $current_user = null;
    public $settings = null;

    public function __construct()
    {
        parent::__construct();

        // Enforce Kolkata time zone
        date_default_timezone_set('Asia/Kolkata');
        $this->db->query("SET time_zone = '+05:30'");

        // 1. Authenticate user
        $this->_check_authentication();

        // 2. Load system settings
        $this->_load_settings();
    }

    private function _check_authentication()
    {
        $logged_in = $this->session->userdata('logged_in');

        if (!$logged_in) {
            // Check permanent 10-year remember cookie
            $token = get_cookie('crm_auth_token');
            if ($token) {
                $decoded = base64_decode($token);
                $parts = explode(':', $decoded, 2);
                if (count($parts) === 2) {
                    $uid = intval($parts[0]);
                    $hash = $parts[1];
                    $user = $this->General_model->getOne('users', ['id' => $uid, 'role' => 1, 'status' => 1]);
                    if ($user && hash_equals(hash('sha256', $user->email . $user->password . 'pg_crm_secret'), $hash)) {
                        $this->session->set_userdata([
                            'user_id'       => $user->id,
                            'user_name'     => $user->name,
                            'user_email'    => $user->email,
                            'user_role'     => $user->role,
                            'profile_image' => $user->profile_image,
                            'logged_in'     => TRUE
                        ]);
                        $logged_in = TRUE;
                    }
                }
            }
        }

        if (!$logged_in) {
            if ($this->input->is_ajax_request()) {
                $this->output->set_content_type('application/json');
                $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Session expired. Please log in again.']));
                $this->output->_display();
                exit;
            }
            redirect(base_url('auth/login'));
        }

        $uid = $this->session->userdata('user_id');
        $this->current_user = $this->General_model->getOne('users', ['id' => $uid]);
    }

    private function _load_settings()
    {
        $this->settings = $this->General_model->getOne('settings', ['id' => 1]);
        if (!$this->settings) {
            $this->General_model->insert('settings', [
                'id'            => 1,
                'crm_name'      => 'StayFlow CRM',
                'currency'      => '₹',
                'rent_due_date' => 10,
                'dark_mode'     => 0
            ]);
            $this->settings = $this->General_model->getOne('settings', ['id' => 1]);
        }
    }

    /**
     * Helper to render full page with header and footer
     */
    public function render_page($view, $data = [], $active_page = 'dashboard')
    {
        $data['user'] = $this->current_user;
        $data['settings'] = $this->settings;
        $data['active_page'] = $active_page;

        $this->load->view('layout/header', $data);
        $this->load->view($view, $data);
        $this->load->view('layout/footer', $data);
    }
}
