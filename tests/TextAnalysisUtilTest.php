<?php

namespace EvanPiAlert\Test;

use EvanPiAlert\Util\TextAnalysisUtil;
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . "/../src/autoload.php");

class TextAnalysisUtilTest extends TestCase {

    public function testIsTextFitToMask() {
        $mask = 'проверочный текст со * и точкой. ага$ угу';
        $this->assertFalse(TextAnalysisUtil::isTextFitToMask('проверочный текст со * и точкой) ага$ угу', $mask), 'Сломалось экранирование регулярных символов 1');
        $this->assertFalse(TextAnalysisUtil::isTextFitToMask('проверочный текст со * и точкой. ага', $mask), 'Сломалось экранирование регулярных символов 2');
        $this->assertTrue(TextAnalysisUtil::isTextFitToMask('проверочный текст со звездочкой и точкой. ага$ угу', $mask), 'Сломалась поддержка *');
        $this->assertFalse(TextAnalysisUtil::isTextFitToMask('проверочный текст со звездочкой и точкой.', $mask), 'Строки проверяются не целиком');
        $this->assertTrue(TextAnalysisUtil::isTextFitToMask('проверочный текст со звездочкой
             и точкой. ага$ угу', $mask), 'Сломался перенос строк');
        $mask = 'проверочный текст, в котором ? вопрос';
        $this->assertTrue(TextAnalysisUtil::isTextFitToMask('проверочный текст, в котором А вопрос', $mask), 'Сломалась поддержка ?');
    }

    public function testGetMainPartOfPiErrorText() {
        $a = "Error occurred while sending query result: 'com.sap.aii.af.lib.util.configuration.ConfigurationException: Sender Agreement lookup failed: com.sap.aii.af.service.administration.api.cpa.CPAChannelStoppedException: Channel stopped by administrative task. Channel Name: ELT_Price_JDBC_Sender'";
        $b = "Channel stopped by administrative task";
        $this->assertEquals(TextAnalysisUtil::getMainPartOfPiErrorText($a), $b, 'Некорректно извлекли основную часть ошибки');

        $a = "java.sql.SQLException: 2092.0";
        $b = "java.sql.SQLException: 2092.0";
        $this->assertEquals(TextAnalysisUtil::getMainPartOfPiErrorText($a), $b, 'Некорректно извлекли основную часть ошибки');
        
        $a = "Failed to put message into DB store. Reason: com.sap.engine.services.ts.transaction.TxRollbackException: Current transaction is marked for rollback";
        $b = "Current transaction is marked for rollback";
        $this->assertEquals(TextAnalysisUtil::getMainPartOfPiErrorText($a), $b, 'Некорректно извлекли основную часть ошибки');
    }
    
    public function testIsSimilarText() {
        $a = "Runtime Exception when executing application mapping program com/sap/xi/tf/_Invoice_to_InvoicePackage_; Details: com.sap.aii.mapping.tool.tf7.IllegalInstanceException; Cannot create target element /ns0:InvoicePackage/InvoiceHeader/. Values missing in queue context. Target XSD requires a value for this element, but the target-field mapping does not create one. Check whether the XML instance is valid for the source XSD, and whether the target-field mapping fulfils the requirement of the target XSD";
        $this->assertTrue(TextAnalysisUtil::isSimilarText($a, $a), 'Идентичные строки считаются разными');

        $a = "Runtime Exception when executing application mapping program com/sap/xi/tf/_Offer_to_Offer_; Details: com.sap.aii.mappingtool.tf7.MessageMappingException; Runtime exception when processing target-field mapping /ns1:Offer/access[1]/cont_start_date; root message: Unparseable date: ''";
        $b = "Runtime Exception when executing application mapping program com/sap/xi/tf/_Offer_to_Offer_; Details: com.sap.aii.mappingtool.tf7.MessageMappingException; Runtime exception when processing target-field mapping /ns1:Offer/access[20]/cont_start_date; root message: Unparseable date: ''";
        $this->assertTrue(TextAnalysisUtil::isSimilarText($a, $b), 'Похожий xpath попал признан разным');

        $a = "Runtime Exception when executing application mapping program com/sap/xi/tf/_ORDERS_SAP_to_CISLink_; Details: com.sap.aii.mappingtool.tf7.IllegalInstanceException; Cannot create target element /ns0:ORDERS96A/M_ORDERS/G_SG2[3]/S_NAD/C_C058/D_3124_1. Values missing in queue context. Target XSD requires a value for this element, but the target-field mapping does not create one. Check whether the XML instance is valid for the source XSD, and whether the target-field mapping fulfils the requirement of the target XSD";
        $b = "Runtime Exception when executing application mapping program com/sap/xi/tf/_ORDERS_SAP_to_CISLink_; Details: com.sap.aii.mappingtool.tf7.IllegalInstanceException; Cannot create target element /ns0:ORDERS96A/M_ORDERS/G_SG2[3]/S_NAD/C_C059/D_3042_1. Values missing in queue context. Target XSD requires a value for this element, but the target-field mapping does not create one. Check whether the XML instance is valid for the source XSD, and whether the target-field mapping fulfils the requirement of the target XSD";
        $this->assertFalse(TextAnalysisUtil::isSimilarText($a, $b), 'Ошибки в разных полях маппинга склеились');
        /*
         * Ранее было неточное сравнение текстов, но от него отказались
        $b = $a.'12345';
        $this->assertTrue(TextAnalysisUtil::isSimilarText($a, $b), 'Длинные похожие строки оказались разными');

        $b = $a.$a;
        $this->assertFalse(TextAnalysisUtil::isSimilarText($a, $b), 'Длинные разные строки оказались одинаковым');

        $a = '0123456789';
        $b = '01234567891';
        $this->assertFalse(TextAnalysisUtil::isSimilarText($a, $b), 'Короткие разные строки оказались одинаковым');

        $a = 'java.io.IOException: Communication over HTTP. Unable to create a socket12';
        $b = 'java.io.IOException: Communication over HTTP. Unable to create a socket34';
        $this->assertTrue(TextAnalysisUtil::isSimilarText($a, $b), 'Средние похожие строки оказались разными');

        $a = "com.mysql.cj.jdbc.exceptions.MySQLTransactionRollbackException: Lock wait timeout exceeded; try restarting transaction";
        $b = "com.mysql.cj.jdbc.exceptions.MySQLTransactionRollbackException: Deadlock found when trying to get lock; try restarting transaction";
        $this->assertFalse(TextAnalysisUtil::isSimilarText($a, $b), 'Средние разные строки показаны одинаковыми 1');
        */
    }

    public function testGetMaskFromTexts() {
        $a = "java.sql.SQLException: Could not position within a table (tecsys.xi_imk_stock_event_journal).";
        $b = "java.sql.SQLException: Routine (xi_2_sap_send_delivery_return) can not be resolved.";
        $this->assertNull(TextAnalysisUtil::getMaskFromTexts($a, $b), 'На разные тексты удалось создать маску');

        $a = "Could not process file 'ee2f2d3c-38fa-4e07-a087-e795477c78e9.sbis.xml': File modified during processing. 0 bytes expected, 899 bytes found";
        $b = "Could not process file 'nn111111-n2nn-2222-nn4n-24141414141f.sbis.xml': File modified during processing. 0 bytes expected, 1546 bytes found";
        $mask = TextAnalysisUtil::getMaskFromTexts($a, $b);
        $this->assertNotNull($mask, 'Не удалось создать маску');
        $this->assertTrue(TextAnalysisUtil::isTextFitToMask($a, $mask), 'Маска не подходит под первый текст');
        $this->assertTrue(TextAnalysisUtil::isTextFitToMask($b, $mask), 'Маска не подходит под второй текст');

        $a = "Could not process file '33aa3aa-bbb2-8888-bbb3-ll3ll2ll3ll3.sbis.xml': File modified during processing. 0 bytes expected, 2000 bytes found";
        $mask2 = TextAnalysisUtil::getMaskFromTexts($a, $mask);
        $this->assertNotNull($mask, 'Не удалось обогатить маску');
        $this->assertTrue(TextAnalysisUtil::isTextFitToMask($a, $mask2), 'Новая маска не подходит под первый текст');
        $this->assertTrue(TextAnalysisUtil::isTextFitToMask($mask, $mask2), 'Новая маска не подходит под старую маску');

        $a = "java.sql.SQLException: Could not position within a file via an index.";
        $b = "java.sql.SQLException: Could not position within a table (tecsys.xi_imk_event_journal).";
        $mask = TextAnalysisUtil::getMaskFromTexts($a, $b);
        $this->assertNotNull($mask, 'Не удалось создать маску');
        $this->assertTrue(TextAnalysisUtil::isTextFitToMask($a, $mask), 'Маска не подходит под первый текст');
        $this->assertTrue(TextAnalysisUtil::isTextFitToMask($b, $mask), 'Маска не подходит под второй текст');

        $a = "com.sap.aii.utilxi.xmlvalidation.impl.XMLValidationException: XML Validation for payload with root element name Out_ItemSets_A , target namespace http://komus.ru/I/Elite/MasterData/Hybris Failed!Errors Encountered During Parsing 
1.cvc-maxLength-valid: Value 'Тестовая-коллекция--на-восемьдесят-символов-НЕ-ИСПОЛЬЗОВАТЬ-проверка-дополняем-1' with length = '80' is not facet-valid with respect to maxLength '60' for type '#AnonType_enr_desci_M23_ProdCollection'.
2.cvc-attribute.3: The value 'Тестовая-коллекция--на-восемьдесят-символов-НЕ-ИСПОЛЬЗОВАТЬ-проверка-дополняем-1' of attribute 'enr_desc' on element 'collection' is not valid with respect to its type, '#AnonType_enr_desci_M23_ProdCollection'.";
        $b = "com.sap.aii.utilxi.xmlvalidation.impl.XMLValidationException: XML Validation for payload with root element name Out_ItemSets_A , target namespace http://komus.ru/I/Elite/MasterData/Hybris Failed!Errors Encountered During Parsing 
1.cvc-maxLength-valid: Value 'Тестовая-коллекция-на-восемьдесят-символов-НЕ-ИСПОЛЬЗОВАТЬ-проверка-дополняем-до' with length = '80' is not facet-valid with respect to maxLength '60' for type '#AnonType_enr_desci_M23_ProdCollection'.
2.cvc-attribute.3: The value 'Тестовая-коллекция-на-восемьдесят-символов-НЕ-ИСПОЛЬЗОВАТЬ-проверка-дополняем-до' of attribute 'enr_desc' on element 'collection' is not valid with respect to its type, '#AnonType_enr_desci_M23_ProdCollection'.
3.cvc-maxLength-valid: Value 'Тестовая-коллекция--на-восемьдесят-символов-НЕ-ИСПОЛЬЗОВАТЬ-проверка-дополняем-1' with length = '80' is not facet-valid with respect to maxLength '60' for type '#AnonType_enr_desci_M23_ProdCollection'.
4.cvc-attribute.3: The value 'Тестовая-коллекция--на-восемьдесят-символов-НЕ-ИСПОЛЬЗОВАТЬ-проверка-дополняем-1' of attribute 'enr_desc' on element 'collection' is not valid with respect to its type, '#AnonType_enr_desci_M23_ProdCollection'.";
        $mask = TextAnalysisUtil::getMaskFromTexts($a, $b);
        $this->assertNotNull($mask, 'Не удалось создать маску');
        $this->assertTrue(TextAnalysisUtil::isTextFitToMask($a, $mask), 'Маска не подходит под первый текст');
        $this->assertTrue(TextAnalysisUtil::isTextFitToMask($b, $mask), 'Маска не подходит под второй текст');
    }
}