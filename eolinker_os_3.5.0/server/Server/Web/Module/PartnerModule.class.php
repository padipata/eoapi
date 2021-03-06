<?php

/**
 * @name eolinker ams open source，eolinker开源版本
 * @link https://www.eolinker.com/
 * @package eolinker
 * @author www.eolinker.com 广州银云信息科技有限公司 2015-2017
 * eoLinker是目前全球领先、国内最大的在线API接口管理平台，提供自动生成API文档、API自动化测试、Mock测试、团队协作等功能，旨在解决由于前后端分离导致的开发效率低下问题。
 * 如在使用的过程中有任何问题，欢迎加入用户讨论群进行反馈，我们将会以最快的速度，最好的服务态度为您解决问题。
 *
 * eoLinker AMS开源版的开源协议遵循Apache License 2.0，如需获取最新的eolinker开源版以及相关资讯，请访问:https://www.eolinker.com/#/os/download
 *
 * 官方网站：https://www.eolinker.com/
 * 官方博客以及社区：http://blog.eolinker.com/
 * 使用教程以及帮助：http://help.eolinker.com/
 * 商务合作邮箱：market@eolinker.com
 * 用户讨论QQ群：284421832
 */
class PartnerModule
{
    public function __construct()
    {
        @session_start();
    }

    /**
     * 获取项目用户类型
     * @param $projectID int 项目ID
     * @return bool|int
     */
    public function getUserType(&$projectID)
    {
        $dao = new AuthorizationDao();
        $result = $dao->getProjectUserType($_SESSION['userID'], $projectID);
        if ($result === FALSE) {
            return -1;
        }
        return $result;
    }

    /**
     * 邀请协作人员
     * @param $projectID int 项目ID
     * @param $inviteUserID int 邀请人ID
     * @return bool|int
     */
    public function invitePartner(&$projectID, &$inviteUserID)
    {
        $projectDao = new ProjectDao;
        if ($projectDao->checkProjectPermission($projectID, $_SESSION['userID'])) {
            $projectInfo = $projectDao->getProjectName($projectID);
            $summary = '您已被邀请加入项目：' . $projectInfo['projectName'] . '，开始您的高效协作之旅吧！';
            $msg = '<p>您好！亲爱的用户：</p><p>您已经被加入项目：<b style="color:#4caf50">' . $projectInfo['projectName'] . '</b>，现在你可以参与项目的开发协作工作。</p><p>如果您在使用的过程中遇到任何问题，欢迎前往<a href="http://blog.eolinker.com/#/bbs/"><b style="color:#4caf50">交流社区</b></a>反馈意见，谢谢！。</p>';

            //邀请协作人员
            $partnerDao = new PartnerDao;
            if ($connID = $partnerDao->invitePartner($projectID, $inviteUserID, $_SESSION['userID'])) {
                $inviteUserCall = $partnerDao->getPartnerUserCall($inviteUserID);
                //将操作写入日志
                $log_dao = new ProjectLogDao();
                $log_dao->addOperationLog($projectID, $_SESSION['userID'], ProjectLogDao::$OP_TARGET_PARTNER, $inviteUserID, ProjectLogDao::$OP_TYPE_ADD, "邀请新成员:'$inviteUserCall'", date("Y-m-d H:i:s", time()));

                //给协作人员发送邀请信息
                $msgDao = new MessageDao;
                $msgDao->sendMessage($_SESSION['userID'], $inviteUserID, 1, $summary, $msg);
                return $connID;
            } else
                return FALSE;
        } else
            return FALSE;
    }

    /**
     * 移除协作人员
     * @param $projectID int 项目ID
     * @param $connID int 用户与项目联系ID
     * @return bool
     */
    public function removePartner($projectID, $connID)
    {
        $projectDao = new ProjectDao;
        if ($projectDao->checkProjectPermission($projectID, $_SESSION['userID'])) {
            $projectInfo = $projectDao->getProjectName($projectID);
            $summary = '您已被移除出项目：' . $projectInfo['projectName'];
            $msg = '<p>您好！亲爱的用户：</p><p>您已经被移除出项目：<b style="color:#4caf50">' . $projectInfo['projectName'] . '</b>。</p><p>如果您在使用的过程中遇到任何问题，欢迎前往<a href="http://blog.eolinker.com/#/bbs/"><b style="color:#4caf50">交流社区</b></a>反馈意见，谢谢！。</p>';

            $partnerDao = new PartnerDao;
            $remotePartnerID = $partnerDao->getUserID($connID);
            if ($partnerDao->removePartner($projectID, $connID)) {
                $inviteUserCall = $partnerDao->getPartnerUserCall($remotePartnerID);
                //将操作写入日志
                $log_dao = new ProjectLogDao();
                $log_dao->addOperationLog($projectID, $_SESSION['userID'], ProjectLogDao::$OP_TARGET_PARTNER, $remotePartnerID, ProjectLogDao::$OP_TYPE_DELETE, "移除成员:'$inviteUserCall'", date("Y-m-d H:i:s", time()));

                //给协作人员发送邀请信息
                $msgDao = new MessageDao;
                $msgDao->sendMessage(0, $remotePartnerID, 1, $summary, $msg);
                return TRUE;
            } else
                return FALSE;
        } else
            return FALSE;

    }

