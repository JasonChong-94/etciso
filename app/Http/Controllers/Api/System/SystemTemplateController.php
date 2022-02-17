<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Api\System\SystemTemplate;

class SystemTemplateController extends Controller
{
    /**证书模板列表**/
    public function TemplateIndex(Request $request){
        $flighs = SystemTemplate::orderBy('system','asc')
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**证书模板添加**/
    public function TemplateAdd(Request $request){
        if(!$request->system || !$request->type){
            return response()->json(['status'=>101,'msg'=>'证书体系或证书类型不能为空']);
        }
        if(!$request->hasFile('file')){
            return response()->json(['status'=>101,'msg'=>'证书背景不能为空']);
        }
        $data = array(
            'file_path'=>$request->system,
            'file_size'=>4194304,
            'file_etsn'=>['png','jpg'],
        );
        $path = SystemTemplate::TemplateImg($request->file('file'),$data,'template');
        if($path['status'] == '101'){
            return response()->json($path);
        }
        $flighs = new SystemTemplate;
        $flighs->system   = $request->system;
        $flighs->attribute= $request->attribute;
        $flighs->image     = $path['data'];
        $flighs->type      = $request->type;
        if(!$flighs->save()){
            return response()->json(['status'=>101,'msg'=>$request->system.'证书模板保存失败']);
        }
        return response()->json(['status' => 100, 'msg' =>$request->system.'证书模板保存成功']);
    }

    /**证书模板详情**/
    public function TemplateDetail(Request $request){
        $flighs = SystemTemplate::where('id',$request->id)
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flight = $flighs->first()->toArray();
        $flight['attribute'] = json_decode($flighs->first()->attribute,TRUE);
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flight]);
    }

    /**证书模板修改**/
    public function TemplateEdit(Request $request){
        if(!$request->system || !$request->type){
            return response()->json(['status'=>101,'msg'=>'证书体系或证书类型不能为空']);
        }
        $flighs = SystemTemplate::find($request->id);
        if($request->hasFile('file')){
            $oldImg = $flighs->image;
            $data = array(
                'file_path'=>$request->system,
                'file_size'=>4194304,
                'file_etsn'=>['png','jpg'],
            );
            $path = SystemTemplate::TemplateImg($request->file('file'),$data,'template');
            if($path['status'] == '101'){
                return response()->json($path);
            }
            $flighs->image = $path['data'];
            $flighs->copy  = 0;
        }
        $flighs->system   = $request->system;
        $flighs->attribute= $request->attribute;
        $flighs->type      = $request->type;
        if(!$flighs->save()){
            if(isset($path)){
                Storage::disk('template')->delete($path['data']);
            }
            return response()->json(['status'=>101,'msg'=>$request->system.'证书模板修改失败']);
        }
        if(isset($oldImg)){
            Storage::disk('template')->delete($oldImg);
        }
        return response()->json(['status' => 100, 'msg' =>$request->system.'证书模板修改成功']);
    }

    /**证书模板删除**/
    public function TemplateDelt(Request $request){
        $flights = SystemTemplate::find($request->id);
        if($flights->copy != 1){
            $flight = Storage::disk('template')->delete($flights->image);
            if($flight == false){
                return response()->json(['status'=>101,'msg'=>$flights->system.'证书原背景删除失败']);
            }
        }
        if(!$flights->delete()){
            return response()->json(['status'=>101,'msg'=>'证书模板删除失败']);
        }
        return response()->json(['status'=>100,'msg'=>'证书模板删除成功']);
    }

    /**证书模板复制**/
    public function TemplateCopy(Request $request){
        $flight = SystemTemplate::find($request->id);
        $flighs = new SystemTemplate;
        $flighs->system   = $flight->system.('—副本');
        $flighs->attribute= $flight->attribute;
        $flighs->image    = $flight->image;
        $flighs->type     = $flight->type;
        $flighs->copy     = 1;
        if(!$flighs->save()){
            return response()->json(['status'=>101,'msg'=>$request->system.'证书模板复制失败']);
        }
        return response()->json(['status' => 100, 'msg' =>$request->system.'证书模板复制成功']);
    }

    /**证书模板删除**/
    public function TemplateBase(Request $request){
        $file = $request->file;
        $mime_type= getimagesize($file);
        $base64_data = base64_encode(file_get_contents($file));
        $base64_file = 'data:'.$mime_type['mime'].';base64,'.$base64_data;
        return $base64_file;
    }
}
