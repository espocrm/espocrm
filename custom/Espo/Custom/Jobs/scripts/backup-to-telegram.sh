#!/bin/bash
TOKEN=$1
ID_CHAT=$2
BACKUP_FOLDER=$3

today=$(date '+%Y-%m-%d');
FILE=$BACKUP_FOLDER'/'$today'-backup.zip';

echo $FILE

#message for test
#URL="https://api.telegram.org/bot$TOKEN/sendMessage"
#THE_MESSAGE="Hello from my Desktop silent"
#curl -X POST --silent --output /dev/null $URL -d chat_id=$ID_CHAT -d text="$THE_MESSAGE" -d silent="true"

URL="https://api.telegram.org/bot$TOKEN/sendDocument?chat_id=$ID_CHAT"

curl -F document=@$FILE $URL

exit 0