<?php

namespace AbandonedCartReminder\Model\Map;

use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\InstancePoolTrait;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Map\TableMapTrait;


/**
 * This class defines the structure of the 'abandoned_cart' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class AbandonedCartTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;
    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'AbandonedCartReminder.Model.Map.AbandonedCartTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'thelia';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'abandoned_cart';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\AbandonedCartReminder\\Model\\AbandonedCart';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'AbandonedCartReminder.Model.AbandonedCart';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 7;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 7;

    /**
     * the column name for the ID field
     */
    const ID = 'abandoned_cart.ID';

    /**
     * the column name for the CART_ID field
     */
    const CART_ID = 'abandoned_cart.CART_ID';

    /**
     * the column name for the EMAIL_CLIENT field
     */
    const EMAIL_CLIENT = 'abandoned_cart.EMAIL_CLIENT';

    /**
     * the column name for the LOCALE field
     */
    const LOCALE = 'abandoned_cart.LOCALE';

    /**
     * the column name for the STATUS field
     */
    const STATUS = 'abandoned_cart.STATUS';

    /**
     * the column name for the LOGIN_TOKEN field
     */
    const LOGIN_TOKEN = 'abandoned_cart.LOGIN_TOKEN';

    /**
     * the column name for the LAST_UPDATE field
     */
    const LAST_UPDATE = 'abandoned_cart.LAST_UPDATE';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('Id', 'CartId', 'EmailClient', 'Locale', 'Status', 'LoginToken', 'LastUpdate', ),
        self::TYPE_STUDLYPHPNAME => array('id', 'cartId', 'emailClient', 'locale', 'status', 'loginToken', 'lastUpdate', ),
        self::TYPE_COLNAME       => array(AbandonedCartTableMap::ID, AbandonedCartTableMap::CART_ID, AbandonedCartTableMap::EMAIL_CLIENT, AbandonedCartTableMap::LOCALE, AbandonedCartTableMap::STATUS, AbandonedCartTableMap::LOGIN_TOKEN, AbandonedCartTableMap::LAST_UPDATE, ),
        self::TYPE_RAW_COLNAME   => array('ID', 'CART_ID', 'EMAIL_CLIENT', 'LOCALE', 'STATUS', 'LOGIN_TOKEN', 'LAST_UPDATE', ),
        self::TYPE_FIELDNAME     => array('id', 'cart_id', 'email_client', 'locale', 'status', 'login_token', 'last_update', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('Id' => 0, 'CartId' => 1, 'EmailClient' => 2, 'Locale' => 3, 'Status' => 4, 'LoginToken' => 5, 'LastUpdate' => 6, ),
        self::TYPE_STUDLYPHPNAME => array('id' => 0, 'cartId' => 1, 'emailClient' => 2, 'locale' => 3, 'status' => 4, 'loginToken' => 5, 'lastUpdate' => 6, ),
        self::TYPE_COLNAME       => array(AbandonedCartTableMap::ID => 0, AbandonedCartTableMap::CART_ID => 1, AbandonedCartTableMap::EMAIL_CLIENT => 2, AbandonedCartTableMap::LOCALE => 3, AbandonedCartTableMap::STATUS => 4, AbandonedCartTableMap::LOGIN_TOKEN => 5, AbandonedCartTableMap::LAST_UPDATE => 6, ),
        self::TYPE_RAW_COLNAME   => array('ID' => 0, 'CART_ID' => 1, 'EMAIL_CLIENT' => 2, 'LOCALE' => 3, 'STATUS' => 4, 'LOGIN_TOKEN' => 5, 'LAST_UPDATE' => 6, ),
        self::TYPE_FIELDNAME     => array('id' => 0, 'cart_id' => 1, 'email_client' => 2, 'locale' => 3, 'status' => 4, 'login_token' => 5, 'last_update' => 6, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, )
    );

    /**
     * Initialize the table attributes and columns
     * Relations are not initialized by this method since they are lazy loaded
     *
     * @return void
     * @throws PropelException
     */
    public function initialize()
    {
        // attributes
        $this->setName('abandoned_cart');
        $this->setPhpName('AbandonedCart');
        $this->setClassName('\\AbandonedCartReminder\\Model\\AbandonedCart');
        $this->setPackage('AbandonedCartReminder.Model');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('ID', 'Id', 'INTEGER', true, null, null);
        $this->addForeignKey('CART_ID', 'CartId', 'INTEGER', 'cart', 'ID', true, null, null);
        $this->addColumn('EMAIL_CLIENT', 'EmailClient', 'VARCHAR', false, 255, null);
        $this->addColumn('LOCALE', 'Locale', 'VARCHAR', false, 5, null);
        $this->addColumn('STATUS', 'Status', 'INTEGER', false, 1, 0);
        $this->addColumn('LOGIN_TOKEN', 'LoginToken', 'VARCHAR', false, 255, null);
        $this->addColumn('LAST_UPDATE', 'LastUpdate', 'TIMESTAMP', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('Cart', '\\Thelia\\Model\\Cart', RelationMap::MANY_TO_ONE, array('cart_id' => 'id', ), 'CASCADE', 'RESTRICT');
    } // buildRelations()

    /**
     * Retrieves a string version of the primary key from the DB resultset row that can be used to uniquely identify a row in this table.
     *
     * For tables with a single-column primary key, that simple pkey value will be returned.  For tables with
     * a multi-column primary key, a serialize()d version of the primary key will be returned.
     *
     * @param array  $row       resultset row.
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     */
    public static function getPrimaryKeyHashFromRow($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        // If the PK cannot be derived from the row, return NULL.
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
    }

    /**
     * Retrieves the primary key from the DB resultset row
     * For tables with a single-column primary key, that simple pkey value will be returned.  For tables with
     * a multi-column primary key, an array of the primary key columns will be returned.
     *
     * @param array  $row       resultset row.
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     *
     * @return mixed The primary key of the row
     */
    public static function getPrimaryKeyFromRow($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {

            return (int) $row[
                            $indexType == TableMap::TYPE_NUM
                            ? 0 + $offset
                            : self::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)
                        ];
    }

    /**
     * The class that the tableMap will make instances of.
     *
     * If $withPrefix is true, the returned path
     * uses a dot-path notation which is translated into a path
     * relative to a location on the PHP include_path.
     * (e.g. path.to.MyClass -> 'path/to/MyClass.php')
     *
     * @param boolean $withPrefix Whether or not to return the path with the class name
     * @return string path.to.ClassName
     */
    public static function getOMClass($withPrefix = true)
    {
        return $withPrefix ? AbandonedCartTableMap::CLASS_DEFAULT : AbandonedCartTableMap::OM_CLASS;
    }

    /**
     * Populates an object of the default type or an object that inherit from the default.
     *
     * @param array  $row       row returned by DataFetcher->fetch().
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                 One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     * @return array (AbandonedCart object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = AbandonedCartTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = AbandonedCartTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + AbandonedCartTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = AbandonedCartTableMap::OM_CLASS;
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            AbandonedCartTableMap::addInstanceToPool($obj, $key);
        }

        return array($obj, $col);
    }

    /**
     * The returned array will contain objects of the default type or
     * objects that inherit from the default.
     *
     * @param DataFetcherInterface $dataFetcher
     * @return array
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     */
    public static function populateObjects(DataFetcherInterface $dataFetcher)
    {
        $results = array();

        // set the class once to avoid overhead in the loop
        $cls = static::getOMClass(false);
        // populate the object(s)
        while ($row = $dataFetcher->fetch()) {
            $key = AbandonedCartTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = AbandonedCartTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                AbandonedCartTableMap::addInstanceToPool($obj, $key);
            } // if key exists
        }

        return $results;
    }
    /**
     * Add all the columns needed to create a new object.
     *
     * Note: any columns that were marked with lazyLoad="true" in the
     * XML schema will not be added to the select list and only loaded
     * on demand.
     *
     * @param Criteria $criteria object containing the columns to add.
     * @param string   $alias    optional table alias
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     */
    public static function addSelectColumns(Criteria $criteria, $alias = null)
    {
        if (null === $alias) {
            $criteria->addSelectColumn(AbandonedCartTableMap::ID);
            $criteria->addSelectColumn(AbandonedCartTableMap::CART_ID);
            $criteria->addSelectColumn(AbandonedCartTableMap::EMAIL_CLIENT);
            $criteria->addSelectColumn(AbandonedCartTableMap::LOCALE);
            $criteria->addSelectColumn(AbandonedCartTableMap::STATUS);
            $criteria->addSelectColumn(AbandonedCartTableMap::LOGIN_TOKEN);
            $criteria->addSelectColumn(AbandonedCartTableMap::LAST_UPDATE);
        } else {
            $criteria->addSelectColumn($alias . '.ID');
            $criteria->addSelectColumn($alias . '.CART_ID');
            $criteria->addSelectColumn($alias . '.EMAIL_CLIENT');
            $criteria->addSelectColumn($alias . '.LOCALE');
            $criteria->addSelectColumn($alias . '.STATUS');
            $criteria->addSelectColumn($alias . '.LOGIN_TOKEN');
            $criteria->addSelectColumn($alias . '.LAST_UPDATE');
        }
    }

    /**
     * Returns the TableMap related to this object.
     * This method is not needed for general use but a specific application could have a need.
     * @return TableMap
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     */
    public static function getTableMap()
    {
        return Propel::getServiceContainer()->getDatabaseMap(AbandonedCartTableMap::DATABASE_NAME)->getTable(AbandonedCartTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
      $dbMap = Propel::getServiceContainer()->getDatabaseMap(AbandonedCartTableMap::DATABASE_NAME);
      if (!$dbMap->hasTable(AbandonedCartTableMap::TABLE_NAME)) {
        $dbMap->addTableObject(new AbandonedCartTableMap());
      }
    }

    /**
     * Performs a DELETE on the database, given a AbandonedCart or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or AbandonedCart object or primary key or array of primary keys
     *              which is used to create the DELETE statement
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     */
     public static function doDelete($values, ConnectionInterface $con = null)
     {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(AbandonedCartTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \AbandonedCartReminder\Model\AbandonedCart) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(AbandonedCartTableMap::DATABASE_NAME);
            $criteria->add(AbandonedCartTableMap::ID, (array) $values, Criteria::IN);
        }

        $query = AbandonedCartQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) { AbandonedCartTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) { AbandonedCartTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the abandoned_cart table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return AbandonedCartQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a AbandonedCart or Criteria object.
     *
     * @param mixed               $criteria Criteria or AbandonedCart object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(AbandonedCartTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from AbandonedCart object
        }

        if ($criteria->containsKey(AbandonedCartTableMap::ID) && $criteria->keyContainsValue(AbandonedCartTableMap::ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.AbandonedCartTableMap::ID.')');
        }


        // Set the correct dbName
        $query = AbandonedCartQuery::create()->mergeWith($criteria);

        try {
            // use transaction because $criteria could contain info
            // for more than one table (I guess, conceivably)
            $con->beginTransaction();
            $pk = $query->doInsert($con);
            $con->commit();
        } catch (PropelException $e) {
            $con->rollBack();
            throw $e;
        }

        return $pk;
    }

} // AbandonedCartTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
AbandonedCartTableMap::buildTableMap();
