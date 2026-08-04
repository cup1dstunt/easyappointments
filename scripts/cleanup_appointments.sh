#!/bin/bash
set -o pipefail

# Test run (true for "dry run" in testing, false for production)
TEST_RUN=false

if [ "$TEST_RUN" = "true" ]; then
    echo "Running as test, no modifications done..."
fi

# Todays date in format used in database
TODAY=$(date '+%Y-%m-%d %H:%M:%S')
echo "$TODAY"

# Current folder
echo "Current folder: $(pwd)"

# Logfile
LOGS_FOLDER=../storage/logs/
if [ ! -d "$LOGS_FOLDER" ]; then
    echo "Error: logs folder not found: $LOGS_FOLDER"
    exit 1
fi
TODAY_FILENAME=$(date '+%Y-%m-%d_%H-%M-%S')
LOGFILE=../storage/logs/cleanup_$TODAY_FILENAME.log
echo "Logfile: $LOGFILE" |& tee "$LOGFILE"

# Uploads folder
UPLOADS_FOLDER=../storage/uploads/
echo "Uploads folder: $UPLOADS_FOLDER" |& tee -a "$LOGFILE"
if [ ! -d "$UPLOADS_FOLDER" ]; then
    echo "Error: uploads folder not found: $UPLOADS_FOLDER" |& tee -a "$LOGFILE"
    exit 1
fi

# Sessions folder
SESSIONS_FOLDER=../storage/sessions/
echo "Sessions folder: $SESSIONS_FOLDER" |& tee -a "$LOGFILE"
if [ ! -d "$SESSIONS_FOLDER" ]; then
    echo "Error: sessions folder not found: $SESSIONS_FOLDER" |& tee -a "$LOGFILE"
    exit 1
fi

# Config file
CONFIG_FILE=../config.php
echo "Config file: $CONFIG_FILE" |& tee -a "$LOGFILE"
if [ ! -f "$CONFIG_FILE" ]; then
    echo "Error: config file not found: $CONFIG_FILE" |& tee -a "$LOGFILE"
    exit 1
fi

# Get host, username, password and database name from the config.php file
DB_HOST=$(cat "$CONFIG_FILE" | awk -F"'" '/DB_HOST/ { print $2 }')
DB_DATABASE=$(cat "$CONFIG_FILE" | awk -F"'" '/DB_NAME/ { print $2 }')
DB_USERNAME=$(cat "$CONFIG_FILE" | awk -F"'" '/DB_USERNAME/ { print $2 }')
DB_PASSWORD=$(cat "$CONFIG_FILE" | awk -F"'" '/DB_PASSWORD/ { print $2 }')
echo "Host: $DB_HOST" |& tee -a "$LOGFILE"
echo "Database: $DB_DATABASE" |& tee -a "$LOGFILE"
echo "Username: $DB_USERNAME" |& tee -a "$LOGFILE"
echo "DB_PASSWORD: [set]" |& tee -a "$LOGFILE"

# Deleting files from past appointments
# Files are stored in subfolder under storage/uploads/<appointment_id>
echo "Deleting files attached to past appointments..." |& tee -a "$LOGFILE"
MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -u"$DB_USERNAME" "$DB_DATABASE" -s -N -e \
  "SELECT id FROM ea_appointments WHERE end_datetime < '$TODAY';" | while read -r APPOINTMENT_ID
do
    APPOINTMENT_UPLOADS_FOLDER="$UPLOADS_FOLDER$APPOINTMENT_ID"
    if [ -d "$APPOINTMENT_UPLOADS_FOLDER" ]; then
        echo "$APPOINTMENT_UPLOADS_FOLDER" |& tee -a "$LOGFILE"
        if [ "$TEST_RUN" != "true" ]; then
            rm -rf "$APPOINTMENT_UPLOADS_FOLDER"
        fi
    fi
done || exit 1

# Deleting orphaned files from storage/uploads:
# - Folders not connected to any appointment_id
# - Files in the main folder
echo "Sweeping for orphaned attached files..." |& tee -a "$LOGFILE"
EXISTING_APPOINTMENT_IDS=$(MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -u"$DB_USERNAME" "$DB_DATABASE" -s -N -e \
  "SELECT id FROM ea_appointments;") || exit 1

