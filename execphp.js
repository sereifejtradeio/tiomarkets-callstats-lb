class ExecPHP {
    /**
     *
     */
    constructor() {
        //local
        //this.phpPath = '/Applications/xampp/xamppfiles/bin/php';
        //production
        this.phpPath = '/app/.heroku/php/bin/php';
        this.phpFolder = '';
    }
    /**
     *
     */
    parseFile(fileName,callback) {
        var realFileName = this.phpFolder + fileName;

        var exec = require('child_process').exec;
        var cmd = this.phpPath + ' ' + realFileName;

        exec(cmd, function(error, stdout, stderr) {
            callback(stdout);
        });
    }
}
module.exports = function() {
    return new ExecPHP();
};