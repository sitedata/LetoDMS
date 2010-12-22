<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.ClassDMS.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

if (isset($_GET["action"])) $action = $_GET["action"];
else if (isset($_POST["action"])) $action = $_POST["action"];


//Neue Gruppe anlegen -----------------------------------------------------------------------------
if ($action == "addgroup") {

	$name = sanitizeString($_GET["name"]);
	$comment = sanitizeString($_GET["comment"]);

	if (is_object($dms->getGroupByName($name))) {
		UI::exitError(getMLText("admin_tools"),getMLText("group_exists"));
	}

	$newGroup = $dms->addGroup($name, $comment);
	if (!$newGroup) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
	
	$groupid=$newGroup->getID();
	
	add_log_line();
}

//Gruppe l�schen ----------------------------------------------------------------------------------
else if ($action == "removegroup") {
	
	if (!isset($_POST["groupid"]) || !is_numeric($_POST["groupid"]) || intval($_POST["groupid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}
	
	$group = $dms->getGroup($_POST["groupid"]);
	if (!is_object($group)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}

	if (!$group->remove($user)) {
		UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
	}
	
	add_log_line(".php?groupid=".$groupid."&action=removegroup");
}

//Gruppe bearbeiten -------------------------------------------------------------------------------
else if ($action == "editgroup") {

	if (!isset($_GET["groupid"]) || !is_numeric($_GET["groupid"]) || intval($_GET["groupid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}
	
	$groupid=$_GET["groupid"];
	$group = $dms->getGroup($groupid);
	
	if (!is_object($group)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}
	
	$name = sanitizeString($_GET["name"]);
	$comment = sanitizeString($_GET["comment"]);

	if ($group->getName() != $name)
		$group->setName($name);
	if ($group->getComment() != $comment)
		$group->setComment($comment);
		
	add_log_line();
}

//Benutzer zu Gruppe hinzuf�gen -------------------------------------------------------------------
else if ($action == "addmember") {

	if (!isset($_POST["groupid"]) || !is_numeric($_POST["groupid"]) || intval($_POST["groupid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}
	
	$groupid=$_POST["groupid"];
	$group = $dms->getGroup($groupid);
	
	if (!is_object($group)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}

	if (!isset($_POST["userid"]) || !is_numeric($_POST["userid"]) || intval($_POST["userid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}
	
	$newMember = $dms->getUser($_POST["userid"]);
	if (!is_object($newMember)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}

	if (!$group->isMember($newMember)){
		$group->addUser($newMember);
		if (isset($_POST["manager"])) $group->toggleManager($newMember);
	}
	
	add_log_line(".php?groupid=".$groupid."&userid=".$_POST["userid"]."&action=addmember");
}

//Benutzer aus Gruppe entfernen -------------------------------------------------------------------
else if ($action == "rmmember") {

	if (!isset($_GET["groupid"]) || !is_numeric($_GET["groupid"]) || intval($_GET["groupid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}
	
	$groupid=$_GET["groupid"];
	$group = $dms->getGroup($groupid);
	
	if (!is_object($group)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}

	if (!isset($_GET["userid"]) || !is_numeric($_GET["userid"]) || intval($_GET["userid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}
	
	$oldMember = $dms->getUser($_GET["userid"]);
	if (!is_object($oldMember)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}

	$group->removeUser($oldMember);
	
	add_log_line();
}

// toggle manager flag
else if ($action == "tmanager") {

	if (!isset($_GET["groupid"]) || !is_numeric($_GET["groupid"]) || intval($_GET["groupid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}
	
	$groupid=$_GET["groupid"];
	$group = $dms->getGroup($groupid);
	
	if (!is_object($group)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_group_id"));
	}

	if (!isset($_GET["userid"]) || !is_numeric($_GET["userid"]) || intval($_GET["userid"])<1) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}
	
	$usertoedit = $dms->getUser($_GET["userid"]);
	if (!is_object($usertoedit)) {
		UI::exitError(getMLText("admin_tools"),getMLText("invalid_user_id"));
	}
	
	$group->toggleManager($usertoedit);
	
	add_log_line();
}

header("Location:../out/out.GroupMgr.php?groupid=".$groupid);

?>
