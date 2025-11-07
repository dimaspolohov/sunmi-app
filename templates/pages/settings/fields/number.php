<?php
/**
 * Global input template
 *
 * @package Onepix\PluginTemplate
 *
 * @var string $id   input id
 * @var string $name input name
 * @var string $value option value
 * @var int $min optional. Minvalue
 * @var int $max optional. Max value
 */

?>

<input
		type="number"
		id="<?php echo esc_attr( $id ); ?>"
		name="<?php echo esc_attr( $name ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		<?php echo isset( $min ) ? 'min="' . esc_attr( $min ) . '"' : ''; ?>
		<?php echo isset( $max ) ? 'max="' . esc_attr( $max ) . '"' : ''; ?>
>
