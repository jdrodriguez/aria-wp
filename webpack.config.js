const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
    entry: {
      'admin': './src/js/admin/index.js',
      'admin-react': './src/js/admin/index.js',  // Same source, different output name for backwards compatibility
      'chat': './src/js/chat.js',
      'admin-style': './src/scss/admin.scss',
      'chat-style': './src/scss/chat.scss'
    },
    output: {
      path: path.resolve(__dirname, 'dist'),
      filename: '[name].js',
      clean: true
    },
    module: {
      rules: [
        {
          test: /\.(js|jsx)$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: [
                '@babel/preset-env',
                ['@babel/preset-react', { runtime: 'automatic' }]
              ]
            }
          }
        },
        {
          test: /\.(sa|sc|c)ss$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            {
              loader: 'postcss-loader',
              options: {
                postcssOptions: {
                  plugins: [
                    require('postcss-preset-env')({
                      browsers: 'last 2 versions',
                    })
                  ]
                }
              }
            },
            'sass-loader'
          ]
        },
        {
          test: /\.(png|jpg|jpeg|gif|svg)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'images/[name][ext]'
          }
        },
        {
          test: /\.(woff|woff2|eot|ttf|otf)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'fonts/[name][ext]'
          }
        }
      ]
    },
    plugins: [
      new CleanWebpackPlugin(),
      new MiniCssExtractPlugin({
        filename: '[name].css'
      })
    ],
    optimization: {
      minimize: isProduction,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            compress: {
              drop_console: isProduction
            }
          }
        }),
        new CssMinimizerPlugin()
      ]
    },
    externals: {
      '@wordpress/element': 'wp.element',
      '@wordpress/components': 'wp.components',
      '@wordpress/i18n': 'wp.i18n',
      '@wordpress/api-fetch': 'wp.apiFetch',
      '@wordpress/data': 'wp.data',
      '@wordpress/hooks': 'wp.hooks',
      '@wordpress/compose': 'wp.compose',
      '@wordpress/icons': 'wp.components.icons',
      'react': 'React',
      'react-dom': 'ReactDOM',
      'jquery': 'jQuery'
    },
    resolve: {
      extensions: ['.js', '.jsx']
    },
    devtool: isProduction ? false : 'source-map',
    watchOptions: {
      ignored: /node_modules/
    }
  };
};