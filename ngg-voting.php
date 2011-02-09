<?php
/*
Plugin Name: NextGEN Gallery Voting
Description: This plugin allows users to add user voting to NextGEN Gallery Images 
Version: 1.7.1
Author: Shaun Alberts
*/
/*
Copyright 2011  Shaun Alberts  (email : shaunalberts@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// stop direct call
if(preg_match("#".basename(__FILE__)."#", $_SERVER["PHP_SELF"])) {die("You are not allowed to call this page directly.");}

//{
	// api funcs {
		/**
		 * Gets the voting options for a specific gallery
		 * @param int $gid The NextGEN Gallery ID
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return object of options on success, empty array on failure
		 */
		function nggv_getVotingOptions($gid) {
			global $wpdb;
			$opts = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."nggv_settings WHERE gid = '".$wpdb->escape($gid)."'");
			return $opts ? $opts : array();
		}

		/**
		 * Gets the voting options for a specific image
		 * @param int $pid The image ID
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return object of options on success, empty array on failure
		 */		
		function nggv_getImageVotingOptions($pid) {
			global $wpdb;
			$opts = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."nggv_settings WHERE pid = '".$wpdb->escape($pid)."'");
			return is_numeric($pid) && $opts->pid == $pid ? $opts : array();			
		}
		
		/**
		 Checks if the current user can vote on a gallery
		 * @param int $gid The NextGEN Gallery ID
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return true if the user can vote, string of reason if the user can not vote
		 */
		function nggv_canVote($gid) {
			$options = nggv_getVotingOptions($gid);
			
			if(!$options) {
				return false;
			}
			
			if(!$options->enable) {
				return "VOTING NOT ENABLED";
			}
			
			if($options->force_login) {
				global $current_user;
				get_currentuserinfo();

				if(!$current_user->ID) {
					return "NOT LOGGED IN";
				}
			}
			
			if($options->force_once) {
				if($options->force_login) { //force login, so check userid has voted already
					if(nggv_userHasVoted($gid, $current_user->ID)) {
						return "USER HAS VOTED";
					}
				}else{ //no forced login, so just check the IP for a vote
					if(nggv_ipHasVoted($gid)) {
						return "IP HAS VOTED";
					}
				}
			}
			
			return true;
		}

		/**
		 Checks if the current user can vote on an image (current almost identical to nggv_canVote(), but is seperate function for scalability)
		 * @param int $pid The image ID
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return true if the user can vote, string of reason if the user can not vote
		 */
		function nggv_canVoteImage($pid) {
			$options = nggv_getImageVotingOptions($pid);
			
			if(!$options) {
				return false;
			}
			
			if(!$options->enable) {
				return "VOTING NOT ENABLED";
			}
			
			if($options->force_login) {
				global $current_user;
				get_currentuserinfo();

				if(!$current_user->ID) {
					return "NOT LOGGED IN";
				}
			}
			
			if($options->force_once) {
				if($options->force_login) { //force login, so check userid has voted already
					if(nggv_userHasVotedImage($pid, $current_user->ID)) {
						return "USER HAS VOTED";
					}
				}else{ //no forced login, so just check the IP for a vote
					if(nggv_ipHasVotedImage($pid)) {
						return "IP HAS VOTED";
					}
				}
			}
			
			return true;
		}
		
		/**
		 * Save the vote.  Checks nggv_canVote() to be sure you aren't being sneaky
		 * @param array $config The array that makes up a valid vote
		 *  int config[gid] : The NextGEN Gallery ID
		 *  int config[vote] : The cast vote, must be between 0 and 100 (inclusive)
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return true on success, false on DB failure, string on nggv_canVote() not returning true
		 */
		function nggv_saveVote($config) {
			if(is_numeric($config["gid"]) && $config["vote"] >= 0 && $config["vote"] <= 100) {
				if(($msg = nggv_canVote($config["gid"])) === true) {
					global $wpdb, $current_user;
					get_currentuserinfo();
					$ip = getUserIp();
					if($wpdb->query("INSERT INTO ".$wpdb->prefix."nggv_votes (id, gid, vote, user_id, ip, proxy, dateadded) VALUES (null, '".$wpdb->escape($config["gid"])."', '".$wpdb->escape($config["vote"])."', '".$current_user->ID."', '".$ip["ip"]."', '".$ip["proxy"]."', '".date("Y-m-d H:i:s", time())."')")) {
						return true;
					}else{
						return false;
					}
				}else{
					return $msg;
				}
			}
		}
		
		
		/**
			* Save the vote.  Checks nggv_canVoteImage() to be sure you aren't being sneaky
			* @param array $config The array that makes up a valid vote
			*  int config[pid] : The image id
			*  int config[vote] : The cast vote, must be between 0 and 100 (inclusive)
			* @author Shaun <shaunalberts@gmail.com>
			* @return true on success, false on DB failure, string on nggv_canVoteImage() not returning true
			*/
		function nggv_saveVoteImage($config) {
			if(is_numeric($config["pid"]) && $config["vote"] >= 0 && $config["vote"] <= 100) {
				if(($msg = nggv_canVoteImage($config["pid"])) === true) {
					global $wpdb, $current_user;
					get_currentuserinfo();
					$ip = getUserIp();
					if($wpdb->query("INSERT INTO ".$wpdb->prefix."nggv_votes (id, pid, vote, user_id, ip, proxy, dateadded) VALUES (null, '".$wpdb->escape($config["pid"])."', '".$wpdb->escape($config["vote"])."', '".$current_user->ID."', '".$ip["ip"]."', '".$ip["proxy"]."', '".date("Y-m-d H:i:s", time())."')")) {
						return true;
					}else{
						return false;
					}
				}else{
					return $msg;
				}
			}
		}
		
		//gets the users actual IP even if they are behind a proxy (if the proxy is nice enough to let us know their actual IP of course)
		/**
		 * Get a users IP.  If the users proxy allows, we get their actual IP, not just the proxies
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return array("ip"=>string The IP found[might be proxy IP, sorry], "proxy"=>string The proxy IP if the proxy was nice enough to tell us it)
		 */
		function getUserIp() {
			if ($_SERVER["HTTP_X_FORWARDED_FOR"]) {
				if ($_SERVER["HTTP_CLIENT_IP"]) {
					$proxy = $_SERVER["HTTP_CLIENT_IP"];
				} else {
					$proxy = $_SERVER["REMOTE_ADDR"];
				}
				$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
			} else {
				if ($_SERVER["HTTP_CLIENT_IP"]) {
					$ip = $_SERVER["HTTP_CLIENT_IP"];
				} else {
					$ip = $_SERVER["REMOTE_ADDR"];
				}
			}
			
			//if comma list of IPs, get the LAST one
			if($proxy) {
				$proxy = explode(",", $proxy);
				$proxy = trim(array_pop($proxy));
			}
			if($ip) {
				$ip = explode(",", $ip);
				$ip = trim(array_pop($ip));
			}
			
			return array("ip"=>$ip, "proxy"=>$proxy);
		}

		/**
		 * Check if a user has voted on a gallery before 
		 * @param int $gid The NextGEN Gallery ID
		 * @param int $userid The users id to check
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return object of all the votes the user has cast for this gallery, or blank array
		 */
		function nggv_userHasVoted($gid, $userid) {
			global $wpdb;
			
			if($votes = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."nggv_votes WHERE gid = '".$wpdb->escape($gid)."' AND user_id = '".$wpdb->escape($userid)."'")) {
				return $votes;
			}else{
				return array();
			}
		}
		
		/**
		 * Check if an IP has voted on a gallery before 
		 * @param int $gid The NextGEN Gallery ID
		 * @param string The IP to check.  If not passed, the current users IP will be assumed
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return object of all the votes this IP has cast for this gallery, or blank array
		 */
		function nggv_ipHasVoted($gid, $ip=null) {
			global $wpdb;
			if(!$ip) {
				$tmp = getUserIp();
				$ip = $tmp["ip"];
			}
			
			if($votes = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."nggv_votes WHERE gid = '".$wpdb->escape($gid)."' AND ip = '".$wpdb->escape($ip)."'")) {
				return $votes;
			}else{
				return array();
			}
			
		}
		
		/**
			* Check if a user has voted on an image before 
			* @param int $pid The image ID to check
			* @param int $userid The users id to check
			* @author Shaun <shaunalberts@gmail.com>
			* @return object of all the votes the user has cast for this image, or blank array
			*/
		function nggv_userHasVotedImage($pid, $userid) {
			global $wpdb;
			
			if($votes = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."nggv_votes WHERE pid = '".$wpdb->escape($pid)."' AND user_id = '".$wpdb->escape($userid)."'")) {
				return $votes;
			}else{
				return array();
			}
		}

		/**
		 * Check if an IP has voted on an image before 
		 * @param int $gid The image ID
		 * @param string The IP to check.  If not passed, the current users IP will be assumed
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return object of all the votes this IP has cast for this image, or blank array
		 */
		function nggv_ipHasVotedImage($pid, $ip=null) {
			global $wpdb;
			if(!$ip) {
				$tmp = getUserIp();
				$ip = $tmp["ip"];
			}
			
			if($votes = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."nggv_votes WHERE pid = '".$wpdb->escape($pid)."' AND ip = '".$wpdb->escape($ip)."'")) {
				return $votes;
			}else{
				return array();
			}
			
		}
		
		/**
		 * Get the voting results of a gallery
		 * @param int $gid The NextGEN Gallery ID
		 * @param array $type The type of results to return (can limti number of queries if you only need the avg for example)
		 *  bool type[avg] : Get average vote
		 *  bool type[list] : Get all the votes for the gallery
		 *  bool type[number] : Get the number of votes for the gallery
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return array("avg"=>double average for gallery, "list"=>array of objects of all votes of the gallery, "number"=>integer the number of votes for the gallery)
		 */
		function nggv_getVotingResults($gid, $type=array("avg"=>true, "list"=>true, "number"=>true, "likes"=>true, "dislikes"=>true)) {
			if(is_numeric($gid)) {
				global $wpdb;
				
				if($type["avg"]) {
					$avg = $wpdb->get_row("SELECT SUM(vote) / COUNT(vote) AS avg FROM ".$wpdb->prefix."nggv_votes WHERE gid = '".$wpdb->escape($gid)."' GROUP BY gid");
				}
				if($type["list"]) {
					$list = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."nggv_votes WHERE gid = '".$wpdb->escape($gid)."' ORDER BY dateadded DESC");
				}
				if($type["num"]) {
					$num = $wpdb->get_row("SELECT COUNT(vote) AS num FROM ".$wpdb->prefix."nggv_votes WHERE gid = '".$wpdb->escape($gid)."' GROUP BY gid");
				}
				if($type["likes"]) {
					$likes = $wpdb->get_row("SELECT COUNT(vote) AS num FROM ".$wpdb->prefix."nggv_votes WHERE gid = '".$wpdb->escape($gid)."' AND vote = 100 GROUP BY gid");
				}
				if($type["dislikes"]) {
					$dislikes = $wpdb->get_row("SELECT COUNT(vote) AS num FROM ".$wpdb->prefix."nggv_votes WHERE gid = '".$wpdb->escape($gid)."' AND vote = 0 GROUP BY gid");
				}
				
				return array("avg"=>$avg->avg, "list"=>$list, "number"=>$num->num, "likes"=>($likes->num ? $likes->num : 0), "dislikes"=>($dislikes->num ? $dislikes->num : 0));
			}else{
				return array();
			}
		}

		/**
		 * Get the voting results of an image
		 * @param int $pid The image ID
		 * @param array $type The type of results to return (can limit number of queries if you only need the avg for example)
		 *  bool type[avg] : Get average vote
		 *  bool type[list] : Get all the votes for the gallery
		 *  bool type[number] : Get the number of votes for the gallery
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return array("avg"=>double average for image, "list"=>array of objects of all votes of the image, "number"=>integer the number of votes for the image)
		 */
		function nggv_getImageVotingResults($pid, $type=array("avg"=>true, "list"=>true, "number"=>true, "likes"=>true, "dislikes"=>true)) {
			if(is_numeric($pid)) {
				global $wpdb;
				
				if($type["avg"]) {
					$avg = $wpdb->get_row("SELECT SUM(vote) / COUNT(vote) AS avg FROM ".$wpdb->prefix."nggv_votes WHERE pid = '".$wpdb->escape($pid)."' GROUP BY pid");
				}
				if($type["list"]) {
					$list = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."nggv_votes WHERE pid = '".$wpdb->escape($pid)."' ORDER BY dateadded DESC");
				}
				if($type["num"]) {
					$num = $wpdb->get_row("SELECT COUNT(vote) AS num FROM ".$wpdb->prefix."nggv_votes WHERE pid = '".$wpdb->escape($pid)."' GROUP BY pid");
				}
				if($type["likes"]) {
					$likes = $wpdb->get_row("SELECT COUNT(vote) AS num FROM ".$wpdb->prefix."nggv_votes WHERE pid = '".$wpdb->escape($pid)."' AND vote = 100 GROUP BY pid");
				}
				if($type["dislikes"]) {
					$dislikes = $wpdb->get_row("SELECT COUNT(vote) AS num FROM ".$wpdb->prefix."nggv_votes WHERE pid = '".$wpdb->escape($pid)."' AND vote = 0 GROUP BY pid");
				}

				return array("avg"=>$avg->avg, "list"=>$list, "number"=>$num->num, "likes"=>($likes->num ? $likes->num : 0), "dislikes"=>($dislikes->num ? $dislikes->num : 0));
			}else{
				return array();
			}
		}
	//}
	
	// admin function {
		add_action('admin_menu', 'nggv_adminMenu');
		function nggv_adminMenu() {
			add_menu_page('NGG Voting', 'NGG Voting', 8, __FILE__, 'nggv_admin_options');
		}
		function nggv_admin_options() {
			if($_GET["action"] == "get-votes-list") {
				echo '<!--#NGGV START AJAX RESPONSE#-->'; //do not edit this line!!!
				
				if($_GET["gid"]) {
					$options = nggv_getVotingOptions($_GET["gid"]);
					echo 'var nggv_voting_type = '.$options->voting_type.';';
					
					$results = nggv_getVotingResults($_GET["gid"]);
					echo "var nggv_votes_list = [];";
					foreach ((array)$results["list"] as $key=>$val) {
						$user_info = $val->user_id ? get_userdata($val->user_id) : array();
						echo "
							nggv_votes_list[nggv_votes_list.length] = [];
							nggv_votes_list[nggv_votes_list.length-1][0] = '".$val->vote."';
							nggv_votes_list[nggv_votes_list.length-1][1] = '".$val->dateadded."';
							nggv_votes_list[nggv_votes_list.length-1][2] = '".$val->ip."';
							nggv_votes_list[nggv_votes_list.length-1][3] = [];
							nggv_votes_list[nggv_votes_list.length-1][3][0] = '".$val->user_id."';
							nggv_votes_list[nggv_votes_list.length-1][3][1] = '".$user_info->user_login."';
						";
					}
				}else if($_GET["pid"]){
					$options = nggv_getImageVotingOptions($_GET["pid"]);
					echo 'var nggv_voting_type = '.$options->voting_type.';';

					$results = nggv_getImageVotingResults($_GET["pid"]);
					
					echo "var nggv_votes_list = [];";
					foreach ((array)$results["list"] as $key=>$val) {
						$user_info = $val->user_id ? get_userdata($val->user_id) : array();
						echo "
							nggv_votes_list[nggv_votes_list.length] = [];
							nggv_votes_list[nggv_votes_list.length-1][0] = '".$val->vote."';
							nggv_votes_list[nggv_votes_list.length-1][1] = '".$val->dateadded."';
							nggv_votes_list[nggv_votes_list.length-1][2] = '".$val->ip."';
							nggv_votes_list[nggv_votes_list.length-1][3] = [];
							nggv_votes_list[nggv_votes_list.length-1][3][0] = '".$val->user_id."';
							nggv_votes_list[nggv_votes_list.length-1][3][1] = '".$user_info->user_login."';
						";
					}
				}else{
					//error num?
				}
				
				exit;
			}else{
				if($_POST['nggv']) {
					//Gallery
					if(get_option('nggv_gallery_enable') === false) { //bool false means does not exists
						add_option('nggv_gallery_enable', ($_POST['nggv']['gallery']['enable'] ? '1' : '0'), null, 'no');
					}else{
						update_option('nggv_gallery_enable', ($_POST['nggv']['gallery']['enable'] ? '1' : '0'));
					}
					if(get_option('nggv_gallery_force_login') === false) { //bool false means does not exists
						add_option('nggv_gallery_force_login', ($_POST['nggv']['gallery']['force_login'] ? '1' : '0'), null, 'no');
					}else{
						update_option('nggv_gallery_force_login', ($_POST['nggv']['gallery']['force_login'] ? '1' : '0'));
					}
					if(get_option('nggv_gallery_force_once') === false) { //bool false means does not exists
						add_option('nggv_gallery_force_once', ($_POST['nggv']['gallery']['force_once'] ? '1' : '0'), null, 'no');
					}else{
						update_option('nggv_gallery_force_once', ($_POST['nggv']['gallery']['force_once'] ? '1' : '0'));
					}
					if(get_option('nggv_gallery_user_results') === false) { //bool false means does not exists
						add_option('nggv_gallery_user_results', ($_POST['nggv']['gallery']['user_results'] ? '1' : '0'), null, 'no');
					}else{
						update_option('nggv_gallery_user_results', ($_POST['nggv']['gallery']['user_results'] ? '1' : '0'));
					}
					if(get_option('nggv_gallery_voting_type') === false) { //bool false means does not exists
						add_option('nggv_gallery_voting_type', $_POST['nggv']['gallery']['voting_type'], null, 'no');
					}else{
						update_option('nggv_gallery_voting_type', $_POST['nggv']['gallery']['voting_type']);
					}
					
					//Images
					if(get_option('nggv_image_enable') === false) { //bool false means does not exists
						add_option('nggv_image_enable', ($_POST['nggv']['image']['enable'] ? '1' : '0'), null, 'no');
					}else{
						update_option('nggv_image_enable', ($_POST['nggv']['image']['enable'] ? '1' : '0'));
					}
					if(get_option('nggv_image_force_login') === false) { //bool false means does not exists
						add_option('nggv_image_force_login', ($_POST['nggv']['image']['force_login'] ? '1' : '0'), null, 'no');
					}else{
						update_option('nggv_image_force_login', ($_POST['nggv']['image']['force_login'] ? '1' : '0'));
					}
					if(get_option('nggv_image_force_once') === false) { //bool false means does not exists
						add_option('nggv_image_force_once', ($_POST['nggv']['image']['force_once'] ? '1' : '0'), null, 'no');
					}else{
						update_option('nggv_image_force_once', ($_POST['nggv']['image']['force_once'] ? '1' : '0'));
					}
					if(get_option('nggv_image_user_results') === false) { //bool false means does not exists
						add_option('nggv_image_user_results', ($_POST['nggv']['image']['user_results'] ? '1' : '0'), null, 'no');
					}else{
						update_option('nggv_image_user_results', ($_POST['nggv']['image']['user_results'] ? '1' : '0'));
					}
					if(get_option('nggv_image_voting_type') === false) { //bool false means does not exists
						add_option('nggv_image_voting_type', $_POST['nggv']['image']['voting_type'], null, 'no');
					}else{
						update_option('nggv_image_voting_type', $_POST['nggv']['image']['voting_type']);
					}
				}
				
				$filepath = admin_url()."admin.php?page=".$_GET["page"];
				?>
				<div class="wrap">
					<h2>Welcome to NextGEN Gallery Voting</h2>
					<p>This plugin adds the ability for users to vote on NextGEN Galleries and Images.  If you need any help or find any bugs, please create a post at the Wordpress plugin support forum, with the tag '<a href="http://wordpress.org/tags/nextgen-gallery-voting?forum_id=10" target="_blank">nextgen-gallery-voting</a>'</p>
				
					<h2>Default Options</h2>
					<p>Here you can set the default voting options for <strong>new</strong> Galleries and Images.  Setting these options will not affect any existing Galleries or Images</p>
					<div id="poststuff">
						<form id="" method="POST" action="<?php echo $filepath; ?>" accept-charset="utf-8" >
							<input type="hidden" name="nggv[force]" value="1" /> <!-- this will just force _POST['nggv'] even if all checkboxes are unchecked -->
							<div class="postbox">
								<table class="form-table" style="width:500px;">
									<tr>
										<td colspan="2" style="text-align:right;"><h3>Gallery</h3></th>
										<td style="text-align:center;"><h3>Image</h3></th>
									</tr>
									<tr valign="top">
										<th style="width:280px;">Enable:</th>
										<td style="width:60px; text-align:center;"><input type="checkbox" name="nggv[gallery][enable]" <?php echo (get_option('nggv_gallery_enable') ? 'checked="checked"' : ''); ?> /></td>
										<td style="width:60px; text-align:center;"><input type="checkbox" name="nggv[image][enable]" <?php echo (get_option('nggv_image_enable') ? 'checked="checked"' : ''); ?> /></td>
									</tr>

									<tr valign="top">
										<th>Only allow logged in users to vote:</th>
										<td style="text-align:center;"><input type="checkbox" name="nggv[gallery][force_login]" <?php echo (get_option('nggv_gallery_force_login') ? 'checked="checked"' : ''); ?> /></td>
										<td style="text-align:center;"><input type="checkbox" name="nggv[image][force_login]" <?php echo (get_option('nggv_image_force_login') ? 'checked="checked"' : ''); ?> /></td>
									</tr>

									<tr valign="top">
										<th>Only allow 1 vote per person<br ><em>(IP or userid is used to stop multiple)</em></th>
										<td style="text-align:center;"><input type="checkbox" name="nggv[gallery][force_once]" <?php echo (get_option('nggv_gallery_force_once') ? 'checked="checked"' : ''); ?> /></td>
										<td style="text-align:center;"><input type="checkbox" name="nggv[image][force_once]" <?php echo (get_option('nggv_image_force_once') ? 'checked="checked"' : ''); ?> /></td>
									</tr>

									<tr valign="top">
										<th>Allow users to see results:</th>
										<td style="text-align:center;"><input type="checkbox" name="nggv[gallery][user_results]" <?php echo (get_option('nggv_gallery_user_results') ? 'checked="checked"' : ''); ?> /></td>
										<td style="text-align:center;"><input type="checkbox" name="nggv[image][user_results]" <?php echo (get_option('nggv_image_user_results') ? 'checked="checked"' : ''); ?> /></td>
									</tr>

									<tr valign="top">
										<th>Rating Type:</th>
										<td style="text-align:center;">
											<select name="nggv[gallery][voting_type]">
												<option value="1" <?php echo (get_option('nggv_gallery_voting_type') == 1 ? 'selected="selected"' : ''); ?>>Drop Down</option>
												<option value="2" <?php echo (get_option('nggv_gallery_voting_type') == 2 ? 'selected="selected"' : ''); ?>>Star Rating</option>
												<option value="3" <?php echo (get_option('nggv_gallery_voting_type') == 3 ? 'selected="selected"' : ''); ?>>Link / Dislike</option>
											</select>
										</td>
										<td style="text-align:center;">
											<select name="nggv[image][voting_type]">
												<option value="1" <?php echo (get_option('nggv_image_voting_type') == 1 ? 'selected="selected"' : ''); ?>>Drop Down</option>
												<option value="2" <?php echo (get_option('nggv_image_voting_type') == 2 ? 'selected="selected"' : ''); ?>>Star Rating</option>
												<option value="3" <?php echo (get_option('nggv_image_voting_type') == 3 ? 'selected="selected"' : ''); ?>>Link / Dislike</option>
											</select>

										</td>
									</tr>

									<tr>
										<td colspan="2">
											<div class="submit"><input class="button-primary" type="submit" value="Save Defaults"/>
											</div>
										</td>
									</tr>
								</table>
							</div>
						</form>
					</div>
				</div>
				<?php
			}
		}
		
		add_action('ngg_update_gallery', 'nggv_save_gallery_options', 10, 2);
		/**
		 * Save the options for a gallery and/or images
		 * @param int $gid The NextGEN Gallery ID
		 * @param array $post the _POST array from the gallery save form. We have added the following fields for our options
		 *  bool (int 1/0) post["nggv"]["enable"] : Enable voting for the gallery
		 *  bool (int 1/0) post["nggv"]["force_login"] : Force the user to login to cast vote
		 *  bool (int 1/0) post["nggv"]["force_once"] : Only allow a user to vote once
		 *  bool (int 1/0) post["nggv"]["user_results"] : If users see results
		 *  bool (int 1/0) post["nggv_image"][image ID]["enable"] : Enable voting for the image
		 *  bool (int 1/0) post["nggv_image"][image ID]["force_login"] : Only allow a user to vote once
		 *  bool (int 1/0) post["nggv_image"][image ID]["force_once"] : Only allow a user to vote once
		 *  bool (int 1/0) post["nggv_image"][image ID]["user_results"] : If users see results
		 * @param bool $noReload If set to true, this function will act like an api and simply let the code execution continue after being called.
		 *  If false (default), this funtion uses a js hack to reload the page
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return void
		 */
		function nggv_save_gallery_options($gid, $post, $noReload=false) {
			global $wpdb;

			if($post["nggv"]) {
				$enable = $post["nggv"]["enable"] ? "1" : "0";
				$login = $post["nggv"]["force_login"] ? "1" : "0";
				$once = $post["nggv"]["force_once"] ? "1" : "0";
				$user_results = $post["nggv"]["user_results"] ? "1" : "0";
				$voting_type = is_numeric($post["nggv"]["voting_type"]) ? $post["nggv"]["voting_type"] : 1;
				
				if(nggv_getVotingOptions($gid)) {
					$wpdb->query("UPDATE ".$wpdb->prefix."nggv_settings SET force_login = '".$login."', force_once = '".$once."', user_results = '".$user_results."', enable = '".$enable."', voting_type = '".$voting_type."' WHERE gid = '".$wpdb->escape($gid)."'");
				}else{
					$wpdb->query("INSERT INTO ".$wpdb->prefix."nggv_settings (id, gid, enable, force_login, force_once, user_results, voting_type) VALUES (null, '".$wpdb->escape($gid)."', '".$enable."', '".$login."', '".$once."', '".$user_results."', '".$voting_type."')");
				}
			}
			
			if($post["nggv_image"]) {
				foreach ((array)$post["nggv_image"] as $pid=>$val) {
					$enable = $wpdb->escape($val["enable"]) ? "1" : "0";
					$login = $wpdb->escape($val["force_login"]) ? "1" : "0";
					$once = $wpdb->escape($val["force_once"]) ? "1" : "0";
					$user_results = $wpdb->escape($val["user_results"]) ? "1" : "0";
					$voting_type = is_numeric($val["voting_type"]) ? $val["voting_type"] : 1;

					if(nggv_getImageVotingOptions($pid)) {
						$wpdb->query("UPDATE ".$wpdb->prefix."nggv_settings SET force_login = '".$login."', force_once = '".$once."', user_results = '".$user_results."', enable = '".$enable."', voting_type = '".$voting_type."' WHERE pid = '".$wpdb->escape($pid)."'");
					}else{
						$wpdb->query("INSERT INTO ".$wpdb->prefix."nggv_settings (id, pid, enable, force_login, force_once, user_results, voting_type) VALUES (null, '".$wpdb->escape($pid)."', '".$enable."', '".$login."', '".$once."', '".$user_results."', '".$voting_type."')");
					}
				}
			}
			
			if(!$noReload) {
				//gotta force a reload or the js globals declared in nggv_add_vote_options() are set to the pre-saved values, and the checkboxes are ticked incorrectly (hack hackity hack hack hack)
				echo "<script>window.location = window.location;</script>";
				exit;
			}
		}
		
		// in version 1.7.0 ngg renamed the filter name
		//if(version_compare(NGGVERSION, '1.6.99', '<')) {
			//add_action("ngg_manage_gallery_columns", "nggv_add_image_vote_options_field");
		//}else{
			add_action("ngg_manage_images_columns", "nggv_add_image_vote_options_field");
		//}
		/**
		 * Add a custom field to the images field list.  This give us a place to add the voting options for each image with nggv_add_image_vote_options_field()
		 * Also enqueues a script that will add the gallery voting options with js (sneaky, but has to be done)
		 * @param array $gallery_columns The array of current fields
		 * @author Shaun <shaun@worldwidecreative.co.za>
		 * @return array $gallery_columns with an added field
		 */
		function nggv_add_image_vote_options_field($gallery_columns) {
			wp_enqueue_script('nggc_gallery_options', WP_PLUGIN_URL . '/nextgen-gallery-voting/js/gallery_options.js', array('jquery'), false, true);
			$gallery_columns["nggv_image_vote_options"] = "Image Voting Options";
			return $gallery_columns;
		}

		// in version 1.7.0 ngg renamed the filter name
		//if(version_compare(NGGVERSION, '1.6.99', '<')) {
			//add_action("ngg_manage_gallery_custom_column", "nggv_add_image_voting_options", 10 ,2);
		//}else{
			add_action("ngg_manage_image_custom_column", "nggv_add_image_voting_options", 10 ,2);
		//}
		/**
		 * Add the voing options to the gallery (sneaky js) and each image
		 * @param string $gallery_column_key The key value of the 'custom' fields added by nggv_add_image_vote_options_field()
		 * @author Shaun <shaun@worldwidecreative.co.za>
		 * @return void
		 */
		function nggv_add_image_voting_options($gallery_column_key, $pid) {
			global $nggv_scripted;
			
			if(!$nggv_scripted) { //its a hack, so just check that its only called once :)
				$nggv_scripted = true;
				$options = nggv_getVotingOptions($_GET["gid"]);
				$results = nggv_getVotingResults($_GET["gid"], array("avg"=>true, "num"=>true, "likes"=>true, "dislikes"=>true));
				
				$uri = $_SERVER["REQUEST_URI"];
				$info = parse_url($uri);
				$dirName = plugin_basename(dirname(__FILE__));
				$popup = $info["path"]."?page=".$dirName."/".basename(__FILE__)."&action=get-votes-list";
				
				echo "<script>
				var nggv_gid = parseInt(".$_GET["gid"].");
				var nggv_enable = parseInt(".$options->enable.");
				var nggv_login = parseInt(".$options->force_login.");
				var nggv_once = parseInt(".$options->force_once.");
				var user_results = parseInt(".$options->user_results.");
				var voting_type = parseInt(".$options->voting_type.");
				var nggv_avg = Math.round(".($results["avg"] ? $results["avg"] : 0).") / 10;
				var nggv_num_votes = parseInt(".($results["number"] ? $results["number"] : 0).");
				var nggv_num_likes = parseInt(".($results["likes"] ? $results["likes"] : 0).");
				var nggv_num_dislikes = parseInt(".($results["dislikes"] ? $results["dislikes"] : 0).");
				
				var nggv_more_url = '".$popup."';
				</script>";
				
				//the popup window for results
				echo '<div id="nggvShowList" style="display:none;">';
				echo '<span style="float:right;" width: 100px; height: 40px; border:>';
				echo '<a href="#" id="nggv_more_results_close">Close Window</a>';
				echo '</span>';
				echo '<div style="clear:both;"></div>';
				
				echo '<div id="nggvShowList_content">';
				echo '<img src="'.WP_PLUGIN_URL."/".$dirName."/images/loading.gif".'" />';
				echo '</div>';
				echo '</div>';
			}

			if($gallery_column_key == "nggv_image_vote_options") {
				$opts = nggv_getImageVotingOptions($pid);
				echo "<table width='100%'";
				echo "<tr><td width='1px'><input type='checkbox' name='nggv_image[".$pid."][enable]' value=1 ".($opts->enable ? "checked" : "")." /></td><td>Enable for image</td></tr>";
				echo "<tr><td width='1px'><input type='checkbox' name='nggv_image[".$pid."][force_login]' value=1 ".($opts->force_login ? "checked" : "")." /></td><td>Only allow logged in users</td></tr>";
				echo "<tr><td width='1px'><input type='checkbox' name='nggv_image[".$pid."][force_once]' value=1 ".($opts->force_once ? "checked" : "")." /></td><td>Only allow 1 vote per person</td></tr>";
				echo "<tr><td width='1px'><input type='checkbox' name='nggv_image[".$pid."][user_results]' value=1 ".($opts->user_results ? "checked" : "")." /></td><td>Allow users to see results</td></tr>";
				
				echo "<tr><td colspan=2>";
				echo "Rating Type: <select name='nggv_image[".$pid."][voting_type]'>";
				echo "<option value='1' ".($opts->voting_type == 1 || !$opts->voting_type ? "selected" : "").">Drop Down</option>";
				echo "<option value='2' ".($opts->voting_type == 2 ? "selected" : "").">Star Rating</option>";
				echo "<option value='3' ".($opts->voting_type == 3 ? "selected" : "").">Like / Dislike</option>";
				echo "</select>";
				echo "</td></tr>";

				
				echo "</table>";
				if($opts->voting_type == 3) {
					$results = nggv_getImageVotingResults($pid, array("likes"=>true, "dislikes"=>true));
					echo "Current Votes: ";
					echo "<a href='' class='nggv_mote_results_image' id='nggv_more_results_image_".$pid."'>";
					echo $results['likes'].' ';
					echo $results['likes'] == 1 ? 'Like, ' : 'Likes, ';
					echo $results['dislikes'].' ';
					echo $results['dislikes'] == 1 ? 'Dislike' : 'Dislikes';
					echo "</a>";
				}else{
					$results = nggv_getImageVotingResults($pid, array("avg"=>true, "num"=>true));
					echo "Current Avg: ".round(($results["avg"] / 10), 1)." / 10 <a href='' class='nggv_mote_results_image' id='nggv_more_results_image_".$pid."'>(".($results["number"] ? $results["number"] : "0")." votes cast)</a>";
				}
			}
		}
		
		add_action("ngg_add_new_gallery_form", "nggv_new_gallery_form"); //new in ngg 1.4.0a
		/**
		 * Adds the default voting options for a new gallery.  Can be tweaked for the specif gallery without affecting the defaults
		 * @author Shaun <shaun@worldwidecreative.co.za>
		 * @return void
		 */
		function nggv_new_gallery_form() {
			?>
			<tr valign="top">
			<th scope="row">Gallery Voting Options:<br /><em>(Pre-set from <a href="<?php echo admin_url(); ?>admin.php?page=nextgen-gallery-voting/ngg-voting.php">here</a>)</em></th> 
			<td>
				<input type="checkbox" name="nggv[gallery][enable]" <?php echo (get_option('nggv_gallery_enable') ? 'checked="checked"' : ''); ?> />
				Enable<br />
				
				<input type="checkbox" name="nggv[gallery][force_login]" <?php echo (get_option('nggv_gallery_force_login') ? 'checked="checked"' : ''); ?> />
				Only allow logged in users to vote<br />
				
				<input type="checkbox" name="nggv[gallery][force_once]" <?php echo (get_option('nggv_gallery_force_once') ? 'checked="checked"' : ''); ?> />
				Only allow 1 vote per person <em>(IP or userid is used to stop multiple)</em><br />
				
				<input type="checkbox" name="nggv[gallery][user_results]" <?php echo (get_option('nggv_gallery_user_results') ? 'checked="checked"' : ''); ?> />
				Allow users to see results
			</td>
			</tr>
			<?php
		}
		
		add_action("ngg_created_new_gallery", "nggv_add_new_gallery"); //new in ngg 1.4.0a
		/**
		 * Saves the voting options for the new gallery
		 * @param int $gid the gallery id
		 * @author Shaun <shaun@worldwidecreative.co.za>
		 * @return voide
		 */
		function nggv_add_new_gallery($gid) {
			if($gid) {
				$post = array();
				$post['nggv'] = $_POST['nggv']['gallery'];
				nggv_save_gallery_options($gid, $post, true);
			}
		}
		
		add_action("ngg_added_new_image", "nggv_add_new_image");
		/**
		 * Add the image voting options for a new image (pulled from the defaaults
		 * @param array $image the new image details
		 * @author Shaun <shaun@worldwidecreative.co.za>
		 * @return void
		 */
		function nggv_add_new_image($image) {
			if($image['id']) {
				$post = array();
				$post['nggv_image'] = array();
				$post['nggv_image'][$image['id']] = array();
				$post['nggv_image'][$image['id']]['enable'] = get_option('nggv_image_enable');
				$post['nggv_image'][$image['id']]['force_login'] = get_option('nggv_image_force_login');
				$post['nggv_image'][$image['id']]['force_once'] = get_option('nggv_image_force_once');
				$post['nggv_image'][$image['id']]['user_results'] = get_option('nggv_image_user_results');
				$post['nggv_image'][$image['id']]['voting_type'] = get_option('nggv_image_voting_type');
				
				nggv_save_gallery_options($image['galleryID'], $post, true);
			}
		}
	//}

	// front end funcs {
		/**
		 * Stops the script including a JS file more than once.  wp_enqueue_script only works
		 * before any buffers have been outputted, so this will have to do
		 * @param string $filename The path/url to the js file to be included
		 * @author Shaun <shaun@worldwidecreative.co.za>
		 * @return string with the <script> tags if not included already, else nothing
		 */
		function nggv_include_js($filename) {
			global $nggv_front_scripts;
			
			if(!$nggv_front_scripts) {
				$nggv_front_scripts = array();
			}
			
			if(!$nggv_front_scripts[$filename]) {
				$nggv_front_scripts[$filename] = array('filename'=>$nggv_front_scripts[$filename], 'added'=>true);
				return '<script type="text/javascript" src="'.$filename.'"></script>';
			}
		}
	
		add_filter("ngg_show_gallery_content", "nggv_show_gallery", 10, 2);
		/**
		 * The function that display to voting form, or results depending on if a user can vote or note
		 * @param string $out The entire markup of the gallery passed from NextGEN
		 * @param int $gid The NextGEN Gallery ID
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return string The voting form (or results) appended to the original gallery markup given
		 */
		function nggv_show_gallery($out, $gid) {
			return $out.nggc_voteForm($gid, $buffer);
		}
		
		/**
		 * Using nggv_canVote() display the voting form, or results, or thank you message.  Also calls the nggv_saveVote() once a user casts their vote 
		 * @param int $gid The NextGEN Gallery ID
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return string The voting form, or results, or thank you message markup
		 */
		function nggc_voteForm($gid) {
			if(!is_numeric($gid)) {
				//trigger_error("Invalid argument 1 for function ".__FUNCTION__."(\$galId).", E_USER_WARNING);
				return;
			}
			
			$options = nggv_getVotingOptions($gid);
			$out = "";
			$errOut = "";
			
			if($_POST && !$_POST["nggv"]["vote_pid_id"]) { //select box voting
				if(($msg = nggv_saveVote(array("gid"=>$gid, "vote"=>$_POST["nggv"]["vote"]))) === true) {
					$saved = true;
				}else{
					//$errOut .= '<div class="nggv-error">';
					if($msg == "VOTING NOT ENABLED") {
						$errOut .= "This gallery has not turned on voting.";
					}else if($msg == "NOT LOGGED IN") {
						$errOut .= "You need to be logged in to vote on this gallery.";
					}else if($msg == "USER HAS VOTED") {
						$errOut .= "You have already voted on this gallery.";
					}else if($msg == "IP HAS VOTED") {
						$errOut .= "This IP has already voted on this gallery.";
					}else{
						$errOut .= "There was a problem saving your vote, please try again in a few moments.";
					}
					//$errOut .= '</div>';
					//maybe return $errOut here?  user really should only get here if they are 'hacking' the dom anyway?
				}
			}else if($_GET["gid"] && is_numeric($_GET["r"])) { //star or like/dislike, js disabled
				if($options->voting_type == 3) { //like/dislike
					if($_GET['r']) {$_GET['r'] = 100;} //like/dislike is all or nothing :)
				}
				if(($msg = nggv_saveVote(array("gid"=>$gid, "vote"=>$_GET["r"]))) === true) {
					$saved = true;
				}else{
					//$errOut .= '<div class="nggv-error">';
					if($msg == "VOTING NOT ENABLED") {
						$errOut .= "This gallery has not turned on voting.";
					}else if($msg == "NOT LOGGED IN") {
						$errOut .= "You need to be logged in to vote on this gallery.";
					}else if($msg == "USER HAS VOTED") {
						$errOut .= "You have already voted on this gallery.";
					}else if($msg == "IP HAS VOTED") {
						$errOut .= "This IP has already voted on this gallery.";
					}else{
						$errOut .= "There was a problem saving your vote, please try again in a few moments.";
					}
					//$errOut .= '</div>';
					//maybe return $errOut here?  user really should only get here if they are 'hacking' the dom anyway?
				}
			}

			if($_GET['ajaxify'] && $_GET['gid'] == $gid) {
				$out .= "<!--#NGGV START AJAX RESPONSE#-->";
				$out .= "var nggv_js = {};";
				$out .= "nggv_js.options = {};";
				foreach ($options as $key=>$val) {
					$out .= 'nggv_js.options.'.$key.' = "'.$val.'";';
				}
				
				$out .= "nggv_js.saved = ".($saved ? "1" : "0").";";
				$out .= "nggv_js.msg = '".addslashes($errOut)."';";
			}else if($_GET['gid']){
				$out .= '<div class="nggv-error">';
				$out .= $errOut;
				$out .= '</div>';
			}
			
			if((($canVote = nggv_canVote($gid)) === true) && !$saved) { //they can vote, show the form
				$url = $_SERVER["REQUEST_URI"];
				$url .= (strpos($url, "?") === false ? "?" : (substr($url, -1) == "&" ? "" : "&")); //make sure the url ends in "?" or "&" correctly
				//todo, try not duplicate the GET[gid] and GET[r] if clicked 2x
				
				if($options->voting_type == 3) { //like / dislike (new from 1.5)
					$dirName = plugin_basename(dirname(__FILE__));
					$out .= nggv_include_js(WP_PLUGIN_URL.'/'.$dirName.'/js/ajaxify-likes.js');	//ajaxify voting, from v1.7
					
					$out .= '<div class="nggv_container">';
					$out .= '<a href="'.$url.'gid='.$gid.'&r=1" class="nggv-link-like"><img src="'.WP_PLUGIN_URL."/".$dirName."/images/thumbs_up.png".'" alt="Like" /></a>';
					$out .= '<a href="'.$url.'gid='.$gid.'&r=0" class="nggv-link-dislike"><img src="'.WP_PLUGIN_URL."/".$dirName."/images/thumbs_down.png".'" alt="Dislike" /></a>';
					$out .= '<img class="nggv-star-loader" src="'.WP_PLUGIN_URL.'/'.$dirName.'/images/loading.gif'.'" style="display:none;" />';
					if($options->user_results) {
						$results = nggv_getVotingResults($gid, array("likes"=>true, "dislikes"=>true));
						$out .= '<div class="like-results">';
						$out .= $results['likes'].' ';
						$out .= $results['likes'] == 1 ? 'Like, ' : 'Likes, ';
						$out .= $results['dislikes'].' ';
						$out .= $results['dislikes'] == 1 ? 'Dislike' : 'Dislikes';
						$out .= '</div>';
					}
					$out .= '</div>';
				}elseif($options->voting_type == 2) { //star
					$out .= nggv_include_js(WP_PLUGIN_URL.'/nextgen-gallery-voting/js/ajaxify-stars.js');	//ajaxify voting, from v1.7
					
					$results = nggv_getVotingResults($gid, array("avg"=>true));
					$out .= '<link rel="stylesheet" href="'.WP_PLUGIN_URL.'/nextgen-gallery-voting/css/star_rating.css" type="text/css" media="screen" />';
					$out .= '<div class="nggv_container">';
					$out .= '<span class="inline-rating">';
					$out .= '<ul class="star-rating">';
					if($options->user_results) { //user can see curent rating
						$out .= '<li class="current-rating" style="width:'.round($results["avg"]).'%;">Currently '.round($results["avg"] / 20, 1).'/5 Stars.</li>';
					}
					$out .= '<li><a href="'.$url.'gid='.$gid.'&r=20" title="1 star out of 5" class="one-star">1</a></li>';
					$out .= '<li><a href="'.$url.'gid='.$gid.'&r=40" title="2 stars out of 5" class="two-stars">2</a></li>';
					$out .= '<li><a href="'.$url.'gid='.$gid.'&r=60" title="3 stars out of 5" class="three-stars">3</a></li>';
					$out .= '<li><a href="'.$url.'gid='.$gid.'&r=80" title="4 stars out of 5" class="four-stars">4</a></li>';
					$out .= '<li><a href="'.$url.'gid='.$gid.'&r=100" title="5 stars out of 5" class="five-stars">5</a></li>';
					$out .= '</ul>';
					$out .= '</span>';
					$out .= '<img class="nggv-star-loader" src="'.WP_PLUGIN_URL."/nextgen-gallery-voting/images/loading.gif".'" style="display:none;" />';
					$out .= '</div>';
				}else{ //it will be 1, but why not use a catch all :) (drop down)
					$out .= '<div class="nggv_container">';
					$out .= '<form method="post" action="">';
					$out .= '<label forid="nggv_rating">Rate this gallery:</label>';
					$out .= '<select id="nggv_rating" name="nggv[vote]">';
					$out .= '<option value="0">0</option>';
					$out .= '<option value="10">1</option>';
					$out .= '<option value="20">2</option>';
					$out .= '<option value="30">3</option>';
					$out .= '<option value="40">4</option>';
					$out .= '<option value="50">5</option>';
					$out .= '<option value="60">6</option>';
					$out .= '<option value="70">7</option>';
					$out .= '<option value="80">8</option>';
					$out .= '<option value="90">9</option>';
					$out .= '<option value="100">10</option>';
					$out .= '</select>';
					$out .= '<input type="submit" value="Rate" />';
					$out .= '</form>';
					$out .= '</div>';
				}
			}else{ //ok, they cant vote.  what next?
				if($options->enable) { //votings enabled for this gallery, lets find out more...
					if($canVote === "NOT LOGGED IN") { //the api wants them to login to vote
						$out .= '<div class="nggv_container">';
						$out .= 'Only registered users can vote.  Please login to cast your vote';
						$out .= '</div>';
					}else if($canVote === "USER HAS VOTED" || $canVote === "IP HAS VOTED" || $canVote === true) { //api tells us they have voted, can they see results? (canVote will be true if they have just voted successfully)
						if($options->user_results) { //yes! show it
							if($options->voting_type == 3) {
								$results = nggv_getVotingResults($gid, array("likes"=>true, "dislikes"=>true));
								
								$buffer = '';
								$bufferInner = ''; //buffer the innser, so we can pass it back to the ajax request if enabled
								
								$buffer .= '<div class="nggv_container">';
								$bufferInner .= $results['likes'].' ';
								$bufferInner .= $results['likes'] == 1 ? 'Like, ' : 'Likes, ';
								$bufferInner .= $results['dislikes'].' ';
								$bufferInner .= $results['dislikes'] == 1 ? 'Dislike' : 'Dislikes';
								$buffer .= $bufferInner;
								$buffer .= '</div>';
								
								if($_GET['ajaxify']) {
									$out .= "nggv_js.nggv_container = '".addslashes($bufferInner)."';";
								}else{
									$out .= $buffer;
								}
							}elseif($options->voting_type == 2) {
								$results = nggv_getVotingResults($gid, array("avg"=>true));
								
								$buffer = '';
								$bufferInner = ''; //buffer the innser, so we can pass it back to the ajax request if enabled
								
								$buffer .= '<link rel="stylesheet" href="'.WP_PLUGIN_URL.'/nextgen-gallery-voting/css/star_rating.css" type="text/css" media="screen" />';
								$buffer .= '<div class="nggv_container">';
								$bufferInner .= '<span class="inline-rating">';
								$bufferInner .= '<ul class="star-rating">';
								$bufferInner .= '<li class="current-rating" style="width:'.round($results["avg"]).'%;">Currently '.round($results["avg"] / 20, 1).'/5 Stars.</li>';
								$bufferInner .= '<li>1</li>';
								$bufferInner .= '<li>2</li>';
								$bufferInner .= '<li>3</li>';
								$bufferInner .= '<li>4</li>';
								$bufferInner .= '<li>5</li>';
								$bufferInner .= '</ul>';
								$bufferInner .= '</span>';
								$bufferInner .= '<img class="nggv-star-loader" src="'.WP_PLUGIN_URL."/nextgen-gallery-voting/images/loading.gif".'" style="display:none;" />';
								$buffer .= $bufferInner;
								$buffer .= '</div>';

								if($_GET['ajaxify']) {
									$out .= "nggv_js.nggv_container = '".addslashes($bufferInner)."';";
								}else{
									$out .= $buffer;
								}
							}else{
								$results = nggv_getVotingResults($gid, array("avg"=>true));
								$out .= '<div class="nggv_container">';
								$out .= 'Current Average: '.round(($results["avg"] / 10), 1)." / 10";
								$out .= '</div>';
							}
						}else{ //nope, but thanks for trying
							$buffer = '';
							$bufferInner = ''; //buffer the innser, so we can pass it back to the ajax request if enabled

							$buffer .= '<div class="nggv_container">';
							$bufferInner .= 'Thank you for casting your vote!';
							$buffer .= $bufferInner;
							$buffer .= '</div>';
							
							if($_GET['ajaxify']) {
								$out .= "nggv_js.nggv_container = '".addslashes($bufferInner)."';";
							}else{
								$out .= $buffer;
							}
						}
					}
				}
			}
			
			if($_GET['ajaxify'] && $_GET['gid'] == $gid) {
				$out .= "<!--#NGGV END AJAX RESPONSE#-->";
			}
			
			return $out;
		}

		function nggv_imageVoteForm($pid) {
			if(!is_numeric($pid)) {
				//trigger_error("Invalid argument 1 for function ".__FUNCTION__."(\$galId).", E_USER_WARNING);
				return;
			}
			
			$options = nggv_getImageVotingOptions($pid);
			$out = "";
			$errOut = "";
			
			if($_POST && $_POST["nggv"]["vote_pid_id"] && $pid == $_POST["nggv"]["vote_pid_id"]) { //dont try save a vote for a gallery silly (and make sure this is the right pid cause we are in a loop)
				if(($msg = nggv_saveVoteImage(array("pid"=>$pid, "vote"=>$_POST["nggv"]["vote_image"]))) === true) {
					$saved = true;
				}else{
					//$out .= '<div class="nggv-error">';
					if($msg == "VOTING NOT ENABLED") {
						$errOut .= "Voting is not enabled for this image";
					}else if($msg == "NOT LOGGED IN") {
						$errOut .= "You need to be logged in to vote on this image.";
					}else if($msg == "USER HAS VOTED") {
						$errOut .= "You have already voted on this image.";
					}else if($msg == "IP HAS VOTED") {
						$errOut .= "This IP has already voted on this image.";
					}else{
						$errOut .= "There was a problem saving your vote, please try again in a few moments.";
					}
					//$out .= '</div>';
					//maybe return $out here?  user really should only get here if they are 'hacking' the dom anyway?
				}
			}else if($_GET["ngg-pid"] && is_numeric($_GET["r"]) && $pid == $_GET["ngg-pid"]) { //star and like/dislike rating, js disabled
				if($options->voting_type == 3) { //like/dislike
					if($_GET['r']) {$_GET['r'] = 100;} //like/dislike is all or nothing :)
				}
				if(($msg = nggv_saveVoteImage(array("pid"=>$pid, "vote"=>$_GET["r"]))) === true) {
					$saved = true;
				}else{
					//$out .= '<div class="nggv-error">';
					if($msg == "VOTING NOT ENABLED") {
						$errOut .= "Voting is not enabled for this image";
					}else if($msg == "NOT LOGGED IN") {
						$errOut .= "You need to be logged in to vote on this image.";
					}else if($msg == "USER HAS VOTED") {
						$errOut .= "You have already voted on this image.";
					}else if($msg == "IP HAS VOTED") {
						$errOut .= "This IP has already voted on this image.";
					}else{
						$errOut .= "There was a problem saving your vote, please try again in a few moments.";
					}
					//$out .= '</div>';
					//maybe return $out here?  user really should only get here if they are 'hacking' the dom anyway?
				}
			}
			
			if($_GET['ajaxify'] && $_GET['ngg-pid'] == $pid) {
				$out .= "<!--#NGGV START AJAX RESPONSE#-->";
				$out .= "var nggv_js = {};";
				$out .= "nggv_js.options = {};";
				foreach ($options as $key=>$val) {
					$out .= 'nggv_js.options.'.$key.' = "'.$val.'";';
				}
				
				$out .= "nggv_js.saved = ".($saved ? "1" : "0").";";
				$out .= "nggv_js.msg = '".addslashes($errOut)."';";
			}else{
				//TODO XMAS remove color styling
				$out .= '<div class="nggv-error" style="display:'.($errOut ? 'block' : 'none').'; border:1px solid red; background:#fcc; padding:10px;">';
				$out .= $errOut;
				$out .= '</div>';
			}
			
			if((($canVote = nggv_canVoteImage($pid)) === true) && !$saved) { //they can vote, show the form
				$url = $_SERVER["REQUEST_URI"];
				
				$url .= (strpos($url, "?") === false ? "?" : (substr($url, -1) == "&" ? "" : "&")); //make sure the url ends in "?" or "&" correctly
				//todo, try not duplicate the GET[gid] and GET[r] if clicked 2x
				if($options->voting_type == 3) { //like / dislike (new in 1.5)
					$dirName = plugin_basename(dirname(__FILE__));
					$out .= nggv_include_js(WP_PLUGIN_URL.'/'.$dirName.'/js/ajaxify-likes.js');	//ajaxify voting, from v1.7
					
					$out .= '<div class="nggv_container">';
					$out .= '<a href="'.$url.'ngg-pid='.$pid.'&r=1" class="nggv-link-like"><img src="'.WP_PLUGIN_URL."/".$dirName."/images/thumbs_up.png".'" alt="Like" /></a>';
					$out .= '<a href="'.$url.'ngg-pid='.$pid.'&r=0" class="nggv-link-dislike"><img src="'.WP_PLUGIN_URL."/".$dirName."/images/thumbs_down.png".'" alt="Dislike" /></a>';
					$out .= '<img class="nggv-star-loader" src="'.WP_PLUGIN_URL.'/'.$dirName.'/images/loading.gif'.'" style="display:none;" />';
					if($options->user_results) {
						$results = nggv_getImageVotingResults($pid, array("likes"=>true, "dislikes"=>true));
						$out .= '<div class="like-results">';
						$out .= $results['likes'].' ';
						$out .= $results['likes'] == 1 ? 'Like, ' : 'Likes, ';
						$out .= $results['dislikes'].' ';
						$out .= $results['dislikes'] == 1 ? 'Dislike' : 'Dislikes';
						$out .= '</div>';
					}
					$out .= '</div>';
				}elseif($options->voting_type == 2) { //star
					$out .= nggv_include_js(WP_PLUGIN_URL.'/nextgen-gallery-voting/js/ajaxify-stars.js');	//ajaxify voting, from v1.7
					$results = nggv_getImageVotingResults($pid, array("avg"=>true));
					$out .= '<link rel="stylesheet" href="'.WP_PLUGIN_URL.'/nextgen-gallery-voting/css/star_rating.css" type="text/css" media="screen" />';
					$out .= '<div class="nggv_container">';
					$out .= '<span class="inline-rating">';
					$out .= '<ul class="star-rating">';
					if($options->user_results) { //user can see curent rating
						$out .= '<li class="current-rating" style="width:'.round($results["avg"]).'%;">Currently '.round($results["avg"] / 20, 1).'/5 Stars.</li>';
					}
					$out .= '<li><a href="'.$url.'ngg-pid='.$pid.'&r=20" title="1 star out of 5" class="one-star">1</a></li>';
					$out .= '<li><a href="'.$url.'ngg-pid='.$pid.'&r=40" title="2 stars out of 5" class="two-stars">2</a></li>';
					$out .= '<li><a href="'.$url.'ngg-pid='.$pid.'&r=60" title="3 stars out of 5" class="three-stars">3</a></li>';
					$out .= '<li><a href="'.$url.'ngg-pid='.$pid.'&r=80" title="4 stars out of 5" class="four-stars">4</a></li>';
					$out .= '<li><a href="'.$url.'ngg-pid='.$pid.'&r=100" title="5 stars out of 5" class="five-stars">5</a></li>';
					$out .= '</ul>';
					$out .= '</span>';
					$out .= '<img class="nggv-star-loader" src="'.WP_PLUGIN_URL."/nextgen-gallery-voting/images/loading.gif".'" style="display:none;" />';
					$out .= '</div>';
				}else{
					/* dev note.  you can set any values from 0-100 (the api will only allow this range) */
					$out .= '<div class="nggv-image-vote-container">';
					$out .= '<form method="post" action="">';
					$out .= '<label forid="nggv_rating_image_'.$pid.'">Rate this image:</label>';
					$out .= '<input type="hidden" name="nggv[vote_pid_id]" value="'.$pid.'" />';
					$out .= '<select id="nggv_rating_image_'.$pid.'" name="nggv[vote_image]">';
					$out .= '<option value="0">0</option>';
					$out .= '<option value="10">1</option>';
					$out .= '<option value="20">2</option>';
					$out .= '<option value="30">3</option>';
					$out .= '<option value="40">4</option>';
					$out .= '<option value="50">5</option>';
					$out .= '<option value="60">6</option>';
					$out .= '<option value="70">7</option>';
					$out .= '<option value="80">8</option>';
					$out .= '<option value="90">9</option>';
					$out .= '<option value="100">10</option>';
					$out .= '</select>';
					$out .= '<input type="submit" value="Rate" />';
					$out .= '</form>';
					$out .= '</div>';
				}
			}else{ //ok, they cant vote.  what next?
				if($options->enable) { //votings enabled for this gallery, lets find out more...
					if($canVote === "NOT LOGGED IN") { //the api wants them to login to vote
						$out .= '<div class="nggv-image-vote-container">';
						$out .= 'Only registered users can vote on this image.  Please login to cast your vote';
						$out .= '</div>';
					}else if($canVote === "USER HAS VOTED" || $canVote === "IP HAS VOTED" || $canVote === true) { //api tells us they have voted, can they see results? (canVote will be true if they have just voted successfully)
						if($options->user_results) { //yes! show it
							if($options->voting_type == 3) {
								$results = nggv_getImageVotingResults($pid, array("likes"=>true, "dislikes"=>true));
								
								$buffer = '';
								$bufferInner = ''; //buffer the innser, so we can pass it back to the ajax request if enabled
								
								$buffer .= '<div class="nggv_container">';
								$bufferInner .= $results['likes'].' ';
								$bufferInner .= $results['likes'] == 1 ? 'Like, ' : 'Likes, ';
								$bufferInner .= $results['dislikes'].' ';
								$bufferInner .= $results['dislikes'] == 1 ? 'Dislike' : 'Dislikes';
								$buffer .= $bufferInner;
								$buffer .= '</div>';
								
								if($_GET['ajaxify']) {
									$out .= "nggv_js.nggv_container = '".addslashes($bufferInner)."';";
								}else{
									$out .= $buffer;
								}
							}elseif($options->voting_type == 2) {
								$results = nggv_getImageVotingResults($pid, array("avg"=>true));
								
								$buffer = '';
								$bufferInner = '';
								
								$buffer .= '<link rel="stylesheet" href="'.WP_PLUGIN_URL.'/nextgen-gallery-voting/css/star_rating.css" type="text/css" media="screen" />';
								$buffer .= '<div class="nggv_container">';
								$bufferInner .= '<span class="inline-rating">';
								$bufferInner .= '<ul class="star-rating">';
								$bufferInner .= '<li class="current-rating" style="width:'.round($results["avg"]).'%;">Currently '.round($results["avg"] / 20, 1).'/5 Stars.</li>';
								$bufferInner .= '<li>1</li>';
								$bufferInner .= '<li>2</li>';
								$bufferInner .= '<li>3</li>';
								$bufferInner .= '<li>4</li>';
								$bufferInner .= '<li>5</li>';
								$bufferInner .= '</ul>';
								$bufferInner .= '</span>';
								$bufferInner .= '<img class="nggv-star-loader" src="'.WP_PLUGIN_URL."/nextgen-gallery-voting/images/loading.gif".'" style="display:none;" />';
								$buffer .= $bufferInner;
								$buffer .= '</div>';
								
								if($_GET['ajaxify']) {
									$out .= "nggv_js.nggv_container = '".addslashes($bufferInner)."';";
								}else{
									$out .= $buffer;
								}
							}else{
								$results = nggv_getImageVotingResults($pid, array("avg"=>true));
								$out .= '<div class="nggv-image-vote-container">';
								$out .= 'Current Average: '.round(($results["avg"] / 10), 1)." / 10";
								$out .= '</div>';
							}
						}else{ //nope, but thanks for trying
							$buffer = '';
							$bufferInner = ''; //buffer the innser, so we can pass it back to the ajax request if enabled
							
							$buffer .= '<div class="nggv_container">';
							$bufferInner .= 'Thank you for casting your vote!';
							$buffer .= $bufferInner;
							$buffer .= '</div>';
							
							if($_GET['ajaxify']) {
								$out .= "nggv_js.nggv_container = '".addslashes($bufferInner)."';";
							}else{
								$out .= $buffer;
							}
						}
					}
				}
			}
			
			if($_GET['ajaxify'] && $_GET['ngg-pid'] == $pid) {
				$out .= "<!--#NGGV END AJAX RESPONSE#-->";
			}
			
			return $out;
		}
	//}

	//install funcs{
		register_activation_hook(__FILE__, "nggv_install");
		/**
		 * Create the database tables needed on activation
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return void
		 */
		function nggv_install() {
			global $wpdb;
			
			$table_name = $wpdb->prefix."nggv_settings";
			$sql = "CREATE TABLE ".$table_name." (
				id BIGINT(19) NOT NULL AUTO_INCREMENT,
				gid BIGINT NOT NULL DEFAULT 0,
				pid BIGINT NOT NULL DEFAULT 0,
				enable TINYINT NOT NULL DEFAULT 0,
				force_login TINYINT NOT NULL DEFAULT 0,
				force_once TINYINT NOT NULL DEFAULT 0,
				user_results TINYINT NOT NULL DEFAULT 0,
				voting_type INT NOT NULL DEFAULT 1,
				UNIQUE KEY id (id)
			);";
			require_once(ABSPATH."wp-admin/includes/upgrade.php");
			dbDelta($sql);
			
			$table_name = $wpdb->prefix."nggv_votes";
			$sql = "CREATE TABLE ".$table_name." (
			id BIGINT(19) NOT NULL AUTO_INCREMENT,
			gid BIGINT NOT NULL,
			pid BIGINT NOT NULL,
			vote INT NOT NULL DEFAULT 0,
			user_id BIGINT NOT NULL DEFAULT 0,
			ip VARCHAR(32) NULL,
			proxy VARCHAR(32) NULL,
			dateadded DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			UNIQUE KEY id (id)
			);";
			require_once(ABSPATH."wp-admin/includes/upgrade.php");
			dbDelta($sql);
		}
	//}
//}
?>