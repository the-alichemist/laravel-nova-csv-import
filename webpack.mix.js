let mix = require('laravel-mix');
let path = require('path');

require('./nova.mix');

mix.setPublicPath('dist')
    .js('resources/js/tool.js', 'js')
    .postCss('resources/css/tool.css', 'public/css', [require('tailwindcss')])
    .vue({ version: 3 })
    .nova('simonhamp/laravel-nova-csv-import');

mix.alias({
    'laravel-nova': path.join(__dirname, 'vendor/laravel/nova/resources/js/mixins/packages.js'),
});
