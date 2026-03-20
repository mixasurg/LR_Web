<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Page;
use App\Entity\User;
use App\Entity\Work;
use App\Entity\WorkPhoto;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setEmail('admin@mixas.local')
            ->setFullName('Анциферов Михаил Михайлович')
            ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));
        $manager->persist($admin);

        $homeContent = <<<'HTML'
<p>Добро пожаловать в <strong>Mixas Art Works</strong> — сайт-портфолио моих работ с миниатюрами.</p>
<p>Я увлекаюсь Warhammer 40k, Horus Heresy и Age of Sigmar. Здесь собраны мои покрашенные модели и отряды.</p>
HTML;

        $contactsContent = <<<'HTML'
<p>Связаться со мной можно по контактам ниже:</p>
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Параметр</th>
            <th>Значение</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>ФИО</td>
            <td>Анциферов Михаил Михайлович</td>
        </tr>
        <tr>
            <td>Email</td>
            <td><a href="mailto:ya.mishanc@yandex.ru">ya.mishanc@yandex.ru</a></td>
        </tr>
        <tr>
            <td>Телефон</td>
            <td>+7 (964) 14?-??-52</td>
        </tr>
    </tbody>
</table>
HTML;

        $galleryContent = <<<'HTML'
<p>Фотогалерея моих работ по покрасу миниатюр. Для каждой работы доступно несколько фото.</p>
HTML;

        $feedbackContent = <<<'HTML'
<p>Оставьте отзыв по работам или сообщение для связи.</p>
HTML;

        $aboutContent = <<<'HTML'
