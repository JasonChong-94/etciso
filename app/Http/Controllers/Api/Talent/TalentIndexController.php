<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/5/20
 * Time: 13:51
 */

namespace App\Http\Controllers\Api\Talent;

use App\Http\Controllers\Controller;
use App\Models\Api\Talent\Talent;
use App\Models\Api\User\UserBasic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
date_default_timezone_set('Asia/Shanghai');
class TalentIndexController extends Controller
{
    //导入数据
    public function import(Request $request){
                /*$data = array(
                    ['','QMS1',0,'2',' 1112515','qq'],
                    ['','QMS2',1,'2','111521215'],
                    ['','QMS3',0,'2','121312331','1','1','s','打学'],
                    ['','QM S4',0,'2',' 111235 3315 '],
                    ['','QMS5',0,'2','11513323 315'],
                    ['',' QMS6 ',0,'2','12131231515'],
                );*/
        $data = json_decode($request->data,true );
        unset($data[0]);
        unset($data[1]);
        if(empty($data)){
            return response()->json(['status'=>101,'msg'=>'表格数据为空!']);
        }
        if(count($data)>1000){
            return response()->json(['status'=>101,'msg'=>'每次提交数据不能超过1000条']);
        };
        $count = array_count_values (array_column($data,4));//取出第四个元素电话号码，并对所有的值进行统计
        if(max($count)>1){  //大于1说明有重复手机号
            $repeat=array_search(max($count),$count);
            return response()->json(['status'=>101,'msg'=>'表格中号码'.$repeat.'有重复']);
        }
        $kname=[
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
            'remarks',//备注
        ];
        $res=[];
        $fail=[];
        $typeWhere= array(
            ['xm_id','=',0],
        );
        $typeFile = array(
            'xiangmu',
        );
        //QMS/EMS/EC/OHSMS/ECPSC/养老服务/YY/ISMS/FSMS/HACCP/EIMS/IECE/HSE/SA8000/IPT/EQC/EGSQ/CMS/EQTM/物业服务/家政服务/收费或合同基础上的生产服务/AMS
        $project = Talent::project($typeFile,$typeWhere)->toArray();//领域
//        $allArr=['QMS','EMS'];
        foreach (array_merge($data) as $k=>&$v){
            unset($v[0]);
            $o_res=array_pad($v,14,null);//填充数组
            $res[]=array_combine($kname, $o_res);
            $res[$k]['created_at']=date('Y-m-d H:i:s',time());
            $res[$k]['updated_at']=date('Y-m-d H:i:s',time());
            //验证领域
            if($res[$k]['system']){
                $system=explode('/',$res[$k]['system']);
                for($n=0;$n<count($system);$n++){
                    if (!in_array($system[$n], $project)) {
                        $m=$k+3;
                        $fail[]= '第'.$m.'行注册领域有误';
                    }
                }
            }
            if($res[$k]['ready']){
                $ready=explode('/',$res[$k]['ready']);
                for($io=0;$io<count($ready);$io++){
                    if (!in_array($ready[$io], $project)) {
                        $shu=$k+3;
                        $fail[]= '第'.$shu.'行待考领域有误';
                    }
                }
            }

        }
        $messages = [
            '*.name.required' => '姓名缺少',
            '*.telephone.unique'=>'系统已存在该手机号',
            '*.pp_edct.in' => '学历错误',
        ];
        $validator = Validator::make($res, [
            '*.name' => 'required|string|max:255',
            '*.telephone' => 'unique:talent',
            '*.pp_edct' => Rule::in([null,'大专','本科','研究生硕士','研究生博士']),
        ],$messages);

        if ($validator->fails()) {
            $msg=$validator->errors()->all();
            $key=$validator->errors()->keys();
            for ($i=0;$i<count($msg);$i++){
                $num=substr($key[$i],0,strpos($key[$i], '.'))+3;
                $fail[]='第'.$num.'行'.$msg[$i];
            }
        }

        if(!empty($fail)){
            return response()->json([
                'msg' => $fail,
                'status'=>101
            ]);
        }
        try{
            $ok=DB::table('talent')->insert($res);
            if($ok){
                return response()->json([
                    'msg' => '成功',
                    'status'=>100
                ]);
            }
        }catch (\Exception $e){
            $find = 'SQLSTATE[23000]';
            if(strpos($e->getMessage(),$find)!==false){
                return response()->json(['status'=>101,'msg'=>'有电话重复']);
            }else{
                return response()->json(['status'=>101,'msg'=>'失败,'.$e->getMessage()]);
            }
        }

    }
    //储备人员列表
    public function list(Request $request){
        $status=$request->input('status',1);
        if($request->name){
//            $where[]=['name','=',$request->name];
            $where[] = ['name', 'like', '%'.$request->name.'%'];
        }
        $where[]=['status','=',$status];
        $res = Talent::list($where,$request->limit,$request->sortfiled,$request->sort);
        if($res->first()){
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
        }else{
            return response()->json(['status'=>101,'msg'=>'无记录']);
        }
    }
    //更改作废状态
    public function up_status(Request $request){
        $id=$request->input('id');
        $status=$request->input('status');
        if(!intval($id)){
            return response()->json(['status'=>101,'msg'=>'id类型错误']);
        }
        if(!$status){
            return response()->json(['status'=>101,'msg'=>'缺少状态']);
        }
        $res=DB::table('talent')
            ->where('id', $id)
            ->update(['status' => $status]);
        if($res){
            return response()->json(['status'=>100,'msg'=>'请求成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'失败']);
        }
    }
    //新增;编辑
    public function save(Request $request){

        $res=Talent::FormValidation($request->all());
        if($res['type'] == false){
            return response()->json(['status'=>101,'msg'=>$res['error']]);
        }
        if($request->id){
            $check = Talent::find($request->id);
        }else{
            $check=new Talent;
        }
        $check->name=$request->name;
        $check->sex=$request->sex;
        $check->age=$request->age;
        $check->telephone = $request->telephone;
        $check->system = $request->system;
        $check->ready=$request->ready;
        $check->recruit=$request->recruit;
        $check->pp_edct=$request->pp_edct;
        $check->school=$request->school;
        $check->pp_major=$request->pp_major;
        $check->title=$request->title;
        $check->work_unit=$request->work_unit;
        $check->postal_site=$request->postal_site;
        $check->remarks=$request->remarks;
        if($check->save()){
            return response()->json(['status'=>100,'msg'=>'成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'失败']);
        }
    }
    //人员转正
    public function official(Request $request){
        $id=$request->input('id');
        if(!$id){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少人员id'
            ]);
        }
        $user = Talent::find($id);
        if(!$user){
            return response()->json(['status'=>101,'msg'=>'该人员不存在']);
        }
        $user_basic=new UserBasic;
        $user_basic->name=$user->name;
        $user_basic->username=$user->name;
        $user_basic->sex=$user->sex?$user->sex:0;
        $user_basic->telephone = $user->telephone;
        $user_basic->pp_edct=$user->pp_edct;
        $user_basic->school=$user->school;
        $user_basic->pp_major=$user->pp_major;
        $user_basic->title=$user->title;
        $user_basic->work_unit=$user->work_unit;
        $user_basic->postal_site=$user->postal_site;
        $user_basic->us_type=$request->us_type;//人员类别
        $user_basic->nmbe=$request->nmbe;//合同编号
        $user_basic->nmbe_st=$request->nmbe_st;//合同签订时间
        $user_basic->nmbe_et=$request->nmbe_et;//合同到期时间
        if($user_basic->save()){
            $user->user_id=$user_basic->id;
            $user->save();
            return response()->json(['status'=>100,'msg'=>'成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'失败']);
        }
    }
}
