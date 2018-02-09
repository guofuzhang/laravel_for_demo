<?php
/**
 * @brief 联盟项目发布入口
 */
defined('src.17shanyuan.com') or exit('Access Invalid!');

class project_unionControl extends BaseMemberControl {
    private $pagesize=10;
    private $member_id = null;
    private $offset = 2000;
    private $status = [
        '撤回审核[下线审核]',
        '提交审核',
        '审核通过',
        '审核被拒绝',
        '删除下线'
    ];
    public function __construct()
    {
        parent::__construct();
        $this->member_id = session('member_id');
        if(!isProjectMember($this->member_id) && !isBocMember($this->member_id) && !isUnionAdmin($this->member_id)){//权限检查
            showMessage('权限错误',SHOP_SITE_ROOT_URL.'/boc/index.php','html','succ');
            die();
        }
        Tpl::setDir('project');
        Tpl::setLayout('union_layout');
        if(!in_array($_GET['op'], ['projectlog','add_remark','check_delivery_info','delivery_info','refuseres','public_project_report_list','getsignnum', 'createexcel'])){
            echo '<script src="/data/resource/layui/layui.js"></script>';
        }
        if(in_array($_GET['op'], ['project_money_order_list'])){
            echo '<script src="/data/resource/layui/lay/modules/layer.js"></script>';   
            echo '<link id="layuicss-layer" rel="stylesheet" href="/data/resource/layui/css/modules/layer/default/layer.css?v=3.0.3" media="all">';   
        }
    }

    /**
     * @brief 首页
     */
    public function indexOp()
    {

        Tpl::showpage("index");
    }
    
    /**
     * @brief 发布的项目
     * @author Zhaolun<zhaolun@17shanyuan.com>
     * @date 2017年9月18日12:14:19
     */
    public function projectOp()
    {
        $memberId = $this->member_id;
        $limit = $this->pagesize;
        $page =Service('volunteer')->get_page($limit);
        $model = Model();
        $where="public_project_tmp.publisher_id={$memberId} and if(isnull(public_project.rec_id),1,public_project.project_type=2)";
        $result = $model
            ->table("public_project_tmp,public_project,project_money")
            ->field("SQL_CALC_FOUND_ROWS public_project_tmp.*,group_concat(public_project_tmp.project_union ORDER BY public_project_tmp.rec_id DESC Separator '-') as project_union,group_concat(public_project_tmp.project_name ORDER BY public_project_tmp.rec_id DESC Separator '-') as project_name,group_concat(public_project_tmp.project_addtime ORDER BY public_project_tmp.rec_id DESC Separator '-') as project_addtime,group_concat(public_project_tmp.project_starttime ORDER BY public_project_tmp.rec_id DESC Separator '-') as project_starttime,group_concat(public_project_tmp.project_endtime ORDER BY public_project_tmp.rec_id DESC Separator '-') as project_endtime,public_project.rec_id as rec_id_pp,public_project.project_status,project_money.target_money,project_money.finished_money")
            ->join("left,left")
            ->on("public_project_tmp.project_id=public_project.project_id,public_project_tmp.project_id=project_money.project_id")
            ->where($where)
            ->order("public_project_tmp.project_addtime desc")
            ->group("public_project_tmp.project_id")
            ->limit($page)
            ->select();

        $public_project_model = Model('public_project');
        foreach($result as $k=>$one){
            //格式化
            $project_union = explode("-", $one['project_union']);
            $project_name = explode("-", $one['project_name']);
            $project_addtime = explode("-", $one['project_addtime']);
            $project_starttime = explode("-", $one['project_starttime']);
            $project_endtime = explode("-", $one['project_endtime']);
            $result[$k]['project_union'] = $project_union[0];
            $result[$k]['project_name'] = $project_name[0];
            $result[$k]['project_addtime'] = $project_addtime[0];
            $result[$k]['project_starttime'] = $project_starttime[0];
            $result[$k]['project_endtime'] = $project_endtime[0];

            if(!empty($one['project_status'])){
                $result[$k]['project_status_str'] = $public_project_model->getProjectProcessStatus($one);
            }else{
                $result[$k]['project_status_str'] = '未开始';
            }
            
        }
        $xxx = $model//$xxx统计count1
        ->table("public_project_tmp,public_project,project_money")
            ->field("count(1)")
            ->where($where)
            ->group("public_project_tmp.project_id")
            ->select();
//        var_dump(count($xxx));
        pagecmd('seteachnum', $limit);
        pagecmd('settotalnum', count($xxx));



//
        // error_log(print_r($result,1));
        
        $number = $model->query('SELECT FOUND_ROWS() as num');
        $union_list = Model()->table('union')->field('union_id,union_name')->select();
        $union_list = array_column($union_list, 'union_name', 'union_id');
        Tpl::output('union_list',$union_list);
//        var_dump($union_list);
        $curpage=Service('volunteer')-> get_curpage_size( count($xxx),$limit);
        Tpl::output('result', $result);
//        Tpl::output('page', $model->showpage(6));
        Tpl::showpage('project');
    }

