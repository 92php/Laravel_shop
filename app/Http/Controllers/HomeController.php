<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/5/24
 * Time: 22:01
 */


namespace App\Http\Controllers;

class HomeController extends Controller{
    public function hello(){
        return "hello";
    }

    public function getOrder(\Request $request){
        $input = $request->input();
        return $input;
    }
}
