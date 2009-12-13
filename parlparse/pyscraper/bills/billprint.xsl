<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

<xsl:param name="base">http://www.publications.parliament.uk</xsl:param>

<xsl:template match="/">

<html>

<head>

</head>

<body>

<table>
<tr><td>Name</td><td>Session</td><td>House</td><td>printing</td></tr>
<xsl:apply-templates select="//print">
	<xsl:sort select="@billname" />
	<xsl:sort select="@session" />
	<xsl:sort select="@house" />
	<xsl:sort select="@billno" data-type="text" order="ascending"/>
</xsl:apply-templates>
</table>

</body>

</html>

</xsl:template>

<xsl:template match="print">
<tr>
<td><a href="{concat($base,@link)}"><xsl:value-of select="@billname" /></a></td>
<td><xsl:value-of select="@session" /></td>
<td><xsl:value-of select="@house" /></td>
<td><xsl:value-of select="@billno" /></td>
</tr>

<tr><td>

<xsl:if test="@explanatory_note">
<ul><a href="{concat($base,@explanatory_note)}">Explanatory Note</a></ul>
</xsl:if>

<xsl:if test="@amendment">
<ul><a href="{concat($base,@amendment)}">Proposed Amendments</a></ul>
</xsl:if>

<xsl:if test="@standing_committee">
<ul><a href="{concat($base,@standing_committee)}">Standing Committee</a></ul>
</xsl:if>

<xsl:if test="@house_committee">
<ul><a href="{concat($base,@house_committee)}">Committee of the Whole House</a></ul>
</xsl:if>

<xsl:if test="report">
<ul><a href="{concat($base,@report)}">Report Stage</a></ul>
</xsl:if>

<xsl:if test="@petitions">
<ul><a href="{concat($base,@petitions)}">Petitions</a></ul>
</xsl:if>




</td></tr>
</xsl:template>

</xsl:stylesheet>