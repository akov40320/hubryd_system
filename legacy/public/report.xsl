<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:output method="html" encoding="UTF-8" />

  <xsl:template match="/">
    <html lang="ru">
      <head>
        <meta charset="UTF-8" />
        <title>Overdue report</title>
        <style>
          body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; margin: 24px; }
          table { width: 100%; border-collapse: collapse; }
          th, td { border: 1px solid #ddd; padding: 10px; }
          th { background: #f4f4f4; text-align: left; }
          .muted { color: #666; }
          .badge { display:inline-block; padding: 2px 8px; border-radius: 999px; background:#fff3cd; border:1px solid #ffeeba; }
        </style>
      </head>
      <body>
        <h1>Просроченные книги</h1>
        <p class="muted">Демонстрация XML → XSLT → HTML. Этот же XML можно забрать "сырцом" через <code>?raw=1</code>.</p>

        <table>
          <thead>
            <tr>
              <th>Инв. №</th>
              <th>Название</th>
              <th>Автор</th>
              <th>Чит. билет</th>
              <th>Дата выдачи</th>
              <th>Дней на руках</th>
            </tr>
          </thead>
          <tbody>
            <xsl:for-each select="OverdueReport/items/item">
              <tr>
                <td><xsl:value-of select="inventory_number"/></td>
                <td><xsl:value-of select="title"/></td>
                <td><xsl:value-of select="author"/></td>
                <td><xsl:value-of select="reader_card"/></td>
                <td><xsl:value-of select="date_taken"/></td>
                <td><span class="badge"><xsl:value-of select="days_overdue"/></span></td>
              </tr>
            </xsl:for-each>
          </tbody>
        </table>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
