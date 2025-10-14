<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Core\Models;

use DUP_PRO_Log;
use Duplicator\Libs\Snap\SnapLog;
use VendorDuplicator\Amk\JsonSerialize\JsonSerialize;
use Duplicator\Libs\Snap\SnapWP;
use Error;
use Exception;
use ReflectionClass;
use ReflectionObject;
use wpdb;

/**
 * Abstract Entity
 */
abstract class AbstractEntity
{
    /** @var int */
    protected $id = -1;
    /** @var string generic indexed value */
    protected $value1 = '';
    /** @var string generic indexed value */
    protected $value2 = '';
    /** @var string generic indexed value */
    protected $value3 = '';
    /** @var string generic indexed value */
    protected $value4 = '';
    /** @var string generic indexed value */
    protected $value5 = '';
    /** @var string plugin version on update */
    protected $version = DUPLICATOR_PRO_VERSION;
    /** @var string timestamp YYYY-MM-DD HH:MM:SS UTC */
    protected $created = '';
    /** @var string timestamp YYYY-MM-DD HH:MM:SS UTC */
    protected $updated = '';

    /**
     * Return entity type identifier
     *
     * @return string
     */
    public static function getType()
    {
        // This is to avoid warnings in PHP 5.6 because isn't possibile declare an abstract static method.
        throw new Exception('This method must be extended');
    }

    /**
     * Return entity id
     *
     * @return int
     */
    final public function getId()
    {
        return $this->id;
    }

    /**
     * Set props by array key inpust data
     *
     * @param mixed[]   $data             input data
     * @param ?callable $sanitizeCallback sanitize values callback
     *
     * @return void
     */
    protected function setFromArrayKey($data, $sanitizeCallback = null)
    {
        $reflect = new ReflectionClass($this);
        $props   = $reflect->getProperties();

        foreach ($props as $prop) {
            if (!isset($data[$prop->getName()])) {
                continue;
            }

            if (is_callable($sanitizeCallback)) {
                $value = call_user_func($sanitizeCallback, $prop->getName(), $data[$prop->getName()]);
            } else {
                $value = $data[$prop->getName()];
            }
            $prop->setValue($this, $value);
        }
    }

    /**
     * Initizalize entity from JSON
     *
     * @param string               $json           JSON string
     * @param array<string,scalar> $rowData        DB row data
     * @param ?string              $overwriteClass Overwrite class object, class must extend AbstractEntity
     *
     * @return static
     */
    protected static function getEntityFromJson($json, $rowData, $overwriteClass = null)
    {
        if ($overwriteClass === null) {
            $class = static::class;
        } else {
            if (is_subclass_of($overwriteClass, __CLASS__) === false) {
                throw new Exception('Class ' . $overwriteClass . ' must extend ' . static::class);
            }
            $class = $overwriteClass;
        }

        /** @var static $obj */
        $obj     = JsonSerialize::unserializeToObj($json, $class);
        $reflect = new ReflectionObject($obj);

        $dbValuesToProps = [
            'id'         => 'id',
            'value_1'    => 'value1',
            'value_2'    => 'value2',
            'value_3'    => 'value3',
            'value_4'    => 'value4',
            'value_5'    => 'value5',
            'version'    => 'version',
            'created_at' => 'created',
            'updated_at' => 'updated',
        ];

        if (isset($rowData['id'])) {
            $rowData['id'] = (int) $rowData['id'];
        }

        foreach ($dbValuesToProps as $dbKey => $propName) {
            if (
                !isset($rowData[$dbKey]) ||
                !property_exists($obj, $propName)
            ) {
                continue;
            }

            $prop = $reflect->getProperty($propName);
            $prop->setAccessible(true);
            $prop->setValue($obj, $rowData[$dbKey]);
        }

        return $obj;
    }

    /**
     * Save entity
     *
     * @return bool True on success, or false on error.
     */
    public function save()
    {
        $saved = false;
        if ($this->id < 0) {
            $saved = ($this->insert() !== false);
        } else {
            $saved = $this->update();
        }
        return $saved;
    }

    /**
     * Insert entity
     *
     * @return int|false The number of rows inserted, or false on error.
     */
    protected function insert()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        if ($this->id > -1) {
            throw new Exception('Entity already exists');
        }

