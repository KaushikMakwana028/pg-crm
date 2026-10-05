<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hotels extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $hotels = $this->General_model->getAll('hotels');
        foreach ($hotels as $h) {
            $h->total_rooms = $this->General_model->getCount('rooms', ['hotel_id' => $h->id]);
            $h->total_tenants = $this->General_model->getCount('users', [
                'hotel_id' => $h->id,
                'role'     => 0,
                'status'   => 1
            ]);
        }

        $data = [
            'user'        => $this->current_user,
            'settings'    => $this->settings,
            'active_page' => 'hotels',
            'page_title'  => 'Hotels / Properties - ' . ($this->settings->crm_name ?? 'StayFlow CRM'),
            'hotels'      => $hotels
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('hotels_view', $data);
        $this->load->view('layout/footer', $data);
    }

    public function list()
    {
        $this->output->set_content_type('application/json');
        $hotels = $this->General_model->getAll('hotels');
        $this->output->set_output(json_encode(['success' => TRUE, 'data' => $hotels]));
    }

    public function list_ajax()
    {
        $this->output->set_content_type('application/json');

        $page     = max(1, intval($this->input->get('page', TRUE) ?: 1));
        $per_page = max(1, min(100, intval($this->input->get('per_page', TRUE) ?: 8)));
        $offset   = ($page - 1) * $per_page;
        $q        = trim((string)$this->input->get('q', TRUE));

        $this->db->from('hotels');
        if (!empty($q)) {
            $this->db->group_start();
            $this->db->like('name', $q);
            $this->db->or_like('city', $q);
            $this->db->or_like('phone', $q);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results('', FALSE);

        $this->db->order_by('id', 'DESC');
        $this->db->limit($per_page, $offset);
        $hotels = $this->db->get()->result();

        foreach ($hotels as $h) {
            $h->total_rooms = $this->General_model->getCount('rooms', ['hotel_id' => $h->id]);
            $h->total_tenants = $this->General_model->getCount('users', [
                'hotel_id' => $h->id,
                'role'     => 0,
                'status'   => 1
            ]);
        }

        $total_pages = ceil($total / $per_page) ?: 1;

        $this->output->set_output(json_encode([
            'success'    => TRUE,
            'data'       => $hotels,
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => $total_pages
            ]
        ]));
    }

    public function save()
    {
        $this->output->set_content_type('application/json');

        $id   = $this->input->post('id', TRUE);
        $name = trim($this->input->post('name', TRUE));

        if (empty($name)) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Property name is required.']));
            return;
        }

        $data = [
            'name'        => $name,
            'code'        => trim($this->input->post('code', TRUE) ?? ''),
            'address'     => trim($this->input->post('address', TRUE) ?? ''),
            'city'        => trim($this->input->post('city', TRUE) ?? ''),
            'phone'       => trim($this->input->post('phone', TRUE) ?? ''),
            'description' => trim($this->input->post('description', TRUE) ?? '')
        ];

        if (!empty($id)) {
            $this->General_model->update('hotels', ['id' => intval($id)], $data);
            $msg = 'Property updated successfully.';
        } else {
            $this->General_model->insert('hotels', $data);
            $msg = 'Property added successfully.';
        }

        $this->output->set_output(json_encode(['success' => TRUE, 'message' => $msg]));
    }

    public function delete($id = NULL)
    {
        $this->output->set_content_type('application/json');
        $id = intval($id);
        $this->General_model->delete('hotels', ['id' => $id]);
        $this->output->set_output(json_encode(['success' => TRUE, 'message' => 'Property deleted successfully.']));
    }
}
