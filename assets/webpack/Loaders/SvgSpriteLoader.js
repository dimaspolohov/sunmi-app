/**
 * Webpack loader for creating SVG sprites.
 *
 * @return {Object} Config
 * @see https://github.com/JetBrains/svg-sprite-loader
 */

module.exports = {
    loader: 'svg-sprite-loader',
    options: {
        extract: true,
        outputPath: 'images/icons/',
        spriteFilename: 'svg-sprite.svg'
    }
}