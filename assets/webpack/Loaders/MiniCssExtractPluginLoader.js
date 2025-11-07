const MiniCssExtractPlugin = require("mini-css-extract-plugin").loader;

/**
 * This plugin extracts CSS into separate files. It creates a CSS file for each JS file that contains CSS. It supports loading CSS and SourceMaps on demand.
 * It is based on a new webpack v5 feature and requires webpack 5 to run.
 *
 * @return {Object} Config
 * @see https://webpack.js.org/plugins/mini-css-extract-plugin/
 */

module.exports = MiniCssExtractPlugin