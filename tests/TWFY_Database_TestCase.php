<?php

use PHPUnit\Framework\TestCase;

/**
 * Provides acceptance(ish) tests for API functions.
 */
abstract class TWFY_Database_TestCase extends TestCase
{

    /**
     * database handle for database queries in tests
     */
    protected static $db;

    /**
     * Connects to the testing database.
     */
    public static function setUpBeforeClass(): void
    {
        $dsn = 'mysql:dbname=' . OPTION_TWFY_DB_NAME . ';charset=utf8';
        if (OPTION_TWFY_DB_HOST) {
            $dsn .= ';host=' . OPTION_TWFY_DB_HOST;
        }
        $username = OPTION_TWFY_DB_USER;
        $password = OPTION_TWFY_DB_PASS;
        $pdo = new PDO($dsn, $username, $password);
        self::$db = $pdo;
    }

    function setUp(): void {
        parent::setUp();
        $dataset = $this->getDataSet();
    }

    private $xmlFileContents;

    function createMySQLXMLDataSet($xmlFile) {
        $this->xmlFileContents = \simplexml_load_file($xmlFile, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
        if (!$this->xmlFileContents) {
            $message = '';
            foreach (\libxml_get_errors() as $error) {
                $message .= \print_r($error, true);
            }
            throw new RuntimeException($message);
        }

        \libxml_clear_errors();
        $tableColumns = [];
        $tableValues  = [];
        $this->getTableInfo($tableColumns, $tableValues);
        $this->createTables($tableColumns, $tableValues);
    }

    protected function getTableInfo(array &$tableColumns, array &$tableValues): void
    {
        if ($this->xmlFileContents->getName() != 'mysqldump') {
            throw new RuntimeException('The root element of a MySQL XML data set file must be called <mysqldump>');
        }

        foreach ($this->xmlFileContents->xpath('./database/table_data') as $tableElement) {
            if (empty($tableElement['name'])) {
                throw new RuntimeException('<table_data> elements must include a name attribute');
            }

            $tableName = (string) $tableElement['name'];

            if (!isset($tableColumns[$tableName])) {
                $tableColumns[$tableName] = [];
            }

            if (!isset($tableValues[$tableName])) {
                $tableValues[$tableName] = [];
            }

            foreach ($tableElement->xpath('./row') as $rowElement) {
                $rowValues = [];

                foreach ($rowElement->xpath('./field') as $columnElement) {
                    if (empty($columnElement['name'])) {
                        throw new RuntimeException('<field> element name attributes cannot be empty');
                    }

                    $columnName = (string) $columnElement['name'];

                    if (!\in_array($columnName, $tableColumns[$tableName])) {
                        $tableColumns[$tableName][] = $columnName;
                    }
                }

                foreach ($tableColumns[$tableName] as $columnName) {
                    $fields = $rowElement->xpath('./field[@name="' . $columnName . '"]');

                    if (!isset($fields[0])) {
                        throw new RuntimeException(
                            \sprintf(
                                '%s column doesn\'t exist in current row for table %s',
                                $columnName,
                                $tableName
                            )
                        );
                    }

                    $column = $fields[0];
                    $attr   = $column->attributes('http://www.w3.org/2001/XMLSchema-instance');

                    if (isset($attr['type']) && (string) $attr['type'] === 'xs:hexBinary') {
                        $columnValue = \pack('H*', (string) $column);
                    } else {
                        $null        = isset($column['nil']) || isset($attr[0]);
                        $columnValue = $null ? null : (string) $column;
                    }

                    $rowValues[$columnName] = $columnValue;
                }

                $tableValues[$tableName][] = $rowValues;
            }
        }

        foreach ($this->xmlFileContents->xpath('./database/table_structure') as $tableElement) {
            if (empty($tableElement['name'])) {
                throw new RuntimeException('<table_structure> elements must include a name attribute');
            }

            $tableName = (string) $tableElement['name'];

            foreach ($tableElement->xpath('./field') as $fieldElement) {
                if (empty($fieldElement['Field']) && empty($fieldElement['field'])) {
                    throw new RuntimeException('<field> elements must include a Field attribute');
                }

                $columnName = (string) (empty($fieldElement['Field']) ? $fieldElement['field'] : $fieldElement['Field']);

                if (!\in_array($columnName, $tableColumns[$tableName])) {
                    $tableColumns[$tableName][] = $columnName;
                }
            }
        }
    }

    protected function createTables(array &$tableColumns, array &$tableValues): void
    {
        foreach ($tableValues as $tableName => $values) {
            self::$db->query("TRUNCATE TABLE $tableName");
            foreach ($values as $value) {
                $sth = self::$db->prepare("INSERT INTO $tableName (`" . join('`,`', array_keys($value)) . "`) VALUES (" . str_repeat('?,', count($value)-1) . '?)');
                $sth->execute(array_values($value));
            }
        }
    }

    protected function getRowCount($table, $where) {
        $sth = self::$db->prepare("SELECT COUNT(*) FROM $table WHERE $where");
        $sth->execute();
        return $sth->fetch()[0];
    }

    public static function tearDownAfterClass(): void
    {
        self::$db = null;
    }
}
