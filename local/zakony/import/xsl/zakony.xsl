<!-- local/zakony/import/xsl/zakony.xsl
     šablona pro transformaci xml dodaného ze zakonyprolidi.cz API
-->

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:php="http://php.net/xsl"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:xlink="http://www.w3.org/2001/XMLSchema-instance">
    <xsl:output method="xml" indent="yes" encoding="utf-8"/>
    <xsl:param name="institution">Zakony pro lidi</xsl:param>
    <xsl:param name="collection">Law</xsl:param>
    <xsl:param name="building">zakony</xsl:param>
    <xsl:template match="DocInfo">
        <add>
            <doc>
                <!-- ID -->
                <field name="id">zakony.<xsl:value-of select="/DocInfo/@DocId"/></field>

                <!-- RECORDTYPE -->
                <field name="recordtype">law</field>

                <!-- FULLRECORD -->
                <field name="fullrecord">
                    <xsl:copy-of select="php:function('VuFind::xmlAsText', /DocInfo)"/>
                </field>

                <!-- ALLFIELDS -->
                <field name="allfields">
                    <xsl:value-of select="normalize-space(string(/DocInfo))"/>
                </field>

                <!-- INSTITUTION -->
                <field name="institution">
                    <xsl:value-of select="$institution" />
                </field>

                <!-- BUILDING -->
                <field name="building"><xsl:value-of select="$building" /></field>

                <!-- COLLECTION -->
                <field name="collection">
                    <xsl:value-of select="$collection" />
                </field>

                <!-- ITEMTYPE -->
                <!-- <field name="itemtype">LG</field>-->

                <!-- LANGUAGE -->
                <field name="language">Czech</field>

                <!-- FORMAT -->
                <field name="format">Legislative document</field>

                <!--  LAW DOCTYPE -->
                <field name="law_doctype">
                    <xsl:value-of select="/DocInfo/@DocType"/>
                </field>

                <!-- AUTHOR -->
                <field name="author">Česká Republika</field>

                <!-- TITLE -->
                <field name="title"><xsl:value-of select="/DocInfo/@Title"/>. <xsl:value-of select="/DocInfo/@Quote"/></field>
                <field name="title_short"><xsl:value-of select="/DocInfo/@Title[normalize-space()]"/>. <xsl:value-of select="/DocInfo/@Quote"/></field>
                <field name="title_full"><xsl:value-of select="/DocInfo/@Title[normalize-space()]"/>. <xsl:value-of select="/DocInfo/@Quote"/></field>
                <field name="title_sort"><xsl:value-of select="php:function('VuFind::stripArticles', string(/DocInfo/@Title[normalize-space()]))"/>. <xsl:value-of select="php:function('VuFind::stripArticles', string(/DocInfo/@Quote[normalize-space()]))"/></field>

                <!-- PUBLISHER -->
                <field name="publisher">Česká Republika</field>

                <!-- PUBLISHDATE -->
                <field name="publishDate">
                    <xsl:value-of select="/DocInfo/@Year"/>
                </field>
                <field name="publishDateSort">
                    <xsl:value-of select="/DocInfo/@Year"/>
                </field>

                <!-- URL -->
                <field name="url">http://www.zakonyprolidi.cz<xsl:value-of select="/DocInfo/@Href[normalize-space()]"/></field>
            </doc>
        </add>
    </xsl:template>
</xsl:stylesheet>
