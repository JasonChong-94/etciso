<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * 免登
 **/
Route::namespace('Api')->group(function () {
    /**免登授权码**/
    Route::any('login/code', 'LoginController@UserCode');
    /**账户登录**/
    Route::any('sign/in', 'LoginController@SignIn');
    /**扫码登录**/
    Route::any('wechat/sign', 'LoginController@WechatSign');
    /**获取证书**/
    Route::any('certificate/index', 'SystemCertificateController@CertificateIndex');
    /**证书查询**/
    Route::any('certificate/query', 'SystemCertificateController@CertificateQuery');

    Route::any('request/code', 'LoginController@WechatCode');
    /**微信公众号TOKEN验证**/
    Route::any('weixin/check', 'WeixinCheckController@checkToken');
});

/**
 * 微信授权
 **/
Route::group(['prefix'=>'wechat','namespace'=>'Api\WeChat'], function () {
    /**请求微信Code**/
    Route::any('request/code', 'WeixinCodeController@requestCode');
    /**获取微信Code**/
    Route::any('get/code', 'WeixinCodeController@getCode');
    /**发送模板推送**/
    Route::any('send/template', 'WeixinCodeController@sendTemplate');
    /**审核任务推送**/
    Route::any('task/template', 'WeixinCodeController@taskTemplate');
});

/**
 * 企业邮箱
 **/
Route::group(['middleware' => 'token','prefix'=>'email','namespace'=>'Api\Email'], function () {
    /**企业邮url**/
    Route::any('get/url', 'EmailCodeController@getUrl');
    /**邮件未读数**/
    Route::any('get/count', 'EmailCodeController@getCount');
    /**检查帐号**/
    Route::any('check/user', 'EmailCodeController@checkUser');
/*
    Route::any('get/index', 'EmailCodeController@indexDepartment');*/
});


/**
 * 审批
 **/
Route::group(['middleware' => 'token','prefix'=>'approval','namespace'=>'Api\Approval'], function () {
    /**
    审批分组设置
     **/
    /**审批列表**/
    Route::any('set/index','ApprovalSetController@SetIndex');
    /**审批列表**/
    Route::any('group/set','ApprovalSetController@GroupSet');
    /**已停用类型**/
    Route::any('group/stop','ApprovalSetController@GroupStop');
    /**已启用类型**/
    Route::any('group/enable','ApprovalSetController@GroupEnable');
    /**新建分组**/
    Route::any('set/group','ApprovalSetController@SetGroup');
    /**分组编辑**/
    Route::any('group/edit','ApprovalSetController@GroupEdit');
    /**分组删除**/
    Route::any('group/delt','ApprovalSetController@GroupDelt');
    /**分组列表**/
    Route::any('group/index','ApprovalSetController@GroupIndex');
    /**分组排序**/
    Route::any('group/sort','ApprovalSetController@GroupSort');

    /**
    审批类型设置
     **/
    /**类型停用/启用**/
    Route::any('type/stop','ApprovalSetController@TypeStop');
    /**类型移动分组**/
    Route::any('type/move','ApprovalSetController@TypeMove');
    /**类型添加**/
    Route::any('type/add','ApprovalSetController@TypeAdd');
    /**类型详情**/
    Route::any('type/index','ApprovalSetController@TypeIndex');
    /**类型修改**/
    Route::any('type/edit','ApprovalSetController@TypeEdit');
    /**类型删除**/
    Route::any('type/delt','ApprovalSetController@TypeDelt');
    /**节点删除**/
    Route::any('node/delt','ApprovalSetController@NodeDelt');

    /**
    发起审批
     **/
    /**申请审批**/
    Route::any('apply/add','ApprovalApplyController@ApplyAdd');
    /**审批列表**/
    Route::any('apply/index','ApprovalApplyController@ApplyIndex');
    /**审批查询**/
    Route::any('apply/query','ApprovalApplyController@ApplyQuery');
    /**审批详情**/
    Route::any('apply/detail','ApprovalApplyController@ApplyDetail');
    /**审批撤销**/
    Route::any('apply/revoke','ApprovalApplyController@ApplyRevoke');
    /**审批拒绝**/
    Route::any('apply/refuse','ApprovalApplyController@ApplyRefuse');
    /**审批通过**/
    Route::any('apply/adopt','ApprovalApplyController@ApplyAdopt');
});

