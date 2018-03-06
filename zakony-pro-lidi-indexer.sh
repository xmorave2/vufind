#!/bin/bash
#
# dependencies: xml-twig-tools, wget

apikey=test
collection=cs
export VUFIND_HOME="/usr/local/vufind"
export VUFIND_LOCAL_DIR="/usr/local/vufind/local/zakony"

cd /usr/local/vufind/local/zakony/harvest
if [ ! -d zakony ]; then
    mkdir zakony
fi

#výchozí začátek je rok 1945, pokud už se stahovalo, naváže posledním staženým rokem...
start=1945
if [ -f zakony-date ]; then
    start=$(date -r zakony-date +'%Y')
fi
end=$(date +'%Y')

# časová značka posledního stahování
touch zakony-date
cd zakony
rm *.xml

for (( i=$start; i<=$end; i++ )); do
    wget "https://www.zakonyprolidi.cz/api/v1/data.xml/YearDocList?apikey=$apikey&Collection=$collection&Year=$i" -O "zakony$i.xml"
    echo "Rozděluji soubor pro rok $i"
    xml_split -l 2 zakony$i.xml
    rm "zakony$i.xml"
    rm "zakony$i-00.xml"
done

echo "Mažu zrušené zákony"
for xmlfile in $(ls); do
    zruseno=$(tail -n 1 $xmlfile | sed 's/.*\(EffectTill\).*/1/')
    if [ "$zruseno" = "1" ] ; then
        rm $xmlfile
    fi;
done;

cd $VUFIND_HOME
harvest/batch-import-xsl.sh zakony zakony.properties

