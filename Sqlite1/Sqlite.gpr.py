register(EXPORT,
         id    = 'ex_sqlite1',
         name  = _('SQLite Export keys'),
         description =  _('SQLite is a common local database format including keys'),
         version = '1.2',
         gramps_target_version = "3.3",
         status = STABLE,
         fname = 'ExportSql1.py',
         export_function = 'exportData',
         extension = "db"
)
