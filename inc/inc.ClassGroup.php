<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
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

/**********************************************************************\
|                           Group-Klasse                               |
\**********************************************************************/

class LetoDMS_Group
{
	var $_id;
	var $_name;
	var $_dms;

	function LetoDMS_Group($id, $name, $comment)
	{
		$this->_id = $id;
		$this->_name = $name;
		$this->_comment = $comment;
	}

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function setName($newName)
	{
		global $db;
		
		$queryStr = "UPDATE tblGroups SET name = '" . $newName . "' WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		$this->_name = $newName;
		return true;
	}

	function getComment() { return $this->_comment; }

	function setComment($newComment)
	{
		global $db;
		
		$queryStr = "UPDATE tblGroups SET comment = '" . $newComment . "' WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		$this->_comment = $newComment;
		return true;
	}

	function getUsers()
	{
		global $db;
		
		if (!isset($this->_users))
		{
			$queryStr = "SELECT `tblUsers`.* FROM `tblUsers` ".
				"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
				"WHERE `tblGroupMembers`.`groupID` = '". $this->_id ."'";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_users = array();

			foreach ($resArr as $row)
			{
				$user = new LetoDMS_User($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $row["isAdmin"]);
				array_push($this->_users, $user);
			}
		}
		return $this->_users;
	}

	function addUser($user,$asManager=false)
	{
		global $db;

		$queryStr = "INSERT INTO tblGroupMembers (groupID, userID, manager) VALUES (".$this->_id.", ".$user->getID(). ", " . ($asManager?"1":"0") ." )";
		$res = $db->getResult($queryStr);
		
		if ($res) return false;

		unset($this->_users);
		return true;
	}

	function removeUser($user)
	{
		global $db;

		$queryStr = "DELETE FROM tblGroupMembers WHERE groupID = ".$this->_id." AND userID = ".$user->getID();
		$res = $db->getResult($queryStr);
		
		if ($res) return false;
		unset($this->_users);
		return true;
	}

	// $asManager=false: verify if user is in group
	// $asManager=true : verify if user is in group as manager
	function isMember($user,$asManager=false)
	{
		if (isset($this->_users)&&!$asManager)
		{
			foreach ($this->_users as $usr)
				if ($usr->getID() == $user->getID())
					return true;
			return false;
		}
		
		global $db;
		if ($asManager) $queryStr = "SELECT * FROM tblGroupMembers WHERE groupID = " . $this->_id . " AND userID = " . $user->getID() . " AND manager = 1";
		else $queryStr = "SELECT * FROM tblGroupMembers WHERE groupID = " . $this->_id . " AND userID = " . $user->getID();

		$resArr = $db->getResultArray($queryStr);
		
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;
		
		return true;
	}
	
	function toggleManager($user)
	{
		global $db;
		
		if (!$this->isMember($user)) return false;
		
		if ($this->isMember($user,true)) $queryStr = "UPDATE tblGroupMembers SET manager = 0 WHERE groupID = ".$this->_id." AND userID = ".$user->getID();
		else $queryStr = "UPDATE tblGroupMembers SET manager = 1 WHERE groupID = ".$this->_id." AND userID = ".$user->getID();
		
		if (!$db->getResult($queryStr)) return false;
		return true;
	}

	/**
	 * Entfernt die Gruppe aus dem System.
	 * Dies ist jedoch nicht mit einem L�schen des entsprechenden Eintrags aus tblGroups geschehen - vielmehr
	 * muss daf�r gesorgt werden, dass die Gruppe nirgendwo mehr auftaucht. D.h. auch die Tabellen tblACLs,
	 * tblNotify und tblGroupMembers m�ssen ber�cksichtigt werden.
	 */
	function remove()
	{
		GLOBAl $db, $user;
		
		$queryStr = "DELETE FROM tblGroups WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblGroupMembers WHERE groupID = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblACLs WHERE groupID = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblNotify WHERE groupID = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblMandatoryReviewers WHERE reviewerGroupID = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$queryStr = "DELETE FROM tblMandatoryApprovers WHERE approverGroupID = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
			
		// TODO : update document status if reviewer/approver has been deleted


		$reviewStatus = $this->getReviewStatus();
		foreach ($reviewStatus as $r) {
			$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $r["reviewID"] ."', '-2', 'Review group removed from process', NOW(), '". $user->getID() ."')";
			$res=$db->getResult($queryStr);
		}

		$approvalStatus = $this->getApprovalStatus();
		foreach ($approvalStatus as $a) {
			$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $a["approveID"] ."', '-2', 'Approval group removed from process', NOW(), '". $user->getID() ."')";
			$res=$db->getResult($queryStr);
		}

		return true;
	}

	function getReviewStatus($documentID=null, $version=null) {
		global $db;

		if (!$db->createTemporaryTable("ttreviewid")) {
			return false;
		}

		$status = array();

		// See if the group is assigned as a reviewer.
		$queryStr = "SELECT `tblDocumentReviewers`.*, `tblDocumentReviewLog`.`status`, ".
			"`tblDocumentReviewLog`.`comment`, `tblDocumentReviewLog`.`date`, ".
			"`tblDocumentReviewLog`.`userID` ".
			"FROM `tblDocumentReviewers` ".
			"LEFT JOIN `tblDocumentReviewLog` USING (`reviewID`) ".
			"LEFT JOIN `ttreviewid` on `ttreviewid`.`maxLogID` = `tblDocumentReviewLog`.`reviewLogID` ".
			"WHERE `ttreviewid`.`maxLogID`=`tblDocumentReviewLog`.`reviewLogID` ".
			($documentID==null ? "" : "AND `tblDocumentReviewers`.`documentID` = '". $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentReviewers`.`version` = '". $version ."' ").
			"AND `tblDocumentReviewers`.`type`='1' ".
			"AND `tblDocumentReviewers`.`required`='". $this->_id ."' ";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res)
				$status[] = $res;
		}
		return $status;
	}

	function getApprovalStatus($documentID=null, $version=null) {
		global $db;

		if (!$db->createTemporaryTable("ttapproveid")) {
			return false;
		}

		$status = array();

		// See if the group is assigned as an approver.
		$queryStr = "SELECT `tblDocumentApprovers`.*, `tblDocumentApproveLog`.`status`, ".
			"`tblDocumentApproveLog`.`comment`, `tblDocumentApproveLog`.`date`, ".
			"`tblDocumentApproveLog`.`userID` ".
			"FROM `tblDocumentApprovers` ".
			"LEFT JOIN `tblDocumentApproveLog` USING (`approveID`) ".
			"LEFT JOIN `ttapproveid` on `ttapproveid`.`maxLogID` = `tblDocumentApproveLog`.`approveLogID` ".
			"WHERE `ttapproveid`.`maxLogID`=`tblDocumentApproveLog`.`approveLogID` ".
			($documentID==null ? "" : "AND `tblDocumentApprovers`.`documentID` = '". $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentApprovers`.`version` = '". $version ."' ").
			"AND `tblDocumentApprovers`.`type`='1' ".
			"AND `tblDocumentApprovers`.`required`='". $this->_id ."' ";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res)
				$status[] = $res;
		}

		return $status;
	}
}
?>
