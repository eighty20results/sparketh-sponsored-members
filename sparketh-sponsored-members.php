<?php
/*
Plugin Name: Sparketh: Sponsored Members, etc
Plugin URI: https://eighty20results.com/paid-memberships-pro/do-it-for-me/
Description: Configure sponsored memberships, Export to CSV, etc
Version: 1.2
Author: Eighty / 20 Results by WSC, LLC <thomas@eighty20results.com>
Author URI: https://eighty20results.com/thomas-sjolshagen/
License: GPL2
 *  Copyright (c) 2018. - Eighty / 20 Results by Wicked Strong Chicks.
 *  ALL RIGHTS RESERVED
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  You can contact us at mailto:info@eighty20results.com
*/

if ( ! isset( $pmprosm_sponsored_account_levels ) ) {
	global $pmprosm_sponsored_account_levels;
}

if ( ! isset( $e20r_export_order ) ) {
	global $e20r_export_order;
}

class SP_Sponsored_Members {
	
	/**
	 * Current instance of the SP_Sponsored_Members class (Singleton)
	 *
	 * @var null|SP_Sponsored_Members $instance
	 */
	private static $instance = null;
	
	/**
	 * SP_Sponsored_Members constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * Instantiate or return an existing instance of the SP_Sponsored_Members class
	 *
	 * @return null|SP_Sponsored_Members
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Load all action/filter hooks for the plugin
	 */
	public function loadHooks() {
		
		add_action( 'plugins_loaded', array( $this, 'loadSponsoredMemberSettings' ), 9999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'loadJSForCheckout' ), 10 );
		add_action( 'pmpro_before_change_membership_level', array(
			$this,
			'preventChildAccountFromCancelling',
		), - 1, 4 );
		
