var path = require('path');
const webpack = require('webpack');
const ManifestPlugin = require('webpack-manifest-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CleanWebpackPlugin = require('clean-webpack-plugin');
const ConcatPlugin = require('webpack-concat-plugin');

// const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

module.exports = {
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader'
            },
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {

                    loader: 'babel-loader',
                }
            },
            {
                test: /\.(sass|scss|css)$/,
                use: [
                    "style-loader",
                    MiniCssExtractPlugin.loader,
                    "css-loader",
                    "sass-loader"
                ]
            }
        ],
    },
    output: {
        path: path.join(__dirname, 'public'),
        filename: 'js/[name]-[contenthash].js',
        chunkFilename: 'js/[name]-[contenthash].js',
        publicPath: '/'
    },
    externals: {
        jquery: "jQuery",
        vue: "Vue"
    },
    optimization: {
        runtimeChunk: "single",
        splitChunks: {
            cacheGroups: {
                // split async vendor modules to async chunks
                async: {
                    test: /[\\/]node_modules[\\/]/,
                    chunks: "async",
                    minChunks: 1,
                    minSize: 20000,
                    priority: 2
                },
                // all vendors (except async) goes to vendor.js
                vendor: {
                    test: /[\\/]node_modules[\\/]/,
                    name: "vendor",
                    chunks: "all",
                    priority: 1
                },
                // all common code across entry points
                common: {
                    test: /\.s?js$/,
                    minChunks: 2,
                    name: "common",
                    chunks: "all",
                    priority: 0,
                    enforce: true
                },
                default: false // overwrite default settings
            }
        }
    },
    context: path.join(__dirname, 'resources/assets'),
    entry: {
        app: './js/app.js',
        posting: './js/posting.js',
        microblog: ['./js/pages/microblog.js', './sass/pages/microblog.scss'],
        forum: ['./js/pages/forum.js', './sass/pages/forum.scss'],
        wiki: ['./js/pages/wiki.js', './sass/pages/wiki.scss'],
        job: ['./js/pages/job.js', './sass/pages/job.scss'],
        homepage: ['./js/pages/homepage.js', './sass/pages/homepage.scss'],
        pm: './js/pages/pm.js',
        profile: ['./js/pages/profile.js', './sass/pages/profile.scss'],
        'job-submit': './js/pages/job/submit.js',
        wikieditor: './js/plugins/wikieditor.js',
        main: './sass/main.scss',
        auth: './sass/pages/auth.scss',
        help: './sass/pages/help.scss',
        'user-panel': './sass/pages/user.scss',
        errors: './sass/pages/errors.scss',
        pastebin: './sass/pages/pastebin.scss',
        adm: './sass/pages/adm.scss',
        search: './sass/pages/search.scss'
    },
    plugins: [
        new CleanWebpackPlugin(['public/js/*.*', 'public/css/*.*'], {} ),
        // @see https://webpack.js.org/guides/caching/#module-identifiers
        new webpack.HashedModuleIdsPlugin(),

        new MiniCssExtractPlugin({
            filename: "css/[name]-[contenthash].css"
        }),

        new ManifestPlugin({
            fileName: 'manifest.json'
        }),

        new ConcatPlugin({
            uglify: true,
            sourceMap: false,
            name: 'jquery-ui.js',
            fileName: 'js/jquery-ui.js',
            filesToConcat: [
                '../../node_modules/jquery-ui.1.11.1/ui/core.js',
                '../../node_modules/jquery-ui.1.11.1/ui/widget.js',
                '../../node_modules/jquery-ui.1.11.1/ui/mouse.js',
                '../../node_modules/jquery-ui.1.11.1/ui/resizable.js',
                '../../node_modules/jquery-ui.1.11.1/ui/sortable.js',
            ],
        }),
        //
        // new BundleAnalyzerPlugin()
    ]
};


