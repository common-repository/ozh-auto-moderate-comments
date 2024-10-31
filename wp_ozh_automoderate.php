<?php
/*
Plugin Name: Ozh' Auto Moderate Comments
Plugin URI: http://planetozh.com/blog/2005/02/wordpress-plugin-auto-moderate-comments/
Description: Auto moderate comments when a post is older than XX days (See <a href="../wp-content/plugins/wp_ozh_automoderate.php">quick readme & manual</a>)
Version: 1.0.1
Author: Ozh
Author URI: http://planetOzh.com
*/

/*************************************
 *      OPTIONAL EDIT BELOW          *
 *               ~~                  *
 *************************************/


$ozh_moderate['max_age'] = 5;
		/* Age (number of days) of a post to become auto moderated
		 * The age of the post is the age of the last activity on it :
		 * (writing or) modifying it, or someone commenting it. An old post
		 * with a discussion still going on will be consider active.  */

$ozh_moderate['status_ok'] = '';
		/* Result of <?php wp_ozh_automoderate_status() ?> for a post considered active.
		 * Set this to "This post is opened to comments" if you want to make things clear */

$ozh_moderate['status_moderated'] = "<strong>Moderation Active:</strong> Old stuff here... Therefore your comment on this post will be <strong>moderated</strong> (i.e. don't submit twice !)<br/>";
		/* Result of <?php wp_ozh_automoderate_status() ?> for a post considered inactive.
		 * I find it a good idea to warn about moderation, and to ask for no multiple submission */


/*************************************
 *        DO NOT EDIT BELOW          *
 *               ~~                  *
 *************************************/

/**************************************************************************************************************************/


// script called directly (or something global badly misconfigured :)
if (!function_exists("get_option")) {wp_ozh_automoderate_readme();die;}

//Wordpress Version 1.2 / 1.3+ compatibility, add this to every plugin you write :)
if(!isset($wpdb->posts)) {
	foreach (array ("posts", "users", "categories", "post2cat", "comments",
		"links", "linkcategories", "options", "optiontypes", "optionvalues",
		"optiongroups", "optiongroup_options", "postmeta") as $table) {
			$wpdb->$table = ${"table".$table};
	}
}


// the comment # $id has been added, let's check it against criteria
function wp_ozh_automoderate($id) {
	global $wpdb, $ozh_moderate;
	$max_age = $ozh_moderate['max_age'];
	$limit = date("Y-m-d",mktime(0,0,0,date("m"), date("d") - $ozh_moderate['max_age'], date("Y")));

	$post_id = $wpdb->get_var("SELECT comment_post_ID FROM $wpdb->comments WHERE comment_ID='$id'");
	$lastcomment = $wpdb->get_results("select comment_date from $wpdb->comments WHERE comment_post_ID = '$post_id' AND comment_approved='1' ORDER BY comment_date DESC LIMIT 0,2");
	if (is_array($lastcomment)) array_shift ($lastcomment);

	if (!$lastcomment[0]->comment_date) {
		// this is the first comment on this post
		// let's compare its date with the post's age
		$age = $wpdb->get_var("SELECT post_modified from $wpdb->posts WHERE ID = '$post_id'");
		if ($age < $limit) {
			// post too old, let's moderate
			wp_ozh_automoderate_domod($id);
		}
	} elseif ($lastcomment[0]->comment_date < $limit) {
		// previous comment on this post is too old
		// this comment is going to be moderated
			wp_ozh_automoderate_domod($id);
	}
}

// this function moderates a comment that has been added
function wp_ozh_automoderate_domod($id) {
	global $wpdb;
	$return = $wpdb->query("UPDATE $wpdb->comments SET comment_approved='0' WHERE comment_id='$id'");
}

// this function prints the post status : comments accepted, comments moderated, comments closed
function wp_ozh_automoderate_status($display=1) {
	global $wpdb, $ozh_moderate, $id;
	$post_id = $id;

	$limit = date("Y-m-d",mktime(0,0,0,date("m"), date("d") - $ozh_moderate['max_age'], date("Y")));
	$lastcomment = $wpdb->get_var("select comment_date from $wpdb->comments WHERE comment_post_ID = '$post_id' AND comment_approved='1' ORDER BY comment_date DESC LIMIT 0,1");
	if (!$lastcomment) {
		// no comments, let's check post's age
		$age = $wpdb->get_var("SELECT post_modified from $wpdb->posts WHERE ID = '$post_id'");
		if ($age < $limit) {
			// post too old
			print $ozh_moderate['status_moderated'];
		} else {
			print $ozh_moderate['status_ok'];
		}
	} elseif ($lastcomment < $limit) {
		// last comment on this post is too old
		$msg = $ozh_moderate['status_moderated'];
	} else {
		$msg = $ozh_moderate['status_ok'];
	}
	if ($display) {
		print $msg;
	} else {
		return $msg;
	}
}

function wp_ozh_automoderate_readme () {
	echo '<html><head>
	<title>Auto Moderate Comments on Old Posts Plugin for Wordpress - By Ozh</title>
	<link rel="stylesheet" href="../../wp-admin/wp-admin.css" type="text/css" />
	</head>
	<body>
	<div id="wphead" style="height: 4.5em">
	<h1 align="right">Auto Moderate Comments on Old Posts - By Ozh</h1>
	</div>
	<div class="wrap">
	<h2>Thanks :)</h2>
	<p>Thank you for installing this plugin !</p>
	<h2>About this plugin</h2>
	<p>This plugins sends to moderation queue any comment added on a post where there has been <strong>no activity</strong> for XX days.</p>
	<p>No activity means : the post has not been posted, or modified, or there have been no comment made on it, in the last XX days. So an old post with a discussion still going on it is consider still active, which is smarter than forcing a discussion to be moderated for the sake of the post age.</p>
	<p>Of course, trackbacks and pingbacks are processed the same way.</p>
	<h2>Installation and Configuration</h2>
	<ul><li>Put the script in your plugins directory (<em>blog</em>/wp-content/plugins) which should be the case by now :)</li>
	<li><a href="../../wp-admin/plugin-editor.php?file=wp_ozh_automoderate.php">Edit the beginning of the script</a> to configure a very few vars (this is well documented)</li>
	<li>Don\'t forget to activate it in the <a href="../../wp-admin/plugins.php">Plugins Admin interface</a></li>
	<li>You can  have the plugin to print the status of a post (open or moderated) by adding the following code to a file (suggested : <em>blog</em>/wp-content/themes/your_theme/comments.php or <em>blog</em>/wp-comments.php) :
	<pre class="updated">&lt;?php wp_ozh_automoderate_status() ?></pre></li>
	</ul>
	<h2>Feedback</h2>
	<p>I\'d appreciate your leaving a comment on the plugin page, to suggest any improvement, bug fix, or just to say if you like the plugin or not :)
	By the way, you\'ll find on <a href="http://planetOzh.com/">my site</a> several other <a href="http://planetOzh.com/blog/my-projects/">WordPress plugins</a> you may find valueable</p>
	<h2>Disclaimer</h2>
	<p>Any resemblance between this page and  a well-known admin interface is purely coincidental :-P</p>
	</div>
	<div id="footer"><p><a href="http://planetOzh.com/"><img src="http://planetozh.com/images/buttons/btn_planetozh.png" border="0" alt="planetOzh.com" /></a><br />
	</div>
	</body></html>
	';
}

add_action('comment_post', 'wp_ozh_automoderate',30);
add_action('trackback_post', 'wp_ozh_automoderate',30);
add_action('pingback_post', 'wp_ozh_automoderate',30);

?>
