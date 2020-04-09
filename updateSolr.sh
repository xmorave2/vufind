#!/bin/bash

export VUFIND_HOME="/usr/local/vufind"
export VUFIND_LOCAL_DIR="/usr/local/vufind/local"

cd $VUFIND_HOME

php public/index.php harvest harvest_oai MK-CHOCEN >> harvest-mk-chocen.log 2>&1
harvest/batch-import-marc.sh -r MK-CHOCEN >> harvest-mk-chocen.log 2>&1
harvest/batch-delete.sh MK-CHOCEN

export VUFIND_LOCAL_DIR="/usr/local/vufind/local/chocen-region"

php public/index.php harvest harvest_oai REGION-CHOCEN >> harvest-region-chocen.log 2>&1
harvest/batch-import-marc.sh -r REGION-CHOCEN >> harvest-region-chocen.log 2>&1
harvest/batch-delete.sh REGION-CHOCEN

export VUFIND_LOCAL_DIR="/usr/local/vufind/local/letohrad-region"

php public/index.php harvest harvest_oai REGION-LETOHRAD >> harvest-region-letohrad.log 2>&1
harvest/batch-import-marc.sh -r REGION-LETOHRAD >> harvest-region-letohrad.log 2>&1
harvest/batch-delete.sh REGION-LETOHRAD

export VUFIND_LOCAL_DIR="/usr/local/vufind/local/kraliky-region"
php public/index.php harvest harvest_oai REGION-KRALIKY >> harvest-region-kraliky.log 2>&1
harvest/batch-import-marc.sh -r REGION-KRALIKY >> harvest-region-kraliky.log 2>&1
harvest/batch-delete.sh REGION-KRALIKY

export VUFIND_LOCAL_DIR="/usr/local/vufind/local/brandys-region"
php public/index.php harvest harvest_oai REGION-BRANDYS >> harvest-region-brandys.log 2>&1
harvest/batch-import-marc.sh -r REGION-BRANDYS >> harvest-region-brandys.log 2>&1
harvest/batch-delete.sh REGION-BRANDYS

export VUFIND_LOCAL_DIR="/usr/local/vufind/local/vmyto-region"
php public/index.php harvest harvest_oai REGION-VMYTO >> harvest-region-vmyto.log 2>&1
harvest/batch-import-marc.sh -r REGION-VMYTO >> harvest-region-vmyto.log 2>&1
harvest/batch-delete.sh REGION-VMYTO

export VUFIND_LOCAL_DIR="/usr/local/vufind/local/mkp"
php public/index.php harvest harvest_oai MKP >> harvest-mkp.log 2>&1
harvest/batch-import-marc.sh -r MKP >> harvest-mkp.log 2>&1
harvest/batch-delete.sh MKP


