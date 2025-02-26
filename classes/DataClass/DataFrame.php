<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass;

class Cell {
    public string $key;
    public $value;

    public function getValue() {
        return $this->value;
    }
}

class Row {
    /** @var Cell[] $cells */
    public array $cells = [];

    public function __construct(array $data) {
        foreach ($data as $key => $value) {
            $cell = new Cell();
            $cell->key = (string) $key;
            $cell->value = $value;
            $this->cells[$key] = $cell;
        }
    }

    public function get(string $column) {
        $cell = $this->cells[$column] ?? null;
        return $cell ? $cell->getValue() : null;
    }

    public function toArray(): array {
        $array = [];
        foreach ($this->cells as $cell) {
            $array[$cell->key] = $cell->value;
        }
        return $array;
    }
}

class DataFrame {
    /**
     * @var Row[] $rows An array of Row objects
     * @var string[] $columns An array of strings representing column names
     */
    public array $rows = [];
    public array $columns = [];

    public function __construct(array $data) {
        /**
         * Accepts an array of associative arrays, where each associative array represents a row in the DataFrame.
         * From this extract the column names and create a Row object for each row.
         */
        foreach ($data as $rowData) {
            // update the columns with any new keys in each row
            $this->columns = array_unique(array_merge($this->columns, array_keys($rowData)));
            // make sure all columns are strings rather than ints
            $this->rows[] = new Row($rowData);
        }
    }

    public function toArray(): array {
        return array_map(fn($row) => $row->toArray(), $this->rows);
    }

    public function toHTML(?string $url_column = null): string {
        // Generate a HTML table from the DataFrame
        $html = '<div style="overflow-x: auto; white-space: nowrap;">';
        $html .= '<table class="df-table">';
        // Add table headers
        $html .= '<tr class="df-row">';
        foreach ($this->columns as $column) {
            if ($url_column === $column) {
                continue;
            }
            $html .= '<th class="df-header">' . htmlspecialchars($column) . '</th>';
        }
        $html .= '</tr>';
        // Add table rows
        foreach ($this->rows as $row) {
            $url_value = $url_column ? $row->get($url_column) : null;
            $html .= '<tr class="df-row">';
            foreach ($this->columns as $index => $column) {
                if ($url_column === $column) {
                    continue;
                }
                $raw_cell_value = $row->get($column);
                $cell_value = htmlspecialchars((string) $raw_cell_value);
                $data_classes = ['df-data'];
                if (is_numeric($raw_cell_value)) {
                    $data_classes[] = 'df-data--number';
                }

                if ($url_value && $index === 0) {
                    // wrap first value in url
                    $cell_value = '<a href="' . $url_value . '">' . $cell_value . '</a>';
                }
                $html .= '<td class="' . implode(' ', $data_classes) . '">' . $cell_value . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }
}
