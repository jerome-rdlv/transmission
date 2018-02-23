var
    args = require('yargs').argv,
    gulp = require('gulp'),
    plugins = require('gulp-load-plugins')()
;

var assetsPath = 'assets/';
var srcPath = assetsPath +'src/';
var distPath = assetsPath +'dist/';

var isProd = args.env === 'prod'; 
if (isProd) {
    console.log('production mode');
}

// sass

gulp.task('sass', function () {
    return gulp.src(srcPath + '*.scss')
        .pipe(plugins.sass({
            outputStyle: 'expanded',
            precision: 8
        }))
        .pipe(plugins.rename(function (path) {
            path.dirname = path.dirname.replace('scss', 'css');
        }))
        .pipe(plugins.if(isProd, plugins.autoprefixer('last 10 version', 'ie 8', 'ie 9')))
        .pipe(plugins.if(isProd, plugins.cssnano({safe: true})))
        .pipe(gulp.dest(distPath))
    ;
});

gulp.task('sass-watch', function () {
    return gulp.watch(srcPath + '*.scss', gulp.parallel('sass'));
});


// js
gulp.task('js', function () {
    return gulp.src(srcPath + '*.js')
        .pipe(plugins.jshint())
        .pipe(plugins.jshint.reporter('default'))
        .pipe(plugins.if(isProd, plugins.uglify()))
        .pipe(gulp.dest(distPath))
    ;
});

gulp.task('js-watch', function () {
    return gulp.watch(srcPath + '*.js', gulp.parallel('js'));
});


// defaults
gulp.task('default', gulp.parallel('sass', 'js'));
gulp.task('watch', gulp.parallel('sass-watch', 'js-watch'));