/**
 * 市场部
 **/
Route::group(['middleware' => 'token','prefix'=>'market','namespace'=>'Api\Market'], function () {
    /**我的客户**/
    Route::any('customer/index','MarketCustomerController@CustomerIndex');
    /**全部客户**/
    Route::any('customer/all','MarketCustomerController@CustomerAll');
    /**客户类型**/
    Route::any('customer/type','MarketCustomerController@CustomerType');
    /**客户查询**/
    Route::any('customer/query','MarketCustomerController@CustomerQuery');
    /**添加客户**/
    Route::any('customer/basic','MarketCustomerController@CustomerBasic');
    /**选择所有者**/
    Route::any('customer/person','MarketCustomerController@CustomerPerson');
    /**保存客户**/
    Route::any('customer/add','MarketCustomerController@CustomerAdd');
    /**客户详情**/
    Route::any('customer/details','MarketCustomerController@CustomerDetails');
    /**查看所有者**/
    Route::any('customer/owner','MarketCustomerController@CustomerOwner');
    /**搜索所有者**/
    Route::any('owner/search','MarketCustomerController@SearchOwner');
    /**企业分配**/
    Route::any('owner/add','MarketCustomerController@OwnerAdd');
    /**修改所有者**/
    Route::any('owner/edit','MarketCustomerController@OwnerEdit');
    /**修改客户**/
    Route::any('customer/edit','MarketCustomerController@CustomerEdit');

    /**企业消费金额到账保存/编辑**/
    Route::any('amount/save','MarketCustomerController@save_amount');
    /**企业消费金额到账列表**/
    Route::any('amount/list','MarketCustomerController@list_amount');
    /**企业消费金额 删除**/
    Route::any('amount/del','MarketCustomerController@del_amount');
    /**企业发票申请**/
    Route::any('invoice/save','MarketCustomerController@save_invoice');
    /**企业发票申请列表**/
    Route::any('invoice/list','MarketCustomerController@list_invoice');
    /**企业发票 删除**/
    Route::any('invoice/del','MarketCustomerController@del_invoice');
    /**企业发票认领**/
    Route::any('invoice/get','MarketCustomerController@get_invoice');
    /**发票申请列表 userid**/
    Route::any('invoice/index','MarketCustomerController@userInvoice');

    /**客户合同**/
    Route::any('contract/index','MarketContractController@ContractIndex');
    /**认证项目**/
    Route::any('contract/project','MarketContractController@ContractProject');
    /**合同添加**/
    Route::any('contract/add','MarketContractController@ContractAdd');
    /**合同详情**/
    Route::any('contract/detail','MarketContractController@ContractDetail');
    /**合同删除**/
    Route::any('contract/del','MarketContractController@ContractDel');
    /**合同提交**/
    Route::any('contract/submit','MarketContractController@ContractSubmit');
    /**审核确认**/
    Route::any('contract/sure','MarketContractController@ContractSure');
    /**项目修改**/
    Route::any('contract/systemedit','MarketContractController@SystemEdit');
    /**项目添加**/
    Route::any('contract/systemadd','MarketContractController@SystemAdd');
    /**项目删除**/
    Route::any('contract/systemdel','MarketContractController@SystemDel');
    /**金额修改**/
    Route::any('contract/moneyedit','MarketContractController@MoneyEdit');
    /**金额添加**/
    Route::any('contract/moneyadd','MarketContractController@MoneyAdd');
    /**金额删除**/
    Route::any('contract/moneydel','MarketContractController@MoneyDel');

    /**客户联系人**/
    Route::any('contacts/index','MarketContactsController@ContactsIndex');
    /**联系人类型**/
    Route::any('contacts/type','MarketContactsController@ContactsType');
    /**联系人添加**/
    Route::any('contacts/add','MarketContactsController@ContactsAdd');
    /**联系人修改**/
    Route::any('contacts/edit','MarketContactsController@ContactsEdit');
    /**默认联系人**/
    Route::any('contacts/state','MarketContactsController@ContactsState');

    /**年审复评**/
    Route::any('review/index','MarketReviewController@ReviewIndex');
    /**全部复评**/
    Route::any('review/all','MarketReviewController@ReviewAll');
    /**年审复评导出**/
    Route::any('review/export','MarketReviewController@ReviewExport');
    /**年审复评类型**/
    Route::any('review/type','MarketReviewController@ReviewType');
    /**年审复评查询**/
    Route::any('review/query','MarketReviewController@ReviewQuery');
    /**审核信息**/
    Route::any('review/details','MarketReviewController@ReviewDetails');
});

