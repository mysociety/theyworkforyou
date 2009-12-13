<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="/publicwhip">

<!-- xmlns:fn="http://www.w3.org/2005/xpath-functions" -->

<!-- This is cribbed from a They Work For You page... -->

<html>

<head>
<title></title>
  <meta name="description" content="Making parliament easy."/>
  <link rel="author" title="Send feedback" href="mailto:beta@theyworkforyou.com"/>
  <link rel="home" title="Home" href="http://www.theyworkforyou.com/"/>
  <link rel="start" title="Home" href="http://www.theyworkforyou.com/"/>        
  <link rel="stylesheet" href="global.css" type="text/css"/>
  <link rel="stylesheet" href="global_non_ns4.css" type="text/css"/>
</head>

<body>
 <div id="container">
  <div id="content">
    <a name="top"></a>

    <xsl:for-each select=".">
        <xsl:apply-templates select="*">
        </xsl:apply-templates>
    </xsl:for-each>

  </div>
 </div>
</body>

</html>

</xsl:template>

<xsl:template match="speech">
  <div>
        <xsl:choose>
          <xsl:when test="position() mod 2 = 1">
              <xsl:attribute name="class">stripe-1</xsl:attribute>
          </xsl:when>
          <xsl:otherwise>
              <xsl:attribute name="class">stripe-2</xsl:attribute>
          </xsl:otherwise>
        </xsl:choose>
    <div class="main">
      <p class="speaker">
        <a href="">
          <xsl:if test="@speakerid">
            <img>
              <xsl:attribute name="src">
                <!-- <xsl:value-of select="fn:replace(@speakerid,'publicwhip','')" /> -->
                <xsl:value-of select="@speakerid" />
                </xsl:attribute>
            </img>
          </xsl:if>
          <strong>
            <xsl:value-of select="@speakername">
            </xsl:value-of>
          </strong>
        </a>
      </p>
      <xsl:copy-of select="./*">
      </xsl:copy-of>
    </div>
    <div class="sidebar">
      <p class="comment-teaser"><a href="" title="Comment on this">
        <strong>Add your comment</strong>
      </a></p>
    </div> <!-- end .sidebar -->
    <div class="break"></div>
  </div>
</xsl:template>

<xsl:template match="major-heading">
  <div>
        <xsl:choose>
          <xsl:when test="position() mod 2 = 1">
              <xsl:attribute name="class">strip-head-1</xsl:attribute>
          </xsl:when>
          <xsl:otherwise>
              <xsl:attribute name="class">strip-head-2</xsl:attribute>
          </xsl:otherwise>
        </xsl:choose>
    <div class="main">
      <h2>
        <xsl:value-of select=".">
        </xsl:value-of>
      </h2>
    </div>
  </div>
  <div class="sidebar">
  </div> <!-- end .sidebar -->
  <div class="break"></div>
</xsl:template>

<xsl:template match="minor-heading">
  <div>
        <xsl:choose>
          <xsl:when test="position() mod 2 = 1">
              <xsl:attribute name="class">stripe-head-1</xsl:attribute>
          </xsl:when>
          <xsl:otherwise>
              <xsl:attribute name="class">stripe-head-2</xsl:attribute>
          </xsl:otherwise>
        </xsl:choose>
  <div class="main">
    <h3>
      <xsl:value-of select=".">
      </xsl:value-of>
    </h3>
  </div>
  </div>
  <div class="break"></div>
  <div class="sidebar">
  </div> <!-- end .sidebar -->
</xsl:template>

<xsl:template match="division">
  <div class="division">
  <div class="main">
      <h4>Division Results</h4>
      <table style="padding: 10px">
      <tr><td align="right">votes FOR:</td><td><b><xsl:value-of select="divisioncount/@for"></xsl:value-of></b></td></tr>
      <tr><td align="right">votes AGAINST:</td><td><b><xsl:value-of select="divisioncount/@against"></xsl:value-of></b></td></tr>
      <tr><td align="right">ABSTENTIONS:</td><td><b><xsl:value-of select="divisioncount/@abstentions"></xsl:value-of></b></td></tr>
      <tr><td align="right">SPOILED votes:</td><td><b><xsl:value-of select="divisioncount/@spoiledvotes"></xsl:value-of></b></td></tr>
      </table>
  </div>
  </div>
  <div class="sidebar">
  </div> <!-- end .sidebar -->
  <div class="break"></div>
</xsl:template>


</xsl:stylesheet>
