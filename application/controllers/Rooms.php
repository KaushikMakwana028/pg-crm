<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rooms extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $hotel_id = $this->input->get('hotel_id', TRUE);
        $status   = $this->input->get('status', TRUE);

        $this->db->select('r.*, h.name as hotel_name');
        $this->db->from('rooms r');
        $this->db->join('hotels h', 'h.id = r.hotel_id', 'left');
        if (!empty($hotel_id)) {
            $this->db->where('r.hotel_id', $hotel_id);
        }
        if (!empty($status)) {
            $this->db->where('r.status', $status);
        }
        $this->db->order_by('r.number', 'ASC');
        $rooms = $this->db->get()->result();

        foreach ($rooms as $r) {
            $occ = $this->General_model->getCount('users', [
                'room_id' => $r->id,
                'role'    => 0,
                'status'  => 1
            ]);
            $r->occupied_beds = $occ;
            $r->free_beds = max(0, intval($r->capacity) - $occ);

            if ($r->status !== 'Maintenance') {
                $expected_status = ($occ >= intval($r->capacity)) ? 'Occupied' : 'Available';
                if ($r->status !== $expected_status) {
                    $r->status = $expected_status;
                    $this->General_model->update('rooms', ['id' => $r->id], ['status' => $expected_status]);
                }
            }
        }

        $hotels = $this->General_model->getAll('hotels');

        $data = [
            'user'        => $this->current_user,
            'settings'    => $this->settings,
            'active_page' => 'rooms',
            'page_title'  => 'Rooms - ' . ($this->settings->crm_name ?? 'StayFlow CRM'),
            'rooms'       => $rooms,
            'hotels'      => $hotels,
            'filters'     => ['hotel_id' => $hotel_id, 'status' => $status]
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('rooms_view', $data);
        $this->load->view('layout/footer', $data);
    }

    public function list()
    {
        $this->output->set_content_type('application/json');
        $hotel_id = $this->input->get('hotel_id', TRUE);

        $this->db->select('r.*, h.name as hotel_name');
        $this->db->from('rooms r');
        $this->db->join('hotels h', 'h.id = r.hotel_id', 'left');
        if (!empty($hotel_id)) {
            $this->db->where('r.hotel_id', $hotel_id);
        }
        $this->db->order_by('r.number', 'ASC');
        $rooms = $this->db->get()->result();

        foreach ($rooms as $r) {
            $occ = $this->General_model->getCount('users', [
                'room_id' => $r->id,
                'role'    => 0,
                'status'  => 1
            ]);
            $r->occupied_beds = $occ;
            $r->free_beds = max(0, intval($r->capacity) - $occ);
        }

        $this->output->set_output(json_encode(['success' => TRUE, 'data' => $rooms]));
    }

    public function list_ajax()
    {
        $this->output->set_content_type('application/json');

        $page     = max(1, intval($this->input->get('page', TRUE) ?: 1));
        $per_page = max(1, min(100, intval($this->input->get('per_page', TRUE) ?: 12)));
        $offset   = ($page - 1) * $per_page;
        $hotel_id = $this->input->get('hotel_id', TRUE);
        $status   = $this->input->get('status', TRUE);
        $q        = trim((string)$this->input->get('q', TRUE));

        $this->db->from('rooms r');
        $this->db->join('hotels h', 'h.id = r.hotel_id', 'left');

        if (!empty($hotel_id)) {
            $this->db->where('r.hotel_id', intval($hotel_id));
        }
        if (!empty($status)) {
            $this->db->where('r.status', $status);
        }
        if (!empty($q)) {
            $this->db->group_start();
            $this->db->like('r.number', $q);
            $this->db->or_like('h.name', $q);
            $this->db->or_like('r.floor', $q);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results('', FALSE);

        $this->db->select('r.*, h.name as hotel_name');
        $this->db->order_by('r.number', 'ASC');
        $this->db->limit($per_page, $offset);
        $rooms = $this->db->get()->result();

        foreach ($rooms as $r) {
            $occ = $this->General_model->getCount('users', [
                'room_id' => $r->id,
                'role'    => 0,
                'status'  => 1
            ]);
            $r->occupied_beds = $occ;
            $r->free_beds = max(0, intval($r->capacity) - $occ);

            if ($r->status !== 'Maintenance') {
                $expected_status = ($occ >= intval($r->capacity)) ? 'Occupied' : 'Available';
                if ($r->status !== $expected_status) {
                    $r->status = $expected_status;
                    $this->General_model->update('rooms', ['id' => $r->id], ['status' => $expected_status]);
                }
            }
        }

        $total_pages = ceil($total / $per_page) ?: 1;

        $this->output->set_output(json_encode([
            'success'    => TRUE,
            'data'       => $rooms,
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

        $id       = $this->input->post('id', TRUE);
        $hotel_id = intval($this->input->post('hotel_id', TRUE));
        $number   = trim($this->input->post('number', TRUE));

        if (empty($hotel_id) || empty($number)) {
            $this->output->set_output(json_encode(['success' => FALSE, 'message' => 'Hotel and Room Number are required.']));
            return;
        }

        $capacity = max(1, intval($this->input->post('capacity', TRUE) ?: 1));
        $status   = $this->input->post('status', TRUE) ?: 'Available';

        $data = [
            'hotel_id'     => $hotel_id,
            'number'       => $number,
            'floor'        => '',
            'type'         => 'Single',
            'capacity'     => $capacity,
            'rent_per_bed' => 0,
            'status'       => $status
        ];

        if (!empty($id)) {
            $room_id = intval($id);
            // Check occupancy against new capacity
            $occ = $this->General_model->getCount('users', [
                'room_id' => $room_id,
                'role'    => 0,
                'status'  => 1
            ]);
            if ($status !== 'Maintenance') {
                $data['status'] = ($occ >= $capacity) ? 'Occupied' : 'Available';
            }
            $this->General_model->update('rooms', ['id' => $room_id], $data);
            $msg = 'Room updated successfully.';
        } else {
            $room_id = $this->General_model->insert('rooms', $data);
            $msg = 'Room added successfully.';
        }

        $this->output->set_output(json_encode(['success' => TRUE, 'message' => $msg]));
    }

    public function delete($id = NULL)
    {
        $this->output->set_content_type('application/json');
        $id = intval($id);
        $this->General_model->delete('rooms', ['id' => $id]);
        $this->output->set_output(json_encode(['success' => TRUE, 'message' => 'Room deleted successfully.']));
    }

    public function detail($id = NULL)
    {
        $id = intval($id);
        if (!$id) {
            redirect(base_url('rooms'));
        }

        $this->db->select('r.*, h.name as hotel_name, h.address as hotel_address, h.phone as hotel_phone');
        $this->db->from('rooms r');
        $this->db->join('hotels h', 'h.id = r.hotel_id', 'left');
        $this->db->where('r.id', $id);
        $room = $this->db->get()->row();

        if (!$room) {
            redirect(base_url('rooms'));
        }

        // Active occupants in this room
        $this->db->select('u.*');
        $this->db->from('users u');
        $this->db->where('u.room_id', $room->id);
        $this->db->where('u.role', 0);
        $this->db->where('u.status', 1);
        $this->db->order_by('u.name', 'ASC');
        $tenants = $this->db->get()->result();

        $cur_month = intval(date('n'));
        $cur_year  = intval(date('Y'));

        foreach ($tenants as $t) {
            $rent = $this->General_model->getOne('rents', [
                'user_id' => $t->id,
                'month'   => $cur_month,
                'year'    => $cur_year
            ]);
            if ($rent) {
                $t->rent_status = $rent->status;
                $t->cur_rent_amount = $rent->amount;
            } else {
                $unpaid = $this->General_model->getOne('rents', [
                    'user_id' => $t->id,
                    'status'  => 'Unpaid'
                ]);
                $t->rent_status = $unpaid ? 'Unpaid' : 'Paid';
                $t->cur_rent_amount = $t->rent_amount;
            }
        }

        $occ = count($tenants);
        $room->occupied_beds = $occ;
        $room->free_beds = max(0, intval($room->capacity) - $occ);

        // Auto update room status if not maintenance
        if ($room->status !== 'Maintenance') {
            $expected_status = ($occ >= intval($room->capacity)) ? 'Occupied' : 'Available';
            if ($room->status !== $expected_status) {
                $room->status = $expected_status;
                $this->General_model->update('rooms', ['id' => $room->id], ['status' => $expected_status]);
            }
        }

        // Get all active tenants without this room or all active tenants for assignment dropdown
        $this->db->select('id, name, phone, room_id')
            ->from('users')
            ->where('role', 0)
            ->where('status', 1)
            ->group_start()
                ->where('room_id !=', $room->id)
                ->or_where('room_id IS NULL', null, false)
            ->group_end()
            ->order_by('name', 'ASC');
        $all_active_tenants = $this->db->get()->result();

        $hotels = $this->General_model->getAll('hotels');

        $data = [
            'user'               => $this->current_user,
            'settings'           => $this->settings,
            'active_page'        => 'rooms',
            'page_title'         => 'Room ' . $room->number . ' - ' . ($this->settings->crm_name ?? 'StayFlow CRM'),
            'room'               => $room,
            'tenants'            => $tenants,
            'all_active_tenants' => $all_active_tenants,
            'hotels'             => $hotels
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('room_detail_view', $data);
        $this->load->view('layout/footer', $data);
    }
}
