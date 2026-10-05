<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // Fetch freshest user data directly from DB
        $user = $this->General_model->getOne('users', ['id' => $this->current_user->id]);

        $data = [
            'user'        => $user,
            'settings'    => $this->settings,
            'active_page' => 'profile',
            'page_title'  => 'Admin Profile - ' . ($this->settings->crm_name ?? 'StayFlow CRM')
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('profile_view', $data);
        $this->load->view('layout/footer', $data);
    }

    public function update()
    {
        $uid      = $this->current_user->id;
        $name     = trim((string)$this->input->post('name', TRUE));
        $email    = trim((string)$this->input->post('email', TRUE));
        $phone    = trim((string)$this->input->post('phone', TRUE));
        $address  = trim((string)$this->input->post('address', TRUE));
        $password = (string)$this->input->post('password');
        $confirm  = (string)$this->input->post('confirm_password');

        if (empty($name) || empty($email)) {
            $resp = ['success' => FALSE, 'message' => 'Full Name and Email Address are required.'];
            if ($this->input->is_ajax_request() || $this->input->post('is_ajax')) {
                return $this->output->set_content_type('application/json')->set_output(json_encode($resp));
            }
            $this->session->set_flashdata('error', $resp['message']);
            redirect('profile');
        }

        // Check if email already used by another user
        $email_exists = $this->General_model->getOne('users', ['email' => $email, 'id !=' => $uid]);
        if ($email_exists) {
            $resp = ['success' => FALSE, 'message' => 'This email address is already in use by another account.'];
            if ($this->input->is_ajax_request() || $this->input->post('is_ajax')) {
                return $this->output->set_content_type('application/json')->set_output(json_encode($resp));
            }
            $this->session->set_flashdata('error', $resp['message']);
            redirect('profile');
        }

        $update_data = [
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'address' => $address
        ];

        // Password update
        if (!empty($password)) {
            if (!empty($confirm) && $password !== $confirm) {
                $resp = ['success' => FALSE, 'message' => 'Password and Confirm Password do not match.'];
                if ($this->input->is_ajax_request() || $this->input->post('is_ajax')) {
                    return $this->output->set_content_type('application/json')->set_output(json_encode($resp));
                }
                $this->session->set_flashdata('error', $resp['message']);
                redirect('profile');
            }
            $update_data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        // Profile Image Upload (Max 2MB)
        if (!empty($_FILES['profile_image']['name'])) {
            if ($_FILES['profile_image']['size'] > 2097152) { // 2MB in bytes
                $resp = ['success' => FALSE, 'message' => 'Profile image cannot exceed 2MB.'];
                if ($this->input->is_ajax_request() || $this->input->post('is_ajax')) {
                    return $this->output->set_content_type('application/json')->set_output(json_encode($resp));
                }
                $this->session->set_flashdata('error', $resp['message']);
                redirect('profile');
            }

            $upload_dir = FCPATH . 'uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $config['upload_path']   = $upload_dir;
            $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
            $config['max_size']      = 2048; // 2MB
            $config['encrypt_name']  = TRUE;

            $this->load->library('upload', $config);
            if ($this->upload->do_upload('profile_image')) {
                $upload_res = $this->upload->data();
                $new_image_rel = 'uploads/profiles/' . $upload_res['file_name'];
                $update_data['profile_image'] = $new_image_rel;

                // Delete old image if exists
                if (!empty($this->current_user->profile_image) && file_exists(FCPATH . $this->current_user->profile_image)) {
                    @unlink(FCPATH . $this->current_user->profile_image);
                }
            } else {
                $upload_error = strip_tags($this->upload->display_errors());
                $resp = ['success' => FALSE, 'message' => 'Photo upload failed: ' . $upload_error];
                if ($this->input->is_ajax_request() || $this->input->post('is_ajax')) {
                    return $this->output->set_content_type('application/json')->set_output(json_encode($resp));
                }
                $this->session->set_flashdata('error', $resp['message']);
                redirect('profile');
            }
        }

        // Update database using General_model
        $this->General_model->update('users', ['id' => $uid], $update_data);

        // Update session
        $this->session->set_userdata('user_name', $name);
        $this->session->set_userdata('user_email', $email);
        if (isset($update_data['profile_image'])) {
            $this->session->set_userdata('profile_image', $update_data['profile_image']);
        }

        $resp = [
            'success'       => TRUE,
            'message'       => 'Profile updated successfully!',
            'user_name'     => $name,
            'user_email'    => $email,
            'profile_image' => isset($update_data['profile_image']) ? base_url($update_data['profile_image']) : (!empty($this->current_user->profile_image) ? base_url($this->current_user->profile_image) : null)
        ];

        if ($this->input->is_ajax_request() || $this->input->post('is_ajax')) {
            return $this->output->set_content_type('application/json')->set_output(json_encode($resp));
        }

        $this->session->set_flashdata('success', 'Profile updated successfully!');
        redirect('profile');
    }
}
