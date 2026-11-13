<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261111150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Anomalies : mot de passe hashé des utilisateurs (provider entity) et ON DELETE SET NULL sur media.album_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD password VARCHAR(255) DEFAULT NULL');
        // Hash de "password" (ancien compte in-memory), à changer en prod.
        $this->addSql('UPDATE "user" SET password = :hash', ['hash' => '$2y$13$7JS0ehfU8vZhB3Q8o1sPGuoQxkiPGXRGgrAizmNfI5Sgy.Dqt9xoW']);
        $this->addSql('ALTER TABLE "user" ALTER password SET NOT NULL');
        $this->addSql('ALTER TABLE media DROP CONSTRAINT FK_6A2CA10C1137ABCF');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media DROP CONSTRAINT FK_6A2CA10C1137ABCF');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C1137ABCF FOREIGN KEY (album_id) REFERENCES album (id)');
        $this->addSql('ALTER TABLE "user" DROP password');
    }
}
