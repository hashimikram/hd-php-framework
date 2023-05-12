<?php
namespace HDFramework\src;

use PDO;

/**
 * Data Access layer customized with most used database calls
 *
 * Dependencies: HDLog, HDApplication
 * Configurations dependencies:<br />
 * - config.*.php: DBMODE<br />
 * <br />Release date: 16/05/2015
 *
 * @version 6.0
 * @author Alin
 * @package framework
 */
class HDPDO extends PDO
{

    /**
     * Format output keys of a result to lower case
     *
     * @param array $resultArray
     */
    private function formatResult($inputResult)
    {
        if (is_array($inputResult)) {
            $processedResult = array();
            if (count($inputResult) > 0) {
                foreach ($inputResult as $record) {
                    $formattedRecordArray = array();
                    foreach ($record as $field => $value) {
                        $formattedRecordArray[strtolower($field)] = $value;
                    }
                    $processedResult[] = $formattedRecordArray;
                }
            }
            return $processedResult;
        } else {
            return $inputResult;
        }
    }

    /**
     * Log in the file some database statement execution error
     *
     * @param
     *            PDOStatement Object $statementObject - PDO Statement Object
     * @param String $sqlStatement
     *            - SQL statement to be executed
     * @param Array $statementParameters
     *            - Executipon parameters
     */
    private function logDataBaseError($methodName, $sqlStatement, $statementParameters, $errorMessage)
    {
        HDLog::AppLogMessage("HDPDO.php", "HDPDO.logDataBaseError", $methodName . " SQL", $sqlStatement, 3, "IN");
        HDLog::AppLogMessage("HDPDO.php", "HDPDO.logDataBaseError", $methodName . " SQL Params", $statementParameters, 3, "IN");
        if (is_array($errorMessage)) {
            HDLog::AppLogMessage("HDPDO.php", "HDPDO.logDataBaseError", $methodName . " Error:", $errorMessage, 3, "L");
        } else {
            HDLog::AppLogMessage("HDPDO.php", "HDPDO.logDataBaseError", $methodName . " Error:", $errorMessage, 3, "L");
        }
    }

    /**
     * Log in the file database statement execution
     *
     * @param String $methodName
     *            - name of the method where SQL syntax is executed
     * @param String $sqlStatement
     *            - SQL syntax to be executed
     * @param Array $statementParameters
     *            - SQL statement parameters
     */
    private function logExecutionStatement($methodName, $sqlStatement, $statementParameters)
    {
        HDLog::AppLogMessage("HDPDO.php", "HDPDO.logExecutionStatement", $methodName . " SQL", $sqlStatement, 3, "L");
        HDLog::AppLogMessage("HDPDO.php", "HDPDO.logExecutionStatement", $methodName . " SQL Params", $statementParameters, 3, "L");
    }

    /**
     * Return number of records mathcing fieldName with fieldValue in tableName
     *
     * @param String $tableName
     * @param String $fieldName
     * @param Object $fieldValue
     * @return Integer
     */
    public function countRecordsByField($tableName, $fieldName, $fieldValue)
    {
        $valuesArray = array();
        $result = array();

        $sqlRecordExists = 'SELECT count(ID) AS RECORDNO FROM ' . $tableName . ' WHERE ' . $fieldName . '=:' . $fieldName;
        $valuesArray[$fieldName] = $fieldValue;

        $this->logExecutionStatement("countRecordsByField", $sqlRecordExists, $valuesArray);

        if ($sth = $this->prepare($sqlRecordExists)) {
            if ($sth->execute($valuesArray)) {
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $this->logDataBaseError("countRecordsByField", $sqlRecordExists, $valuesArray, $sth->errorInfo());
            }
        }
        return $result[0]["RECORDNO"];
    }

    /**
     * Return one record from the specified table, providing the primary key (id) value
     *
     * @param String $tableName
     * @param Object $idValue
     * @return Array with record data
     */
    public function findOneRecordById($tableName, $idValue)
    {
        $valuesArray = array();
        $result = array();
        $sqlFindOneRecord = "SELECT * FROM " . $tableName . " WHERE ID = :id";
        $valuesArray["id"] = $idValue;

        $this->logExecutionStatement("findOneRecordById", $sqlFindOneRecord, $valuesArray);

        if ($sth = $this->prepare($sqlFindOneRecord)) {
            if ($sth->execute($valuesArray)) {
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $this->logDataBaseError("findOneRecordById", $sqlFindOneRecord, $valuesArray, $sth->errorInfo());
            }
        }
        return $this->formatResult($result);
    }

