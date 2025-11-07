<?php
/**
 * Global image template
 *
 * @package Onepix\PluginTemplate
 *
 * @var string $id    input id
 * @var string $name  input name
 * @var string $value image url
 */

$default_image = 'https://fakeimg.pl/100x100/dbdbdb/909090';

$uploaded = ! empty( $value );
$value    = $value ?: $default_image;
?>

<div
		class="js-image-uploader"
		data-default-image="<?php echo esc_attr( $default_image ); ?>"
>
	<img
			src="<?php echo esc_attr( $value ); ?>"
			style="max-width:100px; max-height: 100px;"
			alt="">

	<br>

	<button
			type="submit"
			class="js-image-upload">
		<?php esc_html_e( 'Upload', 'plugin-template' ); ?>
	</button>
	<button
			type="submit"
			class="js-image-remove"
	>
		<?php esc_html_e( 'Remove', 'plugin-template' ); ?>
	</button>

	<input
			type="hidden"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo $value === $default_image ? '' : esc_attr( $value ); ?>"
			style="width:100%"
	>
</div>
