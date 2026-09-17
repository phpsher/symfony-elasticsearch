<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $products = [
            [
                'title' => 'iPhone 15 Pro',
                'description' => 'Смартфон Apple с чипом A17 Pro, титановым корпусом и камерой 48 МП. Поддерживает USB-C и Action Button.',
                'price' => 129990.00,
                'image' => 'http://localhost:8000/images/iphone-15-p.png',
            ],
            [
                'title' => 'Samsung Galaxy S24 Ultra',
                'description' => 'Флагман Samsung с встроенным стилусом S Pen, титановой рамкой и 200-мегапиксельной камерой. Экран Dynamic AMOLED 2X.',
                'price' => 139990.00,
                'image' => 'http://localhost:8000/images/samsung-s24-u.png'
            ],
            [
                'title' => 'MacBook Air 13 M3',
                'description' => 'Тонкий и лёгкий ноутбук Apple на чипе M3. До 18 часов автономной работы, дисплей Liquid Retina, MagSafe.',
                'price' => 114990.00,
                'image' => 'http://localhost:8000/images/macbook-air-13-m3.png'
            ],
            [
                'title' => 'iPad Pro 11',
                'description' => 'Планшет Apple с чипом M2, дисплеем Liquid Retina и поддержкой Apple Pencil 2. Идеален для рисования и работы.',
                'price' => 89990.00,
                'image' => 'http://localhost:8000/images/ipad-11-p.png'
            ],
            [
                'title' => 'Nintendo Switch OLED',
                'description' => 'Игровая консоль с OLED-экраном 7 дюймов, улучшенным звуком и подставкой. Подходит для игр дома и в дороге.',
                'price' => 32990.00,
                'image' => 'http://localhost:8000/images/nintendo-switch.png'
            ],
        ];

        foreach ($products as $data) {
            $product = new Product();
            $product->setTitle($data['title']);
            $product->setDescription($data['description']);
            $product->setPrice($data['price']);
            $product->setImage($data['image']);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
