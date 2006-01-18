<?php
/* 
** ZABBIX
** Copyright (C) 2000-2005 SIA Zabbix
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/
?>
<?php
	include "include/config.inc.php";
	include "include/forms.inc.php";
	$page["title"] = "S_HOSTS";
	$page["file"] = "hosts.php";
	show_header($page["title"]);
	insert_confirm_javascript();
?>
<?php
	if(!check_anyright("Host","U"))
	{
		show_table_header("<font color=\"AA0000\">".S_NO_PERMISSIONS."</font>");
		show_page_footer();
		exit;
	}

	if(!isset($_REQUEST["config"]))	$_REQUEST["config"] = get_profile("web.hosts.config",0);

	update_profile("web.hosts.config",$_REQUEST["config"]);
	update_profile("web.menu.config.last",$page["file"]);
?>
<?php
	if(isset($_REQUEST["save"])&&!isset($_REQUEST["hostid"])&&($_REQUEST["config"]==0))
	{
		$groups=array();
		$db_groups=DBselect("select groupid from groups");
		while($db_group=DBfetch($db_groups))
		{
			if(!isset($_REQUEST[$db_group["groupid"]]))	continue;
			array_push($groups,$db_group["groupid"]);
		}
		$result=add_host(
			$_REQUEST["host"],$_REQUEST["port"],$_REQUEST["status"],$_REQUEST["useip"],
			$_REQUEST["ip"],$_REQUEST["host_templateid"],$_REQUEST["newgroup"],$groups);

		show_messages($result, S_HOST_ADDED, S_CANNOT_ADD_HOST);
		if($result){
			add_audit(AUDIT_ACTION_ADD,AUDIT_RESOURCE_HOST,
				"Host [".addslashes($_REQUEST["host"])."] IP [".$_REQUEST["ip"]."] ".
				"Status [".$_REQUEST["status"]."]");

			unset($_REQUEST["form"]);
			unset($_REQUEST["hostid"]);
		}
	}

	if(isset($_REQUEST["save"])&&isset($_REQUEST["hostid"])&&($_REQUEST["config"]==0))
	{
		$groups=array();
		$db_groups=DBselect("select groupid from groups");
		while($db_group=DBfetch($db_groups))
		{
			if(!isset($_REQUEST[$db_group["groupid"]]))	continue;
			array_push($groups,$db_group["groupid"]);
		}
		$result=update_host(
			$_REQUEST["hostid"],$_REQUEST["host"],$_REQUEST["port"],$_REQUEST["status"],
			$_REQUEST["useip"],$_REQUEST["ip"],$_REQUEST["newgroup"],$groups);

		show_messages($result, S_HOST_UPDATED, S_CANNOT_UPDATE_HOST);
		if($result){
			add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_HOST,
				"Host [".addslashes($_REQUEST["host"])."] IP [".$_REQUEST["ip"]."] ".
				"Status [".$_REQUEST["status"]."]");

			unset($_REQUEST["form"]);
			unset($_REQUEST["hostid"]);
		}
	}

	if(isset($_REQUEST["delete"])&&($_REQUEST["config"]==0))
	{
		$host=get_host_by_hostid($_REQUEST["hostid"]);
		$result=delete_host($_REQUEST["hostid"]);

		show_messages($result, S_HOST_DELETED, S_CANNOT_DELETE_HOST);
		if($result)
		{
			add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_HOST,"Host [".addslashes($host["host"])."]");

			unset($_REQUEST["form"]);
			unset($_REQUEST["hostid"]);
		}
	}

	if(isset($_REQUEST["register"]))
	{
		if($_REQUEST["register"]=="add items from template")
		{
			if(isset($_REQUEST["host_templateid"])&&($_REQUEST["host_templateid"]!=0))
			{
				$result=sync_items_with_template_host($_REQUEST["hostid"],$_REQUEST["host_templateid"]);
				show_messages(TRUE,S_ITEMS_ADDED,S_CANNOT_ADD_ITEMS);
			}
			else
			{
				show_messages(FALSE,"",S_SELECT_HOST_TEMPLATE_FIRST);
			}
		}	
		if($_REQUEST["register"]=="add linkage")
		{	
			$items=0;
			if(isset($_REQUEST["items_add"]))	$items=$items|1;
			if(isset($_REQUEST["items_update"]))	$items=$items|2;
			if(isset($_REQUEST["items_delete"]))	$items=$items|4;
			$triggers=0;
			if(isset($_REQUEST["triggers_add"]))	$triggers=$triggers|1;
			if(isset($_REQUEST["triggers_update"]))	$triggers=$triggers|2;
			if(isset($_REQUEST["triggers_delete"]))	$triggers=$triggers|4;
			$graphs=0;
			if(isset($_REQUEST["graphs_add"]))	$graphs=$graphs|1;
			if(isset($_REQUEST["graphs_update"]))	$graphs=$graphs|2;
			if(isset($_REQUEST["graphs_delete"]))	$graphs=$graphs|4;
			$result=add_template_linkage($_REQUEST["hostid"],$_REQUEST["templateid"],$items,$triggers,$graphs);
			show_messages($result, S_TEMPLATE_LINKAGE_ADDED, S_CANNOT_ADD_TEMPLATE_LINKAGE);
		}
		if($_REQUEST["register"]=="update linkage")
		{	
			$items=0;
			if(isset($_REQUEST["items_add"]))	$items=$items|1;
			if(isset($_REQUEST["items_update"]))	$items=$items|2;
			if(isset($_REQUEST["items_delete"]))	$items=$items|4;
			$triggers=0;
			if(isset($_REQUEST["triggers_add"]))	$triggers=$triggers|1;
			if(isset($_REQUEST["triggers_update"]))	$triggers=$triggers|2;
			if(isset($_REQUEST["triggers_delete"]))	$triggers=$triggers|4;
			$graphs=0;
			if(isset($_REQUEST["graphs_add"]))	$graphs=$graphs|1;
			if(isset($_REQUEST["graphs_update"]))	$graphs=$graphs|2;
			if(isset($_REQUEST["graphs_delete"]))	$graphs=$graphs|4;
			$result=update_template_linkage($_REQUEST["hosttemplateid"],$_REQUEST["hostid"],$_REQUEST["templateid"],$items,$triggers,$graphs);
			show_messages($result, S_TEMPLATE_LINKAGE_UPDATED, S_CANNOT_UPDATE_TEMPLATE_LINKAGE);
		}
		if($_REQUEST["register"]=="delete linkage")
		{
			$result=delete_template_linkage($_REQUEST["hosttemplateid"]);
			show_messages($result, S_TEMPLATE_LINKAGE_DELETED, S_CANNOT_DELETE_TEMPLATE_LINKAGE);
			unset($_REQUEST["hosttemplateid"]);
		}	
		if($_REQUEST["register"]=="add profile")
		{
			$result=add_host_profile($_REQUEST["hostid"],$_REQUEST["devicetype"],$_REQUEST["name"],$_REQUEST["os"],$_REQUEST["serialno"],$_REQUEST["tag"],$_REQUEST["macaddress"],$_REQUEST["hardware"],$_REQUEST["software"],$_REQUEST["contact"],$_REQUEST["location"],$_REQUEST["notes"]);
			show_messages($result, S_PROFILE_ADDED, S_CANNOT_ADD_PROFILE);
		}
		if($_REQUEST["register"]=="update profile")
		{
			$result=update_host_profile($_REQUEST["hostid"],$_REQUEST["devicetype"],$_REQUEST["name"],$_REQUEST["os"],$_REQUEST["serialno"],$_REQUEST["tag"],$_REQUEST["macaddress"],$_REQUEST["hardware"],$_REQUEST["software"],$_REQUEST["contact"],$_REQUEST["location"],$_REQUEST["notes"]);
			show_messages($result, S_PROFILE_UPDATED, S_CANNOT_UPDATE_PROFILE);
		}
		if($_REQUEST["register"]=="delete profile")
		{
			$result=delete_host_profile($_REQUEST["hostid"]);
			show_messages($result, S_PROFILE_DELETED, S_CANNOT_DELETE_PROFILE);
		}
		if($_REQUEST["register"]=="changestatus")
		{
			$host=get_host_by_hostid($_REQUEST["hostid"]);
			$result=update_host_status($_REQUEST["hostid"],$_REQUEST["status"]);
			show_messages($result,S_HOST_STATUS_UPDATED,S_CANNOT_UPDATE_HOST_STATUS);
			if($result)
			{
				add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_HOST,"Old status [".$host["status"]."] New status [$status]");
			}
			unset($_REQUEST["hostid"]);
		}
		if($_REQUEST["register"]=="add group")
		{
			$result=add_host_group($_REQUEST["name"], $_REQUEST["hosts"]);
			show_messages($result, S_GROUP_ADDED, S_CANNOT_ADD_GROUP);
		}
		if($_REQUEST["register"]=="delete group")
		{
			$result=delete_host_group($_REQUEST["groupid"]);
			show_messages($result, S_GROUP_DELETED, S_CANNOT_DELETE_GROUP);
			unset($_REQUEST["groupid"]);
		}
		if($_REQUEST["register"]=="update group")
		{
			$result=update_host_group($_REQUEST["groupid"], $_REQUEST["name"], $_REQUEST["hosts"]);
			show_messages($result, S_GROUP_UPDATED, _S_CANNOT_UPDATE_GROUP);
		}
		if($_REQUEST["register"]=="start monitoring")
		{
			$result=DBselect("select hostid from hosts_groups where groupid=".$_REQUEST["groupid"]);
			while($row=DBfetch($result))
			{
				$res=update_host_status($row["hostid"],HOST_STATUS_MONITORED);
				if($res)
				{
					add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_HOST,"New status [".HOST_STATUS_MONITORED."]");
				}
			}
			show_messages(1,S_HOST_STATUS_UPDATED,S_CANNOT_UPDATE_HOST_STATUS);
		}
		if($_REQUEST["register"]=="stop monitoring")
		{
			$result=DBselect("select hostid from hosts_groups where groupid=".$_REQUEST["groupid"]);
			while($row=DBfetch($result))
			{
				$res=update_host_status($row["hostid"],HOST_STATUS_NOT_MONITORED);
				if($res)
				{
					add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_HOST,"New status [".HOST_STATUS_NOT_MONITORED."]");
				}
			}
			show_messages(1,S_HOST_STATUS_UPDATED,S_CANNOT_UPDATE_HOST_STATUS);
		}
		if($_REQUEST["register"]=="Activate selected")
		{
			$result=DBselect("select hostid from hosts");
			while($row=DBfetch($result))
			{
// $$ is correct here
				if(isset($_REQUEST[$row["hostid"]]))
				{
					$res=update_host_status($row["hostid"],HOST_STATUS_MONITORED);
					if($res)
					{
						add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_HOST,"New status [".HOST_STATUS_MONITORED."]");
					}
				}
			}
			show_messages(1,S_HOST_STATUS_UPDATED,S_CANNOT_UPDATE_HOST_STATUS);
		}
		if($_REQUEST["register"]=="Disable selected")
		{
			$result=DBselect("select hostid from hosts");
			while($row=DBfetch($result))
			{
// $$ is correct here
				if(isset($_REQUEST[$row["hostid"]]))
				{
					$res=update_host_status($row["hostid"],HOST_STATUS_NOT_MONITORED);
					if($res)
					{
						add_audit(AUDIT_ACTION_UPDATE,AUDIT_RESOURCE_HOST,"New status [".HOST_STATUS_NOT_MONITORED."]");
					}
				}
			}
			show_messages(1,S_HOST_STATUS_UPDATED,S_CANNOT_UPDATE_HOST_STATUS);
		}
		if($_REQUEST["register"]=="Delete selected")
		{
			$result=DBselect("select hostid from hosts");
			while($row=DBfetch($result))
			{
// $$ is correct here
				if(isset($_REQUEST[$row["hostid"]]))
				{
					$host=get_host_by_hostid($row["hostid"]);
					$res=delete_host($row["hostid"]);
					if($res)
					{
						add_audit(AUDIT_ACTION_DELETE,AUDIT_RESOURCE_HOST,"Host [".addslashes($host["host"])."]");
					}
				}
			}
			show_messages($result, S_HOST_DELETED, S_CANNOT_DELETE_HOST);
		}
	}

	if(isset($_REQUEST["cancel"])){
		unset($_REQUEST["form"]);
	}

?>

<?php
	if(!isset($_REQUEST["config"]))
	{
		$_REQUEST["config"]=0;
	}

	$cmbConf = new CComboBox("config",$_REQUEST["config"],"submit()");
	$cmbConf->AddItem(0,S_HOSTS);
	$cmbConf->AddItem(1,S_HOST_GROUPS);
//	$cmbConf->AddItem(2,S_HOSTS_TEMPLATES_LINKAGE);

	switch($_REQUEST["config"]){
		case 0: $btnCaption = S_CREATE_HOST;	break;
		case 1: $btnCaption = S_CREATE_GROUP;	break;
	}

	$frmForm = new CForm("hosts.php");
	$frmForm->AddItem($cmbConf);
	$frmForm->AddItem(SPACE."|".SPACE);
	$frmForm->AddItem(new CButton("form",$btnCaption));
	show_header2(S_CONFIGURATION_OF_HOSTS_AND_HOST_GROUPS, $frmForm);
	echo BR;
?>


<?php
	if($_REQUEST["config"]==2)
	{
	$h1=S_CONFIGURATION_OF_TEMPLATES_LINKAGE;

	if(isset($_REQUEST["groupid"])&&($_REQUEST["groupid"]==0))
	{
		unset($_REQUEST["groupid"]);
	}
	if(isset($_REQUEST["hostid"])&&($_REQUEST["hostid"]==0))
	{
		unset($_REQUEST["hostid"]);
	}

	$h2=S_GROUP."&nbsp;";
	$h2=$h2."<input class=\"biginput\" name=\"config\" type=\"hidden\" value=\"".$_REQUEST["config"]."\">";
	if(isset($_REQUEST["hostid"]))
	{
		$h2=$h2."<input class=\"biginput\" name=\"hostid\" type=\"hidden\" value=\"".$_REQUEST["hostid"]."\">";
	}
	$h2=$h2."<select class=\"biginput\" name=\"groupid\" onChange=\"submit()\">";
	$h2=$h2.form_select("groupid",0,S_ALL_SMALL);

	$result=DBselect("select groupid,name from groups order by name");
	while($row=DBfetch($result))
	{
// Check if at least one host with read permission exists for this group
		$result2=DBselect("select h.hostid,h.host from hosts h,hosts_groups hg where hg.groupid=".$row["groupid"]." and hg.hostid=h.hostid and h.status not in (".HOST_STATUS_DELETED.") group by h.hostid,h.host order by h.host");
		$cnt=0;
		while($row2=DBfetch($result2))
		{
			if(!check_right("Host","U",$row2["hostid"]))
			{
				continue;
			}
			$cnt=1; break;
		}
		if($cnt!=0)
		{
			$h2=$h2.form_select("groupid",$row["groupid"],$row["name"]);
		}
	}
	$h2=$h2."</select>";

	$h2=$h2."&nbsp;".S_HOST."&nbsp;";
	$h2=$h2."<select class=\"biginput\" name=\"hostid\" onChange=\"submit()\">";
	$h2=$h2.form_select("hostid",0,S_SELECT_HOST_DOT_DOT_DOT);

	if(isset($_REQUEST["groupid"]))
	{
		$sql="select h.hostid,h.host from hosts h,hosts_groups hg where hg.groupid=".$_REQUEST["groupid"]." and hg.hostid=h.hostid and h.status not in (".HOST_STATUS_DELETED.") group by h.hostid,h.host order by h.host";
	}
	else
	{
		$sql="select h.hostid,h.host from hosts h where h.status not in (".HOST_STATUS_DELETED.") group by h.hostid,h.host order by h.host";
	}

	$result=DBselect($sql);
	while($row=DBfetch($result))
	{
		if(!check_right("Host","U",$row["hostid"]))
		{
			continue;
		}
		$h2=$h2.form_select("hostid",$row["hostid"],$row["host"]);
	}
	$h2=$h2."</select>";

	show_header2($h1, $h2, "<form name=\"form2\" method=\"get\" action=\"hosts.php\">", "</form>");
	}
?>
<?php
	if($_REQUEST["config"]==1)
	{
		show_table_header(S_HOST_GROUPS_BIG);
		$table = new CTableInfo(S_NO_HOST_GROUPS_DEFINED);
		$table->setHeader(array(S_ID,S_NAME,S_MEMBERS,S_ACTIONS));

		$result=DBselect("select groupid,name from groups order by name");
		$col=0;
		while($row=DBfetch($result))
		{
//		$members=array("hide"=>1,"value"=>"");
			$members=array("hide"=>0,"value"=>"");
			$result1=DBselect("select distinct h.host from hosts h, hosts_groups hg where h.hostid=hg.hostid and hg.groupid=".$row["groupid"]." and h.status not in (".HOST_STATUS_DELETED.") order by host");
			$i=0;
			while($row1=DBfetch($result1))
			{
				$members["hide"]=0;
				$members["value"]=$members["value"].$row1["host"];
				if($i<DBnum_rows($result1)-1)
				{
					$members["value"]=$members["value"].", ";
				}
				$i++;
			}
			$members["value"]=$members["value"]."&nbsp;";
			$actions="<A HREF=\"hosts.php?config=".$_REQUEST["config"]."&groupid=".$row["groupid"]."#form\">".S_CHANGE."</A>";

			$table->addRow(array(
				$row["groupid"],
				$row["name"],
				$members,
				$actions
				));
		}
		$table->show();
	}
?>

<?php
	if(isset($_REQUEST["groupid"])&&($_REQUEST["groupid"]==0))
	{
		unset($_REQUEST["groupid"]);
	}
?>

<?php
	if(isset($_REQUEST["hostid"])&&($_REQUEST["config"]==2))
	{
		$table = new CTableInfo(S_NO_LINKAGES_DEFINED);
		$table->setHeader(array(S_HOST,S_TEMPLATE,S_ITEMS,S_TRIGGERS,S_GRAPHS,S_ACTIONS));

		$result=DBselect("select * from hosts_templates where hostid=".$_REQUEST["hostid"]);
		while($row=DBfetch($result))
		{
			$host=get_host_by_hostid($row["hostid"]);
			$template=get_host_by_hostid($row["templateid"]);
//		$members=array("hide"=>1,"value"=>"");
#			$actions="<A HREF=\"hosts.php?config=".$_REQUEST["config"]."&groupid=".$row["groupid"]."#form\">".S_CHANGE."</A>";
			$actions="<a href=\"hosts.php?config=2&hostid=".$row["hostid"]."&hosttemplateid=".$row["hosttemplateid"]."\">".S_CHANGE."</a>";

			$table->addRow(array(
				$host["host"],
				$template["host"],
				get_template_permission_str($row["items"]),
				get_template_permission_str($row["triggers"]),
				get_template_permission_str($row["graphs"]),
				$actions
				));
		}
		$table->show();
	}
	if(isset($_REQUEST["hostid"])&&$_REQUEST["config"]==2)
	{
		@insert_template_form($_REQUEST["hostid"], $_REQUEST["hosttemplateid"]);
	}
?>

<?php
	if($_REQUEST["config"]==0)
	{
		if(!isset($_REQUEST["form"]))
		{

			$h1="&nbsp;".S_HOSTS_BIG;

			$h2_form1="<form name=\"form2\" method=\"get\" action=\"latest.php\">";


			$h2=S_GROUP."&nbsp;";
			$h2=$h2."<select class=\"biginput\" name=\"groupid\" onChange=\"submit()\">";
			$h2=$h2.form_select("groupid",0,S_ALL_SMALL);
			$result=DBselect("select groupid,name from groups order by name");
			while($row=DBfetch($result))
			{
// Check if at least one host with read permission exists for this group
				$result2=DBselect("select h.hostid,h.host from hosts h,items i,hosts_groups hg where h.hostid=i.hostid and hg.groupid=".$row["groupid"]." and hg.hostid=h.hostid and h.status not in (".HOST_STATUS_DELETED.")group by h.hostid,h.host order by h.host");
				$cnt=0;
				while($row2=DBfetch($result2))
				{
					if(!check_right("Host","R",$row2["hostid"]))
					{
						continue;
					}
					$cnt=1; break;
				}
				if($cnt!=0)
				{
					$h2=$h2.form_select("groupid",$row["groupid"],$row["name"]);
				}
			}
			$h2=$h2."</select>";

			show_header2($h1, $h2, "<form name=\"form2\" method=\"get\" action=\"hosts.php\">", "</form>");
?>

<?php
			$table = new CTableInfo(S_NO_HOSTS_DEFINED);
			$table->setHeader(array(S_ID,S_HOST,S_IP,S_PORT,S_STATUS,S_AVAILABILITY,S_ERROR,S_ACTIONS));
//			$table->setAfterHeader("<form method=\"get\" action=\"hosts.php\">");
		
			if(isset($_REQUEST["groupid"]))
			{
				$sql="select h.hostid,h.host,h.port,h.status,h.useip,h.ip,h.error,h.available from hosts h,hosts_groups hg where hg.groupid=".$_REQUEST["groupid"]." and hg.hostid=h.hostid and h.status<>".HOST_STATUS_DELETED." order by h.host";
			}
			else
			{
				$sql="select h.hostid,h.host,h.port,h.status,h.useip,h.ip,h.error,h.available from hosts h where h.status<>".HOST_STATUS_DELETED." order by h.host";
			}
			$result=DBselect($sql);
		
			while($row=DBfetch($result))
			{
		        	if(!check_right("Host","R",$row["hostid"]))
				{
					continue;
				}
				$id="<INPUT TYPE=\"CHECKBOX\" class=\"biginput\" NAME=\"".$row["hostid"]."\"> ".$row["hostid"];
				$host="<a href=\"items.php?hostid=".$row["hostid"]."\">".$row["host"]."</a>";
		
				if($row["useip"]==1)
				{
					$ip=$row["ip"];
				}
				else
				{
					$ip="-";
				}
		        	if(check_right("Host","U",$row["hostid"]))
				{
					if($row["status"] == HOST_STATUS_MONITORED)	
						$status=new CCol(new CLink(S_MONITORED,"hosts.php?hostid=".$row["hostid"]."&amp;register=changestatus&amp;status=1","off"),"off");
					else if($row["status"] == HOST_STATUS_NOT_MONITORED)
						$status=new CCol(new CLink(S_NOT_MONITORED,"hosts.php?hostid=".$row["hostid"]."&amp;register=changestatus&amp;status=0","on"),"on");
					else if($row["status"] == HOST_STATUS_TEMPLATE)
						$status=new CCol(S_TEMPLATE,"unknown");
					else if($row["status"] == HOST_STATUS_DELETED)
						$status=new CCol(S_DELETED,"unknown");
					else
						$status=S_UNKNOWN;
				}
				else
				{
					if($row["status"] == HOST_STATUS_MONITORED)	
						$status=new CCol(S_MONITORED,"off");
					else if($row["status"] == HOST_STATUS_NOT_MONITORED)
						$status=new CCol(S_NOT_MONITORED,"on");
		//			else if($row["status"] == 2)
		//				$status=array("value"=>S_UNREACHABLE,"class"=>"unknown");
					else if($row["status"] == HOST_STATUS_TEMPLATE)
						$status=new CCol(S_TEMPLATE,"unknown");
					else if($row["status"] == HOST_STATUS_DELETED)
						$status=new CCol(S_DELETED,"unknown");
					else
						$status=S_UNKNOWN;
				}
		
				if($row["available"] == HOST_AVAILABLE_TRUE)	
					$available=new CCol(S_AVAILABLE,"off");
				else if($row["available"] == HOST_AVAILABLE_FALSE)
					$available=new CCol(S_NOT_AVAILABLE,"on");
				else if($row["available"] == HOST_AVAILABLE_UNKNOWN)
					$available=new CCol(S_UNKNOWN,"unknown");
		
				if($row["error"] == "")
				{
					$error=new CCol("&nbsp;","off");
				}
				else
				{
					$error=new CCol($row["error"],"on");
				}
		        	if(check_right("Host","U",$row["hostid"]))
				{
					if($row["status"] != HOST_STATUS_DELETED)
					{
							$actions="<A HREF=\"hosts.php?register=change&amp;form=0&amp;hostid=".$row["hostid"].url_param("groupid").url_param("config")."#form\">".S_CHANGE."</A>";
		/*				if(isset($_REQUEST["groupid"]))
						{
							$actions="<A HREF=\"hosts.php?register=change&config=".$_REQUEST["config"]."&hostid=".$row["hostid"]."&groupid=".$_REQUEST["groupid"]."#form\">".S_CHANGE."</A>";
						}
						else
						{
							$actions="<A HREF=\"hosts.php?register=change&config=".$_REQUEST["config"]."&hostid=".$row["hostid"]."#form\">".S_CHANGE."</A>";
						}*/
					}
					else
					{
							$actions="&nbsp;";
					}
				}
				else
				{
					$actions=S_CHANGE;
				}
				$table->addRow(array(
					$id,
					$host,
					$ip,
					$row["port"],
					$status,
					$available,
					$error,
					$actions));
			}
