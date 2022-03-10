<?php
/**
 * Edupack Support Dashboard
 *
 * Adds Support for Edupack users
 *
 * @package Edupack
 */

/**
 * Crisp.
 *
 * Add Crisp to the admin dashboard for edupack users.
 */

add_action( 'plugins_loaded', 'init_edupack_crisp' );

function init_edupack_crisp() {
	if ( current_user_can( 'publish_posts' ) ) {
		add_action( 'admin_head', 'edupack_crisp_hook_head' );
	}
}

function edupack_crisp_sync_wordpress_user() {
	$output = '';

	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
	}

	if ( ! isset( $current_user ) ) {
		return '';
	}

	$website_verify = get_option( 'website_verify' );

	$email    = $current_user->user_email;
	$nickname = $current_user->display_name;

	if ( ! empty( $email ) && empty( $website_verify ) ) {
		$output .= '$crisp.push(["set", "user:email", "' . $email . '"]);';
	} elseif ( ! empty( $email ) ) {
		$hmac    = hash_hmac( 'sha256', $email, $website_verify );
		$output .= '$crisp.push(["set", "user:email", ["' . $email . '", "' . $hmac . '"]]);';
	}

	if ( ! empty( $nickname ) ) {
		$output .= '$crisp.push(["set", "user:nickname", "' . $nickname . '"]);';
	}

	return $output;
}

function edupack_crisp_sync_woocommerce_customer() {
	$output = '';
	if ( ! class_exists( 'WooCommerce' ) || is_admin() ) {
		return $output;
	}

	$customer = WC()->session->get( 'customer' );

	if ( $customer == null ) {
		return $output;
	}

	if ( isset( $customer['phone'] ) && ! empty( $customer['phone'] ) ) {
		$output .= '$crisp.push(["set", "user:phone", "' . $customer['phone'] . '"]);';
	}

	$nickname = '';

	if ( isset( $customer['first_name'] ) && ! empty( $customer['first_name'] ) ) {
		$nickname = $customer['first_name'];
	}
	if ( isset( $customer['last_name'] ) && ! empty( $customer['last_name'] ) ) {
		$nickname .= ' ' . $customer['last_name'];
	}

	if ( ! empty( $nickname ) ) {
		$output .= '$crisp.push(["set", "user:nickname", "' . $nickname . '"]);';
	}

	$data      = array();
	$data_keys = array(
		'company',
		'address',
		'address_1',
		'address_2',
		'postcode',
		'state',
		'country',
		'shipping_company',
		'shipping_address',
		'shipping_address_1',
		'shipping_address_2',
		'shipping_state',
		'shipping_country',
	);

	foreach ( $data_keys as $key ) {
		if ( isset( $customer[ $key ] ) && ! empty( $customer[ $key ] ) ) {
			$data[] = '["' . $key . '", "' . $customer[ $key ] . '"]';
		}
	}

	if ( count( $data ) > 0 ) {
		$output .= '$crisp.push(["set", "session:data", [[' . implode( ',', $data ) . ']]]);';
	}

	return $output;
}


function edupack_crisp_hook_head() {
	$website_id = 'eb614763-de7b-4daf-9aa0-76a33673b500';
	$locale     = str_replace( '_', '-', strtolower( get_locale() ) );
	global $pagenow;

	if ( ! in_array( $locale, array( 'pt-br', 'pt-pr' ) ) ) {
		$locale = substr( $locale, 0, 2 );
	}

	if ( ! isset( $website_id ) || empty( $website_id ) ) {
		return;
	}

	$output = "<script data-cfasync='false'>
        window.\$crisp=[];
        CRISP_RUNTIME_CONFIG = {
            locale : '$locale'
        };
        CRISP_WEBSITE_ID = '$website_id';";

	$output .= "(function(){
        d=document;s=d.createElement('script');
        s.src='https://client.crisp.chat/l.js';
        s.async=1;d.getElementsByTagName('head')[0].appendChild(s);
    })();";

	$output .= edupack_crisp_sync_wordpress_user();
	$output .= edupack_crisp_sync_woocommerce_customer();

	$output .= "
        \$crisp.push([\"on\", \"message:received\", function() {
            \$crisp.push(['do', 'chat:show']);
        }])
        \$crisp.push([\"on\", \"chat:closed\", function() {
            window.\$crisp.push(['do', 'chat:hide']);
        }])
    ";

	$output .= "
    window.CRISP_READY_TRIGGER = function() {
        if ( \$crisp.is(\"website:available\") === true ) {
            document.getElementById('edupack-chat-button').style.display = \"inline-block\";
        } else {
            document.getElementById('edupack-chat-button').style.display = \"none\";
        }
    };";

	if ( 'index.php' != $pagenow ) {
		$output .= "\$crisp.push(['do', 'chat:hide']);";
	}

	$output .= '</script>';

	echo $output;

}

/**
 * Register the dashboard widget.
 */
function register_edupack_support_dashboard_widget() {
	wp_add_dashboard_widget(
		'edupack_support_dashboard_widget',
		'Edupack Support',
		'edupack_support_dashboard_output'
	);
}
add_action( 'wp_dashboard_setup', 'register_edupack_support_dashboard_widget', 99999 );

/**
 * Hijack output.
 */
function edupack_support_dashboard_output() {
	printf(
		'<p>Thank you for testing <a href="https://edupack.dev/" title="Edupack">Edupack</a>.</p>
		<p><button id="edupack-chat-button" class="button button-primary" style="display:none;" onclick="$crisp.push([\'do\', \'chat:show\']);$crisp.push([\'do\', \'chat:open\']);">Live chat available</button>'
	);
}
