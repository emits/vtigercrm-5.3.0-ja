<?php
/*********************************************************************************
 ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
  * ("License"); You may not use this file except in compliance with the License
  * The Initial Developer of the Original Code is FOSS Labs.
  * Portions created by FOSS Labs are Copyright (C) FOSS Labs.
  * Portions created by vtiger are Copyright (C) vtiger.
  * All Rights Reserved.
  *
  ********************************************************************************/

// JFV - convert mail header date string
function jfv_convert_mail_header_date($date)
{
	$jfv_dtstr = $date;
	$jfv_timestamp =  strtotime($date);
	if ($jfv_timestamp != -1 && $jfv_timestamp != FALSE){
		$jfv_dtstr = date('Y/m/d  G:i',$jfv_timestamp);
	}
	return  $jfv_dtstr;
}
// JFV END
//JFV - create mail list from
function jfv_create_mail_from_name($from)
{
	$jfv_from = str_replace("\\\\","\\",$from);
	$jfv_from = str_replace("\\\'","\'",$jfv_from);
	$jfv_from = str_replace("\\\"","\"",$jfv_from);
	$jfv_from = preg_replace("/<[^>]*>/","",$jfv_from);
	$jfv_from = trim($jfv_from,"\" ");
	return $jfv_from;
}
// JFV END

// draw a row for the listview entry
function show_msg($mails,$start_message)
{
	global $MailBox,$displayed_msgs,$show_hidden,$new_msgs,$theme;

	$num = $mails[$start_message]->msgno;
	$msg_ob = new Webmails($MailBox->mbox,$mails[$start_message]->msgno);
	// TODO: scan the current db vtiger_tables to find a
	// matching email address that will make a good
	// candidate for record_id
	// this module will also need to be able to associate to any entity type
	$record_id='';

	if($mails[$start_message]->subject=="")
		$mails[$start_message]->subject="(No Subject)";

	// Let's pre-build our URL parameters since it's too much of a pain not to
	$detailParams = 'record='.$num.'&mailbox='.$mailbox.'&mailid='.$num.'&parenttab=My Home Page';

	$displayed_msgs++;
	if ($mails[$start_message]->deleted && !$show_hidden)
	{
                $flags = "<tr id='row_".$num."' class='mailSelected' style='display:none' class=\"lvtColData\" bgcolor='#ffffff'><td width='2px'><input type='checkbox' class='msg_check'></td><td colspan='1'>";
        	$displayed_msgs--;
	}
	elseif ($mails[$start_message]->deleted && $show_hidden)
	{
		$flags = "<tr id='row_".$num."' class='mailSelected' class=\"lvtColData\" bgcolor='#ffffff'><td width='2px'><input type='checkbox' class='msg_check'></td><td colspan='1'>";
	}
	elseif (!$mails[$start_message]->seen || $mails[$start_message]->recent)
	{
			$flags = "<tr  id='row_".$num."' class='mailSelected' class=\"lvtColData\" bgcolor='#ffffff'><td width='2px'><input type='checkbox' name='selected_id' onclick='toggleSelectAll(this.name,\"select_all\")' value='$num' class='msg_check'></td><td colspan='1'>";
		$new_msgs++;
	}
	else
	{
	$flags = "<tr id='row_".$num."' class=\"lvtColData\" bgcolor='#ffffff'><td width='2px'><input type='checkbox' name='selected_id' value='$num' onclick='toggleSelectAll(this.name,\"select_all\")' class='msg_check'></td><td colspan='1'>";

	}

		//enable-diable download attachment button
		if($msg_ob->has_attachments){
			$enableDownlaodAttachment = 'yes';
		}else
			$enableDownlaodAttachment = 'no';	

        // Attachment Icons
        if($msg_ob->has_attachments)
                $flags.='<a href="javascript:;" onclick="displayAttachments('.$num.');"><img src="themes/images/attachment.gif" border="0" width="8px" height="13" title="Attachment"></a>&nbsp;';
        else
                $flags.='<img src="themes/images/blank.gif" border="0" width="8px" height="14" alt="">&nbsp;';



        // read/unread/forwarded/replied
        if(!$mails[$start_message]->seen || $mails[$start_message]->recent)
	{
		$flags.='<span id="unread_img_'.$num.'"><a href="javascript:;" onclick="OpenCompose(\''.$num.'\',\'reply\');"><img src="themes/images/newmail.gif" border="0" width="12" height="10" title="Unread"></a></span>&nbsp;';
	}
	elseif ($mails[$start_message]->in_reply_to || $mails[$start_message]->references || preg_match("/^re:/i",$mails[$start_message]->subject))
	{
		$flags.='<a href="javascript:;" onclick="OpenComposer(\''.$num.'\',\'reply\');"><img src="themes/images/stock_mail-replied.png" border="0" width="14" height="16" title="Replied" ></a>&nbsp;';
	}
	elseif (preg_match("/^fw:/i",$mails[$start_message]->subject))
	{
		$flags.='<a href="javascript:;" onclick="OpenComposer(\''.$num.'\',\'reply\');"><img src="themes/images/stock_mail-forward.png" border="0" width="10" height="13" title="Forward" ></a>&nbsp;';
	}
	else
	{
                $flags.='<a href="javascript:;" onclick="OpenComposer(\''.$num.'\',\'reply\');"><img src="themes/images/openmail.gif" border="0" width="12" height="12" title="Read" ></a>&nbsp;';
	}

        // Set IMAP flag
	if($mails[$start_message]->flagged)
	{
		$flags.='<span id="clear_td_'.$num.'"><a href="javascript:runEmailCommand(\'clear_flag\','.$num.');"><img src="themes/images/important1.gif" border="0" width="11" height="11" id="clear_flag_img_'.$num.'"title="Important"></a></span>';
	}
	else
	{
                $flags.='<span id="set_td_'.$num.'"><a href="javascript:void(0);" onclick="runEmailCommand(\'set_flag\','.$num.');"><img src="themes/images/important2.gif" border="0" width="11" height="11" id="set_flag_img_'.$num.'"title="Important"></a></span>';

	}

        $tmp=imap_mime_header_decode($mails[$start_message]->from);
        $from = $tmp[0]->text;
        $listview_entries[$num] = array();

        $listview_entries[$num][] = $flags."</td>";

	if ($mails[$start_message]->deleted)
	{
// JFV - fix wrongly truncationed utf8 string
		if (function_exists("mb_strimwidth")) {
		$listview_entries[$num][] = '<td nowrap align="left" style="cursor:pointer;" id="deleted_subject_'.$num.'" onclick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\'); "><s><a href="javascript:;" >'.mb_strimwidth($mails[$start_message]->subject,0,50, '...', "UTF-8").'</a></s></td>';
		}else{
// JFV END
		$listview_entries[$num][] = '<td nowrap align="left" style="cursor:pointer;" id="deleted_subject_'.$num.'" onclick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\'); "><s><a href="javascript:;" >'.substr($mails[$start_message]->subject,0,40).'</a></s></td>';
// JFV
		}
// JFV END
// JFV - change mail date time format on mail list
//		$listview_entries[$num][] = '<td nowrap align="left" style="cursor:pointer;" onClick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" nowrap id="deleted_date_'.$num.'"><s>'.substr($mails[$start_message]->date,0,25).'</s></td>';
		$listview_entries[$num][] = '<td nowrap align="left" style="cursor:pointer;" onClick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" nowrap id="deleted_date_'.$num.'"><s>'.jfv_convert_mail_header_date($mails[$start_message]->date).'</s></td>';
// JFV END
// JFV - fix wrongly truncationed utf8 string
		if (function_exists("mb_strimwidth")) {
		$listview_entries[$num][] = '<td nowrap align="left" id="deleted_from_'.$num.'" style="cursor:pointer;" onClick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');"><s>'.mb_strimwidth(jfv_create_mail_from_name($from),0,25, '...', "UTF-8").'</s></td>';
		}else{
// JFV END
		$listview_entries[$num][] = '<td nowrap align="left" id="deleted_from_'.$num.'" style="cursor:pointer;" onClick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');"><s>'.substr($from,0,20).'</s></td>';
// JFV
		}
