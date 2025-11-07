<?php
/**
 * Global input template
 *
 * @package Onepix\PluginTemplate
 *
 * @var string $id          input id
 * @var string $name        input name
 * @var string $value       option value
 * @var string $description description
 */

?>

<input
		type="text"
		id="<?php echo esc_attr( $id ); ?>"
		name="<?php echo esc_attr( $name ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		style="width:100%"
>
<?php if ( ! empty( $description ) ) : ?>
	<br>
	<p><?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
<?php endif; ?>
