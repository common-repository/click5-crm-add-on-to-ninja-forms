<?php
/**
* Plugin Name: click5 CRM add-on to Ninja Forms
* Plugin URI: https://www.click5interactive.com/wordpress-ninja-plugin/
* Description: Seemingly integrate your Ninja Forms with click5 CRM
* Version: 1.0.2
* Author: click5 Interactive
* Author URI: https://www.click5interactive.com/?utm_source=ninja-crm-plugin&utm_medium=plugin-list&utm_campaign=wp-plugins
**/


define('CLICK5_NINJA_VERSION', '1.0.2');
define('CLICK5_NINJA_DEV_MODE', true);


require('api.php');

function click5_ninja_auto_update ( $update, $item ) {
	$plugins = array ( 'ninja-add-on-by-click5' );
	if ( in_array( $item->slug, $plugins ) ) {
		// update plugin
		return true; 
	} else {
		// use default settings
		return $update; 
	}
}
add_filter( 'auto_update_plugin', 'click5_ninja_auto_update', 10, 2 );

// create custom plugin settings menu

add_action('admin_menu', 'click5_ninja_create_menu');

function click5_ninja_create_menu() {

  if ( class_exists('Ninja_Forms') ) {
    //create new top-level menu
    add_menu_page('CRM Add-on Settings', 'CRM Add-on', 'administrator', __FILE__, 'click5_ninja_settings_page' , 'dashicons-admin-settings', 26 );

    //call register settings function
    add_action( 'admin_init', 'click5_ninja_settings' );
  }
}

//left sidebar link
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'click5_ninja_add_plugin_page_settings_link');
function click5_ninja_add_plugin_page_settings_link( $links ) {
	$links[] = '<a href="' .
		admin_url( 'options-general.php?page=ninja-addon-by-click5%2Fninja-addon-by-click5.php' ) .
		'">' . __('Settings') . '</a>';
	//$links[] = '<a target="_blank" rel="nofollow" href="https://www.click5interactive.com/wordpress-cf7-plugin">' . __('About plugin') . '</a>';
	return $links;
}

add_filter( 'plugin_row_meta', 'click5_ninja_plugin_meta', 10, 2 );
function click5_ninja_plugin_meta( $links, $file ) { // add some links to plugin meta row
	if ( strpos( $file, 'ninja-addon-by-click5.php' ) !== false ) {
    //$links = array_merge( $links,  );

    array_splice( $links, 2, 0, array( '<a href="https://www.click5interactive.com/wordpress-cf7-plugin" target="_blank" rel="nofollow">About plugin</a>' ) );
	}
	return $links;
}

add_action( 'admin_init', 'click5_ninja_checkfor_ninja_form' );
function click5_ninja_checkfor_ninja_form() {
	if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'ninja-forms/ninja-forms.php' ) ) {
		add_action( 'admin_notices', 'click5_ninja_noc_ninja_notice' );

		deactivate_plugins( plugin_basename( __FILE__ ) ); 

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}

function click5_ninja_noc_ninja_notice() { ?>
	<div class="error">
		<p>
			<?php printf(
				__('%s must be installed and activated for the <strong>Ninja Form Add-on by click5</strong> plugin to work', 'ninja-addon-by-click5'),
				'<a href="'.admin_url('plugin-install.php?tab=search&s=ninja+forms').'">Ninja Forms</a>'
			); ?>
		</p>
	</div>
	<?php
}

// Activation
function click5_ninja_activation(){
    do_action( 'click5_ninja_default_options' );
}
register_activation_hook( __FILE__, 'click5_ninja_activation' );


function click5_ninja_forceDefaultSettings() {

}

add_action( 'click5_ninja_default_options', 'click5_ninja_forceDefaultSettings' );


function click5_ninja_settings() {
  if ( class_exists('Ninja_Forms') ) {
    register_setting('click5_ninja', 'posting_url');
    $available_forms = click5_ninja_get_available_forms();
    foreach($available_forms as $key => $form_title) {
      register_setting('click5_ninja', 'form_enable_'.$key);
    }
  }
}