    /**
     * @brief 发布项目
     * @author Zhaolun<zhaolun@17shanyuan.com>
     * @date 2017年9月18日12:23:53
     */
    public function createProjectOp()
    {
        $this->noCache();
        $model = Model();
        if(!$_POST){
            $id = intval($_GET['recid']);
            $type = intval($_GET['type']) == 0 ? 1 : intval($_GET['type']);
            if($id != 0){
                $result = Model()->table("public_project_tmp")->where("project_id={$id}")->order("rec_id desc")->find();
                if($result['support_type'] == DONATION_TYPE_RETURN){
                    $returnInfo = Model('public_project_support_tmp')->getReturnInfo(['project_id' => $id]);

                    $returnInfo = Model('public_project_support')->getReturnInfoSupportNumberLeft($id, $returnInfo);

                    // error_log('returnInfo='.print_r($returnInfo,1));
                    Tpl::output('returnInfo', $returnInfo);

                    if($id>0){
                        $disabled = 'disabled';
                    }else{
                        $disabled = '';
                    }
                    Tpl::output('disabled', $disabled);
                }
                Tpl::output('edit', true);
                Tpl::output('result', $result);
            }
            $admin_info = Model()->table('union_admin')->where(array('member_id'=>$this->member_id))->find();
            $where = array('union_id'=>$admin_info['union_id']);
            $union_list = Model()->table('union')->field('union_id,union_name')->where($where)->select();
            $tag_list = Model()->table("project_tag")->select();
            Tpl::output('union_list',$union_list);
            Tpl::output('tag_list',$tag_list);
            Tpl::output('type', $type);
            Tpl::showpage("create_project1"/*.$type*/);
        }else{
//            var_dump($_POST);
            if(!empty($_POST['public_project_id']) && !empty($_POST['project_id'])){
                self::auditingRefer(2, $_POST['project_id']);
            }

            //用户输入验证
            $validate_result = $this->project_validate($_POST);
            if(!$validate_result){
                showMessage('输入验证错误');
                exit;
            }

            $adminUnion = Model()->table("union_admin,union")->field("union_name")->join("inner")->on("union_admin.union_id=union.union_id")->where(['member_id' => $this->member_id])->find();
            $public_data['publisher_union'] = empty($adminUnion['union_name']) ? '未知联盟' : $adminUnion['union_name'];
//            $public_data['project_addtime'] = TIMESTAMP;
            if(isset($_GET['recid']) || !empty($_GET['recid'])){
                $public_data['project_addtime']=Model('project_union')->get_project_addtime($_GET['recid']);
            }else{
                $public_data['project_addtime'] = TIMESTAMP;
            }
            $public_data['project_updatetime'] = TIMESTAMP;
            $public_data['project_image'] = $_POST['project_image'];
            $public_data['project_type'] = PUBLIC_PROJECT_TYPE_MONEY;
            $public_data['project_name'] = $_POST['project_name'];
            $public_data['project_organizer'] = $_POST['project_organizer'];
            $public_data['project_sponsor'] = $_POST['project_sponsor'];
            $public_data['project_recipient'] = $_POST['project_recipient'];
            $public_data['project_code'] = $_POST['project_code'];
            $public_data['project_account_alipay'] = $_POST['project_account_alipay'];
            $public_data['project_account_wx'] = $_POST['project_account_wx'];
            $public_data['project_starttime'] = strtotime($_POST['project_starttime']);
            $public_data['project_endtime'] = strtotime($_POST['project_endtime']);
            $public_data['target_money'] = floatval($_POST['target_money']);
            $public_data['project_intro'] = $_POST['project_intro'];
            $public_data['project_content'] = $_POST['project_content'];
            $public_data['project_purpose'] = $_POST['project_purpose'];
            $public_data['project_fund_use'] = $_POST['project_fund_use'];
            $public_data['project_beneficiary_info'] = trimRichText($_POST['project_beneficiary_info']);
            $public_data['project_residual_disposal'] = trimRichText($_POST['project_residual_disposal']);
            $public_data['project_mobile'] = $_POST['project_mobile'];
            $public_data['leader_mobile'] = $_POST['leader_mobile'];
            $public_data['project_tag'] = $_POST['project_tag'];
            $public_data['project_thanks_image'] = $_POST['project_thanks_image'];
            $public_data['project_union'] = "";
            $public_data['publisher_id'] = $_SESSION['member_id'];
            $public_data['project_ad_image'] = $_POST['project_ad_image'];
            $public_data['project_mode'] = $_POST['project_mode'];
            $public_data['support_type'] = $_POST['support_type'];
            $public_data['points_money'] = 10;
            $projectId = $_POST['project_id'];
            
            if(empty($projectId)){
                $projectId = -1;
                $pidRes = $model->table("public_project_tmp")->field("project_id")->where("project_id < 0")->order("project_id asc")->find();
                if($pidRes != false){
                    $projectId = $pidRes['project_id'] - 1;
                }
            }
            $model->beginTransaction();
            $insertReturn = true;
            $deleteReturn = true;

            // error_log(print_r($_POST,1));

            if($_POST['support_type'] == DONATION_TYPE_RETURN){
                if($projectId < 0){
                    //格式化回报信息
                    $formatReturnInfo = Service("public_project")->formatReturnInfo($_POST, $projectId);
                    $insertReturn = Service('public_project')->addReturnInfoTmp($formatReturnInfo, $projectId);
                    $deleteReturn = true;
                }else{
                    $formatReturnInfoDelete = Service("public_project")->formatReturnInfo($_POST, $projectId, 3);
                    if(empty($formatReturnInfoDelete)){
                        $deleteReturn = true;
                    }else{
                        // error_log('formatReturnInfoDelete='.print_r($formatReturnInfoDelete,1));
                        $deleteReturn = Model('public_project_support_tmp')->deleteReturnById($formatReturnInfoDelete);    
                    }

                    $formatReturnInfoAdd = Service("public_project")->formatReturnInfo($_POST, $projectId, 1);
                    if(empty($formatReturnInfoAdd)){
                        $insertReturn = true;
                    }else{
                        // error_log('formatReturnInfoAdd='.print_r($formatReturnInfoAdd,1));
                        $insertReturn = Service('public_project')->addReturnInfoTmp($formatReturnInfoAdd, $projectId, true);    
                    }
                    
                }
            }

            $public_data['project_id'] = $projectId;
            $public_data['project_state'] = 0;
            $public_project_id = $model->table('public_project_tmp')->insert($public_data);
            //var_dump($public_project_id,$_POST);
            $notice = "添加";
            if(!empty($_POST['public_project_id'])){
                $notice = "修改";
                Service('projectLog')->insertLog('编辑了项目信息', $projectId);
                if(intval($_POST['project_id']) <= 0){
                    $model->table('public_project_tmp')->where("rec_id='{$_POST['public_project_id']}'")->delete();
                }else{
                    $model->table('public_project_tmp')->where("project_id='{$projectId}'")->update(['project_state' => 0]);
                }
            }else{
                Service('projectLog')->insertLog('提交了项目信息，进入待提审', $projectId);
            }

            // error_log('public_project_id='.$public_project_id);
            // error_log('insertReturn='.$insertReturn);
            // error_log('deleteReturn='.$deleteReturn);
            if(!empty($public_project_id) && $insertReturn && $deleteReturn){
                $model->commit();
                //exit("suc");
                showMessage($notice.'成功','index.php?act=project_union&op=project', 'html','succ',1, $time =2000);
            }else{
                $model->rollback();
                //exit("err");
                showMessage($notice.'失败','index.php?act=project_union&op=project', 'html','succ',1, $time =2000);
            }
//            $money_data=array(
//                'target_money'=>floatval($_POST['target_money']),
//                'points_money'=>intval($_POST['points_money'])
//            );
//            $public_data['project_addtime'] = TIMESTAMP;
//            $public_data['project_starttime'] = strtotime($_POST['project_starttime']);
//            $public_data['project_endtime'] = strtotime($_POST['project_endtime']);
//            $public_data['project_name'] =$_POST['project_name'];
//            $public_data['project_sponsor'] =$_POST['project_sponsor'];
//            $public_data['project_image'] =$_POST['project_image'];
//            $public_data['project_intro'] = $_POST['project_intro'];
//            $public_data['project_content'] =$_POST['project_content'];
//            $public_data['project_updatetime'] = TIMESTAMP;
//            $public_data['project_type'] = PUBLIC_PROJECT_TYPE_MONEY;
//            $public_data['publisher_id'] = $_SESSION['member_id'];
//            $public_data['project_mobile'] =$_POST['project_mobile'];
//            $public_data['project_mode'] = $_POST['project_mode'];
//            $union_data = $_POST['union'];
//            $union_model = Model('project_union');
//
//            $model->beginTransaction();
//            $project_money_id = $model->table('project_money')->insert($money_data);
//            $public_data['project_id'] = $project_money_id;
//            $public_project_id = $model->table('public_project')->insert($public_data);
//
//            // 记录联盟信息
//            $union_model->add_union($union_data,$public_project_id);
//
//            if(!empty($project_money_id) && !empty($public_project_id)){
//                $model->commit();
//                showMessage('添加成功','index.php?act=auditing&op=isAuditing', 'html','succ',1, $time =2000);
//            }else{
//                $model->rollback();
//                showMessage('添加失败','index.php?act=auditing&op=createProject', 'html','succ',1, $time =2000);
//            }
        }
    }
    
