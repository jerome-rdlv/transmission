var
    gulp = require('gulp'),
    plugins = require('gulp-load-plugins')()
;

var assetsPath = 'assets/';
var srcPath = assetsPath +'src/';
var distPath = assetsPath +'dist/';

gulp.task('sass', function () {
    return gulp.src(srcPath + '*.scss')
        .pipe(plugins.sass({
            outputStyle: 'expanded',
            precision: 8
        }))
        .pipe(plugins.rename(function (path) {
            path.dirname = path.dirname.replace('scss', 'css');
        }))
        .pipe(plugins.autoprefixer('last 10 version', 'ie 8', 'ie 9'))
        .pipe(plugins.cssnano({safe: true}))
        .pipe(gulp.dest(distPath))
    ;
});

gulp.task('sass-watch', function () {
    return gulp.watch(srcPath + '*.scss', gulp.parallel('sass'));
});