function click5_ninja_settings_page() {
?>
<div class="wrap">
  <h1 class="click5_ninja_heading">click5 CRM Ninja Forms Add-on Settings&nbsp;<span class="version">v<?php echo esc_attr(CLICK5_NINJA_VERSION); ?></span></h1>
</div>
<?php if( isset($_GET["settings-updated"]) ) { ?>
<div id="message" class="updated">
<p><strong><?php _e("Settings saved."); ?></strong></p>
</div>
<?php } ?>

<div class="wrap click5_ninja_wrapper_content_settings">
<div class="content-left">
<?php
      $verification_token = md5(uniqid(rand(), true));
      $cur_user_id = wp_get_current_user()->user_login;
      update_option('click5_ninja_authentication_token_'.$cur_user_id, $verification_token);
      $b_options_enabled = strlen(get_option('click5_ninja_addon_posting_url')) > 0;
?>
<input type="hidden" id="verification_token" value="<?php echo esc_attr($verification_token); ?>" />
<input type="hidden" id="user_identificator" value="<?php echo esc_attr($cur_user_id); ?>" />
<div>
    <?php settings_fields( 'click5_ninja' ); ?>
    <?php do_settings_sections( 'click5_ninja' ); ?>
    <div id="poststuff">
      <div id="post-body-content">
        <div class="postbox">
          <h3 class="hndle"><span>click5 CRM <strong>Posting URL</strong></span></h3>
          <div class="inside" id="posting_url_wrapper">
            <input type="text" id="click5_ninja_addon_posting_url" placeholder="This field is required for next steps" name="click5_ninja_addon_posting_url" value="<?php echo esc_url(get_option('click5_ninja_addon_posting_url')); ?>" style="width: 100%;"/>
          </div>
        </div>
        <div class="postbox can-disable <?php echo esc_attr(!$b_options_enabled) ? ' disabled' : ''; ?>">
          <h3 class="hndle"><span><strong>Enable</strong> per Ninja Form</span></h3>
          <div class="inside">
            <?php
            if ( class_exists('Ninja_Forms') ) {
              $available_forms = click5_ninja_get_available_forms();
              foreach($available_forms as $key => $form_title) {
                ?>
                <div class="enable-per">
                  <label style="display: flex; margin: 5px 0; justify-content: flex-start; align-items: center; min-height: 30px;">
                    <input type="checkbox" class="can-disable" value="1" data-value="<?php echo esc_attr($key); ?>" id="click5_ninja_addon_form_enable_<?php echo esc_attr($key); ?>" name="click5_ninja_addon_form_enable_<?php echo esc_attr($key); ?>" <?php echo boolval(esc_attr(get_option('click5_ninja_addon_form_enable_'.esc_attr($key)))) ? ' checked' : ''; ?><?php echo esc_attr($b_options_enabled) ? '' : ' disabled'; ?>/>
                    <span><?php echo esc_attr($form_title); ?></span>
                  </label>
                </div>
                <?php
              }
            }
            ?>
          </div>
        </div>
        <?php
          $available_crm_fields = click5_ninja_get_available_crm_fields();
          $allForms = click5_ninja_get_all_forms();
          $enabledForms = click5_ninja_get_enabled_forms();
        ?>
        <input type="hidden" id="phpFormData" value="<?php echo esc_html(json_encode($allForms)); ?>" />
        <input type="hidden" id="phpCRMfields" value="<?php echo esc_html(json_encode($available_crm_fields)); ?>"/>
        <?php
            $disclaimerClass = 'all-off-text hidden';
            $tabHeadingsClass = 'tab-headings';
            if(count($enabledForms) == 0) {
                $disclaimerClass = 'all-off-text';
                $tabHeadingsClass = 'tab-headings empty';
            }
        ?>
        <div class="<?php echo esc_attr($tabHeadingsClass); ?>">
          <p class="<?php echo esc_attr($disclaimerClass); ?>"><strong>Enable</strong> the individual <strong>forms</strong> above in order to configure them.</p>
          <ul class="nav">
            <?php
              $countTab = 0;
              $activatedAlreadyTab = false;
              foreach($allForms as $id => $form_object) {
                  $tabClass = '';
                  if ($form_object['is_enabled'] && !$activatedAlreadyTab) {
                    $tabClass = ' active';
                    $activatedAlreadyTab = true;
                  } else if (!$form_object['is_enabled']) {
                    $tabClass .= 'hidden';
                  }
                ?>
                  <li class="<?php echo esc_attr($tabClass); ?>" data-value="<?php echo esc_attr($id); ?>"><a href="#" class="toggler" data-value="<?php echo esc_attr($id); ?>"><?php echo esc_attr($form_object['title']); ?><span class="count-errors" style="display:none"><i class="fa fa-exclamation" aria-hidden="true"></i></span></a></li>
                <?php
                $countTab++;
              }
            ?>

            <li data-value="error-log"><a href="#" class="toggler" data-value="error-log">CRM Log<?php if(!empty(get_option('click5_ninja_addon_notifications_count_errors'))){ ?><span class="count-errors"><?php echo get_option('click5_ninja_addon_notifications_count_errors'); ?></span><?php } ?></a></li>
          </ul>
        </div>
        <?php
          $countTabContent = 0;
          $activatedAlreadyTabContent = false;
          foreach($allForms as $id => $form_object) {
            $tabContentClass = 'tab-content';
            
            if ($form_object['is_enabled'] && !$activatedAlreadyTabContent) {
              $tabContentClass .= ' active';
              $activatedAlreadyTabContent = true;
            } else if (!$form_object['is_enabled']) {
              $tabContentClass .= ' hidden';
            }

            ?>
            <div data-value="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($tabContentClass); ?>">
              <div class="postbox can-disable <?php echo esc_attr(!$b_options_enabled) ? ' disabled' : ''; ?>">
                <h3 class="hndle"><span>Enable specific fields for <strong><?php echo esc_attr($form_object['title']); ?></strong></span><span style="flex: 1; width: 100%; max-width: 300px; min-width: 50%;"><strong>Map</strong>&nbsp;to</span></h3>
                  <div class="inside">
                    <?php
                      $fields = click5_ninja_get_form_fields($id);
                      foreach($fields as $field) {
                        if (!$field['type']) {
                          continue;
                        }
                        $is_mapped = click5_ninja_is_mapped('click5_ninja_addon_map_to_'.esc_attr($id).'_'.esc_attr($field['name']));
                        ?>
                        <div class="map-field">
                          <label style="display: inline-flex; margin: 5px 0; justify-content: flex-start; align-items: center;">
                            
                            <div class="enable">
                            <input type="checkbox" class="enable_lvl2 can-disable" data-value="<?php echo esc_attr($id); ?>" id="click5_ninja_addon_field_enabled_<?php echo esc_attr($id).'_'.esc_attr($field['name']); ?>" name="click5_ninja_addon_field_enabled_<?php echo esc_attr($id).'_'.esc_attr($field['name']); ?>" value="1" <?php echo boolval(get_option('click5_ninja_addon_field_enabled_'.$id.'_'.esc_attr($field['name']))) ? ' checked' : ''; ?><?php echo esc_attr($b_options_enabled) ? '' : ' disabled'; ?>/>
                            </div>
                            <span><strong><?php echo esc_attr($field['type']); ?></strong>:&nbsp;<?php echo esc_attr($field['name']); ?></span>
                          </label>
                          <label style="display: inline-block; margin: 5px 0;">
                            <span>
                              <select data-value="<?php echo esc_attr($id); ?>" name="click5_ninja_addon_map_to_<?php echo esc_attr($id).'_'.esc_attr($field['name']); ?>" class="map_to">
                                <?php
                                  $countNotUndefined = 0;
                                  foreach($available_crm_fields as $crm_field) {
                                    $crm_field = (array)$crm_field;
                                    if ($crm_field['parameter'] !== '_undefined_') {
                                      $countNotUndefined++;
                                    }
                                  }
                                  if ($countNotUndefined > 0) {
                                    ?>
                                      <option value="_undefined_">--- Select an Option ---</option>
                                    <?php
                                  }
                                ?>
                                <?php
                                  foreach($available_crm_fields as $crm_field) {
                                    $crm_field = (array)$crm_field;
                                    $is_selected = click5_ninja_is_selected('click5_ninja_addon_map_to_'.$id.'_'.esc_attr($field['name']), $crm_field['parameter']);
                                    $required = false;
                                    if (isset($crm_field['required'])) {
                                      if ($crm_field['required'] == true) {
                                        $required = true;
                                      }
                                    }

                                    ?>
                                      <option value="<?php echo esc_attr($crm_field['parameter']); ?>" <?php echo esc_attr(($is_selected ? ' selected' : ''));?>><?php echo esc_attr($crm_field['label']); ?><?php echo esc_attr($required) ? '*' : ''; ?></option>
                                    <?php
                                  }
                                ?>
                              </select>
                            </span>
                          </label>
                          <div class="round tick">
                              <?php $tickName = 'tick__'.$id.'_'.esc_attr($field['name']); ?>
                              <input type="checkbox" disabled id="<?php echo esc_attr($tickName); ?>" <?php echo esc_attr($is_mapped) ? ' checked' : '' ?>/>
                              <label for="<?php echo esc_attr($tickName); ?>"></label>
                            </div>
                        </div>
                        <?php
                      }
                    ?>
                    <div class="validate-error-info" id="validate-error-info_<?php echo esc_attr($id); ?>"></div>
                  </div>
                </h3>
              </div>
              <div class="postbox can-disable <?php echo esc_attr(!$b_options_enabled) ? ' disabled' : ''; ?>">
                <h3 class="hndle"><span>Constant values for <strong><?php echo esc_attr($form_object['title']); ?></strong></span><span style="flex: 1; width: 100%; max-width: 300px; min-width: 50%;"><strong>Value</strong></span></h3>
                <div class="inside constant-values-wrapper">
                  <p class="no-values-yet hidden" data-value="<?php echo esc_attr($id); ?>">You don't have any saved values yet.</p>
                  <ul class="constants_list" data-value="<?php echo esc_attr($id); ?>"></ul>
                  <form class="add_constant_value" data-value="<?php echo esc_attr($id); ?>">
                    <div class="left">
                      <select name="crm_field" class="map_to not-ajaxable">
                        <?php
                          if ($countNotUndefined > 0) {
                            ?>
                              <option value="_undefined_">--- Select an Option ---</option>
                            <?php
                          }
                          foreach($available_crm_fields as $crm_field) {
                          $crm_field = (array)$crm_field;
                          $required = false;
                          if (isset($crm_field['required'])) {
                            if ($crm_field['required'] == true) {
                              $required = true;
                            }
                          }

                          ?>
                            <option value="<?php echo esc_attr($crm_field['parameter']); ?>"><?php echo esc_attr($crm_field['label']); ?><?php echo esc_attr($required) ? '*' : '' ?></option>
                          <?php
                          }
                        ?>
                      </select>
                      <input type="text" class="value not-ajaxable" placeholder="value" name="value"></input>
                      <select class="value not-ajaxable" name="value" style="display:none"></select>
                    </div>
                    <button data-value="<?php echo esc_attr($id); ?>" class="add">Add Value</button>
                  </form>
                </div>
              </div>
            </div>
            <?php
            $countTabContent++;
          }
        ?>
        <div data-value="error-log" class="tab-content">
          <div class="postbox">
            <div id="crm_validation_notifications">
              <?php

                if(!empty(get_option('click5_ninja_addon_notifications'))){

                  $json_decode = json_decode(get_option('click5_ninja_addon_notifications'), true);

                  $json_decode = array_reverse($json_decode);

                  $len = isset($json_decode) ? count($json_decode) : 0;

                  if($len > 10){
                    $len = 10;
                  }

                  for ($i = 0; $i<$len; $i++){
                  $data = $json_decode[$i];
                    ?>
                      <div id="<?php echo esc_attr($data['uuid']); ?>" class="item _<?php echo esc_attr($data['type']); ?>">
                        <p><?php echo esc_attr($data['message']); ?></p>
                      </div>
                    <?php
                  }
                }
              ?>
            </div>
            <div id="click5_more">Load More <span class="click5_loader"></span></div>
          </div>
        </div>
        <script type="text/javascript">
        jQuery(document).ready(function($){
          var last = 10;

          $( "#click5_more" ).click(function() {
            $(".click5_loader").addClass('visible-loader');
            $.post('../wp-json/click5_ninja_addon/API/get_pagination_logs', {last: last}, function(data) {
              if (data) {

                $("#crm_validation_notifications").html(data);
                $(".click5_loader").removeClass('visible-loader');
                last += 10;

                if( last > data.length ){
                  $( "#click5_more" ).hide();
                }

              }
            });
          });
        });
        </script>
      </div>
    </div>

</div>
</div>
<div class="content-right">
      <div id="poststuff">
        <div id="post-body-content">
            <div class="postbox">
              <h3 class="hndle"><span>Plugin Support</span></h3>
              <div class="inside">
                <p>Visit our <a href="http://wordpress.org/support/plugin/click5-crm-add-on-to-ninja-forms" target="_blank" rel="nofollow">community forum</a> to find answers to common issues, ask questions, submit bug reports, feature suggestions and other tips about our plugin.</p>
                <p>Please consider supporting our plugin by <a href="https://wordpress.org/support/plugin/click5-crm-add-on-to-ninja-forms/reviews/?filter=5" target="_blank" rel="nofollow">leaving a review</a>. Thank You!</p>
              </div>
            </div>
        </div>
      </div>
</div>
</div>

<?php }

