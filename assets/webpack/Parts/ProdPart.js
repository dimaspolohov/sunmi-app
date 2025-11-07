const {merge} = require('webpack-merge')
const BasicPart = require('./BasicPart')
const cssMinimizerWebpackPlugin = require('css-minimizer-webpack-plugin')
const TerserPlugin = require('terser-webpack-plugin')

const buildWebpackConfig = merge(BasicPart, {
    mode: 'production',
    optimization: {
        minimize: true,
        minimizer: [
            new cssMinimizerWebpackPlugin(),
            new TerserPlugin()
        ]
    }
})

module.exports = new Promise((resolve, reject) => {
    resolve(buildWebpackConfig)
})