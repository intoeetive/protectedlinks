<?php
/**
 * Protected Links plugin for Craft CMS 3.x
 *
 * Secure & restricted files download
 *
 * @link      http://www.intoeetive.com/
 * @copyright Copyright (c) 2018 Yurii Salimovskyi
 */

namespace intoeetive\protectedlinks\migrations;

use intoeetive\protectedlinks\ProtectedLinks;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * Protected Links Install Migration
 *
 * If your plugin needs to create any custom database tables when it gets installed,
 * create a migrations/ folder within your plugin folder, and save an Install.php file
 * within it using the following template:
 *
 * If you need to perform any additional actions on install/uninstall, override the
 * safeUp() and safeDown() methods.
 *
 * @author    Yurii Salimovskyi
 * @package   ProtectedLinks
 * @since     0.0.1
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        // protectedlinks_links table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%protectedlinks_links}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%protectedlinks_links}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    // Custom columns in the table
                    'siteId' => $this->integer()->notNull(),
                    'assetId' => $this->integer()->notNull(),
                    'checksum' => $this->string(255)->notNull()->defaultValue(''),
                    'denyHotlink' => $this->integer()->notNull(),
                    'requireLogin' => $this->integer()->notNull(),
                    'members' => $this->string(255)->notNull()->defaultValue(''),
                    'memberGroups' => $this->string(255)->notNull()->defaultValue(''),
                    'inline' => $this->integer()->notNull(),
                    'mimeType' => $this->string(255)->notNull()->defaultValue(''),
                    'dateExpires' => $this->dateTime(),
                    'downloads' => $this->integer()->notNull()->defaultValue(0),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes()
    {
        // protectedlinks_links table
        $this->createIndex(
            $this->db->getIndexName(
                '{{%protectedlinks_links}}',
                'checksum',
                true
            ),
            '{{%protectedlinks_links}}',
            'checksum',
            true
        );
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        // protectedlinks_links table
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%protectedlinks_links}}', 'siteId'),
            '{{%protectedlinks_links}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%protectedlinks_links}}', 'assetId'),
            '{{%protectedlinks_links}}',
            'assetId',
            '{{%assets}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
        // protectedlinks_links table
        $this->dropTableIfExists('{{%protectedlinks_links}}');
    }
}