    /**
     * Return one record from the specified table, providing field name and field value to search for
     *
     * @param String $tableName
     * @param String $fieldName
     * @param Object $fieldValue
     * @return Array with record data
     */
    public function findOneRecordByField($tableName, $fieldName, $fieldValue)
    {
        $valuesArray = array();
        $result = array();

        if (HDApplication::getConfiguration("DBMODE") == "mysql") {
            $sqlFindOneByFieldRecord = "SELECT * FROM " . $tableName . " WHERE " . $fieldName . " = :" . $fieldName . " LIMIT 0,1";
        } elseif (HDApplication::getConfiguration("DBMODE") == "oracle") {
            $sqlFindOneByFieldRecord = "SELECT * FROM " . $tableName . " WHERE " . $fieldName . " = :" . $fieldName . " AND ROWNUM <= 1 ORDER BY ROWNUM";
        }

        $valuesArray[$fieldName] = $fieldValue;

        $this->logExecutionStatement("findOneRecordByField", $sqlFindOneByFieldRecord, $valuesArray);

        if ($sth = $this->prepare($sqlFindOneByFieldRecord)) {
            if ($sth->execute($valuesArray)) {
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $this->logDataBaseError("findOneRecordByField", $sqlFindOneByFieldRecord, $valuesArray, $sth->errorInfo());
            }
        }

        return $this->formatResult($result);
    }

    /**
     * Return all records from the specified table, providing field name and field value to search for
     *
     * @param String $tableName
     * @param String $fieldName
     * @param Object $fieldValue
     * @return Array with record data
     */
    public function findAllRecordsByField($tableName, $fieldName, $fieldValue)
    {
        $valuesArray = array();
        $result = array();
        $sqlFindAllByFieldRecord = "SELECT * FROM " . $tableName . " WHERE " . $fieldName . " = :" . $fieldName;
        $valuesArray[$fieldName] = $fieldValue;

        $this->logExecutionStatement("findAllRecordsByField", $sqlFindAllByFieldRecord, $valuesArray);

        if ($sth = $this->prepare($sqlFindAllByFieldRecord)) {
            if ($sth->execute($valuesArray)) {
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $this->logDataBaseError("findAllRecordsByField", $sqlFindAllByFieldRecord, $valuesArray, $sth->errorInfo());
            }
        }
        return $this->formatResult($result);
    }

    /**
     * Return all records from the specified table, providing array of field names and field values to search for
     *
     * @param String $tableName
     * @param Array $searchArray
     * @return Array with record data
     */
    public function findAllRecordsByFieldGroup($tableName, $searchArray)
    {
        $valuesArray = array();
        $result = array();
        $sqlFindAllRecordsByFieldGroup = "SELECT * FROM " . $tableName . " WHERE 1=1";

        foreach ($searchArray as $key => $value) {
            $sqlFindAllRecordsByFieldGroup .= " AND " . $tableName . "." . $key . " = :" . $key;
            $valuesArray[$key] = $value;
        }

        $this->logExecutionStatement("findAllRecordsByFieldGroup", $sqlFindAllRecordsByFieldGroup, $valuesArray);

        if ($sth = $this->prepare($sqlFindAllRecordsByFieldGroup)) {
            if ($sth->execute($valuesArray)) {
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $this->logDataBaseError("findAllRecordsByFieldGroup", $sqlFindAllRecordsByFieldGroup, $valuesArray, $sth->errorInfo());
            }
        }
        return $this->formatResult($result);
    }

    /**
     * Return last added record in the specified table as an array
     *
     * @param String $tableName
     * @return Array with record data
     */
    public function getLastAddedRecord($tableName)
    {
        $result = array();
        $sqlFindLastRecord = "SELECT * FROM " . $tableName . " WHERE ID = (SELECT MAX(ID) FROM " . $tableName . ")";

        $this->logExecutionStatement("getLastAddedRecord", $sqlFindLastRecord, array());

        if ($sth = $this->prepare($sqlFindLastRecord)) {
            if ($sth->execute()) {
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $this->logDataBaseError("getLastAddedRecord", $sqlFindLastRecord, array(), $sth->errorInfo());
            }
        }
        return $this->formatResult($result);
    }

    /**
     * Delete one record in the specified table.
     * You need to provide id value of the primary key (id)
     *
     * @param String $tableName
     * @param String $idValue
     * @return True/False if deleted or not
     */
    public function deleteOneRecordById($tableName, $idValue)
    {
        $valuesArray = array();
        $result = false;
        $sqlDeleteOneById = "DELETE FROM " . $tableName . " WHERE ID = :id";
        $valuesArray["id"] = $idValue;

        $this->logExecutionStatement("deleteOneRecordById", $sqlDeleteOneById, $valuesArray);

        if ($sth = $this->prepare($sqlDeleteOneById)) {
            if ($sth->execute($valuesArray)) {
                $result = true;
            } else {
                $this->logDataBaseError("deleteOneRecordById", $sqlDeleteOneById, $valuesArray, $sth->errorInfo());
            }
        }
        return $this->formatResult($result);
    }

