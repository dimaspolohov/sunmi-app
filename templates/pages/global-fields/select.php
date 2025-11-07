<?php
/**
 * Global select template
 *
 * @package Onepix\PluginTemplate
 *
 * @var string $id          input id
 * @var string $name        input name
 * @var string $value       option value
 * @var string $description description
 * @var array $values      values to select
 */

?>

<select
        id="<?php echo esc_attr( $id ); ?>"
        name="<?php echo esc_attr( $name ); ?>"
        style="width:100%"
>
	<?php foreach ( $values as $key => $title ) : ?>

        <option value="<?php echo esc_attr( $key ) ?>"<?php selected( $key, $value ) ?>>
            <?php echo esc_html( $title ) ?>
        </option>

	<?php endforeach; ?>
</select>

<?php if ( ! empty( $description ) ) : ?>
	<br>
	<p><?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
<?php endif; ?>