		add_filter( 'pmpro_level_cost_text', array( $this, 'levelCostText' ), 15, 4 );
		add_filter( 'pmpro_members_list_csv_extra_columns', array( $this, 'addBillingInfoToCSV' ), 10, 1 );
		add_filter( 'gettext', array( $this, 'gettextMembership' ), 10, 3);
		// add_action( 'pmpro_after_change_membership_level', array( $this, 'removeDiscountCodeWhenCancelling'), 11, 2 );
	}
	
	/**
	 * Update the info for the level description in PMPro
	 *
	 * @param string $output_text
	 * @param string $input_text
	 * @param string $domain
	 *
	 * @return null|string|string[]
	 */
	public function gettextMembership($output_text, $input_text, $domain) {
		
		if ( 1 === preg_match('/paid-memberships-pro/', $domain ) ) {
			$output_text = preg_replace('/You have selected the/', 'The account you selected is Sparketh ', $output_text );
			$output_text = preg_replace('/membership level\./', 'Subscription which includes 2 student accounts with unlimited access to all courses and new courses weekly.', $output_text);
		}
		return $output_text;
	}


	/**
	 * Add required extra columns to Members List CSV export
	 *
	 * @param array $extra_columns
	 *
	 * @return array
	 */
	public function addBillingInfoToCSV( $extra_columns ) {
		
		$extra_columns['membership_id']                      = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_payment_transaction_id']  = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_payment_subscription_id'] = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_gateway_environment']     = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_gateway']                 = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_status']                  = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_expirationyear']          = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_expirationmonth']         = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_accountnumber']           = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_cardtype']                = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_cardtype']                = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_startdate']               = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_enddate']                 = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_initial_payment']         = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_billing_amount']          = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_cycle_number']            = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_cycle_period']            = array( $this, 'getCustomerInfo' );
		$extra_columns['membership_code_id']                 = array( $this, 'getCustomerInfo' );
		$extra_columns['pmpro_braintree_customerid']         = array( $this, 'getCustomerInfo' );
		$extra_columns['pmprosm_sponsor']                    = array( $this, 'getCustomerInfo' );
		$extra_columns['user_registered']                    = array( $this, 'getCustomerInfo' );
		
		return $extra_columns;
	}
	
	/**
	 * Return the Braintree Customer ID
	 *
	 * @param \WP_User $user
	 * @param string   $header
	 *
	 * @return mixed
	 */
	public function getCustomerInfo( $user, $header ) {
		
		if ( empty( $e20r_export_order ) || ( ! empty( $e20r_export_order ) && empty( $e20r_export_order->id ) ) ) {
			$e20r_export_order = new \MemberOrder();
			$e20r_export_order->getLastMemberOrder( $user->ID );
		}
		
		$user_level = pmpro_getMembershipLevelForUser( $user->ID, true );
		
		$value = null;
		
		switch ( $header ) {
			case 'user_registered':
				
				$value = ! empty( $user->user_registered ) ? date( 'Y-m-d H:i:s', strtotime( $user->user_registered, current_time( 'timestamp' ) ) ) : null;
				break;
			
			case 'pmprosm_sponsor':
				$sponsor_user = function_exists( 'pmprosm_getSponsor' ) ? pmprosm_getSponsor( $user->ID, true ) : null;
				$value =  !empty( $sponsor_user ) ? $user->ID : null;
				break;
			
			case 'membership_startdate':
				$value = isset( $user_level->startdate ) ? date( 'Y-m-d H:i:s', $user_level->startdate ) : null;
				break;
			
			case 'membership_enddate':
				$value = ! empty( $user_level->enddate ) ? date( 'Y-m-d H:i:s', $user_level->enddate ) : null;
				break;
			
			case 'membership_initial_payment':
				$value = isset( $user_level->initial_payment ) ? $user_level->initial_payment : null;
				break;
			
			case 'membership_billing_amount':
				$value = isset( $user_level->billing_amount ) ? $user_level->billing_amount : null;
				break;
			
			case 'membership_cycle_number':
				$value = isset( $user_level->cycle_number ) ? $user_level->cycle_number : null;
				break;
			
			case 'membership_cycle_period':
				$value = isset( $user_level->cycle_period ) ? $user_level->cycle_period : null;
				break;
			
			case 'membership_code_id':
				$value = isset( $user_level->code_id ) ? $user_level->code_id : null;
				break;
			
			case 'pmpro_braintree_customerid':
				$value = get_user_meta( $user->ID, $header, true );
				break;
			
			case 'membership_id':
				$value = ( isset( $user_level->id ) && $user_level->id > 0 ? $user_level->id : null );
				break;
			
			case 'membership_status':
				$status = $this->getMemberLevelStatusFor( $user->ID, $user_level->id );
				$value  = ( ! empty( $status ) ? $status : 'inactive' );
				break;
			
			case 'membership_payment_transaction_id':
				$value = isset($e20r_export_order->payment_transaction_id) ? $e20r_export_order->payment_transaction_id : null;
				break;
			
			case 'membership_payment_subscription_id':
				$value = isset( $e20r_export_order->subscription_transaction_id ) ? $e20r_export_order->subscription_transaction_id : null;
				break;
			
			case 'membership_gateway_environment':
				$value = isset( $e20r_export_order->gateway_environment ) ? $e20r_export_order->gateway_environment : null;
				break;
			
			case 'membership_gateway':
				$value = isset( $e20r_export_order->gateway ) ? $e20r_export_order->gateway : null;
				break;
			
			case 'membership_expirationmonth':
				$value = isset( $e20r_export_order->expirationmonth ) ? $e20r_export_order->expirationmonth : null;
				break;
			
			case 'membership_expirationyear':
				$value = isset( $e20r_export_order->expirationyear ) ? $e20r_export_order->expirationyear : null;
				break;
			
			case 'membership_accountnumber':
				$value = isset( $e20r_export_order->accountnumber ) ? $e20r_export_order->accountnumber : null;
				break;
			
			case 'membership_cardtype':
				$value = isset( $e20r_export_order->cardtype ) ? $e20r_export_order->cardtype : null;
				break;
		}
		
		
		return $value;
	}
	
	/**
	 * Return the Membership status for the user ID/Level ID
	 *
	 * @param int $user_id
	 * @param int $level_id
	 *
	 * @return null|string
	 */
	private function getMemberLevelStatusFor( $user_id, $level_id ) {
		
		global $wpdb;
		
		$sql = $wpdb->prepare(
			"SELECT mu.status
						FROM {$wpdb->pmpro_memberships_users} AS mu
							WHERE mu.user_id = %d AND
							mu.membership_id = %d AND
							mu.status = %s
						ORDER BY mu.id DESC
						LIMIT 1",
			$user_id,
			$level_id,
			'active'
		);
		
		$result = $wpdb->get_var( $sql );
		error_log( " Returned: {$result}" );
		
		return $result;
	}
	
	/**
	 * Don't allow a child account to cancel their membership
	 *
	 * @param int   $level_id
	 * @param int   $user_id
	 * @param array $old_levels
	 * @param int   $cancel_level
	 */
	public function preventChildAccountFromCancelling( $level_id, $user_id, $old_levels, $cancel_level ) {
		
		if ( 0 != $level_id ) {
			return;
		}
		
		if ( ! pmprosm_isSponsoredLevel( $cancel_level ) ) {
			return;
		}
		
		$sponsor_id = pmprosm_getSponsor( $user_id );
		
		if ( empty( $sponsor_id ) ) {
			return;
		}
		
		pmpro_setMessage( __( 'Cannot cancel student account!', 'spartketh-sponsored-members' ), 'pmpro_error' );
		
		wp_redirect( home_url( '/my-account/' ) );
		exit();
	}
	
	/**
	 * Remove the Discount Code for the sponsor when they cancel their membership
	 *
	 * @param int $level_id
	 * @param int $user_id
	 */
	public function removeDiscountCodeWhenCancelling( $level_id, $user_id ) {
		
		if ( ! empty( $level_id ) ) {
			error_log( "Not cancelling so returning!" );
			
			return;
		}
		
		if ( ! function_exists( 'pmprosm_getCodeByUserID' ) ) {
			return;
		}
		
		// is there a discount code attached to this user?
		$discount_code_id = pmprosm_getCodeByUserID( $user_id );
		
		if ( empty( $discount_code_id ) ) {
			return;
		}
		
		global $wpdb;
		
		/**
		 * Disallow discount code use after sponsor cancels account
		 */
		if ( false !== $wpdb->delete( $wpdb->pmpro_discount_codes, array( 'id' => $discount_code_id ) ) ) {
			
			pmprosm_deleteCodeUserID( $discount_code_id );
			
		}
		
	}
	
	/**
	 * Add JavaScript if we're processing a
	 */
	public function loadJSForCheckout() {
		
		global $pmpro_pages;
		$has_level = isset( $_REQUEST['level'] ) ? intval( $_REQUEST['level'] ) : null;
		
		if ( ! empty( $has_level ) ) {
			
			$level_info = pmpro_getLevel( $has_level );
			
			if ( ! isset( $level_info->initial_payment ) ) {
				return;
			}
			
			global $pmprosm_sponsored_account_levels;
			
			$seat_cost = isset( $pmprosm_sponsored_account_levels[ $level_info->id ]['seat_cost'] ) ? $pmprosm_sponsored_account_levels[ $level_info->id ]['seat_cost'] : null;
			
			if ( empty( $seat_cost ) ) {
				return;
			}
			
			$min_seats = isset( $pmprosm_sponsored_account_levels[ $level_info->id ]['min_seats'] ) ? $pmprosm_sponsored_account_levels[ $level_info->id ]['min_seats'] : null;
			
			if ( empty( $min_seats ) ) {
				return;
			}
			
			if ( isset( $_REQUEST['seats'] ) ) {
				$seats = intval( $_REQUEST['seats'] );
			} else {
				$seats = $min_seats;
			}
			
			$level_cost = (float) ( $level_info->initial_payment * ( $seat_cost * $seats ) );
			
			// Register the JS for Sparketh
			wp_register_script( 'sparketh-pricing', plugins_url( 'js/sparketh-pricing.js', __FILE__ ), array( 'jquery' ), '1.09' );
			
			// Load variables
			wp_localize_script( 'sparketh-pricing', 'sparketh', array(
					'level_cost'     => $level_info->initial_payment,
					'initial_seats'  => $min_seats,
					'per_seat_price' => $seat_cost,
					'current_seats'  => $seats,
					'current_price'  => $level_cost,
				)
			);
			
			wp_enqueue_script( 'sparketh-pricing' );
		}
	}
	
	/**
	 * Generate a custom Level Cost text for the membership level to include Sponsored member info when applicable
	 *
	 * @param string    $text
	 * @param \stdClass $level
	 * @param mixed     $tags
	 * @param bool      $is_short
	 *
	 * @return string
	 */
	public function levelCostText( $text, $level, $tags = null, $is_short = false ) {
		
		global $pmpro_pages;
		global $pmprosm_sponsored_account_levels;
		
		$is_parent_level = in_array( $level->id, array_keys( $pmprosm_sponsored_account_levels ) );
		
		if ( ! $is_parent_level ) {
			return $text;
		}
		
		$text_is_replaceable = ( 1 === preg_match( '/[e20r_sponsor_level_text]/i', $text ) );
		
		if ( false === $text_is_replaceable ) {
			return $text;
		}
		
		$text           = '';
		$is_checkout    = is_page( $pmpro_pages['checkout'] );
		$is_levels      = is_page( $pmpro_pages['levels'] );
		$is_settings    = function_exists( 'is_admin' ) ? is_admin() : false;
		$per_seat_price = $pmprosm_sponsored_account_levels[ $level->id ]['seat_cost'];
		$min_seats      = $pmprosm_sponsored_account_levels[ $level->id ]['min_seats'];
		
		if ( isset( $_REQUEST['seats'] ) ) {
			$seats = intval( $_REQUEST['seats'] );
		} else {
			$seats = $min_seats;
		}
		
		$level_cost = ( $level->initial_payment + ( $per_seat_price * $seats ) );
		$period     = $level->cycle_period;
		
		// Change text to 'year' if the # of cycles >= 12
		if ( 'month' == strtolower( $level->cycle_period ) && ! $is_short ) {
			$period = ( 12 <= $level->cycle_number ? 'Year' : 'Month' );
		}
		
		if ( $is_checkout || $is_levels || $is_settings ) {
			
			if ( ! $is_short ) {
				
				$text .= sprintf( '<span class="e20r-member-price">The price for membership is %1$s<span class="e20r-full-price">%2$.2f</span> per %3$s. This price includes <span class="e20r-child-accounts">%4$d</span> child accounts</span>',
					'$',
					$level_cost,
					$period,
					$seats
				);
			} else {
				$text .= sprintf( '<span class="e20r-member-price">%1$s<span class="e20r-full-price">%2$.2f</span> per %3$s %4$s(s). Includes <span class="e20r-child-accounts">%5$d</span> child accounts</span>',
					'$',
					$level_cost,
					$level->cycle_number,
					$level->cycle_period,
					$seats
				);
			}
		}
		
		return $text;
	}
	
	/**
	 * Configure PMPro Sponsored Memberships for 1 Parent + 2 Student Accounts @ USD 25
	 *
	 * Allow adding additional Sponsored Memberships at $5/student
	 */
	public function loadSponsoredMemberSettings() {
		
		global $pmprosm_sponsored_account_levels;
		
		$pmprosm_sponsored_account_levels = array(
			
			// For the Parent- Yearly account type (2 seats included, can add more)
			3 => array(
				'main_level_id'                              => 3,
				'sponsored_level_id'                         => 2,
				'seat_cost'                                  => '50.00',
				'min_seats'                                  => 2,
				'max_seats'                                  => 10,
				'seats'                                      => 2,
				'sponsored_accounts_at_checkout'             => false,
				'add_created_accounts_to_confirmation_email' => true,
				'add_code_to_confirmation_email'             => true,
			),
			//For the Parent - Monthly account type (2 seats included, can add more)
			1 => array(
				'main_level_id'                              => 1,
				'sponsored_level_id'                         => 2, // Student account
				'seat_cost'                                  => '5.00',
				'min_seats'                                  => 2,
				'max_seats'                                  => 10,
				'seats'                                      => 2,
				'sponsored_accounts_at_checkout'             => false,
				'add_created_accounts_to_confirmation_email' => true,
				'add_code_to_confirmation_email'             => true,
			),
		);
	}
}

add_action( 'plugins_loaded', array( SP_Sponsored_Members::getInstance(), 'loadHooks' ) );

/**
 * One-click update handler & checker
 */
if ( ! class_exists( '\\Puc_v4_Factory' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'includes/plugin-updates/plugin-update-checker.php' );
}

$plugin_updates = \Puc_v4_Factory::buildUpdateChecker(
	'https://eighty20results.com/protected-content/sparketh-sponsored-members/metadata.json',
	__FILE__,
	'sparketh-sponsored-members'
);