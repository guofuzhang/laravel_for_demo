<?php

namespace App\Http\Controllers\Home;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Models\Stream;

class VideoController extends Controller
{
    public function play(Request $request,Stream $stream){
        //根据$stream获得对应的拉流地址
        //rtmp://pili-live-rtmp.www.51lfgl.cn/itcast008/quanzhan03
        $urlpath = "rtmp://pili-live-rtmp.www.51lfgl.cn";
        $space = "itcast008";
        $name = $stream->stream_name; //直播流名称
        $pullurl = $urlpath."/".$space."/".$name; //拼装拉流地址

        return view('home/video/play',compact('pullurl'));
    }
}
