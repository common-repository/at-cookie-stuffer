<?php
/*
Plugin Name: AT Cookie Stuffer
Plugin URI: http://www.automatedtraffic.com
Description: Integrates with www.automatedtraffic.com.
Version: 1.0
Author: http://www.automatedtraffic.com
Author URI: http://www.automatedtraffic.com
*/

register_activation_hook(__FILE__, 'install_cookie_stuffer');
register_deactivation_hook( __FILE__, 'remove_cookie_stuffer' );
function install_cookie_stuffer() {
    add_option('load_cookie', '', '', 'yes');
    add_option('link_cookie', '', '', 'yes');
}
function remove_cookie_stuffer() {
    delete_option('load_cookie');
    delete_option('link_cookie');
}

add_filter('the_content', 'cookie_stuffer_content_filter');
function cookie_stuffer_content_filter($content) {
    $link_cookie = get_option('link_cookie');
    if (!empty($link_cookie)) {
        $doc = new DOMDocument();
        if (!@$doc->loadHTML($content)) $content = "Plugin error.";
        $doc = setLinks($doc, getLinks($doc), NULL, $link_cookie);
        return $doc->saveHTML();
    } else {
        return $content;
    }
}
add_action('wp_head', 'cookie_stuffer_header_action');
function cookie_stuffer_header_action() {
    echo "<script type=\"text/javascript\">\nfunction cstuff(url,affiliate){\ndocument.getElementById('cstufflink').src=affiliate;\nsetTimeout(\"window.location.href = '\"+url+\"'\",2000);\n}\n</script>\n";
}
add_action('wp_footer', 'cookie_stuffer_footer_action');
function cookie_stuffer_footer_action() {
    $load_cookie = get_option('load_cookie');
    echo "<iframe id=\"cstufflink\" frameborder=\"0\" width=\"0\" height=\"0\" src=\"$load_cookie\"></iframe>";
}

/**
 * This function finds all the anchor tags on the page and returns them
 *
 * @param object $doc This is the dom document object passed in
 * @return array $url This returns an array of urls for the page
 */
function getLinks($doc){
	$params = $doc->getElementsByTagName('a');

	foreach ($params as $param) {
		$url = $param -> getAttribute('href');

		//make sure it was a link
		if($url != ''){
			$arrURL[] = $url;
		}
	}
	return $arrURL;
}
/**
 * This function replaces all the links specified with their affiliate link cookie stuffed. Basically we set the link to itself and
 * call our function which stuffs the cookies and then redirects the page.
 *
 * @param object $doc This is the dom document object passed in
 * @return object $doc We return the edited dom document with our new code added
 */
function setLinks($doc,$arrLinks='',$affiliateLinks='',$linkAffiliate=''){
	//$affiliate = 'http://click.linksynergy.com/fs-bin/click?id=9*Wc/rzHVzY&offerid=53196.10000257&type=3&subid=0';

	$params = $doc->getElementsByTagName('a');


	foreach ($params as $param) {
		$url = $param -> getAttribute('href');

		//make sure it was a link
		if($url != ''){
			$redirect = $param -> getAttribute("href");



			//check if link needs to be set
			$key = array_search($redirect,$arrLinks);

			if($key !== false){


				//check if affiliate link is blank if so set it to what they put in the general affiliate link setting for the page
				$affiliate = ($affiliateLinks[$key] == '') ? $linkAffiliate : $affiliateLinks[$key];

				//make sure we have http:// at the beginning
				$affiliate = (substr($affiliate,0,7) != 'http://')? 'http://'.$affiliate : $affiliate;

				//not blank or just a http://
				if( $affiliate !== false && $affiliate != 'http://'){
					//disable links from working
//					$param -> setAttribute("href","#");
                                        $param -> removeAttribute("href");
                                        $param -> setAttribute("style", "cursor: pointer;");

					//add our own call to the cookie stuffer function
					$param -> setAttribute("onclick","cstuff('{$redirect}','{$affiliate}')");
				}
			}
		}
	}
	return $doc;
}

add_filter('plugin_action_links', 'cookie_stuffer_plugin_action_links', 10, 2);
function cookie_stuffer_plugin_action_links($links, $file) {
    if (strstr($file, 'cookie_stuffer')) {
        $settings_link = "<a href='options-general.php?page=cookie_stuffer.php'>Settings</a>";
        array_unshift($links, $settings_link);
    }
    return $links;
}

if (is_admin()) {
    add_action('admin_menu', 'cookie_stuffer_admin_menu');
    function cookie_stuffer_admin_menu() {
        add_options_page('Cookie Stuffer', 'Cookie Stuffer', 8, 'cookie_stuffer', 'cookie_stuffer_html');
    }
}

function cookie_stuffer_html() {
    ?>
        <div>
            <h2>Cookie Stuffer Settings</h2>

            <form method="post" action="options.php">

                <?php wp_nonce_field('update-options');?>

                <b>Please enter a cookie to stuff when your blog loads:</b>
                <input name="load_cookie" value="<?php echo get_option('load_cookie'); ?>" size="60" />

                <p />

                <b>Please enter a cookie to stuff when a visitor clicks a link in one of your blog posts:</b>
                <input name="link_cookie" value="<?php echo get_option('link_cookie'); ?>" size="60" />

                <p />

                <input type="submit" value="Save Changes" />

                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="page_options" value="load_cookie,link_cookie" />

            </form>

        </div>
    <?php
}

?>