<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\System;
use Illuminate\Database\Eloquent\Model;

class SystemMajor extends Model{

    /**定义表名**/
    protected $table = 'major';

    public $timestamps = false;

    /**专业代码**/
    protected function IndexMajor($cndtn,$where,$limit='',$major=''){
        $flighs = SystemMajor::select($cndtn)
            ->where($where)
            ->when($major,function ($query) use ($major) {
                return  $query->whereIn('b_code',$major);
            })
            ->when($limit,function ($query) use ($limit) {
                return $query->orderBy('e_name','desc');
            })
            ->when(!$limit,function ($query) use ($limit) {
                return  $query->get();
            })
            ->when($limit,function ($query) use ($limit) {
                return $query->paginate($limit);
            });
        return($flighs);
    }

    /**CNAS专业代码**/
    protected function CnasMajor($flights)
    {
        if(!empty($flights->first()->major_code)){
            $majorField = array(
                'b_code',
                'b_range',
                'n_old',
            );
            if(strpos($flights->first()->major_code,'N') !== false){
                $majorWhere  = array(
                    ['e_name','=',$flights->first()->rztx],
                    ['n_old','=',1],
                );
                $majorCode= explode(";",str_replace("N","",$flights->first()->major_code));
                $major = $majorCode;
            }else{
                $majorWhere  = array(
                    ['e_name','=',$flights->first()->rztx],
                    ['n_old','=',0],
                );
                $majorCode= explode(";",$flights->first()->major_code);
                $major = $majorCode;
            }
            $flights->first()->major_code = SystemMajor::IndexMajor($majorField,$majorWhere,'',$major);
        }
        return($flights);
    }
}