        $this->updated = $this->created = gmdate("Y-m-d H:i:s");
        $this->version = DUPLICATOR_PRO_VERSION;

        $result = $wpdb->insert(
            self::getTableName(),
            [
                'type'       => $this->getType(),
                'data'       => '', // First I create a row without an object to generate the id, and then I update the row create
                'version'    => $this->version,
                'created_at' => $this->created,
                'updated_at' => $this->updated,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );
        if ($result === false) {
            return false;
        }
        $this->id = $wpdb->insert_id;

        if ($this->update() === false) {
            $this->delete();
            return false;
        }
        return $this->id;
    }

    /**
     * Update entity
     *
     * @return bool True on success, or false on error.
     */
    protected function update()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        if ($this->id < 0) {
            throw new Exception('Entity don\'t exists in database');
        }

        $this->updated = gmdate("Y-m-d H:i:s");
        $this->version = DUPLICATOR_PRO_VERSION;

        return ($wpdb->update(
            self::getTableName(),
            [
                'type'       => $this->getType(),
                'value_1'    => $this->value1,
                'value_2'    => $this->value2,
                'value_3'    => $this->value3,
                'value_4'    => $this->value4,
                'value_5'    => $this->value5,
                'data'       => JsonSerialize::serialize($this, JsonSerialize::JSON_SKIP_CLASS_NAME | JSON_PRETTY_PRINT),
                'version'    => $this->version,
                'created_at' => $this->created,
                'updated_at' => $this->updated,
            ],
            ['id' => $this->id],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ],
            ['%d']
        ) !== false);
    }

    /**
     * Delete current entity
     *
     * @return bool True on success, or false on error.
     */
    public function delete()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        if ($this->id < 0) {
            return true;
        }

        if (
            $wpdb->delete(
                self::getTableName(),
                ['id' => $this->id],
                ['%d']
            ) === false
        ) {
            return false;
        }

        $this->id = -1;
        return true;
    }

    /**
     * Entity table name
     *
     * @param bool $escape If true apply esc_sql to table name
     *
     * @return string
     */
    public static function getTableName($escape = false)
    {
        /** @var wpdb $wpdb */
        global $wpdb;
        $res = $wpdb->base_prefix . 'duplicator_entities';
        return ($escape ? esc_sql($res) : $res);
    }

    /**
     * Get entities of current type
     *
     * @param int<0, max>                          $page           current page, if $pageSize is 0 o 1 $pase is the offset
     * @param int<0, max>                          $pageSize       page size, 0 return all entities
     * @param ?callable                            $sortCallback   sort function on items result
     * @param ?callable                            $filterCallback filter on items result
     * @param array{'col': string, 'mode': string} $orderby        query ordder by
     *
     * @return static[]|false return entities list of false on failure
     */
    protected static function getItemsFromDatabase(
        $page = 0,
        $pageSize = 0,
        $sortCallback = null,
        $filterCallback = null,
        $orderby = [
            'col'  => 'id',
            'mode' => 'ASC',
        ]
    ) {
        try {
            /** @var wpdb $wpdb */
            global $wpdb;

            $offset   = $page * max(1, $pageSize);
            $pageSize = ($pageSize ? $pageSize : PHP_INT_MAX);
            $orderCol = isset($orderby['col']) ? $orderby['col'] : 'id';
            $order    = isset($orderby['mode']) ? $orderby['mode'] : 'ASC';

            $query = $wpdb->prepare(
                "SELECT * FROM `" . self::getTableName(true) . "` WHERE type = %s ORDER BY {$orderCol} {$order} LIMIT %d OFFSET %d",
                static::getType(),
                $pageSize,
                $offset
            );

            if (($rows = $wpdb->get_results($query, ARRAY_A)) === null) {
                throw new Exception('Get item query fail');
            }

            $instances = [];
            foreach ($rows as $row) {
                $instances[] = static::getEntityFromJson($row['data'], $row);
            }

            if (is_callable($filterCallback)) {
                $instances = array_filter($instances, $filterCallback);
            }

            if (is_callable($sortCallback)) {
                usort($instances, $sortCallback);
            } else {
                $instances = array_values($instances);
            }
        } catch (Exception $e) {
            DUP_PRO_Log::traceError(SnapLog::getTextException($e));
            return false;
        } catch (Error $e) {
            DUP_PRO_Log::traceError(SnapLog::getTextException($e));
            return false;
        }

        return $instances;
    }

    /**
     * Get ids of current type
     *
     * @param int<0, max>                          $page           current page, if $pageSize is 0 o 1 $pase is the offset
     * @param int<0, max>                          $pageSize       page size, 0 return all entities
     * @param ?callable                            $sortCallback   sort function on items result
     * @param ?callable                            $filterCallback filter on items result
     * @param array{'col': string, 'mode': string} $orderby        query ordder by
     *
     * @return int[]|false return entities list of false on failure
     */
    protected static function getIdsFromDatabase(
        $page = 0,
        $pageSize = 0,
        $sortCallback = null,
        $filterCallback = null,
        $orderby = [
            'col'  => 'id',
            'mode' => 'ASC',
        ]
    ) {
        try {
            /** @var wpdb $wpdb */
            global $wpdb;

            $offset   = $page * max(1, $pageSize);
            $pageSize = ($pageSize ? $pageSize : PHP_INT_MAX);
            $orderCol = isset($orderby['col']) ? $orderby['col'] : 'id';
            $order    = isset($orderby['mode']) ? $orderby['mode'] : 'ASC';

            $query = $wpdb->prepare(
                "SELECT id FROM `" . self::getTableName(true) . "` WHERE type = %s ORDER BY {$orderCol} {$order} LIMIT %d OFFSET %d",
                static::getType(),
                $pageSize,
                $offset
            );

            if (($rows = $wpdb->get_results($query, ARRAY_A)) === null) {
                throw new Exception('Get item query fail');
            }

            $ids = array();
            foreach ($rows as $row) {
                $ids[] = (int) $row['id'];
            }

            if (is_callable($filterCallback)) {
                $ids = array_filter($ids, $filterCallback);
            }

            if (is_callable($sortCallback)) {
                usort($ids, $sortCallback);
            } else {
                $ids = array_values($ids);
            }
        } catch (Exception $e) {
            DUP_PRO_Log::traceError(SnapLog::getTextException($e));
            return false;
        } catch (Error $e) {
            DUP_PRO_Log::traceError(SnapLog::getTextException($e));
            return false;
        }

        return $ids;
    }

    /**
     * Count entity items
     *
     * @return int|false
     */
    protected static function countItemsFromDatabase()
    {
        try {
            /** @var wpdb $wpdb */
            global $wpdb;

            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM `" . self::getTableName(true) . "` WHERE type = %s",
                static::getType()
            );

            if (($count = $wpdb->get_var($query)) === null) {
                throw new Exception('Get item query fail');
            }
        } catch (Exception $e) {
            DUP_PRO_Log::traceError(SnapLog::getTextException($e));
            return false;
        } catch (Error $e) {
            DUP_PRO_Log::traceError(SnapLog::getTextException($e));
            return false;
        }

        return (int) $count;
    }


    /**
     * Init entity table
     *
     * @return string[] Strings containing the results of the various update queries.
     */
    final public static function initTable()
    {
        /** @var wpdb $wpdb */
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name      = static::getTableName(true);

        // PRIMARY KEY must have 2 spaces before for dbDelta to work
        // Mysql 5.5 can't have more than 1 DEFAULT CURRENT_TIMESTAMP
        $sql = <<<SQL
CREATE TABLE `{$table_name}` (
    `id` bigint(20) unsigned NOT null AUTO_INCREMENT,
    `type` varchar(100) NOT NULL,
    `value_1` varchar(255) NOT NULL DEFAULT '',
    `value_2` varchar(255) NOT NULL DEFAULT '',
    `value_3` varchar(255) NOT NULL DEFAULT '',
    `value_4` varchar(255) NOT NULL DEFAULT '',
    `value_5` varchar(255) NOT NULL DEFAULT '',
    `data` longtext NOT null,
    `version` varchar(30) NOT NULL DEFAULT '',
    `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (`id`),
    KEY `type_idx` (`type`),
    KEY `created_at` (`created_at`),
    KEY `updated_at` (`updated_at`),
    KEY `version` (`version`),
    KEY `value_1` (`value_1`),
    KEY `value_2` (`value_2`),
    KEY `value_3` (`value_3`),
    KEY `value_4` (`value_4`),
    KEY `value_5` (`value_5`)
) {$charset_collate};
SQL;

        return SnapWP::dbDelta($sql);
    }
}