/**
 * 技术部
 **/
Route::group(['middleware' => 'token','prefix'=>'examine','namespace'=>'Api\Examine'], function () {
    /**
    项目评审
     **/
    /**未评审项目**/
    Route::any('review/index','ExamineReviewController@ReviewIndex');
    /**未评审查询**/
    Route::any('review/nquery','ExamineReviewController@ReviewQuery');
    /**已评审项目**/
    Route::any('review/adopt','ExamineReviewController@ReviewAdopt');
    /**已评审查询**/
    Route::any('review/select','ExamineReviewController@ReviewSelect');
    /**评审导出**/
    Route::any('review/export','ExamineReviewController@ReviewExport');
    /**多领域类别**/
    Route::any('review/names','ExamineReviewController@ReviewNames');
    /**保存企业信息**/
    Route::any('review/customer','ExamineReviewController@ReviewCustomer');
    /**评审信息详情**/
    Route::any('review/detail','ExamineReviewController@ReviewDetail');
    /**评审信息保存**/
    Route::any('review/add','ExamineReviewController@ReviewAdd');
    /**评审信息修改**/
    Route::any('review/edit','ExamineReviewController@ReviewEdit');
    /**评审信息提交**/
    Route::any('review/submit','ExamineReviewController@ReviewSubmit');
    /**项目编号验证**/
    Route::any('review/project','ExamineReviewController@ProjectCode');
    /**注册号验证**/
    Route::any('review/register','ExamineReviewController@RegisterCode');
    /**认证标准**/
    Route::any('review/rule','ExamineReviewController@ReviewRule');
    /**专业代码**/
    Route::any('review/major','ExamineReviewController@ReviewMajor');
    /**经济代码**/
    Route::any('review/economy','ExamineReviewController@ReviewEconomy');
    /**地区代码**/
    Route::any('review/region','ExamineReviewController@ReviewRegion');
    /**风险等级**/
    Route::any('review/risk','ExamineReviewController@ReviewRisk');
    /**结合类型**/
    Route::any('review/union','ExamineReviewController@ReviewUnion');
    /**变更类型**/
    Route::any('change/type','ExamineReviewController@ChangeType');
    /**企业信息变更**/
    Route::any('change/basic','ExamineReviewController@BasicChange');
    /**企业信息变更列表**/
    Route::any('change/list','ExamineReviewController@BasicList');
    /**审核信息变更**/
    Route::any('review/change','ExamineReviewController@ReviewChange');
    /**审核信息变更列表**/
    Route::any('review/list','ExamineReviewController@ReviewList');
    /**审核信息变更提交**/
    Route::any('change/submit','ExamineReviewController@ChangeSubmit');

    /**
    评定安排
     **/
    /**评定安排列表**/
    Route::any('personnel/index','ExaminePersonnelController@PersonnelIndex');
    /**评定安排查询**/
    Route::any('personnel/select','ExaminePersonnelController@PersonnelSelect');
    /**评定安排导出**/
    Route::any('personnel/export','ExaminePersonnelController@PersonnelExport');
    /**评定安排项目**/
    Route::any('personnel/project','ExaminePersonnelController@PersonnelProject');
    /**评定项目人员**/
    Route::any('personnel/user','ExaminePersonnelController@PersonnelUser');
    /**评定组长匹配**/
    Route::any('personnel/leader','ExaminePersonnelController@PersonnelLeader');
    /**评定组员匹配**/
    Route::any('personnel/member','ExaminePersonnelController@PersonnelMember');
    /**评定组员搜索**/
    Route::any('personnel/uquery','ExaminePersonnelController@UserQuery');
    /**评定组员删除**/
    Route::any('personnel/udele','ExaminePersonnelController@UserDelete');
    /**评定组员保存**/
    Route::any('personnel/uadd','ExaminePersonnelController@UserAdd');
    /**评定安排提交**/
    Route::any('personnel/submit','ExaminePersonnelController@PersonnelSubmit');
    /**评定安排修改**/
    Route::any('personnel/edit','ExaminePersonnelController@PersonnelEdit');
    /**评定安排详情**/
    Route::any('personnel/evte','ExaminePersonnelController@PersonnelEvte');
    /**评定批准**/
    Route::any('personnel/adopt','ExaminePersonnelController@PersonnelAdopt');

    /**
    技术评定
     **/
    /**评定列表**/
    Route::any('evaluate/index','ExamineEvaluateController@EvaluateIndex');
    /**评定查询**/
    Route::any('evaluate/select','ExamineEvaluateController@EvaluateSelect');
    /**评定详情**/
    Route::any('evaluate/detail','ExamineEvaluateController@EvaluateDetail');
    /**企业英文**/
    Route::any('evaluate/english','ExamineEvaluateController@EvaluateEnglish');
    /**评定提交**/
    Route::any('evaluate/submit','ExamineEvaluateController@EvaluateSubmit');
    /**评定修改**/
    Route::any('evaluate/edit','ExamineEvaluateController@EvaluateEdit');
});

