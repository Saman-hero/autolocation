<?php
/**
 * Minimal XLSX reader using ZipArchive + SimpleXML (no external library needed).
 * Returns rows as associative arrays using the first row as header keys.
 */
class XlsxReader
{
    private array $sharedStrings = [];

    public function read(string $filePath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException("L'extension ZipArchive est requise.");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("Impossible d'ouvrir le fichier Excel.");
        }

        // 1. Load shared strings
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $ss = new SimpleXMLElement($ssXml);
            $ss->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($ss->xpath('//x:si') as $si) {
                $t = $si->xpath('.//x:t');
                $val = '';
                foreach ($t as $part) {
                    $val .= (string)$part;
                }
                $this->sharedStrings[] = $val;
            }
        }

        // 2. Load first worksheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheetXml) {
            throw new RuntimeException("Feuille de calcul introuvable dans le fichier.");
        }

        $sheet = new SimpleXMLElement($sheetXml);
        $sheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rawRows = [];
        foreach ($sheet->xpath('//x:row') as $row) {
            $rowData  = [];
            $colIndex = 0;

            foreach ($row->xpath('x:c') as $cell) {
                $ref    = (string)$cell['r'];           // e.g. A1, B2
                $type   = (string)$cell['t'];
                $vNodes = $cell->xpath('x:v');
                $rawVal = $vNodes ? (string)$vNodes[0] : '';

                // Resolve column index from cell ref (handle gaps like A, C — skip B)
                $colLetter = preg_replace('/[0-9]/', '', $ref);
                $targetCol = $this->colLetterToIndex($colLetter);

                // Fill gaps with empty strings
                while ($colIndex < $targetCol) {
                    $rowData[] = '';
                    $colIndex++;
                }

                // Resolve value
                if ($type === 's') {
                    // Shared string
                    $value = $this->sharedStrings[(int)$rawVal] ?? '';
                } elseif ($type === 'b') {
                    $value = $rawVal ? 'TRUE' : 'FALSE';
                } else {
                    $value = $rawVal;
                }

                $rowData[] = $value;
                $colIndex++;
            }

            $rawRows[] = $rowData;
        }

        if (empty($rawRows)) return [];

        // 3. Use first row as headers
        $headers = array_map(fn($h) => strtolower(trim($h)), $rawRows[0]);
        $result  = [];

        for ($i = 1; $i < count($rawRows); $i++) {
            $row = $rawRows[$i];
            // Skip completely empty rows
            if (empty(array_filter($row, fn($v) => $v !== ''))) continue;

            $padded = array_pad($row, count($headers), '');
            $result[] = array_combine($headers, array_slice($padded, 0, count($headers)));
        }

        return $result;
    }

    private function colLetterToIndex(string $col): int
    {
        $col   = strtoupper($col);
        $index = 0;
        for ($i = 0; $i < strlen($col); $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }
}
