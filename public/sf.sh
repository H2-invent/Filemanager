#!/bin/bash

# export SIMPLE_FILEMANAGER_AUTH_TOKEN=0314e4dd5031799beb52a24e6521e38204c75be01f64794c96a9af11ec47e61a
# export SIMPLE_FILEMANAGER_DOMAIN=http://localhost:8080
cat << "EOF"
   ___  _  _                                                          
  / __\(_)| |  ___  _ __ ___    __ _  _ __    __ _   __ _   ___  _ __ 
 / _\  | || | / _ \| '_ ` _ \  / _` || '_ \  / _` | / _` | / _ \| '__|
/ /    | || ||  __/| | | | | || (_| || | | || (_| || (_| ||  __/| |   
\/     |_||_| \___||_| |_| |_| \__,_||_| |_| \__,_| \__, | \___||_|   
                                                    |___/             
by H2 invent

EOF

echo "The file will be uploaded to: $SIMPLE_FILEMANAGER_DOMAIN"
echo ""

read -p "Enter the TITLE: " SIMPLE_FILEMANAGER_TITLE
read -p "Enter the DESCRIPTION: " SIMPLE_FILEMANAGER_DESCRIPTION
read -p "Expire after X hours: " SIMPLE_FILEMANAGER_EXPIRE
read -p "Enter EMAIL of user for permission: " SIMPLE_FILEMANAGER_EMAIL
read -p "Enter the FILE PATH: " SIMPLE_FILEMANAGER_FILE

curl -X POST $SIMPLE_FILEMANAGER_DOMAIN/filemanager/upload \
     -H "Authorization: Bearer $SIMPLE_FILEMANAGER_AUTH_TOKEN" \
     -F "title=$SIMPLE_FILEMANAGER_TITLE" \
     -F "description=$SIMPLE_FILEMANAGER_DESCRIPTION" \
     -F "expire=$SIMPLE_FILEMANAGER_EXPIRE" \
     -F "permission=$SIMPLE_FILEMANAGER_EMAIL" \
     -F "file=@$SIMPLE_FILEMANAGER_FILE"
echo ""
echo ""
