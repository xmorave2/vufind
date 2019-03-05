#!/bin/bash

export VUFIND_HOME="/usr/local/vufind"
export VUFIND_LOCAL_DIR="/usr/local/vufind/local"

cd $VUFIND_HOME

php public/index.php harvest harvest_oai MK-LETOHRAD >> harvest-mk-letohrad.log 2>&1
harvest/batch-import-marc.sh -r MK-LETOHRAD >> harvest-mk-letohrad.log 2>&1
harvest/batch-delete.sh MK-LETOHRAD

#export VUFIND_LOCAL_DIR="/usr/local/vufind/local/mkp"
#php public/index.php harvest harvest_oai MKP >> harvest-mkp.log 2>&1
#harvest/batch-import-marc.sh -r MKP >> harvest-mkp.log 2>&1
#harvest/batch-delete.sh MKP