function click5_ninja_init_admin_scripts() {
  //libraries
  $screen = get_current_screen();
  $version = CLICK5_NINJA_DEV_MODE ? time() : CLICK5_NINJA_VERSION;

  if(strpos($screen->base, 'ninja-addon-by-click5') !== false) {
    wp_enqueue_style( 'click5_ninja_css_admin', plugins_url('/css/admin/index.css', __FILE__), array(), $version);
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array(), $version);
    wp_enqueue_script('click5_ninja_js_main', plugins_url('/main.js', __FILE__), array(), $version);
  }
}
add_action('admin_enqueue_scripts','click5_ninja_init_admin_scripts');


function click5_ninja_count_errors($arr){
  $i=0;
  foreach($arr as $x=>$x_value) {
    if($x_value == 'error'){
      $i++;
    }
  }
  return $i;
}

// do work
function click5_ninja_inlineNotificationPush($notification) {
  $notification = (array)$notification;
  $current_notifications = (array)(json_decode(get_option('click5_ninja_addon_notifications')));
  if (!is_array($current_notifications) || empty($current_notifications)) {
    $current_notifications = array();
  }

  $newNotification = array(
    'uuid' => time().rand(),
    'type' => $notification['type'],
    'message' => '<strong>'.$notification['form_name'].'</strong> on '.$notification['date'].'<br>'.$notification['message']
  );

  if(!empty(get_option('click5_ninja_addon_notifications_count_errors'))){
    $curr_error_value = get_option('click5_ninja_addon_notifications_count_errors');
    $new_error_value = $curr_error_value + click5_ninja_count_errors($newNotification);
  } else {
    $new_error_value = click5_ninja_count_errors($newNotification);
  }

  update_option('click5_ninja_addon_notifications_count_errors', $new_error_value);

  $current_notifications[] = $newNotification;
  update_option('click5_ninja_addon_notifications', json_encode($current_notifications));

}

