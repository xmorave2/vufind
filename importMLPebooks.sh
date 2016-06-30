#!/bin/bash

# depends on xml_split - debian package xml-twig-tools

cd /usr/local/vufind
rm -r data/MLPebooks
mkdir data/MLPebooks
cd data/MLPebooks

wget "http://search.mlp.cz/?action=qc&espQCId=E-K4JIB" --no-check-certificate -O "MLPebooks.xml"

echo "Splitting files"
xml_split -l 3 MLPebooks.xml
rm MLPebooks-00.xml
rm MLPebooks.xml

echo "Renaming files"
for xmlfile in $(ls *.xml)
do
    filename=$(xml_grep --cond "/Titul/KEY" --text_only "$xmlfile")
    if [ "$filename" != "" ] ; then
        mv $xmlfile $filename.xml
        echo "$xmlfile -> $filename.xml"
    else
        rm $xmlfile
    fi;
done;

cd ../../harvest

php harvest_oai.php MLPebooks
./batch-import-marc.sh -p /usr/local/vufind/local/import/importMLPebooks.properties MLPebooks

cd ..

