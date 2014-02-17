<?php

/**
 * XML template for excel file format
 *
 * @since 0.7.7
 **/

$xml_doc_begin  =
'<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook
   xmlns="urn:schemas-microsoft-com:office:spreadsheet"
   xmlns:o="urn:schemas-microsoft-com:office:office"
   xmlns:x="urn:schemas-microsoft-com:office:excel"
   xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
   xmlns:html="http://www.w3.org/TR/REC-html40">
  <Worksheet ss:Name="Exported Users">
    <Table>';

$xml_pre = '
      <Row>
        <Cell>
          <Data ss:Type="String">';

$xml_seperator = '</Data>
        </Cell>
        <Cell>
          <Data ss:Type="String">';

$xml_breaker = '</Data>
        </Cell>
      </Row>';

$xml_doc_end = '
    </Table>
  </Worksheet>
</Workbook>';