/**
 * 认证部
 **/
Route::group(['middleware' => 'token','prefix'=>'inspect','namespace'=>'Api\Inspect'], function () {
    /**
    审核计划安排
    **/
    /**计划列表**/
    Route::any('dispatch/index','InspectDispatchController@DispatchIndex');
    /**计划查询**/
    Route::any('dispatch/query','InspectDispatchController@DispatchQuery');
    /**计划导出**/
    Route::any('dispatch/export','InspectDispatchController@DispatchExport');
    /**阶段添加**/
    Route::any('dispatch/stage','InspectDispatchController@DispatchStage');
    /**结合审核**/
    Route::any('dispatch/union','InspectDispatchController@DispatchUnion');
    /**结合取消**/
    Route::any('dispatch/cancel','InspectDispatchController@DispatchCancel');
    /**项目安排**/
    Route::any('dispatch/project','InspectDispatchController@DispatchProject');
    /**项目人员**/
    Route::any('dispatch/user','InspectDispatchController@DispatchUser');
    /**人员修改**/
    Route::any('dispatch/uedit','InspectDispatchController@UserEdit');
    /**人员删除**/
    Route::any('dispatch/udele','InspectDispatchController@UserDelete');
    /**组长匹配**/
    Route::any('dispatch/group','InspectDispatchController@DispatchGroup');
    /**组员匹配**/
    Route::any('dispatch/member','InspectDispatchController@DispatchMember');
    /**人员搜索**/
    Route::any('dispatch/uquery','InspectDispatchController@UserQuery');
    /**人员专业**/
    Route::any('dispatch/umajor','InspectDispatchController@UserMajor');
    /**时间验证**/
    Route::any('dispatch/time','InspectDispatchController@TimeMatch');
    /**人员保存**/
    Route::any('dispatch/uadd','InspectDispatchController@UserAdd');
    /**计划详情**/
    Route::any('dispatch/detail','InspectDispatchController@DispatchDetail');
    /**计划保存**/
    Route::any('dispatch/add','InspectDispatchController@DispatchAdd');
    /**计划修改**/
    Route::any('dispatch/edit','InspectDispatchController@DispatchEdit');
    /**计划提交**/
    Route::any('dispatch/submit','InspectDispatchController@DispatchSubmit');
    /**模板匹配**/
    Route::any('template/number','InspectDispatchController@TemplateNumber');
    /**管理体系计划书**/
    Route::any('template/plan','InspectDispatchController@TemplatePlan');
    /**管理体系通知书**/
    Route::any('template/notice','InspectDispatchController@TemplateNotice');
    /**体系模板删除**/
    Route::any('template/del','InspectDispatchController@TemplateDelete');

    Route::any('template/state','InspectDispatchController@TemplateState');

    /**
    审核计划安排
     **/
    /**计划列表**/
    Route::any('task/index','InspectTaskController@index');
    /**计划查询**/
    Route::any('task/query','InspectTaskController@query');
    /**列表详情**/
    Route::any('task/detail','InspectTaskController@detail');
    /**条款添加**/
    Route::any('task/add','InspectTaskController@add');

    /**
    审核数据上报
     **/
    /**数据上报**/
    Route::any('report/plan','InspectReportController@ReportPlan');
    /**数据上报导出**/
    Route::any('report/export','InspectReportController@ReportExport');
    /**计划查询**/
    Route::any('plan/query','InspectReportController@PlanQuery');
    /**计划上报详情**/
    Route::any('plan/detail','InspectReportController@PlanDetail');
    /**计划/结果状态修改**/
    Route::any('plan/state','InspectReportController@PlanState');
    /**计划上报保存**/
    Route::any('plan/submit','InspectReportController@PlanSubmit');
    /**计划导出**/
    Route::any('plan/export','InspectReportController@PlanExport');

    /**暂停撤销列表**/
    Route::any('report/revoke','InspectReportController@ReportRevoke');
    /**暂停撤销查询**/
    Route::any('revoke/query','InspectReportController@RevokeQuery');
    /**暂停撤销详情**/
    Route::any('revoke/detail','InspectReportController@RevokeDetail');
    /**暂停撤销保存**/
    Route::any('revoke/add','InspectReportController@RevokeAdd');
    /**暂停撤销删除**/
    Route::any('revoke/delt','InspectReportController@RevokeDelt');
    /**暂停撤销导出**/
    Route::any('revoke/export','InspectReportController@RevokeExport');

    /**证书结果列表**/
    Route::any('report/result','InspectReportController@ReportResult');
    /**证书结果查询**/
    Route::any('result/query','InspectReportController@ResultQuery');
    /**证书结果详情**/
    Route::any('result/detail','InspectReportController@ResultDetail');
    /**证书结果保存**/
    Route::any('result/submit','InspectReportController@ResultSubmit');
    /**证书结果导出(证书信息)**/
    Route::any('result/export','InspectReportController@ResultExport');
    /**证书结果导出(审核结果)**/
    Route::any('result/user','InspectReportController@ResultUser');

    /**
    证书打印
     **/
    /**证书打印列表**/
    Route::any('print/index','InspectPrintController@PrintIndex');
    /**证书打印查询**/
    Route::any('print/query','InspectPrintController@PrintQuery');
    /**证书打印导出**/
    Route::any('print/export','InspectPrintController@PrintExport');
    /**证书打印项目**/
    Route::any('print/project','InspectPrintController@PrintProject');
    /**证书信息详情**/
    Route::any('print/detail','InspectPrintController@PrintDetail');
    /**体系子证书**/
    Route::any('print/sub','InspectPrintController@PrintSub');
    /**上报类型**/
    Route::any('print/type','InspectPrintController@PrintType');
    /**换证原因**/
    Route::any('print/reason','InspectPrintController@PrintReason');
    /**证书信息保存**/
    Route::any('print/add','InspectPrintController@PrintAdd');
    /**证书信息修改**/
    Route::any('print/edit','InspectPrintController@PrintEdit');
    /**证书打印样本**/
    Route::any('print/sample','InspectPrintController@PrintSample');
    /**有机证书打印（中）**/
    Route::any('print/ogac','InspectPrintController@ogaChina');
    /**生成nfc证书**/
    Route::any('sample/copy','InspectPrintController@sampleCopy');

    /**证书暂停撤销列表**/
    Route::any('print/suspend','InspectPrintController@PrintSuspend');
    /**证书暂停撤销查询**/
    Route::any('suspend/query','InspectPrintController@SuspendQuery');
    /**证书暂停撤销导出**/
    Route::any('suspend/export','InspectPrintController@SuspendExport');
    /**证书暂停撤销详情**/
    Route::any('suspend/detail','InspectPrintController@SuspendDetail');
    /**证书暂停撤销原因**/
    Route::any('suspend/reason','InspectPrintController@SuspendReason');
    /**证书暂停撤销保存**/
    Route::any('suspend/add','InspectPrintController@SuspendAdd');

    /**
    审核人员管理
     **/
    /**审核人员列表**/
    Route::any('auditor/index','InspectAuditorController@AuditorIndex');
    /**人员资质代码列表**/
    Route::any('type/major','InspectAuditorController@TypeMajor');
    /**审核人员查询**/
    Route::any('auditor/query','InspectAuditorController@AuditorQuery');
    /**人员导出**/
    Route::any('auditor/export','InspectAuditorController@AuditorExport');
    /**人员基本信息**/
    Route::any('auditor/user','InspectAuditorController@AuditorUser');
    /**人员基本添加/修改**/
    Route::any('user/add','InspectAuditorController@UserAdd');
    /**资质类别**/
    Route::any('auditor/category','InspectAuditorController@AuditorCategory');
    /**人员资质信息**/
    Route::any('auditor/type','InspectAuditorController@AuditorType');
    /**人员资质添加/修改**/
    Route::any('type/add','InspectAuditorController@TypeAdd');
    /**人员资质删除**/
    Route::any('type/del','InspectAuditorController@TypeDel');
    /**人员评定信息**/
    Route::any('auditor/decide','InspectAuditorController@AuditorDecide');
    /**人员评定添加/修改**/
    Route::any('decide/add','InspectAuditorController@DecideAdd');
    /**人员评定删除**/
    Route::any('decide/del','InspectAuditorController@DecideDel');
    /**人员教育经历**/
    Route::any('auditor/study','InspectAuditorController@AuditorStudy');
    /**人员教育添加/修改**/
    Route::any('study/add','InspectAuditorController@StudyAdd');
    /**人员教育删除**/
    Route::any('study/del','InspectAuditorController@StudyDel');
    /**人员工作经历**/
    Route::any('auditor/work','InspectAuditorController@AuditorWork');
    /**人员工作添加/修改**/
    Route::any('work/add','InspectAuditorController@WorkAdd');
    /**人员工作删除**/
    Route::any('work/del','InspectAuditorController@WorkDel');
    /**人员培训经历**/
    Route::any('auditor/train','InspectAuditorController@AuditorTrain');
    /**人员培训添加/修改**/
    Route::any('train/add','InspectAuditorController@TrainAdd');
    /**人员培训删除**/
    Route::any('train/del','InspectAuditorController@TrainDel');
    /**人员专业代码**/
    Route::any('auditor/major','InspectAuditorController@AuditorMajor');
    /**人员代码查询**/
    Route::any('major/query','InspectAuditorController@MajorQuery');
    /**人员代码导出**/
    Route::any('major/export','InspectAuditorController@MajorExport');
    /**人员代码添加/修改**/
    Route::any('major/add','InspectAuditorController@MajorAdd');
    /**人员代码导入（EXCEL）**/
    Route::any('major/import','InspectAuditorController@MajorImport');
    /**人员代码删除**/
    Route::any('major/del','InspectAuditorController@MajorDel');
    /**人员代码转态修改**/
    Route::any('major/state','InspectAuditorController@MajorState');
    /**人员代码删除**/
    Route::any('major/copy','InspectAuditorController@MajorCopy');

    /**
    人员活动
     **/
    /**人员活动列表**/
    Route::any('activity/index','InspectActivityController@ActivityIndex');
    /**人员活动查询**/
    Route::any('activity/query','InspectActivityController@ActivityQuery');
    /**人员活动导出**/
    Route::any('activity/export','InspectActivityController@ActivityExport');

    /**
    人员排班
     **/
    /**人员排班列表**/
    Route::any('agenda/index','InspectAgendaController@AgendaIndex');
    /**人员排班查询**/
    Route::any('agenda/query','InspectAgendaController@AgendaQuery');
    /**人员排班统计**/
    Route::any('agenda/statistic','InspectAgendaController@AgendaStatistic');
    /**人员排班详情**/
    Route::any('agenda/detail','InspectAgendaController@AgendaDetail');
    /**人员排班导入**/
    Route::any('agenda/import','InspectAgendaController@AgendaImport');

    /**
    人员审核代码
     **/
    /**人员审核代码列表**/
    Route::any('code/index','InspectCodeController@CodeIndex');
    /**人员审核代码查询**/
    Route::any('code/query','InspectCodeController@CodeQuery');
    /**人员审核代码详情**/
    Route::any('code/details','InspectCodeController@CodeDetails');
    /**导入人员审核代码**/
    Route::any('code/import','InspectCodeController@CodeImport');

    /**
    专业扩项
     **/
    /**专业扩项列表**/
    Route::any('extension/index','InspectExtensionController@index');
    /**专业扩项详情**/
    Route::any('extension/details','InspectExtensionController@details');
    /**专业扩项删除**/
    Route::any('extension/delete','InspectExtensionController@delete');

});