    /**
     * Delete data in table based on field value match
     *
     * @param String $tableName
     * @param String $fieldName
     * @param Object $fieldValue
     * @return True/False if deleted or not
     */
    public function deleteRecordsByField($tableName, $fieldName, $fieldValue)
    {
        $valuesArray = array();
        $result = false;
        $sqlDeleteRecords = "DELETE FROM " . $tableName . " WHERE " . $fieldName . " = :" . $fieldName . "";
        $valuesArray[$fieldName] = $fieldValue;

        $this->logExecutionStatement("deleteRecordsByField", $sqlDeleteRecords, $valuesArray);

        if ($sth = $this->prepare($sqlDeleteRecords)) {
            if ($sth->execute($valuesArray)) {
                $result = true;
            } else {
                $this->logDataBaseError("deleteRecordsByField", $sqlDeleteRecords, $valuesArray, $sth->errorInfo());
            }
        }

        return $result;
    }

    /**
     * Delete all records from the specified table, providing array of field names and field values to search for
     *
     * @param String $tableName
     * @param Array $searchArray
     */
    public function deleteRecordsByFieldGroup($tableName, $searchArray)
    {
        $valuesArray = array();
        $result = false;
        $sqlDeleteRecordsByFieldGroup = "DELETE FROM " . $tableName . " WHERE 1=1";

        foreach ($searchArray as $key => $value) {
            $sqlDeleteRecordsByFieldGroup .= " AND " . $tableName . "." . $key . " = :" . $key;
            $valuesArray[$key] = $value;
        }

        $this->logExecutionStatement("deleteRecordsByFieldGroup", $sqlDeleteRecordsByFieldGroup, $valuesArray);

        if ($sth = $this->prepare($sqlDeleteRecordsByFieldGroup)) {
            if ($sth->execute($valuesArray)) {
                $result = true;
            } else {
                $this->logDataBaseError("deleteRecordsByFieldGroup", $sqlDeleteRecordsByFieldGroup, $valuesArray, $sth->errorInfo());
            }
        }
        return $result;
    }

    /**
     *
     * Insert a record in the specified table by providing an array with fields and values
     *
     * @param String $tableName
     * @param Array $insertArray
     * @param Boolean $requestLastId
     * @param Boolean $blockLogging
     *            This is to be set true when we insert logs to avoid infinite loops
     */
    public function insert($tableName, $insertArray, $requestLastId = false, $blockLogging = false)
    {
        $result = 0;
        $insertFieldList = array();
        $insertParamList = array();

        foreach (array_keys($insertArray) as $key) {
            $insertFieldList[] = $key;
            $insertParamList[] = ":" . $key;
        }

        if (count($insertArray) > 0) {
            $sqlInsert = "INSERT INTO " . $tableName . " (" . implode(",", $insertFieldList) . ") VALUES (" . implode(",", $insertParamList) . ")";
        } else {
            $sqlInsert = "INSERT INTO " . $tableName . " VALUES ()";
        }

        if (! $blockLogging) {
            $this->logExecutionStatement("insert", $sqlInsert, $insertArray);
        }

        if ($sth = $this->prepare($sqlInsert)) {
            if ($sth->execute($insertArray)) {
                if (HDApplication::getConfiguration("DBMODE") == "mysql") {
                    $result = $this->lastInsertId();
                } elseif (HDApplication::getConfiguration("DBMODE") == "oracle") {
                    if ($requestLastId) {
                        $result = $this->getLastAddedRecord($tableName);
                        $result = $result[0]["id"];
                    } else {
                        return 1;
                    }
                }
            } else {
                $this->logDataBaseError("insert", $sqlInsert, $insertArray, $sth->errorInfo());
            }
        }
        return $result;
    }

    /**
     * Update a record in the specified table based on the specified array of field => values and primary key field(id) value
     *
     * @param String $tableName
     * @param Array $updateArray
     * @param String $idValue
     * @param String $idField
     *            - default NULL - use ID field
     * @return boolean = True/False if record updated or not
     */
    public function update($tableName, $updateArray, $idValue, $idField = '')
    {
        $sqlUpdate = "UPDATE " . $tableName . " SET ";
        $result = false;
        $index = 0;

        foreach (array_keys($updateArray) as $key) {
            $index ++;
            if ($index == count($updateArray))
                $sqlUpdate .= " " . $tableName . "." . $key . "=:" . $key;
            else
                $sqlUpdate .= " " . $tableName . "." . $key . "=:" . $key . ",";
        }

        if ($idField != '') {
            $sqlUpdate .= " WHERE " . $idField . " = '" . $idValue . "'";
        } else {
            $sqlUpdate .= " WHERE id = " . $idValue;
        }

        $this->logExecutionStatement("update", $sqlUpdate, $updateArray);

        if ($sth = $this->prepare($sqlUpdate)) {
            if ($sth->execute($updateArray)) {
                $result = true;
            } else {
                $this->logDataBaseError("update", $sqlUpdate, $updateArray, $sth->errorInfo());
            }
        }
        return $result;
    }

