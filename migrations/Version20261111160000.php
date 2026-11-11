<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261111160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Gestion des invités : statut « bloqué » et suppression en cascade des médias d\'un invité';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD blocked BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE media DROP CONSTRAINT FK_6A2CA10CA76ED395');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media DROP CONSTRAINT FK_6A2CA10CA76ED395');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
        $this->addSql('ALTER TABLE "user" DROP blocked');
    }
}