// JFV END
	}
	elseif(!$mails[$start_message]->seen || $mails[$start_message]->recent)
	{
// JFV - fix wrongly truncationed utf8 string
		if (function_exists("mb_strimwidth")) {
		$listview_entries[$num][] = '<td nowrap align="left" onclick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" style="cursor:pointer;" ><a href="javascript:;" id="ndeleted_subject_'.$num.'"><font id="fnt_subject_'.$num.'" color="green">'.mb_strimwidth($mails[$start_message]->subject,0,50, '...', "UTF-8").'</font></a></td>';
		}else{
// JFV END
		$listview_entries[$num][] = '<td nowrap align="left" onclick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" style="cursor:pointer;" ><a href="javascript:;" id="ndeleted_subject_'.$num.'"><font id="fnt_subject_'.$num.'" color="green">'.substr($mails[$start_message]->subject,0,40).'</font></a></td>';
// JFV
		}
// JFV END
// JFV - change mail date time format on mail list
//		$listview_entries[$num][] = '<td nowrap align="left" nowrap id="ndeleted_date_'.$num.'" style="cursor:pointer;" onClick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" ><font id="fnt_date_'.$num.'" color="green">'.substr($mails[$start_message]->date,0,25).' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</font></td>';
		$listview_entries[$num][] = '<td nowrap align="left" nowrap id="ndeleted_date_'.$num.'" style="cursor:pointer;" onClick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" ><font id="fnt_date_'.$num.'" color="green">'.jfv_convert_mail_header_date($mails[$start_message]->date).' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</font></td>';
// JFV END
// JFV - fix wrongly truncationed utf8 string
		if (function_exists("mb_strimwidth")) {
		$listview_entries[$num][] = '<td  nowrap align="left" id="ndeleted_from_'.$num.'"><font id="fnt_from_'.$num.'" style="cursor:pointer;" onClick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" >'.mb_strimwidth(jfv_create_mail_from_name($from),0,25, '...', "UTF-8").'</font></td>';
		}else{
// JFV END
		$listview_entries[$num][] = '<td  nowrap align="left" id="ndeleted_from_'.$num.'"><font id="fnt_from_'.$num.'" style="cursor:pointer;" onClick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" >'.substr($from,0,20).'</font></td>';
// JFV
		}
// JFV END
	}
	else
	{
		//IMPORTANT - This UTF-8 conversion has been done in ListView.php so no need to do again here
		//Added to shown the original UTF-8 characters - Mickie - 30-11-06 - Starts
		//we can use the option 1 or option 2
		//Option 1 - Starts
		/*
		$translated_subject = imap_mime_header_decode($mails[$start_message]->subject);
		for($i=0;$i<count($translated_subject);$i++)
		{
			if($translated_subject[$i]->charset != 'default')
			{
				$tmp .= $translated_subject[$i]->text;
				$mails[$start_message]->subject = utf8_decode($tmp);//$tmp;
			}
		}
		//Option 1 - Ends
		*/
		//Option 2 - Starts
		//$mails[$start_message]->subject = utf8_decode(imap_utf8($mails[$start_message]->subject));//imap_utf8($mails[$start_message]->subject);
		//Option 2 - Ends
		//Added to shown the original UTF-8 characters - Mickie - 30-11-06 - Ends
// JFV - fix wrongly truncationed utf8 string
		if (function_exists("mb_strimwidth")) {
		$listview_entries[$num][] = '<td nowrap align="left" onclick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" style="cursor:pointer;" ><a href="javascript:;" id="ndeleted_subject_'.$num.'">'.mb_strimwidth($mails[$start_message]->subject, 0, 50, '...', "UTF-8").'</a></td>';
		}else{
// JFV END
		$listview_entries[$num][] = '<td nowrap align="left" onclick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" style="cursor:pointer;" ><a href="javascript:;" id="ndeleted_subject_'.$num.'">'.substr($mails[$start_message]->subject,0,40).'</a></td>';
// JFV
		}
// JFV END
// JFV - change mail date time format on mail list
//		$listview_entries[$num][] = '<td npwrap align="left" nowrap id="ndeleted_date_'.$num.'" style="cursor:pointer;" onClick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" >'.substr($mails[$start_message]->date,0,25).'</td>';
		$listview_entries[$num][] = '<td npwrap align="left" nowrap id="ndeleted_date_'.$num.'" style="cursor:pointer;" onClick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" >'.jfv_convert_mail_header_date($mails[$start_message]->date).'</td>';
// JFV END
// JFV - fix wrongly truncationed utf8 string
		if (function_exists("mb_strimwidth")) {
		$listview_entries[$num][] = '<td nowrap align="left" id="ndeleted_from_'.$num.'" style="cursor:pointer;" onClick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" >'.mb_strimwidth(jfv_create_mail_from_name($from)."12345123451234512345",0,25, '...', "UTF-8").'</td>';
		}else{
// JFV END
		$listview_entries[$num][] = '<td nowrap align="left" id="ndeleted_from_'.$num.'" style="cursor:pointer;" onClick="load_webmail(\''.$num.'\', \''.$enableDownlaodAttachment.'\');" >'.substr($from,0,20).'</td>';
// JFV
		}
// JFV END
	}


	
        if($mails[$start_message]->deleted)
                $listview_entries[$num][] = '<td nowrap align="center" id="deleted_td_'.$num.'"><span id="del_link_'.$num.'"><a href="javascript:void(0);" onclick="runEmailCommand(\'undelete_msg\','.$num.');"><img src="themes/images/gnome-fs-trash-empty.png" border="0" width="14" height="14" alt="del"  title="Delete"></a></span></td></tr>';
        else
                $listview_entries[$num][] = '<td nowrap align="center" id="ndeleted_td_'.$num.'"><span id="del_link_'.$num.'"><a href="javascript:void(0);" onclick="runEmailCommand(\'delete_msg\','.$num.');"><img src="themes/images/no.gif" border="0" width="14" height="14" alt="del"  title="Delete"></a></span></td></tr>';


        return $listview_entries[$num];
}

?>
