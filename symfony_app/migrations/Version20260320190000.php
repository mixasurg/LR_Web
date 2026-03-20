<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cleans work titles/descriptions, inserts new works by slug, and resets work sort numbering.';
    }

    public function up(Schema $schema): void
    {
        $catalog = $this->workCatalog();
        $insertableSlugs = $this->insertableSlugs();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($catalog as $work) {
            $updated = $this->connection->executeStatement(
                'UPDATE work SET title = :title, description = :description WHERE slug = :slug',
                [
                    'title' => $work['title'],
                    'description' => $work['description'],
                    'slug' => $work['slug'],
                ]
            );

            if ($updated === 0 && isset($insertableSlugs[$work['slug']])) {
                $this->connection->executeStatement(
                    'INSERT INTO work (title, slug, description, is_published, sort_order, created_at)
                     VALUES (:title, :slug, :description, :is_published, :sort_order, :created_at)',
                    [
                        'title' => $work['title'],
                        'slug' => $work['slug'],
                        'description' => $work['description'],
                        'is_published' => 1,
                        'sort_order' => 1000,
                        'created_at' => $now,
                    ]
                );
            }
        }

        $this->resequenceSortOrder($catalog);
        $this->resetWorkAutoIncrement();
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('This migration normalizes text content and ordering in work records.');
    }

    /**
     * @param list<array{slug: string, title: string, description: string}> $catalog
     */
    private function resequenceSortOrder(array $catalog): void
    {
        $knownSlugs = [];
        $order = 10;

        foreach ($catalog as $work) {
            $workId = $this->connection->fetchOne('SELECT id FROM work WHERE slug = :slug', ['slug' => $work['slug']]);
            if ($workId === false) {
                continue;
            }

            $this->connection->executeStatement(
                'UPDATE work SET sort_order = :sort_order WHERE id = :id',
                [
                    'sort_order' => $order,
                    'id' => (int) $workId,
                ]
            );

            $knownSlugs[] = $work['slug'];
            $order += 10;
        }

        if ($knownSlugs === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($knownSlugs), '?'));
        $extraWorkIds = $this->connection->fetchFirstColumn(
            sprintf('SELECT id FROM work WHERE slug NOT IN (%s) ORDER BY sort_order ASC, id ASC', $placeholders),
            $knownSlugs
        );

        foreach ($extraWorkIds as $extraWorkId) {
            $this->connection->executeStatement(
                'UPDATE work SET sort_order = :sort_order WHERE id = :id',
                [
                    'sort_order' => $order,
                    'id' => (int) $extraWorkId,
                ]
            );

            $order += 10;
        }
    }

    private function resetWorkAutoIncrement(): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $maxId = (int) $this->connection->fetchOne('SELECT COALESCE(MAX(id), 0) FROM work');

        if ($platform instanceof SQLitePlatform) {
            $sequenceExists = $this->connection->fetchOne(
                "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'sqlite_sequence'"
            );
            if ($sequenceExists === false) {
                return;
            }

            $sequenceRow = $this->connection->fetchOne("SELECT seq FROM sqlite_sequence WHERE name = 'work'");
            if ($sequenceRow === false) {
                $this->connection->executeStatement(
                    "INSERT INTO sqlite_sequence (name, seq) VALUES ('work', :seq)",
                    ['seq' => $maxId]
                );

                return;
            }

            $this->connection->executeStatement(
                "UPDATE sqlite_sequence SET seq = :seq WHERE name = 'work'",
                ['seq' => $maxId]
            );

            return;
        }

        if ($platform instanceof MySQLPlatform) {
            $nextId = max(1, $maxId + 1);
            $this->connection->executeStatement(sprintf('ALTER TABLE work AUTO_INCREMENT = %d', $nextId));
        }
    }

    /**
     * @return array<string, bool>
     */
    private function insertableSlugs(): array
    {
        return [
            'consul' => true,
            'lieutenantwithcombiweapon' => true,
            'reivers' => true,
            'sanguinarypriest' => true,
            'sanguinor' => true,
            'lieutenantwithstormshield' => true,
            'outriders' => true,
            'ethereal' => true,
            'darkstrider' => true,
            'dante' => true,
            'sternguardveteransquad' => true,
            'captainterminator' => true,
            'chaplainterminator' => true,
            'deathcompanysquad' => true,
            'astorath' => true,
            'lemartes' => true,
            'captainwsword' => true,
            'librarianphobos' => true,
        ];
    }

    /**
     * @return list<array{slug: string, title: string, description: string}>
     */
    private function workCatalog(): array
    {
        return [
            [
                'slug' => 'blood-angels-primaris-captain',
                'title' => 'Blood Angels Primaris Captain',
                'description' => '<p>Это миниатюра капитана легиона «Кровавые Ангелы», вооружённого волкит-пистолетом, силовым кулаком и мечом.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'blood-angels-primaris-lietenant',
                'title' => 'Blood Angels Primaris Lieutenant',
                'description' => '<p>Это миниатюра примарис-лейтенанта легиона «Кровавые Ангелы».</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'blood-angels-primaris-ancient',
                'title' => 'Blood Angels Primaris Ancient',
                'description' => '<p>Это миниатюра примарис-эншента, знаменосца легиона «Кровавые Ангелы».</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'norn-emissary',
                'title' => 'Norn Emissary',
                'description' => '<p>Это миниатюра Норн-эмиссара — высшего хищника тиранидского флота-улья.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'gavriel-sureheart',
                'title' => 'Gavriel Sureheart',
                'description' => '<p>Это миниатюра Гавриэля Светосердца, командира армии Stormcast Eternals.</p><p>Работа выполнена по вселенной Warhammer: Age of Sigmar.</p>',
            ],
            [
                'slug' => 'stormcast-judicators',
                'title' => 'Stormcast Judicators',
                'description' => '<p>Это миниатюры отряда тяжёлых лучников Stormcast Eternals.</p><p>Работа выполнена по вселенной Warhammer: Age of Sigmar.</p>',
            ],
            [
                'slug' => 'stormcast-evocators',
                'title' => 'Stormcast Evocators',
                'description' => '<p>Это миниатюры отряда боевых магов Stormcast Eternals.</p><p>Работа выполнена по вселенной Warhammer: Age of Sigmar.</p>',
            ],
            [
                'slug' => 'primaris-inceptors',
                'title' => 'Primaris Inceptors',
                'description' => '<p>Это миниатюры отряда инцепторов легиона «Кровавые Ангелы».</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'sons-of-horus-pretor',
                'title' => 'Sons of Horus Praetor',
                'description' => '<p>Это миниатюра претора легиона «Сыны Хоруса», вооружённого силовым топором.</p><p>Работа выполнена по вселенной Warhammer: Horus Heresy.</p>',
            ],
            [
                'slug' => 'sons-of-horus-librarian',
                'title' => 'Sons of Horus Librarian',
                'description' => '<p>Это миниатюра библиария легиона «Сыны Хоруса».</p><p>Работа выполнена по вселенной Warhammer: Horus Heresy.</p>',
            ],
            [
                'slug' => 'sons-of-horus-vheren-ashurhaddon',
                'title' => 'Sons of Horus Vheren Ashurhaddon',
                'description' => '<p>Это миниатюра именного знаменосца Верен Ашурхаддона из легиона «Сыны Хоруса».</p><p>Работа выполнена по вселенной Warhammer: Horus Heresy.</p>',
            ],
            [
                'slug' => 'luna-wolves-pretor',
                'title' => 'Luna Wolves Praetor',
                'description' => '<p>Это миниатюра претора легиона «Лунные Волки», вооружённого силовым мечом.</p><p>Работа выполнена по вселенной Warhammer: Horus Heresy.</p>',
            ],
            [
                'slug' => 'space-wolves-venerable-dreadnought',
                'title' => 'Space Wolves Venerable Dreadnought',
                'description' => '<p>Это миниатюра почтенного дредноута ордена «Космические Волки».</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'xv86-coldstar',
                'title' => 'XV86 "Coldstar"',
                'description' => '<p>Это миниатюра командира Тау в броне XV86 «Холодная звезда».</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'consul',
                'title' => 'Надзиратель-консул легиона «Сыны Хоруса»',
                'description' => '<p>Надзиратель-консул легиона «Сыны Хоруса» ведёт отряд в ближний бой и удерживает строй под плотным огнём.</p><p>Работа выполнена по вселенной Warhammer: Horus Heresy.</p>',
            ],
            [
                'slug' => 'lieutenantwithcombiweapon',
                'title' => 'Примарис-лейтенант в броне «Фобос»',
                'description' => '<p>Офицер скрытного наступления Кровавых Ангелов, вооружённый комбиболтером и действующий на переднем крае.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'reivers',
                'title' => 'Рейверы',
                'description' => '<p>Рейверы Кровавых Ангелов специализируются на запугивании и молниеносных штурмах в ближнем бою.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'sanguinarypriest',
                'title' => 'Сангвинарный жрец',
                'description' => '<p>Сангвинарный жрец бережёт геносемя и поддерживает братьев как на операционном столе, так и в гуще боя.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'sanguinor',
                'title' => 'Сангвинор',
                'description' => '<p>Сангвинор — ангельский мститель Кровавых Ангелов, внезапно являющийся в ключевые моменты битвы.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'lieutenantwithstormshield',
                'title' => 'Примарис-лейтенант со штормовым щитом',
                'description' => '<p>Примарис-лейтенант в тяжёлой броне ведёт отделение в наступление и прикрывает братьев штормовым щитом.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'outriders',
                'title' => 'Аутрайдеры',
                'description' => '<p>Аутрайдеры Кровавых Ангелов проводят скоростные рейды и сминают фланги врага огнём болтеров.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'ethereal',
                'title' => 'Эфирный',
                'description' => '<p>Эфирный — духовный лидер Империи Тау, чьё присутствие укрепляет дисциплину и решимость воинов.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'darkstrider',
                'title' => 'Darkstrider',
                'description' => '<p>Darkstrider — мастер разведки Империи Тау, выбирающий слабые места обороны противника.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'dante',
                'title' => 'Commander Dante',
                'description' => '<p>Данте, магистр ордена Кровавых Ангелов, ведёт воинов в бой личным примером и непреклонной волей.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'sternguardveteransquad',
                'title' => 'Отряд ветеранов Sternguard',
                'description' => '<p>Ветераны Sternguard применяют специализированные боеприпасы и действуют как элитная огневая поддержка.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'captainterminator',
                'title' => 'Капитан Кровавых Ангелов в терминаторской броне',
                'description' => '<p>Капитан в терминаторской броне возглавляет штурмовые действия в самых тяжёлых участках фронта.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'chaplainterminator',
                'title' => 'Капеллан Кровавых Ангелов в терминаторской броне',
                'description' => '<p>Капеллан вдохновляет братьев литаниями войны и ведёт их вперёд под грохот болтерного огня.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'deathcompanysquad',
                'title' => 'Отряд «Рота Смерти»',
                'description' => '<p>Бойцы Роты Смерти, охваченные Чёрной Яростью, бросаются в самое пекло боя без страха и сомнений.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'astorath',
                'title' => 'Асторат Мрачный',
                'description' => '<p>Асторат Мрачный — верховный капеллан Кровавых Ангелов и хранитель трагического наследия ордена.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'lemartes',
                'title' => 'Лемартес, Страж Заблудших',
                'description' => '<p>Лемартес направляет Роту Смерти и удерживает её ярость в русле священной войны.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'captainwsword',
                'title' => 'Капитан Кровавых Ангелов с силовым мечом и щитом',
                'description' => '<p>Капитан Кровавых Ангелов сочетает выверенную оборону со стремительными выпадами силовым мечом.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
            [
                'slug' => 'librarianphobos',
                'title' => 'Библиарий в броне «Фобос»',
                'description' => '<p>Библиарий в броне «Фобос» поддерживает отряд психосилами и действует в составе скрытных ударных групп.</p><p>Работа выполнена по вселенной Warhammer 40,000.</p>',
            ],
        ];
    }
}
