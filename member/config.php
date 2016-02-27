<?php
/**
 * @version        $Id: config.php 1 8:38 2010��7��9��Z tianya $
 * @package        DedeCMS.Member
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__).'/../include/common.inc.php');
require_once(DEDEINC.'/filter.inc.php');
require_once(DEDEINC.'/memberlogin.class.php');
require_once(DEDEINC.'/dedetemplate.class.php');

//��õ�ǰ�ű����ƣ�������ϵͳ��������$_SERVER�����������и������ѡ��
$dedeNowurl = $s_scriptName = '';
$dedeNowurl = GetCurUrl();
$dedeNowurls = explode('?', $dedeNowurl);
$s_scriptName = $dedeNowurls[0];
$menutype = '';
$menutype_son = '';
$gourl = empty($gourl)? "" : RemoveXSS($gourl);

//����Ƿ񿪷Ż�Ա����
if($cfg_mb_open=='N')
{
    ShowMsg("ϵͳ�ر��˻�Ա���ܣ�������޷����ʴ�ҳ�棡","javascript:;");
    exit();
}
$keeptime = isset($keeptime) && is_numeric($keeptime) ? $keeptime : -1;
$cfg_ml = new MemberLogin($keeptime);

//�ж��û��Ƿ��¼
$myurl = '';
if($cfg_ml->IsLogin())
{
    $myurl = $cfg_memberurl."/index.php?uid=".urlencode($cfg_ml->M_LoginID);
    if(!preg_match("#^http:#i", $myurl)) $myurl = $cfg_basehost.$myurl;
}

/**
 *  ����û��Ƿ���Ȩ�޽���ĳ������
 *
 * @param     int  $rank  Ȩ��ֵ
 * @param     int  $money  ���
 * @param     bool  $needinfo  �Ƿ���Ҫ��д��ϸ��Ϣ
 * @return    void
 */
function CheckRank($rank=0, $money=0, $needinfo=TRUE)
{
    global $cfg_ml,$cfg_memberurl,$cfg_mb_reginfo,$cfg_mb_spacesta;
    if(!$cfg_ml->IsLogin())
    {
        header("Location:{$cfg_memberurl}/login.php?gourl=".urlencode(GetCurUrl()));
        exit();
    }
    else
    {
        if($cfg_mb_reginfo == 'Y' && $needinfo)
        {
            //�������ע����ϸ��Ϣ
            if($cfg_ml->fields['spacesta'] == 0 || $cfg_ml->fields['spacesta'] == 1)
            {
                ShowMsg("��δ�����ϸ���ϣ�������...","{$cfg_memberurl}/index_do.php?fmdo=user&dopost=regnew&step=2",0,1000);
                exit;
            }
        }
        if($cfg_mb_spacesta == '-10')
        {
            //�������ע���ʼ���֤
            if($cfg_ml->fields['spacesta'] == '-10')
            {
                  $msg="����δ�����ʼ���֤���뵽�������...</br>���·����ʼ���֤ <a href='/member/index_do.php?fmdo=sendMail'><font color='red'>����˴�</font></a>";
                ShowMsg($msg,"-1",0,5000);
                exit;
            }
        }
        if($cfg_ml->M_Rank < $rank)
        {
            $needname = "";
            if($cfg_ml->M_Rank==0)
            {
                $row = $dsql->GetOne("SELECT membername FROM #@__arcrank WHERE rank='$rank'");
                $myname = "��ͨ��Ա";
                $needname = $row['membername'];
            }
            else
            {
                $dsql->SetQuery("SELECT membername From #@__arcrank WHERE rank='$rank' OR rank='".$cfg_ml->M_Rank."' ORDER BY rank DESC");
                $dsql->Execute();
                $row = $dsql->GetObject();
                $needname = $row->membername;
                if($row = $dsql->GetObject())
                {
                    $myname = $row->membername;
                }
                else
                {
                    $myname = "��ͨ��Ա";
                }
            }
            ShowMsg("�Բ�����Ҫ��<span style='font-size:11pt;color:red'>$needname</span> ���ܷ��ʱ�ҳ�档<br>��Ŀǰ�ĵȼ��ǣ�<span style='font-size:11pt;color:red'>$myname</span> ��","-1",0,5000);
            exit();
        }
        else if($cfg_ml->M_Money < $money)
        {
            ShowMsg("�Բ�����Ҫ���ѽ�ң�<span style='font-size:11pt;color:red'>$money</span> ���ܷ��ʱ�ҳ�档<br>��Ŀǰӵ�еĽ���ǣ�<span style='font-size:11pt;color:red'>".$cfg_ml->M_Money."</span>  ��","-1",0,5000);
            exit();
        }
    }
}

/**
 *  �����ĵ�ͳ��
 *
 * @access    public
 * @param     int  $channelid  Ƶ��ģ��id
 * @return    string
 */
function countArchives($channelid)
{
    global $cfg_ml,$dsql;
    $id = (int)$channelid;
    if($cfg_ml->IsLogin())
    {
        $channeltype = array(1 => 'article',2 => 'album',3 => 'soft',-8 => 'infos');
        if(isset($channeltype[$id]))
        {
            $_field = $channeltype[$id];
        }
        else
        {
            $_field = 'articles';
        }
        $row = $dsql->GetOne("SELECT COUNT(*) AS nums FROM #@__archives WHERE channel='$id' AND mid='".$cfg_ml->M_ID."'");
        
        $dsql->ExecuteNoneQuery("UPDATE #@__member_tj SET ".$_field."='".$row['nums']."' WHERE mid='".$cfg_ml->M_ID."'");
    }
    else
    {
        return FALSE;
    }
}