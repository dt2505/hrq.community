<?php
/**
 * This file is Copyright (c).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

const ERROR_GROUP_ALREADY_EXIST = "您要建的群已经存在";
const MAX_LENGTH_OF_GROUP_NAME = 20;
const ERROR_GROUP_NAME_IS_TOO_LONG = "群的名字太长 (最多只允许输入%d个字)";
const MAX_LENGTH_OF_GROUP_DESC = 150;
const ERROR_GROUP_DESC_IS_TOO_LONG = "群简介太长 (最多只允许输入%d个字)";

/**
 * check if the given group name already exists before it gets persisted
 * @param $name
 * @param $id
 * @return bool
 */
function check_duplicated_group_name($name, $id) {
    if (bp_has_groups( ["user_id" => bp_loggedin_user_id()] )) {

        // 1. validate the length of group name
        if (mb_strlen($name) > MAX_LENGTH_OF_GROUP_NAME) {
            bp_core_add_message( __( sprintf(ERROR_GROUP_NAME_IS_TOO_LONG, MAX_LENGTH_OF_GROUP_NAME), 'buddypress' ), 'error' );
            bp_core_redirect( trailingslashit( bp_get_groups_directory_permalink() . 'create/step/' . bp_get_groups_current_create_step() ) );
        }

        // 2. check if the group name already exists when creating a new group, case-sensitive
        $existed = false;
        while(bp_groups()) {
            bp_the_group();

            if (empty($id) && strtoupper($name) === strtoupper(bp_get_group_name())) {
                $existed = true;
                break;
            }
        }
        if ($existed) {
            bp_core_add_message( __( ERROR_GROUP_ALREADY_EXIST, 'buddypress' ), 'error' );
            bp_core_redirect( trailingslashit( bp_get_groups_directory_permalink() . 'create/step/' . bp_get_groups_current_create_step() ) );
        }
    }
    return $name;
}
add_filter('groups_group_name_before_save','check_duplicated_group_name', 10, 2);
/** END - checking group name **/

/**
 * check the number of characters in group description
 * @param $description
 * @param $id
 */
function check_characters_of_group_description($description, $id) {
    // check only when it is new
    if (empty($id)) {
        if (mb_strlen($description) > MAX_LENGTH_OF_GROUP_DESC) {
            bp_core_add_message( __( sprintf(ERROR_GROUP_DESC_IS_TOO_LONG, MAX_LENGTH_OF_GROUP_DESC), 'buddypress' ), 'error' );
            bp_core_redirect( trailingslashit( bp_get_groups_directory_permalink() . 'create/step/' . bp_get_groups_current_create_step() ) );
        }
    } else {
        return $description;
    }
}
add_filter("groups_group_description_before_save", "check_characters_of_group_description", 10, 2);
/** END - checking group description characters **/

/**-------------------------RESTRICT MIME TYPES BASED ON USER ROLE -------------------------*/
/**
 * @return array|null, all possible role id for the current logged in user
 */
function get_user_roles() {
    if ( is_user_logged_in() ) {
        global $current_user;

        return $current_user->roles;
    } else {
        return null;
    }
}
function getMimeTypesByRole($role = null) {
    $defaultMimeTypes = [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    switch($role) {
        case "普通用户":
        default:
            return $defaultMimeTypes;
    }
}
function getMimeTypesByRoleIds($roleIds) {
    $defaultMimeTypes = getMimeTypesByRole();
    if (empty($roleIds)) {
        return $defaultMimeTypes;
    }

    global $wp_roles;
    $allRoles = $wp_roles->get_names();

    $allowedMimeTypes = [];
    foreach($roleIds as $roleId) {
        if (array_key_exists($roleId, $allRoles)) {
            $mimeTypes = getMimeTypesByRole($allRoles[$roleId]);
            $allowedMimeTypes = array_merge($allowedMimeTypes, $mimeTypes);
        }
    }

    return empty($allowedMimeTypes) ? $defaultMimeTypes : $allowedMimeTypes;
}
/** restrict mime types based on user roles */
/**
 * use this to restrict mime types
 * @param array $existing_mimes
 * @return array
 */
function custom_upload_mimes ( $existing_mimes = array() ) {
    if (is_super_admin(get_current_user_id())) {
        return $existing_mimes;
    }
    return getMimeTypesByRoleIds(get_user_roles());
}
add_filter('upload_mimes', 'custom_upload_mimes');
/** END - restrict mime types */
