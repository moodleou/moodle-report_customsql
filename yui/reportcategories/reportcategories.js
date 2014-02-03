YUI.add('moodle-report_customsql-reportcategories', function(Y) {
    M.report_customsql = M.report_customsql || {};
    M.report_customsql.init = function() {
        Y.all('.csql_category').each(function(cat) {
            cat.one('h2').on('click', function(e) {
                var catwrapper = e.target.get('parentNode').get('parentNode');
                if (catwrapper) {
                    if (catwrapper.hasClass('csql_categoryhidden')) {
                        catwrapper.replaceClass('csql_categoryhidden', 'csql_categoryshown');
                    } else {
                        catwrapper.replaceClass('csql_categoryshown', 'csql_categoryhidden');
                    }
                    e.preventDefault();
                }
            });
        });
    };
}, '@VERSION@', {
    requires:['base']
});
