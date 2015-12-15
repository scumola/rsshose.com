<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:atom="http://purl.org/atom/ns#"
	xmlns:xhtml="http://www.w3.org/1999/xhtml"
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns:dc="http://purl.org/dc/elements/1.1/" 
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns="http://purl.org/rss/1.0/">

<xml:output mode="xml" encoding="UTF-8"/>

<xsl:template match="//atom:feed">
	<rdf:RDF>
	<channel rdf:about="{atom:link[@rel='alternate']/@href}">
		<title><xsl:value-of select="atom:title"/></title>
		<description><xsl:value-of select="atom:tagline"/></description>
		<link><xsl:value-of select="atom:link[@rel='alternate']/@href"/></link>
		<items>
			<xsl:call-template name="itemList"/>
		</items>
	</channel>
	
	<xsl:call-template name="items"/>
	</rdf:RDF>
</xsl:template>

<xsl:template name="itemList">
	<rdf:Seq>
		<xsl:for-each select="atom:entry">
			<rdf:li resource="{atom:link[@rel='alternate']/@href}"/>
		</xsl:for-each>
	</rdf:Seq>
</xsl:template>

<xsl:template name="items">
	<xsl:for-each select="atom:entry">
		<item rdf:about="{atom:link[@rel='alternate']/@href}">
			<title>
				<xsl:value-of select="atom:title"/>
			</title>
			<link><xsl:value-of select="atom:link[@rel='alternate']/@href"/></link>
			<content:encoded>
				<xsl:value-of select="atom:content"/>
			</content:encoded>
			<dc:date>
				<xsl:value-of select="atom:issued"/>
			</dc:date>
		</item>
	</xsl:for-each>
</xsl:template>

</xsl:stylesheet>
