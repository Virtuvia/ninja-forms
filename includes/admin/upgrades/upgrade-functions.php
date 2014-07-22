<?php
/**
 * Upgrade Functions
 *
 * @package     Ninja Forms
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2014, WP Ninjas
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.7
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Display Upgrade Notices
 *
 * @since 2.7
 * @return void
*/
function nf_show_upgrade_notices() {
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'nf-upgrades' )
		return; // Don't show notices on the upgrades page

	$step = get_option( 'nf_convert_subs_step' );

	if ( $step != 'complete' ) {
		if ( empty( $step ) ) {
			$step = 1;
		}
		printf(
			'<div class="updated"><p>' . __( 'Ninja Forms needs to upgrade the submissions table, click <a href="%s">here</a> to start the upgrade.', 'ninja-forms' ) . '</p></div>',
			admin_url( 'index.php?page=nf-upgrades&nf-upgrade=upgrade_subs_to_cpt&step=' . $step )
		);
	}
}
add_action( 'admin_notices', 'nf_show_upgrade_notices' );

/**
 * Triggers all upgrade functions
 *
 * This function is usually triggered via AJAX
 *
 * @since 2.7
 * @return void
*/
function nf_trigger_upgrades() {
	if ( DOING_AJAX )
		die( 'complete' ); // Let AJAX know that the upgrade is complete
}
add_action( 'wp_ajax_edd_trigger_upgrades', 'nf_trigger_upgrades' );

/**
 * Upgrades for Ninja Forms v2.7 and Submission Custom Post Type.
 *
 * @since 2.7
 * @return void
 */
function nf_v27_upgrade_subs_to_cpt() {
	//Bail if we aren't in the admin.
	if ( ! is_admin() )
		return false;

	// Bail if we don't have the appropriate permissions.
	if ( ! current_user_can( 'install_plugins' ) )
		return false;

	ignore_user_abort( true );

	if ( ! nf_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		set_time_limit( 0 );
	}

	$step   = isset( $_GET['step'] )  ? absint( $_GET['step'] )  : 1;
	$total  = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;
	$number  = isset( $_GET['custom'] ) ? absint( $_GET['custom'] ) : 1;
	$form_id  = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;

	update_option( 'nf_convert_subs_step', $step );

	$convert_subs = new NF_Convert_Subs();
	$old_sub_count = $convert_subs->count_old_subs();

	if( empty( $total ) || $total <= 1 ) {
		$total = round( ( $old_sub_count / 100 ), 0, PHP_ROUND_HALF_UP ) + 1;
	}
	
	if ( $step <= $total ) {
		if ( $step == 1 ) {
			$begin = 0;
		} else {
			$begin = ( $step - 1 ) * 100;
		}

		$subs_results = $convert_subs->get_old_subs( $begin, 100 );

		if ( is_array( $subs_results ) && ! empty( $subs_results ) ) {
			
			foreach ( $subs_results as $sub ) {
				if ( $form_id != $sub['form_id'] ) {
					$form_id = $sub['form_id'];
					$number = 1;
				}				
				$converted = get_option( 'nf_converted_subs' );
				if ( empty( $converted ) )
					$converted = array();

				if ( ! in_array( $sub['id'], $converted ) ) {
					$convert_subs->convert( $sub, $number );
				}
					
				$converted[] = $sub['id'];
				update_option( 'nf_converted_subs', $converted );

				$number++;
			}
		}

		$step++;

		$redirect = add_query_arg( array(
			'page'        	=> 'nf-upgrades',
			'nf-upgrade' 	=> 'upgrade_subs_to_cpt',
			'step'        	=> $step,
			'custom'      	=> $number,
			'total'       	=> $total,
			'form_id'		=> $form_id
		), admin_url( 'index.php' ) );
		wp_redirect( $redirect ); exit;

	} else {
		update_option( 'nf_convert_subs_step', 'complete' );
		wp_redirect( admin_url( 'index.php?page=nf-about' ) ); exit;
	}
}
add_action( 'nf_upgrade_subs_to_cpt', 'nf_v27_upgrade_subs_to_cpt' );