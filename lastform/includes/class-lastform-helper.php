<?php
/**
 * General helper funtions
 *
 * @link       http://meydjer.com
 * @since      2.0.0
 *
 * @package    Lastform
 * @subpackage Lastform/includes
 */

/**
 * General helper funtions
 *
 * @since      2.0.0
 * @package    Lastform
 * @subpackage Lastform/includes
 * @author     Meydjer Windmüller <meydjer@gmail.com>
 */

class Lastform_Helper {

	public static function check_sub_id($string, $to_check) {
		$string = explode('.', $string);
		$sub_id = $string[1];

		return $sub_id == $to_check;
	}


  public static function get_mime_type($ext) {
    $mime_list = array("application/msword,doc dot","application/pdf,pdf","application/pgp-signature,pgp","application/postscript,ps ai eps","application/rtf,rtf","application/vnd.ms-excel,xls xlb","application/vnd.ms-powerpoint,ppt pps pot","application/zip,zip","application/x-shockwave-flash,swf swfl","application/vnd.openxmlformats-officedocument.wordprocessingml.document,docx","application/vnd.openxmlformats-officedocument.wordprocessingml.template,dotx","application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,xlsx","application/vnd.openxmlformats-officedocument.presentationml.presentation,pptx","application/vnd.openxmlformats-officedocument.presentationml.template,potx","application/vnd.openxmlformats-officedocument.presentationml.slideshow,ppsx","application/x-javascript,js","application/json,json","audio/mpeg,mp3 mpga mpega mp2","audio/x-wav,wav","audio/x-m4a,m4a","audio/ogg,oga ogg","audio/aiff,aiff aif","audio/flac,flac","audio/aac,aac","audio/ac3,ac3","audio/x-ms-wma,wma","image/bmp,bmp","image/gif,gif","image/jpeg,jpg jpeg jpe","image/photoshop,psd","image/png,png","image/svg+xml,svg svgz","image/tiff,tiff tif","text/plain,asc txt text diff log","text/html,htm html xhtml","text/css,css","text/csv,csv","text/rtf,rtf","video/mpeg,mpeg mpg mpe m2v","video/quicktime,qt mov","video/mp4,mp4","video/x-m4v,m4v","video/x-flv,flv","video/x-ms-wmv,wmv","video/avi,avi","video/webm,webm","video/3gpp,3gpp 3gp","video/3gpp2,3g2","video/vnd.rn-realvideo,rv","video/ogg,ogv","video/x-matroska,mkv","application/vnd.oasis.opendocument.formula-template,otf","application/octet-stream,exe,");

    foreach ($mime_list as $mime_string) {
      $parts      = explode(',', $mime_string);
      $mime       = $parts[0];
      $extensions = explode(' ', $parts[1]);
      if (in_array(trim($ext), $extensions))
        return $mime;
    }
  }

}
