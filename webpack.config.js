const Encore = require('@symfony/webpack-encore');

if (!Encore.isProduction()) {
    Encore.addEntry('app', './public/assets/js/app.js');
} else {
    Encore.addEntry('app', './public/assets/js/app.js')
          .addEntry('runtime', './public/assets/js/runtime.js');
}

Encore
    .setOutputPath('public/build')
    .setPublicPath('/build')
    .setManifestKeyPrefix('build/')
    // .enableStimulusBridge('./assets/controllers.json')
    .enableReactPreset()
    .splitEntryFiles()
    .disableFrequentRequests()
    .enableSassLoader()
    .enablePostCssLoader()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enableBrowserSyncLoader(function(browserSync) {
        browserSync.init({
            proxy: 'localhost:8000',
            files: [
                './templates/**/*.twig',
                './public/**/*.js',
                './public/**/*.css'
            ]
        });
    });

module.exports = Encore.getConfigureation();