function click5_ninja_get_form_name_by_id($id) {
  if ( class_exists('Ninja_Forms') ) {
    $name = '';
    $forms = click5_ninja_get_available_forms();
    foreach($forms as $key => $title) {
      if ($key == $id) {
        $name = $title;
      }
    }
    return $name;
    }
}


function click5_ninja_do_work($contact_form) {
        $form_id =  $contact_form["form_id"];
        $form_name = click5_ninja_get_form_name_by_id($form_id);

          $postBody = array();

          $posted_data = (array)$contact_form["fields"];

        foreach($posted_data as $value) {

            $key = str_replace(' ', '_', esc_attr($value['label']));
            if($key != "Imię") {
                $key = preg_replace('/[^A-Za-z0-9\_]/', '', $key);
            }
            $crm_field = esc_attr(get_option('click5_ninja_addon_map_to_'.$form_id.'_'.$key));
              if (strlen($crm_field)) {
                  if ($crm_field != '_undefined_') {
                      $postBody[] = array('field' => $crm_field, 'value' => esc_attr($value['value']));
                  }
              }
          }

          $available_crm_fields = click5_ninja_get_available_crm_fields();

          $final_post_body = array();

          foreach($available_crm_fields as $crm_field) {
            $crm_field = (array)$crm_field;
            if ($crm_field['parameter'] !== '_undefined_') {
              foreach($postBody as &$posted) {
                if ($posted['field'] == $crm_field['parameter']) {


                  if($crm_field['parameter'] == 'name'){
                    if(strpos(trim($posted['value']), ' ') !== false) {
                      $posted_value = $posted['value'];
                    } else {
                      $posted_value = $posted['value'] . ' click5CRM';
                    }
                  } elseif($crm_field['parameter'] == 'cellPhone' || $crm_field['parameter'] == 'workPhone'){
                    $posted_value = str_replace(array('(', ')', ' ', '-'), '', $posted['value']);;
                  } else {
                    $posted_value = $posted['value'];
                  }


                  $crm_field['value'] = $posted_value;
                  $crm_field['is_custom'] = $crm_field['is_custom'] == true;
                  $crm_field['is_plugin_const_value'] = false;
                  $final_post_body[] = $crm_field;
                }
              }
            }
          }

          $constValues = click5_ninja_get_const_values($form_id);

          foreach($constValues as $const_value) {
            $const_value = (array)$const_value;

            $final_post_body[] = array(
              'parameter' => $const_value['id'],
              'label' => $const_value['label'],
              'value' => $const_value['value'],
              'is_custom' => $const_value['is_custom'],
              'is_plugin_const_value' => true
            );
            
            
          }

          if (count($final_post_body)) {
            $final_post_body[] = array(
              'parameter' => 'type',
              'label' => 'Type',
              'value' => 'person',
              'is_custom' => false,
              'is_plugin_const_value' => true
            );

            //file_put_contents(dirname(__FILE__).'/debug.txt', print_r($final_post_body, true));
            $url = esc_url(get_option('click5_ninja_addon_posting_url'));
            $payload = json_encode( $final_post_body );

            $args = array(
                'body'        => $payload,
                'headers'     => array('Content-Type' => 'application/json'),
            );

            $result = wp_remote_post( $url, $args );

            //parse result
            try {
              $notificationArray = array();
              $submissionDate = date_i18n('F d Y, G:ia');
              $submissionDate = str_replace (",", " at", $submissionDate);

              $resultObject = (array)(json_decode(wp_remote_retrieve_body($result)));
              if (is_array($resultObject)) {
                foreach($resultObject as $key => $value) {
                  if ($key == 'error') {
                    //push error static notification
                    $message = $value;
                    $notificationArray[] = array('type' => 'error', 'message' => $message, 'date' => $submissionDate, 'form_name' => $form_name);
                  } else if ($key == 'warnings') {
                    $arrayWarnings = (array)$value;
                    foreach($arrayWarnings as $warning) {
                      $objWarning = (array)$warning;
                      foreach($objWarning as $message) {
                        $notificationArray[] = array('type' => 'warning', 'message' => $message, 'date' => $submissionDate, 'form_name' => $form_name);
                      }
                    }
                  }
                }
              }

              foreach($notificationArray as $notification) {
                //push final notifications
                click5_ninja_inlineNotificationPush($notification);
              }
            } catch (Exception $e) {

            }
          }

  return $contact_form;
}

add_action("ninja_forms_after_submission", "click5_ninja_do_work"); 

// uninstall hook

function click5_ninja_uninstallFunction() {
}

register_uninstall_hook(__FILE__, 'click5_ninja_uninstallFunction');