    private function project_validate($data){
        //项目名称1-16字
        //project_name
        if(empty(trim($data['project_name'])) || mb_strlen($data['project_name']) > 16){
            return false;
        }

        //主办方1-12字
        //project_sponsor
        if(empty(trim($data['project_sponsor'])) || mb_strlen($data['project_sponsor']) > 12){
            return false;
        }

        //发起人0-12字
        //project_organizer
        if(mb_strlen($data['project_organizer']) > 12){
            return false;
        }

        //项目备案号0-32字
        //project_code
        if(mb_strlen($data['project_code']) > 32){
            return false;
        }

        //善款接收人0-15字
        //project_recipient
        if(mb_strlen($data['project_recipient']) > 32){
            return false;
        }

        //善款接收账号(支付宝)0-30字
        //project_account_alipay
        if(mb_strlen($data['project_account_alipay']) > 32){
            return false;
        }

        //善款接收账号(微信)0-30字
        //project_account_wx
        if(mb_strlen($data['project_account_wx']) > 32){
            return false;
        }

        //募集开始时间应该大于当天，并且小于募集结束时间
        //募集结束时间应该大于当天
        //project_starttime
        //project_endtime
        if(empty($data['project_starttime']) || empty($data['project_endtime'])){
            return false;
        }else{
            $today_begin = strtotime(date('Y-m-d', TIMESTAMP).' 0:0:0');
            $today_end = strtotime(date('Y-m-d', TIMESTAMP).' 23:59:59');
            $project_starttime = strtotime($data['project_starttime']);
            $project_endtime = strtotime($data['project_endtime']);
            if($data['project_id'] <= 0){
                if($project_starttime < $today_begin){
                    return false;
                }
            }
            
            if($project_endtime <= $today_end){
                return false;
            }

            if($project_starttime >= $project_endtime){
                return false;
            }
        }

        //募集金额在1千-10万之间
        //target_money
        if(($data['target_money'] < 1000) || ($data['target_money'] > 100000)){
            return false;
        }

        //项目宣传图不能为空
        //project_ad_image
        if(empty($data['project_ad_image'])){
            return false;
        }

        //项目头图不能为空，最多6张
        //project_image
        if(empty($data['project_image'])){
            return false;
        }else{
            $project_images = explode(',', $data['project_image']);
            if(count($project_images) > 6){
                return false;
            }
        }

        //项目简介1-50字
        //project_intro
        if(empty(trim($data['project_intro'])) || mb_strlen($data['project_intro']) > 50){
            return false;
        }

        //项目详情不能为空
        //project_content
        if(empty(trimRichText($data['project_content']))){
            return false;
        }

        //款项用途不能为空
        //project_fund_use
        if(empty(trimRichText($data['project_fund_use']))){
            return false;
        }

        //募捐目的1-100字
        //project_purpose
        if(empty(trim($data['project_purpose'])) || mb_strlen($data['project_purpose']) > 100){
            return false;
        }

        //款项用途不能为空
        //project_fund_use
        if(empty($data['project_fund_use'])){
            return false;
        }

        //支持方式(自由捐赠,定制捐赠)
        //support_type
        if($data['support_type'] == 1){
            //感谢卡图片不能为空
            //project_thanks_image
            if(empty($data['project_thanks_image'])){
                return false;
            }
        }else if($data['support_type'] == 2){

        }else{
            return false;
        }

        //项目负责人联系方式1-100字
        //leader_mobile
        if(empty(trim($data['leader_mobile'])) || mb_strlen($data['leader_mobile']) > 100){
            return false;
        }

        //用户咨询电话不能为空
        //project_mobile
        if(empty(trim($data['project_mobile']))){
            return false;
        }

        return true;
    }

    /**
     * @brief 判断状态
     */
    static private function auditingRefer($type, $id)
    {
        $table = "public_project_tmp";
        switch ($type){
            case '0':
                $where = "project_state=0 and project_id={$id}";
                break;
            case '1':
                $where = "project_state=1 and project_id={$id}";
                break;
            case '2':
                $where = "project_state!=1 and project_id={$id}";
                break;
            case '4':
                $where = "project_state!=4 and project_id={$id}";
                break;
            case 'to1':
                $where = "(project_state!=2) and project_id={$id}";
                break;
            case 'report':
                $where = "(report_state=0 or report_state=3) and report_id_tmp={$id}";
                $table = "public_project_report_tmp";
                break;
            case 'report1':
                $where = "report_state=1 and report_id_tmp={$id}";
                $table = "public_project_report_tmp";
                break;
            case 'report4':
                $where = "report_state!=4 and report_id_tmp={$id}";
                $table = "public_project_report_tmp";
                break;
            default:
                $where = "project_state=1 and project_id={$id}";
                break;
        }
        $isTrue = Model($table)->where($where)->find();
        if($isTrue == false){
            showMessage("项目不存在或者有变动");
            exit;
        }
    }
    
