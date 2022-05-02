<?php

namespace EvanPiAlert\Util;

/**
 * Почти "Excel" файл
 */
class ExcelFile {

    protected string $fileName;
    protected string $fileContent = "";

    public function __construct(string $fileName) {
        $this->fileName = $fileName;
    }

    protected function addHTTPHeaders() : void {
        header("Pragma: public");
        header("Expires: 0");
        header("Accept-Ranges: bytes");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=".$this->fileName.".xls");
        header("Content-Transfer-Encoding: binary");
    }

    public function addCell(?string $text, int $cols = 1, int $rows = 1, bool $header = false) : void {
        $this->fileContent .= "<td";
        if ( $header ) {
            $this->fileContent .= " align='center'";
            $text = "<b>".$text."</b>";
        }
        if ($cols > 1) $this->fileContent .= " colspan='".$cols."'";
        if ($rows > 1) $this->fileContent .= " rowspan='".$rows."'";
        $this->fileContent .= ">".$text."</td>";
    }

    public function newLine() : void {
        $this->fileContent .= "</tr><tr>";
    }

    public function serializeFileForUser() : void {
        $this->addHTTPHeaders();
        /** @noinspection HtmlDeprecatedAttribute
         *  @noinspection HtmlRequiredTitleElement
         */
        echo "<head>
                    <meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">
                </head>
                <table border='1'><tr>".
                $this->fileContent.
                "</tr></table>";
    }
}