/**
 * 财务部
 **/
Route::group(['middleware' => 'token','prefix'=>'finance','namespace'=>'Api\Finance'], function () {
    /**到款确认**/
    Route::any('confirm/receipt','FinanceInvoiceController@confirmReceipt');

    Route::any('confirm/invoic','FinanceInvoiceController@Invoice');
    /**收款修改**/
    Route::any('edit/receipt','FinanceInvoiceController@editReceipt');
    /**修改记录**/
    Route::any('index/receipt','FinanceInvoiceController@indexReceipt');
    /**开票确认**/
    Route::any('confirm/invoice','FinanceInvoiceController@confirmInvoice');
    /**到款列表**/
    Route::any('index/amount','FinanceInvoiceController@indexAmount');
    /**开票列表**/
    Route::any('index/invoice','FinanceInvoiceController@indexInvoice');
    Route::any('index/test','FinanceInvoiceController@test');
    Route::any('index/test1','FinanceInvoiceController@test1');
});

/**
 * 账户管理
 **/
Route::group(['middleware' => 'token','prefix'=>'user','namespace'=>'Api\User'], function () {
    /**人员账户列表**/
    Route::any('user/index','UserAccountController@UserIndex');
    /**人员账户查询**/
    Route::any('user/query','UserAccountController@UserQuery');
    /**人员账户添加**/
    Route::any('user/add','UserAccountController@UserAdd');
    /**人员账户查重**/
    Route::any('user/repeat','UserAccountController@UserRepeat');
    /**人员账户详情**/
    Route::any('user/details','UserAccountController@UserDetails');
    /**账户密码修改**/
    Route::any('user/word','UserAccountController@UserWord');
    /**微信账户绑定**/
    Route::any('user/weixin','UserAccountController@userWeixin');
    /**人员职务权限**/
    Route::any('user/role','UserAccountController@UserRole');
    /**人员职务列表**/
    Route::any('role/index','UserAccountController@RoleIndex');
    /**职务权限添加**/
    Route::any('role/add','UserAccountController@RoleAdd');
    /**权限人员添加**/
    Route::any('role/user','UserAccountController@RoleUser');
    /**职务权限详情**/
    Route::any('role/details','UserAccountController@RoleDetails');
    /**职务权限删除**/
    Route::any('role/del','UserAccountController@RoleDel');
    /**人员部门更换**/
    Route::any('user/ment','UserAccountController@UserMent');
    /**人员部门添加**/
    Route::any('ment/add','UserAccountController@MentAdd');
    /**人员部门删除**/
    Route::any('ment/del','UserAccountController@MentDel');
    /**人员考勤列表**/
    Route::any('clock/index','UserAccountController@userClock');

    Route::any('send/message','MessageSendController@sendMessage');

});

