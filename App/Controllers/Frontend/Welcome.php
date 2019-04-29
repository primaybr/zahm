<?php

namespace App\Controllers\Frontend;

use Core\Controller as Controller;

class Welcome extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->model('user_admin');
    }

    public function index()
    {
		//$data = $this->user_admin->where('id',1)->get();
		$data['date'] = date('Y');
        $this->render('frontend\welcome', $data);
    }
}
