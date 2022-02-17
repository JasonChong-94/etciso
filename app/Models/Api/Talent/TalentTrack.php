<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/5/24
 * Time: 10:57
 */
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Talent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
class TalentTrack extends Model{

    /**定义表名**/
    protected $table = 'talent_track';
    //自动维护时间戳  默认是'true'
    public $timestamps = false;
    /**表单提交验证**/
    protected function FormValidation($request){
        $messages = [
            'id.integer'  => '人员id类型错误',
            'date.required'  => '确少日期',
            'contents.required'  => '确少内容',
            'state.required'  => '缺少状态',
            'state.integer'  => '状态类型错误',

        ];
        $rules = [
            'id' => 'required|integer',
            'date' => 'required',
            'contents' => 'required',
            'state' => 'required|integer',
        ];
        $validator = Validator::make($request,$rules,$messages);
        if ($validator->fails()) {
            $data['type']  = false;
            $data['error'] = $validator->errors()->first();
            return $data;
        }else{
            $data['type']  = true;
            return $data;
        }
    }
    protected function list($where=[],$limit=15){
        $filed=[
            'id',
            't_id',
            'date',
            'contents',
            'person',
        ];
        $list=TalentTrack::
            where($where)
            ->select($filed)
            ->orderBy('id','desc')
            ->paginate($limit);
//        dump(DB::getQueryLog());
        return $list;
    }
}
