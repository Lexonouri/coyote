<?php namespace Coyote\Http\Controllers;

class LoginController extends Controller {

    /**
     * @return Response
     */
    public function getIndex()
    {
        return view('login');
    }

    public function postIndex()
    {

    }

}
