<?php
if (!defined('ABSPATH')) {
	exit; //Exit if accessed directly
}

/**
 * All ids and static names, array.
 */
class AIOS_Abstracted_Ids {

	/**
	 * Get firewall block request methods.
	 *
	 * @return array
	 */
	public static function get_firewall_block_request_methods() {
		return array('DEBUG','MOVE', 'PUT', 'TRACK');
	}

}