//			$table->show();

			$footerButtons = array();
			array_push($footerButtons, new CButton('register','Activate selected',
				"return Confirm('".S_ACTIVATE_SELECTED_HOSTS_Q."');"));
			array_push($footerButtons, new CButton('register','Disable selected',
				"return Confirm('".S_DISABLE_SELECTED_HOSTS_Q."');"));
			array_push($footerButtons, new CButton('register','Delete selected',
				"return Confirm('".S_DELETE_SELECTED_HOSTS_Q."');"));
			$table->SetFooter(new CCol($footerButtons),'table_footer');

			$form = new CForm('hosts.php');
			$form->AddItem($table);
			$form->Show();


//			show_form_begin();
//			$h="<input class=\"button\" type=\"submit\" name=\"register\" value=\"Activate selected\" onClick=\"return Confirm('".S_ACTIVATE_SELECTED_HOSTS_Q."');\">";
//			$h=$h."<input class=\"button\" type=\"submit\" name=\"register\" value=\"Disable selected\" onClick=\"return Confirm('".S_DISABLE_SELECTED_HOSTS_Q."');\">";
//			$h=$h."<input class=\"button\" type=\"submit\" name=\"register\" value=\"Delete selected\" onClick=\"return Confirm('".S_DELETE_SELECTED_HOSTS_Q."');\">";
//			$h=$h."</form>";
//			show_table_header($h);
		}
		else
		{
			insert_host_form();
			echo BR;
			if(isset($_REQUEST["form"])&&isset($_REQUEST["hostid"]))
			{
				insert_host_profile_form($_REQUEST["hostid"]);
			}
		}

	}
?>

<?php
	if($_REQUEST["config"]==1)
	{
		@insert_hostgroups_form($_REQUEST["groupid"]);
	}
?>
<?php
	show_page_footer();
?>
