const mix = require('laravel-mix');

mix.disableSuccessNotifications();

mix.scripts('resources/js/scripts.js', 'assets/js/scripts.js');
mix.scripts('resources/js/admin.js', 'assets/js/admin.js');

mix.sass('resources/scss/styles.scss', 'assets/css').options({
    processCssUrls: false
});
mix.sass('resources/scss/admin.scss', 'assets/css');

mix.copy('resources/icons/fonts', 'assets/fonts');
mix.copy('resources/images', 'assets/images');
mix.copy('node_modules/sweetalert2/dist', 'assets/vendor/sweetalert2/dist');

