<?php 
// Busted! No direct file access
if (!defined( 'ABSPATH' ))
	die('no');



// Define Relative Constants to use with get_theme_part()
// Sets relative paths for the default directories/paths
if ( !defined( 'THEME_LIBRARY' ) )
    define( 'THEME_LIBRARY', 'lib' );

if ( !defined( 'THEME_I18N' ) )
    define( 'THEME_I18N', THEME_LIBRARY . '/languages' );

if ( !defined( 'THEME_FUNC' ) )
    define( 'THEME_FUNC', THEME_LIBRARY . '/functions' );

if ( !defined( 'THEME_IMG' ) )
    define( 'THEME_IMG', THEME_LIBRARY . '/assets/images' );

if ( !defined( 'THEME_LESS' ) )
    define( 'THEME_LESS', THEME_LIBRARY . '/assets/less' );

if ( !defined( 'THEME_CSS' ) )
    define( 'THEME_CSS', THEME_LIBRARY . '/assets/css' );

if ( !defined( 'THEME_JS' ) )
    define( 'THEME_JS', THEME_LIBRARY . '/assets/js' );

if ( !defined( 'THEME_ADMIN' ) )
    define( 'THEME_ADMIN', THEME_LIBRARY . '/admin' );


//require_once(WPF_FUNCTIONS . 'theme-functions.php');
//require_once(WPF_FUNCTIONS . 'shortcodes.php');
//require_once(WPF_CORE.'class.wpf-core.php');
//require_once(WPF_CORE.'class.wpf-post-type-archive-links.php');
//if(is_admin()) {
//    require_once(WPF_LIB_DIR.'admin/class.wpf-admin.php');
//}
//require_once(WPF_INCLUDES.'class.ge-gravity-forms.php');
//require_once(WPF_LIB_DIR.'custom-functions.php');