    /**
     * @brief 修改项目状态
     */
    public function changeStateOp()
    {
        $id = intval($_GET['pid']);
        $state = intval($_GET['state']);
        if(!in_array($state, [0,1,2,3,4]) || $id == 0){
            showMessage('操作有误','', 'html','succ',1, $time = 2000);
        }
        $model = Model();
        $data = [
            'project_state' => $state,
        ];
        $type = 0;
        if($state == 0){
            self::auditingRefer(1, $id);
        }
        if($state == 1){
            self::auditingRefer('to1', $id);
            //更新提审时间
            //需要记录提审的特殊
            $type = 1;
//            $data['project_addtime'] = TIMESTAMP;
        }
        $res = $model->table("public_project_tmp")->where("project_id={$id}")->update($data);
        Service('projectLog')->insertLog($this->status[$state], $id, $type);
        if($res){
            showMessage('操作成功','', 'html','succ',1, $time =2000);
        }else{
            showMessage('操作失败','', 'html','succ',1, $time =2000);
        }
    }
    
    /**
     * @brief 获得项目log
     */
    public function projectLogOp()
    {
        $retArr = [
            'code' => 500,
            'content' => '操作有误'
        ];
        $id = intval($_GET['id']);
        if($id == 0){
            exit(json_encode($retArr));
        }
        $model = Model();
        //$pid = $model->table("public_project_tmp")->field("project_id")->where("rec_id={$id}")->find();
        $where['project_tmp_id'] = $id;
        $result = Model()->field('*,FROM_UNIXTIME(addtime,"%Y-%m-%d %H:%i") as addtime,log_admin,ip')->table('public_project_log')->order("project_log_id desc")->where($where)->select();
        $retArr = [
            'code' => 200,
            'content' => $result
        ];
        exit(json_encode($retArr));
    }
    
    /**
     * @brief 看不同
     */
    public function diffOp()
    {
        $id = $_GET['id'];
        $where = "project_id={$id} and project_state in(0,1)";
        $model = Model();
        $status = $model->table("public_project_tmp")->where($where)->find();
        if($status == false){
            showMessage('该项目尚未更新版本','', 'html','succ',1, $time = 2000);
        }
        $lineRes = $model->table("public_project")->where("project_id={$id} and project_type=2")->find();
        
        $newRes = $model->table("public_project_tmp")->where("project_id={$id}")->order("rec_id desc")->find();
        $union_list = Model()->table('union')->field('union_id,union_name')->where("union_free=1")->select();
        $project_union = Model()->table("project_union")->field("GROUP_CONCAT(union_id) as project_union")->where("project_id={$lineRes['rec_id']}")->find();
        $project_money = Model()->table("project_money")->field("target_money")->where("project_id={$id}")->find();
        $lineRes['project_union'] = $project_union['project_union'];
        $lineRes['target_money'] = $project_money['target_money'];
        $tag_list = Model()->table("project_tag")->select();
        Tpl::output('union_list',$union_list);
        Tpl::output('tag_list',$tag_list);
        Tpl::output('line',$lineRes);
        Tpl::output('new',$newRes);
        Tpl::showpage('create_project_diff');
    }
    
    /**
     * @brief 发送项目汇报用户消息通知
     */
    function send_donation_msgOp()
    {

        $public_project_id= intval($_GET['public_project_id']) ? intval($_GET['public_project_id']) : 0;//获取捐款项目的id
        if($public_project_id<=0){
            showMessage('参数错误:义卖项目id','index.php','html','error');//'参数错误:义卖项目id'
        }

        $report_id= intval($_GET['report_id']) ? intval($_GET['report_id']) : 0;//获取汇报的id
        if($report_id<=0){
            showMessage('参数错误:汇报id','index.php','html','error');//'参数错误:汇报id'
        }

        $model = Model();
        $recid = $model->table("public_project")->field("rec_id")->where("project_id={$public_project_id} and project_type=2")->find();
        // 项目汇报信息
        $report_model = Model('public_project_report');
        $report_detail = $report_model->where(array('report_id'=>$report_id, 'project_id'=>$recid['rec_id']))->find();//根据项目id和汇报id查询
        if(empty($report_detail)){
            showMessage('参数错误:汇报id和项目id不符','index.php','html','error');//'参数错误:汇报id和项目id不符'
        }

        // 项目信息
        $project_condition['rec_id'] = $recid['rec_id'];
        $project_info = $model->table('public_project')->where($project_condition)->field('project_name')->find();
        $project_name = $project_info['project_name'];

        // 项目参与人员列表
        $user_condition['public_project_id'] = $recid['rec_id'];
        $user_condition['order_state'] = array('in', array(ORDER_STATE_PAY,ORDER_STATE_SEND,ORDER_STATE_SUCCESS));
        $user_list = $model->table('project_money_order')->where($user_condition)->field('distinct buyer_id as member_id')->select();

        if (empty($user_list)) {
            showMessage('发送通知失败[没有参与人员]');
        }

        $url = SHOP_SITE_MOBILE_URL.'/index.php?act=syproject&op=syproject_detail&tab=2&public_project_id='.$recid['rec_id'];
        Log::i('send_donation_msg, url='.$url);
        $option = array(
            'message_title' => '项目进展通知',
            'message_body' => '您参与的“'.$project_name.'”项目，有了最新的进展，点击查看详情!',
            'message_link' => $url
        );

        //记录在通知中心
        foreach($user_list as $value)
        {
            $member_id = $value['member_id'];
            $result = Service('message')->sendDonationMsgToMember($member_id, $recid['rec_id'], $option);
        }

        if (!$result) {
            showMessage('发送通知失败[用户消息]');
        }
        //给用户发送Push通知
        $model = Model();
        $message_service = Service("message");
        $model_token = Model('mb_user_token');
        $content = array();
        $content['ticker'] = '项目进展通知';
        $content['title'] = '项目进展通知';
        $content['text'] = '您参与的项目有了最新进展，快去看看吧~~';
        $page_url = $url;
        $content['extra'] = array('loadUrl' => urlencode(SHOP_SITE_MOBILE_URL . '/index.php?act=index&op=notify_page&page_url=' . urlencode($page_url)));

        $receiver_array = array();
        $n = 0;

        foreach ($user_list as $value) {
            $member_token_info = $model_token->getMbUserTokenInfoByMemberId($value['member_id'], 'token,client_type,app_variant');
            $receiver_array[$n]['client_type'] = $member_token_info['client_type'];
            $receiver_array[$n]['alias'] = $member_token_info['token'];
            $receiver_array[$n]['app_variant']=$member_token_info['app_variant'];
            $n++;//500个一组发送
            if ($n == 500 ) {
                $message_service->sendMulitAppMsgByAlias($content, $receiver_array);
                $n = 0;
                $receiver_array = null;
                $receiver_array = array();
                sleep(1);
            }
        }

        if (!empty($receiver_array)) {
            $message_service->sendMulitAppMsgByAlias($content, $receiver_array);
            $n == 0;
            $receiver_array = null;
            $receiver_array = array();
            sleep(1);
        }

        // 更新汇报通知状态
        $nowTime = time();
        $data = array(
            'report_msg_state' => 1,
            'report_msg_time' => $nowTime
        );
        $report_model->where(array('report_id'=>$report_id, 'project_id'=>$recid['rec_id']));
        $result = $report_model->where(array('report_id' => $report_id, 'project_id' => $recid['rec_id']))->update($data);
        $result1 = Model("public_project_report_tmp")->where(array('report_id_tmp' => $report_id, 'project_id' => $public_project_id))->update(['report_msg_state' => 1]);
        if ($result && $result1) {
            showMessage('发送通知成功');
        }
        else{
            showMessage('发送通知失败');
        }
    }
    
