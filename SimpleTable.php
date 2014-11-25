<?php
// Copyright (C) 2014 Ryan Mitchener
// Free under the terms of MIT license:

// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.


// Converts a table of short tags into a full HTML table
Class SimpleTable {
    const START_TABLE = "/\[table(\s([^\]]*))?\]/i";
    const END_TABLE = "[/table]";
    const ATTRIBUTE_TH = "/th=['\"]([0-1])['\"]/i";
    const ATTRIBUTE_TF = "/tf=['\"]([0-1])['\"]/i";
    const ATTRIBUTE_CD = "/cd=['\"]([^'\"]*)['\"]/i";
    const ATTRIBUTE_RD = "/rd=['\"]([^'\"]*)['\"]/i";
    const ATTRIBUTE_CLASS = "/class=['\"]([^'\"]*)['\"]/i";
    const ATTRIBUTE_COLSPAN = "/colspan=['\"]([0-9]+)['\"]/i";
    const ATTRIBUTE_ROWSPAN = "/rowspan=['\"]([0-9]+)['\"]/i";
    const COL_ATTRIBUTES = "/\[attr\s([^\]]*)\]/i";
    private $string;
    private $table_pos_start;

    // User Customizable settings
    private $COL_DELIMITER = "|";
    private $ROW_DELIMITER = ";;";
    private $NEW_LINE = "~~";

    // Constructor
    public function __construct($string, $new_line, $row_delimiter, $col_delimiter) {
        $this->NEW_LINE = (isset($new_line)) ? $new_line : $this->NEW_LINE;
        $this->ROW_DELIMITER = (isset($row_delimiter)) ? $row_delimiter : $this->ROW_DELIMITER;
        $this->COL_DELIMITER = (isset($col_delimiter)) ? $col_delimiter : $this->COL_DELIMITER;
        $this->string = $string;

        // Start table search        
        if (preg_match(self::START_TABLE, $this->string, $match, PREG_OFFSET_CAPTURE)) {
            $this->table_pos_start = $match[0][1];
            while ($this->table_pos_start !== false) {
                $this->replaceTable();
            }
        }
    }

    // Gets the content
    public function get() {
        return $this->string;
    }

    // Replaces a table
    private function replaceTable() {
        // Set up table variables
        $useHeader = false;
        $useFooter = false;
        $row_delimiter = $this->ROW_DELIMITER;
        $col_delimiter = $this->COL_DELIMITER;

        // Get end position of table, get the table substring, replace string with nothing
        $table_pos_end = strpos($this->string, self::END_TABLE, $this->table_pos_start + 1) + strlen(self::END_TABLE);
        $table = substr($this->string, $this->table_pos_start, $table_pos_end - $this->table_pos_start);        
        $this->string = substr_replace($this->string, "", $this->table_pos_start, $table_pos_end - $this->table_pos_start);

        // Replace WordPress's texturized special characters (quotes, apostrophes, primes, .etc)
        $table = str_replace(array("&#8216;","&#8217;","&#8220;","&#8221;", "&#8211;", "&#8242;", "&#8243"), array("'", "'", "\"", "\"", "-", "'", "\""), $table);

        // Get attributes on table tag
        $attributes = (preg_match(self::START_TABLE, $table, $match)) ? $match[1] : null;
        $class = "";
        if ($attributes != null) {
            $useHeader = (preg_match(self::ATTRIBUTE_TH, $attributes, $match) && $match[1] == 1) ? true : false;
            $useFooter = (preg_match(self::ATTRIBUTE_TF, $attributes, $match) && $match[1] == 1) ? true : false;
            $row_delimiter = (preg_match(self::ATTRIBUTE_RD, $attributes, $match)) ? $match[1] : $row_delimiter;
            $col_delimiter = (preg_match(self::ATTRIBUTE_CD, $attributes, $match)) ? $match[1] : $col_delimiter;
            $class = (preg_match(self::ATTRIBUTE_CLASS, $attributes, $match)) ? " ".$match[1] : $class;
            $patterns = array(self::ATTRIBUTE_TH, self::ATTRIBUTE_TF, self::ATTRIBUTE_RD, self::ATTRIBUTE_CD);
            $attributes = preg_replace($patterns, "", $attributes);
        }

        // Replace table shortcodes with HTML table
        $table = preg_replace(array(self::START_TABLE, "/<br\s?\/?>/", "/<\/?p>/"), "", $table);
        $table = str_replace(array(self::END_TABLE, $this->NEW_LINE), array("", "<br>"), $table);

        // Make table
        $newTable = "<table class='simple-table".$class."' ".$attributes.">";
        $rows = explode($row_delimiter, $table);
        $rowSpan = array();
        $rowPos = -1;

        // Loop through rows
        foreach ($rows as $row) {
            $rowPos++;
            $row = trim($row);
            if ($row === "") {
                continue;
            }

            // Create thead, tbody, or tfoot
            if ($rowPos == 0 && $useHeader == true) {
                $newTable .= "<thead>";
            } else if ($rowPos == 0 && $useHeader == false && $useFooter == false) {
                $newTable .= "<tbody>";
            } else if ($rowPos == 0 && $useHeader == false && $useFooter == true) {
                $newTable .= "<tfoot>";
            } else if ($rowPos == 1 && $useHeader == true && $useFooter == true) {
                $newTable .= "</thead><tfoot>";
            } else if ($rowPos == 1 && $useHeader == true && $useFooter == false) {
                $newTable .= "</thead><tbody>";
            } else if ($rowPos == 1 && $useHeader == false && $useFooter == true) {
                $newTable .= "</tfoot><tbody>";
            } else if ($rowPos == 2 && $useHeader == true && $useFooter == true) {
                $newTable .= "</tfoot><tbody>";
            }

            $newTable .= "<tr>";
            $cols = explode($col_delimiter, $row);
            $colSpan = 0;
            $colPos = -1;

            // Loop through columns
            foreach ($cols as $col) {
                $colPos++;
                if (isset($rowSpan[$rowPos][$colPos]) || $colSpan > 1) {
                    $colSpan--;
                    continue;
                }
                $col = trim($col);

                // Get column attributes
                $colAttributes = "";
                if (preg_match(self::COL_ATTRIBUTES, $col, $match)) {
                    $col = preg_replace(self::COL_ATTRIBUTES, "", $col);
                    $colAttributes = $match[1];

                    // Get colspan attribute
                    if (preg_match(self::ATTRIBUTE_COLSPAN, $colAttributes, $match)) {
                        $colSpan = $match[1];
                    }
                    // Get rowspan attribute
                    if (preg_match(self::ATTRIBUTE_ROWSPAN, $colAttributes, $match)) {
                        // Rowspans with colspans
                        for ($i = $rowPos; $i < ($rowPos + $match[1]); $i++) {
                            for ($j = 1; $j < $colSpan; $j++) {
                                $rowSpan[$i][] = $colPos + $j;
                            }
                            $rowSpan[$i][] = $colPos;
                        }
                    }
                }

                if ($rowPos == 0 && $useHeader == true) {
                    $newTable .= "<th ".$colAttributes.">".$col."</th>";
                } else {
                    $newTable .= "<td ".$colAttributes.">".$col."</td>";
                }
            }
            $newTable .= "</tr>";            
        }
        $newTable .= "</table>";

        // Insert new table into original string   
        $this->string = substr_replace($this->string, $newTable, $this->table_pos_start, 0);
        
        // Update position to look for the next table
        if (preg_match(self::START_TABLE, $this->string, $match, PREG_OFFSET_CAPTURE, $this->table_pos_start + 1)) {
            $this->table_pos_start = $match[0][1];
        } else {
            $this->table_pos_start = false;
        }
    }
}
?>