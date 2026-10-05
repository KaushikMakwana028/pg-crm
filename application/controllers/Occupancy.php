<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Occupancy extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->db->select('r.*, h.name as hotel_name');
        $this->db->from('rooms r');
        $this->db->join('hotels h', 'h.id = r.hotel_id', 'left');
        $this->db->order_by('r.number', 'ASC');
        $rooms = $this->db->get()->result();

        $total_capacity = 0;
        $total_occupied = 0;

        foreach ($rooms as $r) {
            $occ = $this->General_model->getCount('users', [
                'room_id' => $r->id,
                'role'    => 0,
                'status'  => 1
            ]);
            $r->occupied_beds = $occ;
            $r->free_beds = max(0, intval($r->capacity) - $occ);

            $total_capacity += intval($r->capacity);
            $total_occupied += $occ;
        }

        $total_available = max(0, $total_capacity - $total_occupied);
        $occupancy_rate = $total_capacity ? round(($total_occupied / $total_capacity) * 100) : 0;

        $data = [
            'user'            => $this->current_user,
            'settings'        => $this->settings,
            'active_page'     => 'occupancy',
            'page_title'      => 'Occupancy Analytics - ' . ($this->settings->crm_name ?? 'StayFlow CRM'),
            'total_capacity'  => $total_capacity,
            'total_occupied'  => $total_occupied,
            'total_available' => $total_available,
            'occupancy_rate'  => $occupancy_rate,
            'rooms'           => $rooms
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('occupancy_view', $data);
        $this->load->view('layout/footer', $data);
    }
}