    /**
     * @brief 项目下线
     */
    public function offlineOp()
    {
        $project_id = intval($_GET['id']);
        $type = intval($_GET['type']);
        if($project_id*$type == 0){
            exit("项目不存在");
        }
        $model = Model();
        $projectId = -1;
        $pidRes = $model->table("public_project_tmp")->field("project_id")->where("project_id < 0")->order("project_id asc")->find();
        if($pidRes != false){
            $projectId = $pidRes['project_id'] - 1;
        }
        $model->beginTransaction();
        $res = $model->table('public_project_tmp')->where("project_id={$project_id} and project_mode={$type}")->update(['project_state' => 4,'project_id'=>$projectId]);
        $res2 = $model->table("public_project")->where("project_id={$project_id} and project_mode={$type}")->update(['project_state' => 0]);
        $res3 = $model->table("public_project_log")->where("project_tmp_id={$project_id}")->update(['project_tmp_id'=>$projectId]);
        if($res && $res2 && $res3){
            Service('projectLog')->insertLog('项目下线', $projectId, 4);
            $model->commit();
            showMessage('操作成功');
        }else{
            $model->rollback();
            showMessage('操作失败');
        }
    }
    
    /**
     * @brief 删除项目汇报
     */
    public function public_projcet_report_delOp(){
        $this->noCache();
        if(!empty($_GET['report_id'])){
            $model=Model();
            $model->table('public_project_report_tmp')->where(array('report_id_tmp'=>$_GET['report_id_tmp']))->delete();
            if($_GET['report_id_tmp'] > 0){
                $model->table("public_project_report")->where(['report_id' => $_GET['report_id_tmp']])->delete();
            }
            showMessage('操作成功',getReferer());
        }else{
            showMessage('参数错误',getReferer());
        }
    }
    
    /**
     * @brief 获取拒绝原因
     */
    public function refuseResOp()
    {
        $retArr = [
            'code' => 500,
            'content' => '操作有误'
        ];
        $id = intval($_POST['id']);
        if($id == 0){
            exit(json_encode($retArr));
        }
        $type = empty(intval($_POST['type'])) ? 0 : intval($_POST['type']);
        $model = Model();
        $result = $model->table('public_project_log')->field("log_do")->where("project_tmp_id={$id} and log_type=-1 and log_mode={$type}")->order("project_log_id desc")->find();

        $retArr = [
            'code' => 200,
            'content' => $result
        ];
        exit(json_encode($retArr));
    }
    
    /**
     * @brief 项目汇报列表
     */
    public function public_project_report_listOp() {
        $this->noCache();
        $public_project_id =$_GET['id'];
        $model=Model();
        $pagesize = 50;
        $public_project_report_list=$model->table('public_project_report_tmp')->field("admin_id,report_msg_state,report_state,project_id,report_id,report_id_tmp,group_concat(report_title ORDER BY report_id DESC) report_title,group_concat(report_time ORDER BY report_id DESC) report_time")
            ->where(array('project_id'=>$public_project_id))
            ->group("report_id_tmp")
            ->page($pagesize)
            ->select();
        Tpl::output('public_project_report_list',$public_project_report_list);  //列表数据
        Tpl::output('show_page',$model->showpage(6));
        Tpl::showpage('public_project_report_list');
    }
    
    /**
     * @brief 修改汇报的状态
     */
    public function changeReportStateOp()
    {
        $state = intval($_GET['state']);
        $id = intval($_GET['report_id']);
        if($id == 0){
            showMessage('参数错误');
        }
        //var_dump($state);die;
        if($state == 0){
            self::auditingRefer('report1', $id);
        }
        if($state == 1){
            self::auditingRefer('report', $id);
        }
        if(Model()->table("public_project_report_tmp")->where("report_id_tmp={$id}")->update(['report_state' => $state]))
            showMessage('操作成功');
        else
            showMessage('操作失败');
    }
    
    /**
     * @brief 添加或编辑项目汇报
     */
    public function public_projcet_report_editOp(){
        $this->noCache();
        $model=Model();
        if(chksubmit()){
            if(empty($_POST['public_project_id'])){
                showMessage('参数错误','index.php?act=project_union&op=public_project_report_list&id='.$_POST['public_project_id'], 'html','succ',1, $time = 2000);
            }
            $data['report_content'] = $_POST['report_content'];
            $data['report_title'] = $_POST['report_title'];
            $data['report_time']=strtotime($_POST['report_time']);
            $data['report_addtime'] = TIMESTAMP;
            $data['project_id'] = $_POST['public_project_id'];
            
            if(!empty($_POST['report_id'])){
                //$result = $model->table('public_project_report_tmp')->field("report_id_tmp")->where(array('report_id'=>$_POST['report_id']))->find();
                $tmpId = $_POST['report_id_tmp'];
                $model->table("public_project_report_tmp")->where("report_id_tmp={$tmpId}")->update(['report_state' => 0]);
            }else{
                $tmpRes = $model->table("public_project_report_tmp")->field("report_id_tmp")->order("report_id_tmp asc")->find();
                $tmpId = -1;
                if($tmpRes != false && $tmpRes['report_id_tmp'] <= 0){
                    $tmpId = $tmpRes['report_id_tmp'] - 1;
                }
            }
            $data['report_id_tmp'] = $tmpId;
            $result=$model->table('public_project_report_tmp')->insert($data);
            
            if($result){
                showMessage('添加成功','index.php?act=project_union&op=public_project_report_list&id='.$_POST['public_project_id'], 'html','succ',1, $time = 2000);
            }else{
                showMessage('添加失败','index.php?act=project_union&op=public_project_report_list&id='.$_POST['public_project_id'], 'html','succ',1, $time = 2000);
            }
        }else{
            if(!empty($_GET['report_id_tmp'])){
                $public_project_report_info=$model->
                table('public_project_report_tmp')->
                field('report_id,report_id_tmp,project_id,report_content,report_title,report_time')->
                    where(array('report_id_tmp'=>$_GET['report_id_tmp']))->order("report_id desc")->find();
                //var_dump($public_project_report_info);die;
                $public_project_report_info['report_time']=date('Y/m/d',$public_project_report_info['report_time']);
                Tpl::output('public_project_report_info',$public_project_report_info);
            }
            Tpl::showpage('public_projcet_report_edit');
        }
    }
    
