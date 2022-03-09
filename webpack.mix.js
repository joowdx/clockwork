const mix = require('laravel-mix');
const del = require('del');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js(
        mix.inProduction()
            ? ['resources/js/app.js']
            : ['resources/js/app.js', 'resources/js/browsersync.js'],
        'public/js/app.js'
    )
    .postCss('resources/css/app.css', 'public/css', [
        require('postcss-import'),
        require('tailwindcss'),
    ])
    .browserSync({
        open: false,
        notify: false,
        proxy: process.env.APP_URL,
        files: [
            'resources/views/**/*.php',
            'public/js/**/*.js',
            'public/css/**/*.css',
        ],
    })
    .webpackConfig(require('./webpack.config'))
    .sourceMaps(false, 'source-map')
    .vue()
    .after(() => {
        if(mix.inProduction()) {
            del('public/js/app.js.map');
            del('public/css/app.css.map');
        }
    });

if (mix.inProduction()) {
    mix.version();
}
