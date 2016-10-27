#!/bin/bash

# Copyright 2015-2016 Josef Moravec

cd data/ereading
rm nkc.mrc

echo "open aleph.nkp.cz:9991" > commands.yaz
echo "base SKC-UTF" >> commands.yaz
echo "set_marcdump nkc.mrc" >> commands.yaz

echo "Zjistuji jiz importovane knihy..."
for xmlfile in $(ls *.xml)
do
        isbn="${xmlfile%.*}"
        solr=$(curl "http://localhost:8080/solr/biblio/select?q=isbn:$isbn&rows=0&indent=true" | grep "numFound")

        pocet=$(echo $solr | sed 's/.*numFound="\([0-9]*\)".*/\1/')
        if [ $pocet = "0" ] ; then
                # nenalezeno v indexu, pridano ke stazeni
                echo "find @attr 1=7 \"$isbn\"" >> commands.yaz
                echo "show" >> commands.yaz
        fi
done

echo "quit" >> commands.yaz

echo "Stahuji zaznamy z Narodni knihovny..."
yaz-client -f commands.yaz

cd ../..

"Importuji zaznamy..."
./import-marc.sh -p ./local/import/ereading.properties data/ereading/nkc.mrc