    /**
     * 获取协作人员列表
     * @param $projectID int 项目ID
     * @return bool|array
     */
    public function getPartnerList(&$projectID)
    {
        $projectDao = new ProjectDao;
        if ($projectDao->checkProjectPermission($projectID, $_SESSION['userID'])) {
            $partnerDao = new PartnerDao;
            $list = $partnerDao->getPartnerList($projectID);
            foreach ($list as &$param) {
                if ($param['userID'] == $_SESSION['userID'])
                    $param['isNow'] = 1;
                else
                    $param['isNow'] = 0;
                unset($param['userID']);
            }
            return $list;
        } else
            return FALSE;
    }

    /**
     * 退出协作项目
     * @param $projectID int 项目ID
     * @return bool
     */
    public function quitPartner(&$projectID)
    {
        $projectDao = new ProjectDao;
        if ($projectDao->checkProjectPermission($projectID, $_SESSION['userID'])) {
            $partnerDao = new PartnerDao;
            if ($partnerDao->quitPartner($projectID, $_SESSION['userID'])) {
                $user_call = $partnerDao->getPartnerUserCall($_SESSION['userID']);
                //将操作写入日志
                $log_dao = new ProjectLogDao();
                $log_dao->addOperationLog($projectID, $_SESSION['userID'], ProjectLogDao::$OP_TARGET_PARTNER, $_SESSION['userID'], ProjectLogDao::$OP_TYPE_OTHERS, "'$user_call'退出项目协作", date("Y-m-d H:i:s", time()));

                return TRUE;
            } else
                return FALSE;
        } else
            return FALSE;
    }

    /**
     * 查询是否已经加入过项目
     * @param $projectID int 项目ID
     * @param $userName string 用户名
     * @return bool
     */
    public function checkIsInvited(&$projectID, &$userName)
    {
        $dao = new PartnerDao;
        return $dao->checkIsInvited($projectID, $userName);
    }

    /**
     * 修改协作成员的昵称
     * @param $project_id int 项目ID
     * @param $conn_id int 连接ID
     * @param $nick_name string 昵称
     * @return bool
     */
    public function editPartnerNickName(&$project_id, &$conn_id, &$nick_name)
    {
        $dao = new PartnerDao();
        return $dao->editPartnerNickName($project_id, $conn_id, $nick_name);
    }

    /**
     * 修改协作成员的类型
     * @param $project_id int 项目ID
     * @param $conn_id int 连接ID
     * @param $user_type int 用户类型
     * @return bool
     */
    public function editPartnerType(&$project_id, &$conn_id, &$user_type)
    {
        $dao = new PartnerDao();
        $result = $dao->editPartnerType($project_id, $conn_id, $user_type);
        if ($result) {
            $remote_partner_id = $dao->getUserID($conn_id);
            $invite_user_call = $dao->getPartnerUserCall($remote_partner_id);
            switch ($user_type) {
                case 1:
                    $type = '管理员';
                    break;
                case 2:
                    $type = '普通成员（读写）';
                    break;
                case 3:
                    $type = '普通成员（只读）';
                    break;
                default:
                    break;
            }
            //将操作写入日志
            $log_dao = new ProjectLogDao();
            $log_dao->addOperationLog($project_id, $_SESSION['userID'], ProjectLogDao::$OP_TARGET_PARTNER, $remote_partner_id, ProjectLogDao::$OP_TYPE_DELETE, "修改成员:'$invite_user_call'为'$type'", date("Y-m-d H:i:s", time()));
            return $result;
        } else {
            return FALSE;
        }
    }

    public function getProjectInviteCode(&$project_id)
    {

    }

    public function joinProjectByInviteCode()
    {

    }
}

?>