    /**
     * @brief 捐款进度
     */
    public function project_money_order_list1Op(){
        $public_project_id = $_GET['id'];
        $model=Model();
        $condition['public_project_id']=$public_project_id;
        $condition['order_state']=array('in',array(ORDER_STATE_PAY,ORDER_STATE_SUCCESS,ORDER_STATE_FAIL));
        $project_money_order_list=$model->table('project_money_order')->
        field('buyer_id,order_id,buyer_name,refund_state,order_sn,order_amount,payment_time,order_state,payment_code')->
        where($condition)->page(20)->select();
        //$peopleNumber = $model->table("project_money_order")->field("count(1) as num")->group("buyer_id")->where($condition)->find();//查询参与人数
        //$orderNumber = $model->table("project_money_order")->field("count(1) as num")->where($condition)->find();//查询参与次数
        $projectId = $model->table("public_project")->field("project_id")->where(['rec_id' => $public_project_id])->find();//完成金额
        $money = $model->table("project_money")->field("target_money,finished_money,number,person_time")->where("project_id={$projectId['project_id']}")->find();
        $titleInfo = [
            'peopleNumber' => $money['number'],
            'orderNumber' => $money['person_time'],
            'sum' => $money['finished_money'],
            'money' => $money['target_money'],
        ];
        foreach($project_money_order_list as $k=>$v){
            $member_info=$model->table('member')->field('member_name,member_truename')->where(array('member_id'=>$v['buyer_id']))->find();
            $v['buyer_mobile']=$member_info['member_name'];
            $project_money_order_list[$k]=$v;
        }
        Tpl::output('show_page',$model->showpage(6));
        Tpl::output('titleInfo', $titleInfo);
        Tpl::output('project_money_order_list', $project_money_order_list);
        Tpl::output('public_project_id', $public_project_id);
        Tpl::showpage('project_money_order_list');
    }
    /**
     * @brief 根据订单id获取备注
     */
    public function get_order_remarkOp()
    {
        $model=Model();
        $table=('project_order_remark');
        $field=('remark_content,remark_datetime');
        $where=array('order_id'=>$_GET['id']);
        $order_remarks=$model->table($table)->field($field)->where($where)->order( 'remark_datetime desc')->select();
//        var_dump($order_remarks);

        $res='';
        foreach ($order_remarks as $k=>$v){
            $time='<span>'.date("Y/m/d    H:i:s",$v['remark_datetime']).'</span>';
//            echo $time;
            $node='&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
            $content='<span>'.$v['remark_content'].'</span>';
            $res.='<p>'.$time.$node.$content.'</p>';
        }
        echo $res;
    }

    /**
     * @brief 项目捐赠列表
     * @author zhangguofu<zhangguofu@17shanyuan.com>
     * @date 2017年12月19日12:23:53
     */
    public function project_money_order_listOp(){

        if(empty($_POST) && empty($_GET['curpage'])){
            Service("volunteer")->search_cache('all_volunteer_list', []);
        }

//        if(empty($_POST['search_keyword']) && !empty($_POST['search_method']) && empty($_POST['type'])){
//            Service("volunteer")->search_cache('all_volunteer_list', []);
//        }
        if(empty($_POST['search_method']) && isset($_GET['curpage'])){
            $assign = true;
            $_POST = Service("volunteer")->search_cache('project_money_order_list');
        }
        if((isset($_POST['search_keyword']) && !empty($_POST['search_keyword'])) || !empty($_POST['type'])){
            if(!isset($assign)){
                Service("volunteer")->search_cache('project_money_order_list', $_POST);
                $_GET['curpage'] = 1;
            }
            if(!empty($_POST['search_keyword'])){
                if($_POST['search_method']=='buyer_name')
                    $condition[$_POST['search_method']] = ['like', '%'.$_POST['search_keyword'].'%'];
                if($_POST['search_method']=='member_name')
                    $condition['member.'.$_POST['search_method']] = ['like', '%'.$_POST['search_keyword'].'%'];
                if($_POST['search_method']=='order_sn')
                    $condition['project_money_order.'.$_POST['search_method']] = ['like', '%'.$_POST['search_keyword'].'%'];
            }
//                $condition[$_POST['search_method']] = ['like', '%'.$_POST['search_keyword'].'%'];
            if(!empty($_POST['type'])){
                if($_POST['type']==2){
                    $condition['support_has_address']=1;
                    $condition['delivery_number']='';

                }else if($_POST['type']==3){
                    $condition['support_has_address']=1;
                    $condition['delivery_number']=array('neq','');

                }
            }
//                $condition['type'] = ['eq', $_POST['type']];
        }




        $public_project_id = $_GET['id'];
        $model=Model();
        $condition['order_state']=array('in',array(ORDER_STATE_PAY,ORDER_STATE_SUCCESS,ORDER_STATE_FAIL));
        $condition['project_money_order.public_project_id']=$public_project_id;
//        $condition['order_state']=array('in',array(ORDER_STATE_PAY,ORDER_STATE_SUCCESS,ORDER_STATE_FAIL));
//        $condition['project_money_order.public_project_id']=$_GET['project_id'];
        $project_name=$model->table('public_project')->field('project_name')->where(array('rec_id'=>$public_project_id))->find();
        var_dump($project_name);


        $pagesize=$this->pagesize;
        $table=('project_money_order,public_project_support,project_money_order_extra,member');
        $field=('project_money_order.order_id,payment_time,order_sn,buyer_id,buyer_name,project_money_order_extra.mob_phone,order_amount,finnshed_time,payment_code,order_state,support_has_address,support_return,project_money_order_extra.address,project_money_order_extra.area_info,delivery_number,delivery_name,member_name');
        $join=('left,left,left');

        $on=('project_money_order.project_support_id=public_project_support.project_support_id,project_money_order.order_id=project_money_order_extra.order_id,project_money_order.buyer_id=member.member_id');
        $where=$condition;
        $order='payment_time desc';

        $project_money_order_list=$model->table($table)->field($field)->join($join)->on($on)->where($where)->page($pagesize)->order($order)->select();
        $count=$model->table($table)->join($join)->on($on)->where($where)->count();
//        var_dump($project_money_order_list);
        $project_money_order_list=Service('pubwel')->get_formate_order_list($project_money_order_list);

//        var_dump($project_money_order_list);
//        $project_money_order_list=$model->table('project_money_order,public_project_support')->
//        field('buyer_id,order_id,buyer_name,refund_state,order_sn,order_amount,payment_time,order_state,payment_code,')->
//        where($condition)->page(20)->select();
        //$peopleNumber = $model->table("project_money_order")->field("count(1) as num")->group("buyer_id")->where($condition)->find();//查询参与人数
        //$orderNumber = $model->table("project_money_order")->field("count(1) as num")->where($condition)->find();//查询参与次数
        $projectId = $model->table("public_project")->field("project_id")->where(['rec_id' => $public_project_id])->find();//完成金额
        $money = $model->table("project_money")->field("target_money,finished_money,number,person_time")->where("project_id={$projectId['project_id']}")->find();
        $titleInfo = [
            'peopleNumber' => $money['number'],
            'orderNumber' => $money['person_time'],
            'sum' => $money['finished_money'],
            'money' => $money['target_money'],
        ];
//        foreach($project_money_order_list as $k=>$v){
//            $member_info=$model->table('member')->field('member_name,member_truename')->where(array('member_id'=>$v['buyer_id']))->find();
//            $v['buyer_mobile']=$member_info['member_name'];
//            $project_money_order_list[$k]=$v;
//        }
        if($count>10){
            Tpl::output('show_page',$model->showpage(6));
        }

        Tpl::output('titleInfo', $titleInfo);
        Tpl::output('post', $_POST);
        Tpl::output('project_name', $project_name['project_name']);
        Tpl::output('project_money_order_list', $project_money_order_list);
        Tpl::output('public_project_id', $public_project_id);
        Tpl::showpage('project_money_order_list');
    }
    
