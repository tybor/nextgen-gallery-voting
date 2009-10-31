<?php
/*
Plugin Name: NextGEN Gallery Voting
Description: This plugin allows users to add user voting to NextGEN Gallery Images 
Version: 0.1
Author: Shaun Alberts
Author URI: mailto:shaunalberts@gmail.com
*/
/*  
Copyright 2009  Shaun Alberts  (email : shaunalberts@gmail.com)

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
		 Checks if the current user can vote
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
		 * Get the voting results of a gallery
		 * @param int $gid The NextGEN Gallery ID
		 * @param array $type The type of results to return (can limti number of queries if you only need the avg for example)
		 *  bool type[avg] : Get average vote
		 *  bool type[list] : Get all the votes for the gallery
		 *  bool type[number] : Get the number of votes for the gallery
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return array("avg"=>double average for gallery, "list"=>array of objects of all votes of the gallery, "number"=>integer the number of votes for the gallery)
		 */
		function nggv_getVotingResults($gid, $type=array("avg"=>true, "list"=>true, "number"=>true)) {
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
				
				return array("avg"=>$avg->avg, "list"=>$list, "number"=>$num->num);
			}else{
				return array();
			}
		}
	//}
	
	// admin function {
		/* No need for admin menu, just something I normally have in my WordPress plugin template :)*/
		add_action('admin_menu', 'nggv_adminMenu');
		function nggv_adminMenu() {
			add_menu_page('NGG Voting', 'NGG Voting', 8, __FILE__, 'nggv_admin_options');
		}
		function nggv_admin_options() {
			if($_GET["action"] == "get-votes-list") {
				echo '<!--#NGGV START AJAX RESPONSE#-->'; //do not edit this line!!!
				
				if($_GET["gid"]) {
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
				}else{
					//error num?
				}
				
				exit;
			}
		}
		
		add_filter('ngg_manage_gallery_columns', 'nggv_add_vote_options');
		/**
		 * Add the voting options to a gallery, using some sneaky javascript
		 * @param object $list The list of gallery fields passed from the filter
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return object $list The 1st param passed is returned unaltered.  We only use this hook to inject some javasctipt into the page
		 */
		function nggv_add_vote_options($list) {
			global $wpdb, $nggv_scripted;
			
			if(!$nggv_scripted) { //its a hack, so just check that its only called once :)
				$nggv_scripted = true;
				$options = nggv_getVotingOptions($_GET["gid"]);
				$results = nggv_getVotingResults($_GET["gid"], array("avg"=>true, "num"=>true));
				
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
				var nggv_avg = Math.round(".($results["avg"] ? $results["avg"] : 0).") / 10;
				var nggv_num_votes = parseInt(".($results["number"] ? $results["number"] : 0).");
				
				var nggv_more_url = '".$popup."';
				</script>";
				wp_enqueue_script('newscript', WP_PLUGIN_URL . '/nextgen-gallery-voting/js/gallery_options.js', array('jquery'), false, true);
				
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
			
			return $list;
		}
		
		add_action('ngg_update_gallery', 'nggv_save_gallery_options', 10, 2);
		/**
		 * Save the options for a gallery
		 * @param int $gid The NextGEN Gallery ID
		 * @param array $post the _POST array from the gallery save form. We have added the following fields for our options
		 *  int post["nggv"]["enable"] : Enable voting for the gallery
		 *  int post["nggv"]["force_login"] : Force the user to login to cast vote
		 *  int post["nggv"]["force_once"] : Only allow a user to vote once
		 *  int post["nggv"]["user_results"] : If users see results
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return
		 */
		function nggv_save_gallery_options($gid, $post) {
			global $wpdb;

			$enable = $post["nggv"]["enable"] ? "1" : "0";
			$login = $post["nggv"]["force_login"] ? "1" : "0";
			$once = $post["nggv"]["force_once"] ? "1" : "0";
			$user_results = $post["nggv"]["user_results"] ? "1" : "0";
			
			
			if(nggv_getVotingOptions($gid)) {
				$wpdb->query("UPDATE ".$wpdb->prefix."nggv_settings SET force_login = '".$login."', force_once = '".$once."', user_results = '".$user_results."', enable = '".$enable."' WHERE gid = '".$wpdb->escape($gid)."'");
			}else{
				$wpdb->query("INSERT INTO ".$wpdb->prefix."nggv_settings (id, gid, enable, force_login, force_once, user_results) VALUES (null, '".$wpdb->escape($gid)."', '".$enable."', '".$login."', '".$once."', '".$user_results."')");
			}
			
			//gotta force a reload or the js globals declared in nggv_add_vote_options() are set to the pre-saved values, and the checkboxes are ticked incorrectly (hack hackity hack hack hack)
			echo "<script>window.location = window.location;</script>";
			exit;
		}
	//}

	// front end funcs {
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
			
			$out = "";
			
			if($_POST) {
				if(($msg = nggv_saveVote(array("gid"=>$gid, "vote"=>$_POST["nggv"]["vote"]))) === true) {
					$saved = true;
				}else{
					$out .= '<div class="nggv-error">';
					if($msg == "VOTING NOT ENABLED") {
						$out .= "This gallery has not turned on voting.";
					}else if($msg == "NOT LOGGED IN") {
						$out .= "You need to be logged in to vote on this gallery.";
					}else if($msg == "USER HAS VOTED") {
						$out .= "You have already voted on this gallery.";
					}else if($msg == "IP HAS VOTED") {
						$out .= "This IP has already voted on this gallery.";
					}else{
						$out .= "There was a problem saving your vote, please try again in a few moments.";
					}
					$out .= '</div>';
					//maybe return $out here?  user really should only get here if they are 'hacking' the dom anyway?
				}
			}
			
			if((($canVote = nggv_canVote($gid)) === true) && !$saved) { //they can vote, show the form
				/* dev note.  you can set any values from 0-100 (the api will only allow this range) */
				$out .= '<div class="nggv_container">';
				$out .= '<form method="post" action="">';
				$out .= '<label forid="nggv_rating">Rate this gallery:<label>';
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
			}else{ //ok, they cant vote.  what next?
				$options = nggv_getVotingOptions($gid);
				if($options->enable) { //votings enabled for this gallery, lets find out more...
					if($canVote === "NOT LOGGED IN") { //the api wants them to login to vote
						$out .= '<div class="nggv_container">';
						$out .= 'Only registered users can vote.  Please login to cast your vote';
						$out .= '</div>';
					}else if($canVote === "USER HAS VOTED" || $canVote === "IP HAS VOTED" || $canVote === true) { //api tells us they have voted, can they see results? (canVote will be true if they have just voted successfully)
						if($options->user_results) { //yes! show it
							$results = nggv_getVotingResults($gid, array("avg"=>true));
							$out .= '<div class="nggv_container">';
							$out .= 'Current Average: '.round(($results["avg"] / 10), 1)." / 10";
							$out .= '</div>';
						}else{ //nope, but thanks for trying
							$out .= '<div class="nggv_container">';
							$out .= 'Thank you for casting your vote!';
							$out .= '</div>';
						}
					}
				}
			}
			
			return $out;
		}
	//}

	//install funcs{
		register_activation_hook(__FILE__, "nggv_install");
		/**
		 * Create the database tables needed on install
		 * @author Shaun <shaunalberts@gmail.com>
		 * @return void
		 */
		function nggv_install() {
			global $wpdb;
			
			$table_name = $wpdb->prefix."nggv_settings";
			$sql = "CREATE TABLE ".$table_name." (
				id BIGINT(19) NOT NULL AUTO_INCREMENT,
				gid BIGINT NOT NULL,
				enable TINYINT NOT NULL DEFAULT 0,
				force_login TINYINT NOT NULL DEFAULT 0,
				force_once TINYINT NOT NULL DEFAULT 0,
				user_results TINYINT NOT NULL DEFAULT 0,
				UNIQUE KEY id (id)
			);";
			require_once(ABSPATH."wp-admin/includes/upgrade.php");
			dbDelta($sql);
			
			$table_name = $wpdb->prefix."nggv_votes";
			$sql = "CREATE TABLE ".$table_name." (
			id BIGINT(19) NOT NULL AUTO_INCREMENT,
			gid BIGINT NOT NULL,
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