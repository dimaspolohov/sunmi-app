<?php
/**
 * Settings page template
 *
 * @package Onepix\PluginTemplate
 */

?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php plugin_template()->admin_pages( 'settings' )->print_tabs(); ?>

    <?php
    // QR login block for Android app pairing.
    $current_user_id = get_current_user_id();
    if ( $current_user_id ) {
        $code = wp_generate_password( 20, false, false );
        set_transient( 'qr_login_' . $code, $current_user_id, 2 * MINUTE_IN_SECONDS );

        $endpoint = rest_url( 'custom/v1/qr-login/exchange' );
        $payload  = wp_json_encode( array(
            'action'   => 'qr-login',
            'endpoint' => $endpoint,
            'code'     => $code,
        ) );

        if ( class_exists( '\Endroid\QrCode\Writer\SvgWriter' ) ) {
            $qrCode  = \Endroid\QrCode\QrCode::create( $payload )
                                             ->setEncoding( new \Endroid\QrCode\Encoding\Encoding( 'UTF-8' ) )
                                             ->setErrorCorrectionLevel( new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow() )
                                             ->setSize( 200 )
                                             ->setMargin( 0 )
                                             ->setForegroundColor( new \Endroid\QrCode\Color\Color( 0, 0, 0 ) )
                                             ->setBackgroundColor( new \Endroid\QrCode\Color\Color( 255, 255, 255 ) );

            $writer  = new \Endroid\QrCode\Writer\SvgWriter();
            $result  = $writer->write( $qrCode );
            $qr_src  = $result->getDataUri();
        } else {
            // Fallback: plain text if library not installed yet
            $qr_src = '';
        }
        ?>
        <div class="notice notice-info" style="display:flex;align-items:center;gap:16px;">
            <div>
                <?php if ( ! empty( $qr_src ) ) : ?>
                    <img alt="QR Login" src="<?php echo $qr_src ?>" />
                <?php else : ?>
                    <code><?php echo esc_html( $payload ); ?></code>
                <?php endif; ?>
            </div>
            <div>
                <p><strong><?php echo esc_html__( 'Scan to sign in the Android app', 'lieferchef-sunmi' ); ?></strong></p>
                <p><?php echo esc_html__( 'QR is valid for 2 minutes. Refresh the page to regenerate.', 'lieferchef-sunmi' ); ?></p>
            </div>
        </div>
        <?php
    }
    ?>

    <form class="settings-form js-settings-form" action="options.php" method="POST">
		<?php
		plugin_template()->admin_pages( 'settings' )->do_settings_section();
		submit_button();
		?>
    </form>
</div>
