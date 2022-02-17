<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Market;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MarketCustomer extends Model{

    /**定义表名**/
    protected $table = 'khxx';

    public $timestamps = false;

    public function marketContacts()
    {
        return $this->hasMany('App\Models\Api\Market\MarketContacts','kh_id','id');
    }

    /**查询客户**/
    protected function QueryCustomer($cndtn,$where,$limit,$sortField,$sort,$time){
        $flighs = MarketCustomer::
        leftJoin('khxx_nature', 'khxx_nature.code', '=', 'khxx.qyxz')
            ->leftJoin('money_type', 'money_type.code', '=', 'khxx.zczb_bz')
            ->leftJoin('khxx_type', 'khxx_type.id', '=', 'khxx.khlx')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'khxx.fzjg')
            ->where($where)
            ->where(function ($query) use ($time) {
                if(!empty($time)){
                    foreach($time as $key => $vel){
                        $query->whereBetween($key, $vel);
                    }
                }
            })
            ->select($cndtn)
            ->orderBy($sortField,$sort)
            ->paginate($limit);
        return($flighs);
    }

    /**修改客户**/
    protected function EditCustomer($where,$data){
        $flighs = MarketCustomer::where($where)
            ->update($data);
        return($flighs);
    }

    /**表单提交验证**/
    protected function FormValidation($request){
        $messages = [
            'name.required'  => '客户名称不能为空',
            'legal.required' => '法人不能为空',
            'code.alpha_num' => '信用代码格式错误',
            'nature.required'=> '企业性质不能为空',
/*            'money.required' => '注册资本不能为空',
            'currency.required'=> '货币种类不能为空',*/
            'number.integer' => '企业人数不能为空',
            'register.required'=> '注册地址不能为空',
            'register_code.required'=> '注册邮编不能为空',
            'postal.required'=> '通讯地址不能为空',
            'postal_code.required'=> '通讯邮编不能为空',
            'office.required' => '办公地址不能为空',
            'office_code.required' => '办公邮编不能为空',
            'type.required'   => '客户类型不能为空',
            'region.required' => '所属区域不能为空',
            'person.required' => '所有者不能为空',
        ];
        $rules = [
            'name'  => 'required|string|max:255',
            'legal' => 'required',
            'code'  => 'alpha_num',
            'nature'=> 'required',
/*            'money' => 'required',
            'currency'=> 'required',*/
            'number' => 'integer',
            'register'=> 'required',
            'register_code'=> 'required',
            'postal'=> 'required',
            'postal_code'=> 'required',
            'office' => 'required',
            'office_code' => 'required',
            'type'   => 'required',
            'region' => 'required',
            'person' => 'required',
        ];
        $validator = Validator::make($request->all(),$rules,$messages);
        if ($validator->fails()) {
            $data['type']  = false;
            $data['error'] =  $validator->errors();
            return($data);
        }else{
            $data['type']  = true;
            return($data);
        }
    }

    /**批量更新**/
    protected function UpdateBatch($multipleData = [])
    {
        try {
            if (empty($multipleData)) {
                throw new \Exception("数据不能为空");
            }
            $tableName = DB::getTablePrefix() . $this->getTable(); // 表名
            $firstRow  = current($multipleData);

            $updateColumn = array_keys($firstRow);
            // 默认以id为条件更新，如果没有ID则以第一个字段为条件
            $referenceColumn = isset($firstRow['id']) ? 'id' : current($updateColumn);
            unset($updateColumn[0]);
            // 拼接sql语句
            $updateSql = "UPDATE " . $tableName . " SET ";
            $sets      = [];
            $bindings  = [];
            foreach ($updateColumn as $uColumn) {
                $setSql = "`" . $uColumn . "` = CASE ";
                foreach ($multipleData as $data) {
                    $setSql .= "WHEN `" . $referenceColumn . "` = ? THEN ? ";
                    $bindings[] = $data[$referenceColumn];
                    $bindings[] = $data[$uColumn];
                }
                $setSql .= "ELSE `" . $uColumn . "` END ";
                $sets[] = $setSql;
            }
            $updateSql .= implode(', ', $sets);
            $whereIn   = collect($multipleData)->pluck($referenceColumn)->values()->all();
            $bindings  = array_merge($bindings, $whereIn);
            $whereIn   = rtrim(str_repeat('?,', count($whereIn)), ',');
            $updateSql = rtrim($updateSql, ", ") . " WHERE `" . $referenceColumn . "` IN (" . $whereIn . ")";
            // 传入预处理sql语句和对应绑定数据
            return DB::update($updateSql, $bindings);
        } catch (\Exception $e) {
            return false;
        }
    }
}
