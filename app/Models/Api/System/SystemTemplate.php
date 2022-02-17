<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\System;
use Illuminate\Database\Eloquent\Model;

class SystemTemplate extends Model{

    /**定义表名**/
    protected $table = 'template';

    public $timestamps = false;

    /**证书模板**/
    protected function TemplateIndex($cndtn,$where){
        $flighs = SystemTemplate::select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }

    /**模板背景**/
    protected function TemplateImg($file,$data,$disk){
        if($file->getClientSize() < $data['file_size']){
            //if(in_array($file->getClientOriginalExtension(),$data['file_etsn'])){
                $path = $file->storeAs($data['file_path'],date("YmdHis").'.'.$file->getClientOriginalExtension(),$disk);
                if(!$path){
                    return(['status'=>101,'msg'=>'图片保存失败']);
                }
                return(['status'=>100,'msg'=>'上传成功','data'=>$path]);
/*            }else{
                return(['status'=>101,'msg'=>'上传图片格式不正确']);
            }*/
        }else{
            return(['status'=>101,'msg'=>'上传背景超过4M']);
        }
    }
}
