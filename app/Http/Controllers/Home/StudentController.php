<?php

namespace App\Http\Controllers\Home;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StudentController extends Controller
{
    /**
     * 学员登录
     */
    public function login(Request $request)
    {
        if($request->isMethod('post')){
            $rules = [
                'std_name'=>'required',
                'password'=>'required',
            ];
            $notices = [
                'std_name.required'=>'登录名必填',
                'password.required'=>'密码必填',
            ];
            $validator = \Validator::make($request->all(),$rules,$notices);

            if($validator->passes()){
                //用户名密码校验
                //['std_name'=>'xxx','password'=>'xxxx']
                $name_pass = $request->only(['std_name','password']);

                if(\Auth::guard('home')->attempt($name_pass)){
                    //用户名、密码正确
                    return redirect('/');
                }else{
                    //页面回跳(登录页)
                    return redirect('home/student/login')
                        ->withErrors(['errorinfo'=>'用户名或密码错误'])
                        ->withInput();
                }
            }else{
                //页面回跳(登录页)
                return redirect('home/student/login')
                    ->withErrors($validator)
                    ->withInput();
            }
        }
        return view('home.student.login');
    }

    /**
     * 用户退出系统
     */
    public function logout(){
        \Auth::guard('home')->logout();
        return redirect('/');
    }
}