/**
 * 系统设置
 **/
Route::group(['middleware' => 'token','prefix'=>'system','namespace'=>'Api\System'], function () {
    /**地区代码**/
    Route::any('region/index','SystemRegionController@RegionIndex');
    /**地区代码**/
    Route::any('region/import','SystemRegionController@RegionImport');
    /**地区代码**/
    Route::any('economy/index','SystemRegionController@EconomyIndex');
    /**地区代码**/
    Route::any('economy/import','SystemRegionController@EconomyImport');
    /**审核阶段**/
    Route::any('stage/index','SystemStageController@StageIndex');
    /**多领域**/
    Route::any('field/index','SystemFieldController@FieldIndex');
    /**人员资质类别**/
    Route::any('ctfcate/index','SystemCtfcateController@CtfcateIndex');

    /**
    证书模板
     **/
    /**证书模板列表**/
    Route::any('template/index','SystemTemplateController@TemplateIndex');
    /**证书模板添加**/
    Route::any('template/add','SystemTemplateController@TemplateAdd');
    /**证书模板详情**/
    Route::any('template/detail','SystemTemplateController@TemplateDetail');
    /**证书模板修改**/
    Route::any('template/edit','SystemTemplateController@TemplateEdit');
    /**证书模板删除**/
    Route::any('template/delt','SystemTemplateController@TemplateDelt');
    /**证书模板拷贝**/
    Route::any('template/copy','SystemTemplateController@TemplateCopy');
    /**证书模板背景转换**/
    Route::any('template/base','SystemTemplateController@TemplateBase');

    /**
    业务代码
     **/
    /**业务代码列表**/
    Route::any('major/index','SystemMajorController@MajorIndex');
    /**
    体系条款
     **/
    /**体系条款列表**/
    Route::any('clause/index','SystemClauseController@index');
    /**体系条款添加**/
    Route::any('clause/add','SystemClauseController@add');
});
/**
 * 人员储备
 **/
