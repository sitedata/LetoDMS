<?php
//    MyDMS. Document Management System
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
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

if (!isset($_GET["targetidform3"]) || !is_numeric($_GET["targetidform3"]) || intval($_GET["targetidform3"])<1) {
	UI::exitError(getMLText("admin_tools"),getMLText("invalid_folder_id"));
}

$folderid = $_GET["targetidform3"];
$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	UI::exitError(getMLText("admin_tools"),getMLText("invalid_folder_id"));
}

UI::htmlStartPage(getMLText("files_deletion"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");

UI::contentHeading(getMLText("files_deletion"));
UI::contentContainerStart();

?>
<form action="../op/op.RemoveFolderFiles.php" name="form1" method="POST">
	<input type="Hidden" name="folderid" value="<?php echo $folderid?>">
	<p><?php printMLText("confirm_rm_folder_files", array ("foldername" => $folder->getName()));?></p>
	<input type="Submit" value="<?php printMLText("accept");?>">
</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
