<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Talent;
use App\Models\Api\Examine\ExamineProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
class Talent extends Model{

    /**定义表名**/
    protected $table = 'talent';
    //批量赋值
    protected $fillable = ['name','sex','age','telephone'];

    //链接人员跟进表talent_track
    public function talent_track(){
        return $this->hasOne(TalentTrack::class,'t_id','id')
            ->orderBy('date','desc');
    }
    //修改器 过滤数据
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
    }
    public function setTelephoneAttribute($value)
    {
        $this->attributes['telephone'] = trim($value);
    }
    /**表单提交验证**/
    protected function FormValidation($request){
        $id=isset($request['id'])?$request['id']:null;
        $messages = [
            'name.required'  => '姓名不能为空',
            'telephone.unique' => '电话重复',
        ];
        $rules = [
            'name' => 'required|string',
//            'telephone' => 'unique:talent',
            'telephone' => Rule::unique('talent')->ignore($id),
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
    protected function list($where=[],$limit=15,$sortField='last_date',$sort='desc'){
        $filed=[
            'id',
            'name',
            'sex',
            'age',
            'telephone',
            'system',//注册领域
            'ready',//待考领域
            'recruit',//招聘渠道
            'pp_edct',//人员学历
            'school',//毕业院校
            'pp_major',//专业
            'title',//人员职称
            'work_unit',//工作单位
            'postal_site',//通讯地址
            'remarks',//领域备注
            'state',
            'status',
            'last_date',//上次跟踪时间
        ];
        switch ($sortField)
        {
            case 1:
                $sortField = 'state';
                break;
            case 2:
                $sortField = 'last_date';
                break;
            default:
                $sortField = 'last_date';
        }
        switch ($sort)
        {
            case 1:
                $sort = 'desc';
                break;
            case 2:
                $sort = 'asc';
                break;
            default:
                $sort = 'desc';
        }
        $list=Talent::with('talent_track')
            ->where($where)
            ->select($filed)
            ->orderBy($sortField,$sort)
            ->paginate($limit);
//        dump(DB::getQueryLog());
        return $list;
    }
    protected function project($cndtn,$where){
        $flighs = ExamineProject::select($cndtn)
            ->where($where)
            ->pluck('xiangmu');
        return($flighs);
    }
}
