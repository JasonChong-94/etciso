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

class MarketContacts extends Model{

    /**定义表名**/
    protected $table = 'contacts';

    public $timestamps = false;

    /**客户联系人**/
    protected function IndexContacts($cndtn,$where){
        $flighs = MarketContacts::select($cndtn)
            ->leftJoin('contacts_type', 'contacts_type.id', '=', 'contacts.type')
            ->where($where)
            ->orderBy('contacts.state','desc')
            ->get();
        return($flighs);
    }

    /**表单提交验证**/
    protected function FormValidation($request){
        $messages = [
            'phone.required' => '手机号码不能为空',
            'name.required'  => '联系人姓名不能为空',
        ];
        $rules = [
            'name'  => 'required|string|max:255',
            'phone' => 'required',
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
}
