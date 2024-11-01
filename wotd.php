<?php
/*
Plugin Name: TFD Word of the Day
Plugin URI: http://wordpress.org/plugins/word-of-the-day-from-thefreedictionarycom/
Description: A widget that shows the Word of the Day as selected by TheFreeDictionary.com
Version: 2014.07.03
Author: Dino Chiesa
Author URI: http://www.dinochiesa.net
Donate URI: http://dinochiesa.github.io/TfdWotdWidgetDonate.html
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.txt
*/

// prevent direct access
function tfdwotd_safeRedirect($location, $replace = 1, $Int_HRC = NULL) {
    if(!headers_sent()) {
        header('location: ' . urldecode($location), $replace, $Int_HRC);
        exit;
    }
    exit('<meta http-equiv="refresh" content="4; url=' .
         urldecode($location) . '"/>');
    return;
}

if(!defined('WPINC')){
    tfdwotd_safeRedirect("http://" . $_SERVER["HTTP_HOST"]);
}


$tfdwotd_loglevel = 0; // bitfield?: 1 == global

if (!function_exists('tfdwotd_log')){
    function tfdwotd_log( $lvl, $message ) {
        global $tfdwotd_loglevel;
      if ( WP_DEBUG === true  || ($tfdwotd_loglevel>0 &&
                                  ($lvl & $tfdwotd_loglevel) != 0)) {
      if( is_array( $message ) || is_object( $message ) ){
          // error_log
          echo "<strong>tfdwotd:</strong> " . print_r( $message, true ) . "<br/>\n";
      }
      else {
        echo "<strong>tfdwotd:</strong> " . $message . "<br/>\n";
      }
    }
  }
}