Route::group(['middleware' => 'token','prefix'=>'talent','namespace'=>'Api\Talent'], function () {
    Route::any('talent/import','TalentIndexController@import');//批量录入
    Route::any('talent/save','TalentIndexController@save');//人员编辑
    Route::any('talent/list','TalentIndexController@list');//人员列表
    Route::any('talent/official','TalentIndexController@official');//人员转正
    Route::any('talent/up_status','TalentIndexController@up_status');//人员作废状态更改
    Route::any('track/save','TalentTrackController@save');//人员跟进
    Route::any('track/list','TalentTrackController@list');//人员跟进列表
});
/**
 * 微信
 **/
Route::namespace('wechat')->group(function () {
    /**微信签名**/
    Route::any('js_api/getInfo', 'JsapiController@getInfo');
});

/**
 * 外部接口
 **/
Route::group(['prefix'=>'common','namespace'=>'Api\Common'], function () {
    /**企业消费金额到账保存/编辑**/
    Route::any('amount/save','AmountController@save_amount');
    /**企业消费金额到账列表**/
    Route::any('amount/list','AmountController@list_amount');
    /**企业消费金额 删除**/
    Route::any('amount/del','AmountController@del_amount');
    /**企业发票申请**/
    Route::any('invoice/save','AmountController@save_invoice');
    /**企业发票申请列表**/
    Route::any('invoice/list','AmountController@list_invoice');
    /**企业发票 删除**/
    Route::any('invoice/del','AmountController@del_invoice');
    /**企业发票认领**/
    Route::any('invoice/get','AmountController@get_invoice');
    /**发票申请列表 userid**/
    Route::any('invoice/index','AmountController@userInvoice');
    Route::any('invoice/test','AmountController@test');
});