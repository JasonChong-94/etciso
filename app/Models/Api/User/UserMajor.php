<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
class UserMajor extends Model{

    /**定义表名**/
    protected $table = 'major_userm';

    public $timestamps = false;

    /****/
    protected function UserMajor($cndtn,$where,$major){
        $flighs = UserMajor::select($cndtn)
            ->where($where)
            ->when($major,function ($query) use ($major) {
                return  $query->whereIn('major_code',$major);
            })
            ->get();
        return($flighs);
    }

    /**人员类型转换**/
    protected function UserType($value){
        if(isset($value['us_qlfts']))
        switch ($value['us_qlfts']) {
            case '01':
                $value['us_qlfts'] = '高级审核员';
                break;
            case '02':
                $value['us_qlfts'] = '审核员';
                break;
            case '03':
                $value['us_qlfts'] = '实习审核员';
                break;
            case '04':
                $value['us_qlfts'] = '技术专家';
                break;
            case '05':
                $value['us_qlfts'] = '高级审查员';
                break;
            case '06':
                $value['us_qlfts'] = '审查员';
                break;
            case '07':
                $value['us_qlfts'] = '主任审核员';
                break;
            default:
                $value['us_qlfts'] = '其他';
        }
        if(isset($value['type']))
        $value['type']  = $value['type'] == 1 ? '专职':'兼职';
        if(isset($value['group_abty']))
            $value['group'] = $value['group_abty'] == 1 ? '是' : '否';
        if(isset($value['ealte_abty']))
            $value['ealte']= $value['ealte_abty'] == 1 ? '是' : '否';
        return($value);
    }

    /**表单提交验证**/
    protected function FormValidation($request){
        $messages = [
            'major_name.required'  => '认证领域不能为空',
            'major_m.required' => '认证标识不能为空',
            'major_m.integer' => '认证标识有误',
            'major_code.required' => '专业代码不能为空',
            'major_code.min' => '专业代码有误',
            'major_code.max' => '专业代码有误',
            'major_time.date' => '评定时间格式不正确',
        ];
        $rules = [
            'major_name' => 'required|string',
            'major_m' => 'required|integer',
            'major_code' => 'required|min:8|max:9',
            'major_time' => 'date',
        ];
        $validator = Validator::make($request,$rules,$messages);
        if ($validator->fails()) {
            $data['type']  = false;
            $data['error'] = $validator->errors()->all();
            return($data);
        }else{
            $data['type']  = true;
            return($data);
        }
    }
}
