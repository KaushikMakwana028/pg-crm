<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Asia/Kolkata');
    }

    public function index()
    {
        $this->login();
    }

    public function login()
    {
        // 1. If already logged in, redirect directly to dashboard
        if ($this->session->userdata('logged_in')) {
            redirect(base_url('dashboard'));
        }

        // Check permanent remember cookie
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
                    redirect(base_url('dashboard'));
                }
            }
        }

        // 2. Handle POST request for login
        if ($this->input->method() === 'post') {
            $this->output->set_content_type('application/json');

            $email    = trim($this->input->post('email', TRUE));
            $password = $this->input->post('password');

            if (empty($email) || empty($password)) {
                $this->output->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Please enter both email and password.'
                ]));
                return;
            }

            // Fetch admin user (role = 1) from users table
            $user = $this->General_model->getOne('users', [
                'email' => $email,
                'role'  => 1
            ]);

            if (!$user) {
                $this->output->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Invalid email address or unauthorized account.'
                ]));
                return;
            }

            if (intval($user->status) !== 1) {
                $this->output->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Your account is inactive. Please contact the administrator.'
                ]));
                return;
            }

            // Verify password
            $match = FALSE;
            if (password_verify($password, $user->password)) {
                $match = TRUE;
            } elseif ($password === $user->password || md5($password) === $user->password) {
                $match = TRUE;
                $this->General_model->update('users', ['id' => $user->id], [
                    'password' => password_hash($password, PASSWORD_BCRYPT)
                ]);
            }

            if (!$match) {
                $this->output->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Incorrect password. Please try again.'
                ]));
                return;
            }

            // Set permanent session in CodeIgniter
            $session_data = [
                'user_id'       => $user->id,
                'user_name'     => $user->name,
                'user_email'    => $user->email,
                'user_role'     => $user->role,
                'profile_image' => $user->profile_image,
                'logged_in'     => TRUE
            ];
            $this->session->set_userdata($session_data);

            // Set 10-year permanent remember cookie
            $token = base64_encode($user->id . ':' . hash('sha256', $user->email . $user->password . 'pg_crm_secret'));
            set_cookie('crm_auth_token', $token, 315360000); // 10 years

            $this->output->set_output(json_encode([
                'success' => TRUE,
                'message' => 'Logged in successfully.'
            ]));
            return;
        }

        // 3. Render login_view.php on GET
        $settings = $this->General_model->getOne('settings', ['id' => 1]);
        $this->load->view('login_view', ['settings' => $settings]);
    }

    public function logout()
    {
        $this->session->sess_destroy();
        delete_cookie('crm_auth_token');

        if ($this->input->is_ajax_request()) {
            $this->output->set_content_type('application/json');
            $this->output->set_output(json_encode(['success' => TRUE, 'message' => 'Logged out successfully.']));
            return;
        }

        redirect(base_url('auth/login'));
    }

    public function check()
    {
        $this->output->set_content_type('application/json');
        $logged_in = $this->session->userdata('logged_in');
        $this->output->set_output(json_encode(['logged_in' => !!$logged_in]));
    }
}
