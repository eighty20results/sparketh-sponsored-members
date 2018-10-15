<?php
/**
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

/**
 * Sponsored Members set up
 *
 * See: https://www.paidmembershipspro.com/add-ons/pmpro-sponsored-members/
 */


// S812456E01B

/**
 * Member login redirect based on level
 */

function my_login_redirect($redirect_to, $request, $user)
{
	//is there a user to check?
	if(!empty($user->ID))
	{
		//check level
		if(pmpro_hasMembershipLevel(2, $user->ID))
			return home_url("/my-account/");
		elseif (pmpro_hasMembershipLevel(1, $user->ID))
			return home_url("/my-account/");
		else
			return $redirect_to;
	}
}
add_filter("login_redirect", "my_login_redirect", 10, 3);

//The account you selected is Sparketh Parent - Monthly Subscription which includes 2 student accounts with unlimited access to all courses and new courses weekly.
//
//You have selected the Parent membership level.

function gettext_membership($output_text, $input_text, $domain)
{
	if ('paid-memberships-pro' === $domain) {
		$output_text = str_replace('You have selected the', 'The account you selected is Sparketh ', $output_text);
		$output_text = str_replace('membership level.', 'Subscription which includes 2 student accounts with unlimited access to all courses and new courses weekly.', $output_text);
	}
	return $output_text;
}

add_filter('gettext', 'gettext_membership', 10, 3);