    /**
     * @brief 导出捐款记录
     */
    public function project_money_order_exportOp(){
        $public_project_id=$_GET['public_project_id'];
        $model=Model();
        $condition['public_project_id']=$public_project_id;
        $condition['order_state']=array('in',array(ORDER_STATE_PAY,ORDER_STATE_SUCCESS,ORDER_STATE_FAIL));
        $project_money_order_list=$model->table('project_money_order')->
        field('buyer_id,order_id,buyer_name,order_sn,order_amount,payment_time,order_state,payment_code')->
        where($condition)->limit(2000)->select();
        @header("Content-type: application/unknown");
        @header("Content-Disposition: attachment; filename=捐款记录.csv");
        $str = "捐赠者,订单金额,支付时间,支付渠道,订单号,支付者电话,状态\n";
        echo $str;
        foreach($project_money_order_list as $k=>$v){
            $member_info=$model->table('member')->field('member_name,member_truename')->where(array('member_id'=>$v['buyer_id']))->find();
            $v['buyer_mobile']='电话：'.$member_info['member_name'];
            $v['order_sn']='订单：'.$v['order_sn'];
            $project_money_order_list[$k]=$v;
            if(empty($v['payment_time'])){
                $v['payment_time']= '无';
            }else{
                $v['payment_time']=date('Y-m-d',$v['payment_time']);
            }
            if($v['order_state']==ORDER_STATE_PAY){
                $v['order_state']='待确认';
            }
            if($v['order_state']==ORDER_STATE_SUCCESS){
                $v['order_state']='已确认';
            }
            echo  $v['buyer_name'].','.($v['order_amount'] / 100).','.$v['payment_time'].','.getPaymentNameByPaymentCode($v['payment_code']).','.$v['order_sn'].','.$v['buyer_mobile'].','.$v['order_state']."\n";
        }
    }


    
    /**
     * @brief 根据项目id得到报名人数
     */
    public function getSignNumOp()
    {
        $public_project_id = intval($_GET['public_project_id']);
        if($public_project_id <= 0){
            exit(json_encode(['msg' => '参数错误', 'code' => 500]));
        }
        
        $model=Model();
        $condition['public_project_id'] = $public_project_id;
        $condition['order_state'] = array('in',array(ORDER_STATE_PAY,ORDER_STATE_SUCCESS,ORDER_STATE_FAIL));

        $count = $model->table('project_money_order')
                                        ->where($condition)
                                        ->count();

        exit(json_encode(['number' => $count, 'code' => 200, 'offset' => $this->offset]));
    }

    /**
     * @brief 导出捐款记录v2
     */
    public function createExcelOp(){
        set_time_limit(120);
        import('libraries.excelReader');
        import('PHPExcel');

        $page = abs($_GET['page']) == 0 ? 1 : abs($_GET['page']);
        $public_project_id = intval($_GET['public_project_id']);
        if($public_project_id <= 0){
            exit("参数错误");
        }

        $objPHPExcel = new PHPExcel();
        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator('17SY')
            ->setLastModifiedBy('17SY')
            ->setTitle('cpdonation enroll list')
            ->setSubject('cpdonation enroll list')
            ->setDescription('cpdonation enroll list')
            ->setCategory('cpdonation enroll list');
        // 设置标题栏
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '捐赠者')
            ->setCellValue('B1', '订单金额')
            ->setCellValue('C1', '支付时间')
            ->setCellValue('D1', '支付渠道')
            ->setCellValue('E1', '订单号')
            ->setCellValue('F1', '支付者电话')
            ->setCellValue('G1', '状态');

        $row = 1;

        $model=Model();
        $condition['public_project_id'] = $public_project_id;
        $condition['order_state'] = array('in',array(ORDER_STATE_PAY,ORDER_STATE_SUCCESS,ORDER_STATE_FAIL));

