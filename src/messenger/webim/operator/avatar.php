<?php
/*
 * This file is part of Web Instant Messenger project.
 *
 * Copyright (c) 2005-2009 Web Messenger Community
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Evgeny Gryaznov - initial API and implementation
 */

require_once('../libs/common.php');
require_once('../libs/operator.php');

$operator = check_login();

$opId = verifyparam( "op","/^\d{1,9}$/");
$page = array('op' => $opId, 'avatar' => '');
$errors = array();

$op = operator_by_id($opId);

if( !$op ) {
	$errors[] = getlocal("no_such_operator");

} else if( isset($_POST['op']) ) {
	$avatar = $op['vcavatar'];

	if( isset($_FILES['avatarFile']) && $_FILES['avatarFile']['name']) {
        $valid_types = array("gif","jpg", "png", "tif");

        $orig_filename = $_FILES['avatarFile']['name'];
        $tmp_file_name = $_FILES['avatarFile']['tmp_name'];

        $ext = strtolower(substr($orig_filename, 1 + strrpos($orig_filename, ".")));
        $new_file_name = "$opId.$ext";
        loadsettings();

        $file_size = $_FILES['avatarFile']['size'];
        if ($file_size == 0 || $file_size > $settings['max_uploaded_file_size']) {
            $errors[] = failed_uploading_file($orig_filename, "errors.file.size.exceeded");
        } elseif(!in_array($ext, $valid_types)) {
            $errors[] = failed_uploading_file($orig_filename, "errors.invalid.file.type");
        } else {
            $avatar_local_dir = "../images/avatar/";
            $full_file_path = $avatar_local_dir.$new_file_name;
            if (file_exists($full_file_path)) {
                unlink($full_file_path);
            }
            if (!move_uploaded_file($_FILES['avatarFile']['tmp_name'], $full_file_path)) {
                $errors[] = failed_uploading_file($orig_filename, "errors.file.move.error");
            } else {
                $avatar = "$webimroot/images/avatar/$new_file_name";
            }
        }
    } else {
    	$errors[] = "No file selected";
    }

	if(count($errors) == 0) {
		update_operator_avatar($op['operatorid'],$avatar);

		if ($opId && $avatar && $_SESSION['operator'] && $operator['operatorid'] == $opId) {
			$_SESSION['operator']['vcavatar'] = $avatar;
		}
		header("Location: $webimroot/operator/avatar.php?op=$opId");
		exit;
	} else {
		$page['avatar'] = topage($op['vcavatar']);
	}

} else {
	if (isset($_GET['delete']) && $_GET['delete'] == "true") {
		update_operator_avatar($op['operatorid'],'');
		header("Location: $webimroot/operator/avatar.php?op=$opId");
		exit;
	}
	$page['avatar'] = topage($op['vcavatar']);
}

$page['tabs'] = array(
	getlocal("page_agent.tab.main") => "$webimroot/operator/operator.php?op=$opId",
	getlocal("page_agent.tab.avatar") => "",
	getlocal("page_agent.tab.permissions") => "$webimroot/operator/permissions.php?op=$opId"
);
$page['currentop'] = topage(get_operator_name($op))." (".$op['vclogin'].")";

prepare_menu($operator);
start_html_output();
require('../view/avatar.php');
?>