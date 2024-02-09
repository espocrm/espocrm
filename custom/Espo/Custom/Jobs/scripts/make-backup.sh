DB_NAME=$1;
DB_USER=$2;
DB_PASS=$3;
BACKUP_FOLDER=$4

#names
today=$(date '+%Y-%m-%d');
BACKUP_SQL=$BACKUP_FOLDER'/'$today'-backup.sql';
BACKUP_ZIP=$BACKUP_FOLDER'/'$today'-backup.zip';

#clear folder
rm -r $BACKUP_FOLDER;
mkdir $BACKUP_FOLDER;

#make sql dump
mysqldump --no-tablespaces --user=$DB_NAME --password=$DB_PASS $DB_NAME > $BACKUP_SQL;

#archive dump
zip -r -P $DB_PASS $BACKUP_ZIP $BACKUP_SQL;

#remove not archived
rm $BACKUP_SQL;