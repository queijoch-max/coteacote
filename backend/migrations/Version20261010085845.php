<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261010085845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, libelle VARCHAR(80) NOT NULL, actif BOOLEAN NOT NULL, ordre INTEGER NOT NULL, parent_id INTEGER DEFAULT NULL, CONSTRAINT FK_497DD634727ACA70 FOREIGN KEY (parent_id) REFERENCES categorie (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_497DD634727ACA70 ON categorie (parent_id)');
        $this->addSql('CREATE TABLE commune (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(120) NOT NULL, code_postal VARCHAR(5) NOT NULL, code_insee VARCHAR(5) NOT NULL, email_contact VARCHAR(180) NOT NULL, telephone_contact VARCHAR(20) NOT NULL)');
        $this->addSql('CREATE TABLE journal_action (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, "action" VARCHAR(60) NOT NULL, cible VARCHAR(30) NOT NULL, cible_id INTEGER DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, CONSTRAINT FK_9C949E77A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_9C949E77A76ED395 ON journal_action (user_id)');
        $this->addSql('CREATE TABLE mot_interdit (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, mot VARCHAR(60) NOT NULL, actif BOOLEAN NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_mot_interdit ON mot_interdit (mot)');
        $this->addSql('CREATE TABLE publication (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(10) NOT NULL, nature VARCHAR(10) NOT NULL, complement VARCHAR(30) DEFAULT NULL, statut VARCHAR(12) NOT NULL, publiee_par_mairie BOOLEAN NOT NULL, beneficiaire VARCHAR(70) DEFAULT NULL, rue_beneficiaire VARCHAR(120) DEFAULT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, renouvelee BOOLEAN NOT NULL, auteur_id INTEGER NOT NULL, categorie_id INTEGER NOT NULL, CONSTRAINT FK_AF3C677960BB6FE6 FOREIGN KEY (auteur_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AF3C6779BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX idx_publication_liste ON publication (statut, expires_at)');
        $this->addSql('CREATE INDEX IDX_AF3C677960BB6FE6 ON publication (auteur_id)');
        $this->addSql('CREATE INDEX IDX_AF3C6779BCF5E72D ON publication (categorie_id)');
        $this->addSql('CREATE TABLE reponse (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, publication_id INTEGER NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_5FB6DEC738B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5FB6DEC7A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX uniq_reponse ON reponse (publication_id, user_id)');
        $this->addSql('CREATE INDEX IDX_5FB6DEC738B217A7 ON reponse (publication_id)');
        $this->addSql('CREATE INDEX IDX_5FB6DEC7A76ED395 ON reponse (user_id)');
        $this->addSql('CREATE TABLE signalement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, motif VARCHAR(30) NOT NULL, statut VARCHAR(12) NOT NULL, created_at DATETIME NOT NULL, publication_id INTEGER NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_F4B5511438B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F4B55114A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX uniq_signalement ON signalement (publication_id, user_id)');
        $this->addSql('CREATE INDEX IDX_F4B5511438B217A7 ON signalement (publication_id)');
        $this->addSql('CREATE INDEX IDX_F4B55114A76ED395 ON signalement (user_id)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, prenom VARCHAR(60) NOT NULL, nom VARCHAR(60) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, rue VARCHAR(120) NOT NULL, roles CLOB NOT NULL, statut VARCHAR(20) NOT NULL, motif_refus VARCHAR(30) DEFAULT NULL, partage_telephone BOOLEAN NOT NULL, partage_email BOOLEAN NOT NULL, telephone VARCHAR(20) DEFAULT NULL, email_confirme BOOLEAN NOT NULL, confirmation_token VARCHAR(64) DEFAULT NULL, confirmation_expires_at DATETIME DEFAULT NULL, cgu_acceptees_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, statut_change_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, statut_avant_suppression VARCHAR(20) DEFAULT NULL, commune_id INTEGER NOT NULL, CONSTRAINT FK_8D93D649131A4F72 FOREIGN KEY (commune_id) REFERENCES commune (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON "user" (email)');
        $this->addSql('CREATE INDEX IDX_8D93D649131A4F72 ON "user" (commune_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE commune');
        $this->addSql('DROP TABLE journal_action');
        $this->addSql('DROP TABLE mot_interdit');
        $this->addSql('DROP TABLE publication');
        $this->addSql('DROP TABLE reponse');
        $this->addSql('DROP TABLE signalement');
        $this->addSql('DROP TABLE "user"');
    }
}