        $list = $model->table('project_money_order,member')
                        ->join('inner')
                        ->on('project_money_order.buyer_id=member.member_id')
                        ->field('project_money_order.*,member.member_name')
                        ->where($condition)
                        ->order('project_money_order.order_id desc')
                        ->limit(($page-1)*$this->offset.','.$this->offset)
                        ->select();
        // $list = $this->model->getSignInfo(['cpdonation_id' => $id],'*',($page-1)*$this->offset, $this->offset);
        if($list == false){
            exit("数据不存在");
        }        
        if(!empty($list)){
            foreach ($list as $key => $value) {
                $value['buyer_mobile']='电话：'.$value['member_name'];
                $value['order_sn']='订单：'.$value['order_sn'];

                if(empty($value['payment_time'])){
                    $value['payment_time']= '无';
                }else{
                    $value['payment_time']=date('Y-m-d',$value['payment_time']);
                }

                if($value['order_state']==ORDER_STATE_PAY){
                    $value['order_state']='待确认';
                }
                if($value['order_state']==ORDER_STATE_SUCCESS){
                    $value['order_state']='已确认';
                }

                $row++;
                $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValueExplicit('A' . $row, $value['buyer_name'])
                ->setCellValueExplicit('B' . $row, ' '.($value['order_amount'] / 100))
                ->setCellValueExplicit('C' . $row, $value['payment_time'])
                ->setCellValueExplicit('D' . $row, getPaymentNameByPaymentCode($value['payment_code']))
                ->setCellValueExplicit('E' . $row, $value['order_sn'])
                ->setCellValueExplicit('F' . $row, $value['buyer_mobile'])
                ->setCellValueExplicit('G' . $row, $value['order_state']);
            }
        }
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $file_path = BASE_DATA_PATH . '/log/' . date('Ymd_His') . '-project_money_order.xls';
        $objWriter->save($file_path);
        // 释放内存
        $objPHPExcel->disconnectWorksheets();
        unset($objPHPExcel);
        unset($objWriter);
        header("Content-Disposition:attachment; filename=捐赠者信息导出.xls");
        $content = file_get_contents($file_path);
        @unlink($file_path);
        echo $content;
    }
    /**
     * @brief 填写备注信息
     * @author zhangguofu<zhangguofu@17shanyuan.com>
     * @date 2017年9月18日12:23:53
     */
    public function add_remarkOp(){
        if(empty($_POST['order_id']) || empty($_POST['remark_content'])){
           $this->return_result();
        }
        if(mb_strlen($_POST['remark_content'])>301){
            $retArr = [
                'code' => 404,
                'message'=>'最多输入300字',
                'max_node'=>mb_strlen($_POST['remark_content'])

            ];
            exit(json_encode($retArr));
        }
//        var_dump($_POST);
        $model=Model();
        $table=('project_order_remark');
        $where=array('order_id'=>$_POST['order_id']);
        $count=$model->table($table)->where($where)->count();
//        var_dump($count);
        if($count>51){
            $retArr = [
                'code' => 403,
                'message'=>'备注最多50条信息',

            ];
            exit(json_encode($retArr));
        }
        $val=array('order_id'=>$_POST['order_id'],'remark_content'=>$_POST['remark_content'],'remark_datetime'=>TIMESTAMP);
        $res=$model->table($table)->insert($val);
        if($res){
            $this->return_result(1);

        }else{
            $this->return_result();
        }

    }
    /**
     * @brief 获取举报信息
     * @author zhangguofu<zhangguofu@17shanyuan.com>
     * @date 2017年9月18日12:23:53
     */
    public function get_inform_infoOp(){
        $inform_id=$_GET['inform_id'];
        if(empty($inform_id)){
            $this->return_result();
        }
//        var_dump($inform_id);
        $model=Model();
        $condition=array('inform_id'=>$inform_id);
        $table=('public_project_inform');
        $field=('inform_image,inform_content');
        $where=$condition;
        $delivery_info=$model->table($table)->field($field)->where($where)->find();
        if($delivery_info){
            $this->return_result($delivery_info);
        }else{
            $this->return_result();
        }
    }

    /**
     * @brief 返回错误或正确信息时显示内容
     * @author zhangguofu<zhangguofu@17shanyuan.com>
     * @date 2017年12月18日12:23:53
     */
    public function return_result($result=''){
        if(empty($result)){
            $retArr = [
                'code' => 500,
                'message' => '操作有误'
            ];
            exit(json_encode($retArr));
        }else{
            $retArr = [
                'code' => 200,
                'message'=>'success',
                'content' => $result
            ];
            exit(json_encode($retArr));

        }
    }

    /**
     * @brief 地址信息
     * @author zhangguofu<zhangguofu@17shanyuan.com>
     * @date 2017年12月18日12:23:53
     */
    public function check_delivery_infoOp(){
//        var_dump($_GET);
        $model=Model();
        $table=('project_money_order_extra');
        $field=('*');
        $where=array('order_id'=>$_GET['order_id']);
        if(empty($_GET['order_id'])){
            $this->return_result();

        }
        $delivery_info=$model->table($table)->field($field)->where($where)->find();
        if($delivery_info){
            $this->return_result($delivery_info);
        }

    }
    /**
     * @brief 补充地址信息
     * @author zhangguofu<zhangguofu@17shanyuan.com>
     * @date 2017年12月18日12:23:53
     */
    public function delivery_infoOp(){
        $model=Model();
        $table=('project_money_order_extra');
        $update=array('delivery_name'=>$_POST['company_name'],'delivery_number'=>$_POST['delivery_number']);
        $where=array('order_id'=>$_POST['order_id']);
        if(empty($_POST['company_name'])||empty($_POST['delivery_number'])||empty($_POST['order_id'])){
            $this->return_result();
        }




        if(!preg_match('|^[0-9a-zA-Z]+$|', trim($_POST['delivery_number'])) || strlen($_POST['delivery_number'])>21){
            $retArr = [
                'code' => 403,
                'message' => '请填写正确的快递单号'
            ];
            exit(json_encode($retArr));
        }
//var_dump(is_numeric($_POST['delivery_number']));

//        var_dump($_POST);
        $res=$model->table($table)->where($where)->update($update);
//        var_dump($res);
        if($res){
            $this->return_result($res);
        }else{
            $this->return_result();
        }
    }



    public function agreementOp(){
        Tpl::showpage('agreement', 'null_layout');
    }

    public function support_type_readmeOp(){
        Tpl::showpage('support_type_readme', 'null_layout');
    }    
}
