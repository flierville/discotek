<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024100912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Event types: pricing mode (guest range, fixed price or bonus / malus) and price adjustment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event_type ADD pricing VARCHAR(20) DEFAULT \'guest_range\' NOT NULL, ADD price_adjustment INT DEFAULT NULL');
        $this->addSql('UPDATE event_type SET pricing = \'fixed\' WHERE fixed_price IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event_type DROP pricing, DROP price_adjustment');
    }
}
