<?php
$ADMIN->add('reports', new admin_externalpage('reportcustomsql',
        get_string('customsql', 'report_customsql'),
        $CFG->wwwroot . '/' . $CFG->admin . '/report/customsql/index.php',
        'report/customsql:view'));