<p>Mixas Art Works — персональный проект с портфолио покраса миниатюр.</p>
<p>В работах я стараюсь держать аккуратный tabletop+, чистые контуры и контрастные детали.</p>
HTML;

        $manager->persist($this->createPage(
            title: 'Главная',
            slug: 'home',
            systemKey: 'home',
            menuTitle: 'Главная',
            content: $homeContent,
            position: 10,
            showInMenu: true,
        ));

        $manager->persist($this->createPage(
            title: 'Контакты',
            slug: 'contacts',
            systemKey: 'contacts',
            menuTitle: 'Контакты',
            content: $contactsContent,
            position: 20,
            showInMenu: true,
        ));

        $manager->persist($this->createPage(
            title: 'Фотогалерея',
            slug: 'gallery',
            systemKey: 'gallery',
            menuTitle: 'Галерея',
            content: $galleryContent,
            position: 30,
            showInMenu: true,
        ));

        $manager->persist($this->createPage(
            title: 'Обратная связь',
            slug: 'feedback',
            systemKey: 'feedback',
            menuTitle: 'Обратная связь',
            content: $feedbackContent,
            position: 40,
            showInMenu: true,
        ));

        $manager->persist($this->createPage(
            title: 'О проекте',
            slug: 'about-project',
            systemKey: null,
            menuTitle: 'О проекте',
            content: $aboutContent,
            position: 50,
            showInMenu: true,
        ));

        $works = [
            [
                'title' => 'Blood Angels Primaris Captain',
                'slug' => 'blood-angels-primaris-captain',
                'description' => '<p>Это миниатюра капитана легиона "Кровавых Ангелов" с Волкит пистолетом из эры Раздора и силовым кулаком и мечом.</p><p>Миниатюра относится к варгейму Warhammer 40 000</p>',
                'photos' => [
                    'mixas-works/40k/Captain1.jpg',
                    'mixas-works/40k/Captain2.jpg',
                    'mixas-works/40k/Captain3.jpg',
                    'mixas-works/40k/Captain4.jpg',
                ],
            ],
            [
                'title' => 'Blood Angels Primaris Lietenant',
                'slug' => 'blood-angels-primaris-lietenant',
                'description' => '<p>Это миниатюра лейтенант легиона "Кровавых Ангелов".</p><p>Миниатюра относится к варгейму Warhammer 40 000</p>',
                'photos' => [
                    'mixas-works/40k/Lietenant1.jpg',
                    'mixas-works/40k/Lietenant2.jpg',
                    'mixas-works/40k/Lietenant3.jpg',
                    'mixas-works/40k/Lietenant4.jpg',
                ],
            ],
            [
                'title' => 'Blood Angels Primaris Ancient',
                'slug' => 'blood-angels-primaris-ancient',
                'description' => '<p>Это миниатюра знаменосца легиона "Кровавых Ангелов".</p><p>Миниатюра относится к варгейму Warhammer 40 000</p>',
                'photos' => [
                    'mixas-works/40k/Ancient1.jpg',
                    'mixas-works/40k/Ancient2.jpg',
                    'mixas-works/40k/Ancient3.jpg',
                    'mixas-works/40k/Ancient4.jpg',
                ],
            ],
            [
                'title' => 'Norn Emissary',
                'slug' => 'norn-emissary',
                'description' => '<p>Это миниатюра высшего хищника флота улья Норн-Эмиссар.</p><p>Миниатюра относится к варгейму Warhammer 40 000</p>',
                'photos' => [
                    'mixas-works/40k/Norn1.jpg',
                    'mixas-works/40k/Norn2.jpg',
                    'mixas-works/40k/Norn3.jpg',
                    'mixas-works/40k/Norn4.jpg',
                ],
            ],
            [
                'title' => 'Gavriel Sureheart',
                'slug' => 'gavriel-sureheart',
                'description' => '<p>Это миниатюра командуещего армии Stormcast Eternals Гавриэль Чистое сердце.</p><p>Миниатюра относится к варгейму Warhammer: Age of Sigmar</p>',
                'photos' => [
                    'mixas-works/AOS/Gavriel_Sureheart1.jpg',
                    'mixas-works/AOS/Gavriel_Sureheart2.jpg',
                    'mixas-works/AOS/Gavriel_Sureheart3.jpg',
                ],
            ],
            [
                'title' => 'Stormcast Judicators',
                'slug' => 'stormcast-judicators',
                'description' => '<p>Это миниатюры отряда тяжелых лучников армии Stormcast Eternals.</p><p>Миниатюры относится к варгейму Warhammer: Age of Sigmar</p>',
                'photos' => [
                    'mixas-works/AOS/Archer_all.jpg',
                    'mixas-works/AOS/Archer_sgt.jpg',
                ],
            ],
            [
                'title' => 'Stormcast Evocators',
                'slug' => 'stormcast-evocators',
                'description' => '<p>Это миниатюры отряда боевых магов армии Stormcast Eternals.</p><p>Миниатюры относится к варгейму Warhammer: Age of Sigmar</p>',
                'photos' => [
                    'mixas-works/AOS/Evocators_all.jpg',
                    'mixas-works/AOS/Evocators_sgt1.jpg',
                    'mixas-works/AOS/Evocators_sgt2.jpg',
                    'mixas-works/AOS/Evocators_sgt3.jpg',
                    'mixas-works/AOS/Evocators_troop1.jpg',
                    'mixas-works/AOS/Evocators_troop2.jpg',
                    'mixas-works/AOS/Evocators_troop3.jpg',
                ],
            ],
            [
                'title' => 'Primaris Inceptors',
                'slug' => 'primaris-inceptors',
                'description' => '<p>Это миниатюры отряда зачинателей легиона "Кровавые Ангелы".</p><p>Миниатюры относится к варгейму Warhammer 40 000</p>',
                'photos' => [
                    'mixas-works/40k/Inceptors_all.jpg',
                    'mixas-works/40k/Inceptors_sgt1.jpg',
                    'mixas-works/40k/Inceptors_sgt2.jpg',
                    'mixas-works/40k/Inceptors_sgt3.jpg',
                    'mixas-works/40k/Inceptors_troop1.jpg',
                    'mixas-works/40k/Inceptors_troop2.jpg',
                    'mixas-works/40k/Inceptors_troop3.jpg',
                    'mixas-works/40k/Inceptors_troop4.jpg',
                ],
            ],
            [
                'title' => 'Sons of Horus Pretor',
                'slug' => 'sons-of-horus-pretor',
                'description' => '<p>Это миниатюра командуещего армии с силовым топром, который относится к ордену "Сыны Хоруса".</p><p>Миниатюра относится к варгейму Warhammer: Horus Heresy</p>',
                'photos' => [
                    'mixas-works/Heresy/SoH_Pretor1.jpg',
                    'mixas-works/Heresy/SoH_Pretor2.jpg',
                    'mixas-works/Heresy/SoH_Pretor3.jpg',
                ],
            ],
            [
                'title' => 'Sons of Horus Librarian',
                'slug' => 'sons-of-horus-librarian',
                'description' => '<p>Это миниатюра библиария, который относится к ордену "Сыны Хоруса".</p><p>Миниатюра относится к варгейму Warhammer: Horus Heresy</p>',
                'photos' => [
                    'mixas-works/Heresy/SoH_Libr1.jpg',
                    'mixas-works/Heresy/SoH_Libr2.jpg',
                    'mixas-works/Heresy/SoH_Libr3.jpg',
                    'mixas-works/Heresy/SoH_Libr4.jpg',
                ],
            ],
            [
                'title' => 'Sons of Horus Vheren Ashurhaddon',
                'slug' => 'sons-of-horus-vheren-ashurhaddon',
                'description' => '<p>Это миниатюра именного знаменосца Vheren Ashurhaddon, который относится к ордену "Сыны Хоруса".</p><p>Миниатюра относится к варгейму Warhammer: Horus Heresy</p>',
                'photos' => [
                    'mixas-works/Heresy/Soh_Vheren1.jpg',
                    'mixas-works/Heresy/Soh_Vheren2.jpg',
                    'mixas-works/Heresy/Soh_Vheren3.jpg',
                    'mixas-works/Heresy/Soh_Vheren4.jpg',
                ],
            ],
            [
                'title' => 'Luna Wolves Pretor',
                'slug' => 'luna-wolves-pretor',
                'description' => '<p>Это миниатюра командуещего армии с силовым мечом, который относится к ордену "Лунные Волки".</p><p>Миниатюра относится к варгейму Warhammer: Horus Heresy</p>',
                'photos' => [
                    'mixas-works/Heresy/LW_pretor1.jpg',
                    'mixas-works/Heresy/LW_pretor2.jpg',
                    'mixas-works/Heresy/LW_pretor3.jpg',
                    'mixas-works/Heresy/LW_pretor4.jpg',
                ],
            ],
            [
                'title' => 'Space Wolves Venerable Dreadnought',
                'slug' => 'space-wolves-venerable-dreadnought',
                'description' => '<p>Это миниатюра почетного дредноута ордена "Космические Волки".</p><p>Миниатюры относится к варгейму Warhammer 40000</p>',
                'photos' => [
                    'mixas-works/40k/SW_Dread1.jpg',
                    'mixas-works/40k/SW_Dread2.jpg',
                    'mixas-works/40k/SW_Dread3.jpg',
                    'mixas-works/40k/SW_Dread4.jpg',
                    'mixas-works/40k/SW_Dread5.jpg',
                    'mixas-works/40k/SW_Dread6.jpg',
                    'mixas-works/40k/SW_Dread7.jpg',
                    'mixas-works/40k/SW_Dread8.jpg',
                ],
            ],
            [
                'title' => 'XV 86 "ColdStar"',
                'slug' => 'xv86-coldstar',
                'description' => '<p>Это миниатюра командуещего в броне XV86, модификации "Холодная звезда", фракции "Империя ТАУ".</p><p>Миниатюра относится к варгейму Warhammer 40000</p>',
                'photos' => [
                    'mixas-works/40k/XV86_1.jpg',
                    'mixas-works/40k/XV86_2.jpg',
                    'mixas-works/40k/XV86_3.jpg',
                    'mixas-works/40k/XV86_4.jpg',
                ],
            ],
        ];

        $workOrder = 10;
        foreach ($works as $workData) {
            $work = (new Work())
                ->setTitle($workData['title'])
                ->setSlug($workData['slug'])
                ->setDescription($workData['description'])
                ->setSortOrder($workOrder)
                ->setIsPublished(true);
            $manager->persist($work);

            $photoOrder = 10;
            foreach ($workData['photos'] as $photoPath) {
                $photo = (new WorkPhoto())
                    ->setWork($work)
                    ->setImagePath($photoPath)
                    ->setSortOrder($photoOrder)
                    ->setCaption(null)
                    ->setIsPublished(true);
                $manager->persist($photo);
                $photoOrder += 10;
            }

            $workOrder += 10;
        }

        $manager->flush();
    }

    private function createPage(
        string $title,
        string $slug,
        ?string $systemKey,
        string $menuTitle,
        string $content,
        int $position,
        bool $showInMenu,
    ): Page {
        return (new Page())
            ->setTitle($title)
            ->setSlug($slug)
            ->setSystemKey($systemKey)
            ->setMenuTitle($menuTitle)
            ->setContent($content)
            ->setPosition($position)
            ->setShowInMenu($showInMenu)
            ->setIsPublished(true);
    }
}
