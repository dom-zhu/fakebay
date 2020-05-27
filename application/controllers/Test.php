<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Test extends CI_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->model('product_model');
		$this->load->helper('url_helper');
        $this->load->helper('form');
        $this->load->library('Payment');
    }

    
    public function index() {
        $this->payment->index();
    }
}