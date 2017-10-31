#!/bin/bash

export VUFIND_HOME="/usr/local/vufind"
export VUFIND_LOCAL_DIR="/usr/local/vufind/local"

cd $VUFIND_HOME

php public/index.php harvest harvest_oai >> harvest.log 2>&1

harvest/batch-import-marc.sh MK-CHOCEN >> harvest.log 2>&1
