<?php

namespace SFS\craftpicturetag\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * Picture Tag Install Migration
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Create any custom tables if needed
        $this->createTables();
        
        // Create any indexes
        $this->createIndexes();
        
        // Insert any default data
        $this->insertDefaultData();
        
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // Drop any custom tables
        $this->dropTables();
        
        return true;
    }

    /**
     * Creates the tables needed for the plugin
     */
    protected function createTables(): void
    {
        // Example: Create a table for caching image transform info
        $this->createTable('{{%picture_tag_cache}}', [
            'id' => $this->primaryKey(),
            'assetId' => $this->integer()->notNull(),
            'transformKey' => $this->string()->notNull(),
            'srcset' => $this->text(),
            'sizes' => $this->text(),
            'webpSrcset' => $this->text(),
            'avifSrcset' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        
        // Add foreign key constraint
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%picture_tag_cache}}', 'assetId'),
            '{{%picture_tag_cache}}',
            'assetId',
            Table::ASSETS,
            'id',
            'CASCADE'
        );
    }

    /**
     * Creates the indexes needed for the plugin
     */
    protected function createIndexes(): void
    {
        // Create indexes for better performance
        $this->createIndex(
            $this->db->getIndexName('{{%picture_tag_cache}}', ['assetId', 'transformKey'], true),
            '{{%picture_tag_cache}}',
            ['assetId', 'transformKey'],
            true
        );
        
        $this->createIndex(
            $this->db->getIndexName('{{%picture_tag_cache}}', 'dateCreated'),
            '{{%picture_tag_cache}}',
            'dateCreated'
        );
    }

    /**
     * Inserts the default data needed for the plugin
     */
    protected function insertDefaultData(): void
    {
        // No default data to insert for this plugin
    }

    /**
     * Drops the tables created for the plugin
     */
    protected function dropTables(): void
    {
        // Drop the cache table
        $this->dropTableIfExists('{{%picture_tag_cache}}');
    }
}
