<?php
/**
 * Determines Material WP Admin CSS version based on WordPress core version.
 */
function mtrl_css_version(){
    global $wp_version;

    $version = $wp_version;
    if(strlen($version) == 3){
        $version .= '.0';
    }

    return version_compare($version, '4.0.0', '>=') ? 'css40' : '';
}

$GLOBALS['mtrl_css_ver'] = mtrl_css_version();
?>
