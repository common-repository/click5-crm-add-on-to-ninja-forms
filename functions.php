<?php
function click5_ninja_get_available_forms() {
    if ( class_exists('Ninja_Forms') ) {
        $posts = Ninja_Forms()->form()->get_forms();
        $array = array();
        foreach ($posts as $post) {
            $element = $post->get_settings();
            $id = $post->get_id();
            $array[$id] = $element["title"];
        }
        return $array;
    }
}

function click5_ninja_is_selected($option_name, $value) {
  return esc_attr(get_option($option_name)) == $value;
}

function click5_ninja_is_mapped($option_name) {
  $str_option = esc_attr(get_option($option_name));
  return $str_option !== '_undefined_' && strlen($str_option);
}

function click5_ninja_get_enabled_forms() {
  if ( class_exists('Ninja_Forms') ) {
    $posts = Ninja_Forms()->form()->get_forms();
    $array = array();
    foreach ($posts as $post) {
        $element = $post->get_settings();
        if (boolval(get_option('click5_ninja_addon_form_enable_'.$post->get_id()))) {
          $array[$post->get_id()] = $element["title"];
        }
    }
    return $array;
  }
}

function click5_ninja_get_all_forms() {
  $allForms = click5_ninja_get_available_forms();
  $enabledForms = click5_ninja_get_enabled_forms();

  $result_array = array();
  foreach($allForms as $key => $title) {
    $is_enabled = false;
    foreach($enabledForms as $enabled_key => $enabled_title) {
      if ($key == $enabled_key) {
        $is_enabled = true;
      }
    }
    $result_array[$key] = array('title' => $title, 'is_enabled' => $is_enabled);
  }
  return $result_array;
}

function click5_ninja_get_available_crm_fields() {
  //request
  $array_fields = (array)json_decode(get_option('click5_ninja_addon_crm_fields_stored'));
  $isEmpty = true;
  if ($array_fields) {
    if (count($array_fields)) {
      $isEmpty = false;
    }
  }
  return !$isEmpty ? $array_fields : array(array('parameter' => '_undefined_', 'label' => 'Please enter the Posting URL first', 'is_custom' => false));
}

function click5_ninja_get_form_fields($id) {
    $fields = Ninja_Forms()->form($id)->get_fields();
    if (!empty($fields)) {
        $result = array();
        foreach($fields as $field) {
            $result_field = array();
            $field_setting = $field->get_settings();
            if($field_setting['label'] != "Submit"){
              $result_field['name'] = $field_setting['label'];
              $result_field['type'] = $field_setting['type'];
              array_push($result, $result_field);
            }
        }
        return $result;
    } else {
        return array();
    }
}

function click5_ninja_get_const_values($form_id) {
  $allConstValues = (array)(json_decode(get_option('click5_ninja_addon_const_values')));
  $resultArray = array();
  foreach($allConstValues as $const_value) {
    $const_value = (array)$const_value;
    if ($const_value['form_id'] == $form_id) {
      $resultArray[] = $const_value;
    }
  }
  return $resultArray;
}

?>