    /**
     * Execute SQL command and returns the number of affected records
     * To be used for DELETE/UPDATE comands which have complicated where clauses
     *
     * @param String $sql
     *            to be executed as a command - for complicated sql commands(Eg.: DELETE FROM table_name WHERE field=:field AND ...);
     * @param Array $paramsArray
     *            (Eg $paramsArray["field"] = val;)
     * @return Number of affected rows
     */
    public function command($sql, $paramsArray, $logQuery = true)
    {
        $result = 0;

        $logQuery ? $this->logExecutionStatement("command", $sql, $paramsArray) : "";

        if ($sth = $this->prepare($sql)) {
            if ($sth->execute($paramsArray)) {
                $result = $sth->rowCount();
            } else {
                $this->logDataBaseError("command", $sql, $paramsArray, $sth->errorInfo());
            }
        }
        return $result;
    }

    /**
     * Execute a query and return selected records as an array of records based on parameters
     * To be used for SELECT clauses
     *
     * @param String $sql
     *            to be executed as a query - for complicated querries (Eg.: SELECT * FROM table_name,table_2 WHERE field=:field AND ...);
     * @param Array $paramsArray
     *            (Eg $paramsArray["field"] = val;)
     * @return Array of records
     */
    public function query($sql, $paramsArray)
    {
        $result = array();

        $this->logExecutionStatement("query", $sql, $paramsArray);

        if ($sth = $this->prepare($sql)) {
            if ($sth->execute($paramsArray)) {
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $this->logDataBaseError("query", $sql, $paramsArray, $sth->errorInfo());
            }
        }
        return $this->formatResult($result);
    }

    /**
     * Return table data as an array of records based on suplied parameters
     *
     * @param String $tableName
     *            = Table name to retrieve data. Eg: "users"
     * @param Array $fieldsArray
     *            = List of fields to retrieve from table. Eg: array of format ("email" => "email","password" => "password");
     * @param Array $paramsArray
     *            = List of params to be used in where clause. Eg: array of format ("id" => 1,"email" => "test@domain.com");
     * @param Array $orderClause
     *            = List of field and order direction for order by clause. Eg: array of format ("id" => "desc", "email" => "asc")
     * @return Array containing table records
     */
    public function getTableData($tableName, $fieldsArray, $paramsArray, $orderClause)
    {
        $result = array();
        $ordersSet = false;

        // first line
        $sqlGetTableFields = "";

        if (count($fieldsArray) > 0) {
            // iterate all fields specified and add them to select clause
            foreach ($fieldsArray as $key => $value) {
                $sqlGetTableFields .= "," . $key . "";
                if ($value != "")
                    $sqlGetTableFields .= " AS " . $value;
            }
            $sqlGetTableFields = substr($sqlGetTableFields, 1);
        } else // if the fields array is empty we bring them all
        {
            $sqlGetTableFields = "*";
        }

        $sqlGetTable = "SELECT " . $sqlGetTableFields;

        // add from clause including dummy where clause
        $sqlGetTable .= " FROM " . $tableName . " WHERE 1=1 ";

        // iterate all params
        foreach ($paramsArray as $key => $value) {
            $sqlGetTable .= " AND " . $key . "=:" . $key;
        }

        // add order by clause
        $sqlGetTable .= " ORDER BY";

        // iterate all order by clauses
        foreach ($orderClause as $key => $value) {
            if (! $ordersSet) {
                $ordersSet = true;
                $sqlGetTable .= " " . $key . " " . $value;
            } else {
                $sqlGetTable .= ", " . $key . " " . $value;
            }
        }

        // if no order by is specified then select by id desc
        if ($ordersSet == false) {
            $sqlGetTable .= " ID DESC";
        }

        $this->logExecutionStatement("getTableData", $sqlGetTable, $paramsArray);

        // execute statement
        if ($sth = $this->prepare($sqlGetTable)) {
            if ($sth->execute($paramsArray)) {
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $this->logDataBaseError("getTableData", $sqlGetTable, $paramsArray, $sth->errorInfo());
            }
        }
        return $this->formatResult($result);
    }
}
