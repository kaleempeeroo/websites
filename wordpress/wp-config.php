<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'shah' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '#t1jr-=Kn,3<PB8pP-g@@O8uovkx&wS9k(j1(1%52%DWWV&j6<>U>r)hZO0wNN`?' );
define( 'SECURE_AUTH_KEY',  'gh=?c0u1RB8QeNn7.PuYGi>M!?&p2Oz}13G *kF/*nGTzrGz*e/uTNL|N]f(C/~v' );
define( 'LOGGED_IN_KEY',    '#UsRXo[4E@Y03I}m( 0vZ&Ji0Dl )_-j6`h@2y[Kz4zrP;F,@0M7xXUozDD~IewE' );
define( 'NONCE_KEY',        'Rxf88V@F:N<guCuUWfP9wWyc1Vl.,NRGuwUq==*8ED)zHlY{E-jh,dXK<d;(b:Qj' );
define( 'AUTH_SALT',        'Vw-G8WmKHl/j>*DVtbZeeb!]U?LN=~~@`,MRTR3-@3%D+I)I:Y(dN/.,6t]$^s3|' );
define( 'SECURE_AUTH_SALT', 'E /UdcG~^6vko1d B$1u|oUv`D~?SLnO+lL.5=,H|L@@juq}?fgQ^q09g{6A1-DI' );
define( 'LOGGED_IN_SALT',   '(d?WzoqYm1FnboH[yO>@9EU`S/DCM/K0a%b|]vC=9[y:v,YgO2&,RuxN}Z?KX-.q' );
define( 'NONCE_SALT',       '[Z<8xvbr5M5VMJ*epGbPfKHrIn4iBx=DB0`KI1Z-u0hY{K)9DPUpZx1TA++D#=X-' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