if ( ! defined( 'TFDWOTD_PLUGIN_BASENAME' ) )
        define( 'TFDWOTD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( ! defined( 'TFDWOTD_PLUGIN_NAME' ) )
        define( 'TFDWOTD_PLUGIN_NAME', trim( dirname( TFDWOTD_PLUGIN_BASENAME ), '/' ) );

if ( ! defined( 'TFDWOTD_PLUGIN_DIR' ) )
        define( 'TFDWOTD_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . TFDWOTD_PLUGIN_NAME );



class TfdWotdWidget extends WP_Widget {

    private $cacheDir, $instance, $cacheLife;

    /** constructor */
    function TfdWotdWidget() {
        $opts = array('classname' => 'widget_tfdwotd',
                      'description' => __( 'Display Word of the Day from TheFreeDictionary.com') );
        parent::WP_Widget(false, $name = 'TFD Word of the Day', $opts);

        // Need a cache directory.  The /tmp dir is not writable on all
        // hosts.  In the future, I may make it possible to configure
        // this in the plugin settings.  For now it is fine to hard-code
        // it into the wp-content dir.
        $cacheDir = WP_CONTENT_DIR . '/cache/';
        self::setupCacheDir($cacheDir);
        $this->cacheDir = $cacheDir;
        $this->cacheLife = 120; // minutes

        // If in the future, I provide some possibilities for styling,
        // I may need to include the CSS and JS files here.
        //
        //$css = '/wp-content/plugins/tfdwotd/css/tfdwotd.css';
        //wp_enqueue_style('tfdwotd', $css);
        //$js = '/wp-content/plugins/tfdwotd/js/tfdwotd.js';
        //wp_enqueue_script('tfdwotd', $js);
    }

    private static function setupCacheDir($cacheDir) {
        if ( file_exists( $cacheDir )) {
            if (@is_dir( $cacheDir )) {
                return $cacheDir;
            }
            else {
                return null;
            }
        }

        if ( @mkdir( $cacheDir ) ) {
            $stat = @stat( dirname( $cacheDir ) );
            $dir_perms = $stat['mode'] & 0007777;
            @chmod( $cacheDir, $dir_perms );
            return $cacheDir;
        }
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        $this->instance = $instance;
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);

        echo $before_widget;
        if ( $title ) {
            echo $before_title .
                '<a href="http://www.thefreedictionary.com/">' .
                $title . '</a>' .
                $after_title;
        }

        $this->renderHtml();

        echo $after_widget;
    }

    private function getCacheFileName() {
        $cacheFile = 'tfd-wotd-cache.json';
        $fqfname = $this->cacheDir . $cacheFile;
        return $fqfname;
    }

    private function putCache($json) {
        $fqfname = $this->getCacheFileName();
        file_put_contents($fqfname, $json, LOCK_EX);
    }

    private function getCache() {
        $cache_life = $this->cacheLife; // minutes
        if ($cache_life <= 0) return null;

        $fqfname = $this->getCacheFileName();

        tfdwotd_log( 2, "getCache() filename: " . $fqfname);

        if (file_exists($fqfname)) {
            tfdwotd_log( 2, "getCache() file exists");
            if (filemtime($fqfname) > (time() - 60 * $cache_life)) {
                tfdwotd_log( 2, "getCache() is fresh");
                // The cache file is fresh.
                $json = file_get_contents($fqfname);
                $results = json_decode($json, true);
                return $results;
            }
            else {
                tfdwotd_log( 2, "getCache() is stale");
                unlink($fqfname);
            }
        }

        return null;
    }

    private static function curl_get_url_contents($url) {
        tfdwotd_log( 1, "curl_get_url_contents url:" . $url );
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_URL, $url);
        $contents = curl_exec($c);

        tfdwotd_log( 1, "contents: " . $contents );
        $resp = Array();
        $resp['status'] = curl_getinfo($c,CURLINFO_HTTP_CODE);
        curl_close($c);
        if ($contents) { $resp['contents']= $contents;}
        return $resp;
    }


    private function callOut() {
        $now = time();
        $url = 'http://www.thefreedictionary.com/_/WoD/jsa.aspx?_=' . $now;

        tfdwotd_log( 1, "callOut ENTER" );
        $r = self::curl_get_url_contents($url);
        if (!isset($r['status']) || ($r['status'] != '200')) {
            $resp = array( "error" =>
                           "could not retrieve Word of the Day. HTTP Status - " .
                           $r['status'] );
            return $resp;
        }
        // successful
        $contents = $r['contents'];

        $p = strpos($contents,'[');
        if ($p>0) {
            $json = substr($contents, $p);
            // strip comments
            if (preg_match("#.+(//.+$)#", $json, $matches, PREG_OFFSET_CAPTURE)) {
                $json = substr($json, 0, $matches[1][1]);
            }

            // swap double and single quotes.
            $json = preg_replace("/\"/", "\xa0", $json);
            $json = preg_replace("/'/", "\"", $json);
            $json = preg_replace("/\xa0/", "'", $json);

            $this->putCache($json);
            $results = json_decode($json, true);
            return $results;
        }

        return array( "error" => "could not parse WOTD page." );
    }


    private function renderHtml() {
        // render the widget
        echo "<div class='tfd-wotd'>\n";
        tfdwotd_log( 1, "render" );
        try {
            $results = $this->getCache(); // a string
            if (!$results) {
                // cache miss; must call out
                $results = $this->callOut();
                tfdwotd_log( 2, "callOut results: " . print_r( $results, true ) );
            }
            if (!isset($results['error'])) {
                echo "<h3><a href='http://www.thefreedictionary.com/" . $results[0] . "'>" .
                    $results[0] . "</a></h3>\n"; // the word
                echo "<table>\n<tbody>\n" .
                    "<tr><td>Word&nbsp;Form</td><td>" . $results[1] . "</td></tr>\n" .
                    "<tr><td>Definition</td><td>" . $results[2] . "</td></tr>\n" .
                    "<tr><td>Synonyms</td><td>" . $results[3] . "</td></tr>\n" .
                    "<tr><td>Usage</td><td>" . $results[4] . "</td></tr>\n" .
                    "</tbody>\n</table>\n";
            }
            else {
                echo "<p>The Word of the Day is not available.</p>\n";
                echo "<!-- " . $results['error'] . "-->\n";
            }
        }
        catch (Exception $ex1) {
            echo "<p>The Word of the Day is not available.</p>\n";
            echo "<!-- " . $ex1 . "-->\n";
        }
        echo "</div>\n";
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title']       = strip_tags($new_instance['title']);
        return $instance;
    }

    function form($instance) {
        $title = 'Word of the Day';

        if ($instance) {
            $title       = esc_attr($instance['title']);
        }
        else {
            $defaults = array('title'       => $title);
            $instance = wp_parse_args( (array) $instance, $defaults );
        }

        $fields = array(array('title', 'Title:', $title));

        include TFDWOTD_PLUGIN_DIR . '/view/form.php';
    }
}



if ( !function_exists('dpc_emit_paypal_donation_button') ) {
    function dpc_emit_paypal_donation_button($widget, $clazzName, $buttonCode) {
        if (!is_object($widget)) {
            /* sanity */
            echo "Not object<br/>\n";
            return;
        }
        $clz = get_class($widget);
        // only for this class
        if ($clz == $clazzName) {
            echo
                "<a target='_blank' " .
                "href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=" .
                $buttonCode . "'>" .
                "<img src='https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif' " .
                "border='0' alt='donate via PayPal'>" .
                "</a>\n" ;

        }
    }
}

add_action( 'in_widget_form', 'tfdwotd_appendDonation' );
function tfdwotd_appendDonation($widget, $arg2=null, $arg3=null) {
    dpc_emit_paypal_donation_button($widget, 'TfdWotdWidget', '2AFVRJ2WGCQBJ');
}

add_action( 'widgets_init', 'tfdwotd_widget_init' );
function tfdwotd_widget_init() {
    register_widget('TfdWotdWidget');
}

add_action( 'wp_enqueue_scripts', 'tfdwotd_add_style');
function tfdwotd_add_style() {
    wp_register_style( "tfd-wotd",
                       plugin_dir_url( __FILE__ ) . 'wotd.css',
                       false, false, 'all');
    wp_enqueue_style( "tfd-wotd");
}

?>