for ENTRY in "$UPLOADS_FOLDER"*; do
    [ -e "$ENTRY" ] || continue

    ENTRY_NAME=$(basename "$ENTRY")
    if [ "$ENTRY_NAME" = ".htaccess" ] || [ "$ENTRY_NAME" = "index.html" ]; then
        continue
    fi
    if [ -f "$ENTRY" ]; then
        echo "Orphaned file (not inside an appointment subfolder): $ENTRY" |& tee -a "$LOGFILE"
        if [ "$TEST_RUN" != "true" ]; then
            rm -f "$ENTRY"
        fi
    elif [ -d "$ENTRY" ] && [[ "$ENTRY_NAME" =~ ^[0-9]+$ ]] && ! grep -qx "$ENTRY_NAME" <<< "$EXISTING_APPOINTMENT_IDS"; then
        echo "Orphaned folder (no matching appointment): $ENTRY" |& tee -a "$LOGFILE"
        if [ "$TEST_RUN" != "true" ]; then
            rm -rf "$ENTRY"
        fi
    fi
done

# Delete appointments in the past
echo "Deleting past appointments from database..." |& tee -a "$LOGFILE"
MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -u"$DB_USERNAME" "$DB_DATABASE" --table -e \
  "SELECT * FROM ea_appointments WHERE end_datetime < '$TODAY';" |& tee -a "$LOGFILE" || exit 1
if [ "$TEST_RUN" != "true" ]; then
    MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -u"$DB_USERNAME" "$DB_DATABASE" --table -e \
      "DELETE FROM ea_appointments WHERE end_datetime < '$TODAY';" |& tee -a "$LOGFILE" || exit 1
fi

# Delete sessions older than 7 days
echo "Deleting session files older than 7 days..." |& tee -a "$LOGFILE"
find "$SESSIONS_FOLDER" -depth -mindepth 1 -type f -mtime +7 ! -name ".htaccess" ! -name "index.html" -print |& tee -a "$LOGFILE"
if [ "$TEST_RUN" != "true" ]; then
    find "$SESSIONS_FOLDER" -mindepth 1 -type f -mtime +7 ! -name ".htaccess" ! -name "index.html" -delete |& tee -a "$LOGFILE"
fi

# Delete working plan exceptions in the past
echo "Deleting past working plan exceptions from database..." |& tee -a "$LOGFILE"
MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -u"$DB_USERNAME" "$DB_DATABASE" --table -e \
  "SELECT * FROM ea_working_plan_exceptions WHERE end_date < CURDATE();" |& tee -a "$LOGFILE" || exit 1
if [ "$TEST_RUN" != "true" ]; then
    MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -u"$DB_USERNAME" "$DB_DATABASE" --table -e \
      "DELETE FROM ea_working_plan_exceptions WHERE end_date < CURDATE();" |& tee -a "$LOGFILE" || exit 1
fi

# Delete blocked periods in the past
echo "Deleting past blocked periods from database..." |& tee -a "$LOGFILE"
MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -u"$DB_USERNAME" "$DB_DATABASE" --table -e \
  "SELECT * FROM ea_blocked_periods WHERE end_datetime < '$TODAY';" |& tee -a "$LOGFILE" || exit 1
if [ "$TEST_RUN" != "true" ]; then
    MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -u"$DB_USERNAME" "$DB_DATABASE" --table -e \
      "DELETE FROM ea_blocked_periods WHERE end_datetime < '$TODAY';" |& tee -a "$LOGFILE" || exit 1
fi

# Delete customers having no appointments
echo "Deleting customers with no appointments in database..." |& tee -a "$LOGFILE"
MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -u"$DB_USERNAME" "$DB_DATABASE" --table -e \
  "SELECT * FROM ea_users WHERE id_roles = (SELECT id FROM ea_roles WHERE slug = 'customer') AND id NOT IN (SELECT id_users_customer FROM ea_appointments);" |& tee -a "$LOGFILE" || exit 1
if [ "$TEST_RUN" != "true" ]; then
    MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -u"$DB_USERNAME" "$DB_DATABASE" --table -e \
      "DELETE FROM ea_users WHERE id_roles = (SELECT id FROM ea_roles WHERE slug = 'customer') AND id NOT IN (SELECT id_users_customer FROM ea_appointments);" |& tee -a "$LOGFILE" || exit 1
fi
