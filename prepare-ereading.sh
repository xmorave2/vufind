#!/bin/bash

# Copyright 2015-2016 Josef Moravec

cd data

mkdir ereading

rm xml_rent.php
wget "http://www.ereading.cz/xml/xml_rent.php"

rm ereading/*.xml
mv xml_rent.php ereading/ereading.xml

cd ereading

echo "Rozděluji soubory..."
xml_split ereading.xml
rm ereading.xml
rm ereading-00.xml

echo "Přejmenovávám a odstraňuji nepotřebné soubory..."
for xmlfile in $(ls *.xml)
do
        vypujcka=$(tail -n 1 $xmlfile | sed 's/.*<VYPUJCKA>\([0-9]*\).*/\1/')
        if [ "$vypujcka" = "0" ] ; then
                rm $xmlfile
        else
                filename=$(tail -n 1 $xmlfile | sed 's/.*<EAN>\(.*\)<\/EAN>.*/\1/' | sed 's/[^0-9]*//g')
                if [ "$filename" = "" ] ; then
                        filename=$(tail -n 1 $xmlfile | sed 's/.*<ISBN_TISTENE>\(.*\)<\/ISBN_TISTENE>.*/\1/' | sed 's/[^0-9]*//g')
                fi;

                if [ "$filename" != "" ] ; then
                        mv $xmlfile $filename.xml
                else
                        rm $xmlfile
                fi;
        fi;
done;

cd ../..

