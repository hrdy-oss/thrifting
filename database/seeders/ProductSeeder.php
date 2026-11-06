<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['Dark Dreams Hoodie', 'hoodies', 1699000, 'XL', 9.2, 'Vintage heavyweight cotton with natural fade.', 'https://lh3.googleusercontent.com/aida-public/AB6AXuBYOLCgRHpdV8UqUWSp7w1Bm001dO2WVWMnHkkyTjywOenNOVsPDLhrKtxHeNWK90QyeiLXWAiiULCUj6X7PkDgj0RsdZ2iVQaY4A_3RgzBJNRYUV92cf2b6anC5QWz0q6jmiS_BynrJ73_BTWHcOiGT_R8zDdX0CpDq_ONbgP7b9Oewi9LSAixhwUJYmT4tAMzV4428yux7SkSyXQey5rl9h_y5ZYYydhz'],
            ['Chaos Club Vintage Tee', 't-shirts', 899000, 'L', 8.8, 'Soft cotton graphic tee with light print wear.', 'https://lh3.googleusercontent.com/aida-public/AB6AXuBi-PCy5LBusFXKyXIG4Wqun-9hbPRTbdam_zgyo8jEa3aKn2nN54F20mVox5ncROw1QVeS-XYOsRKYOWyjsZdM00n79ji4MaIg_JTiC87GFGgJfcaMijLnLJl8_zei37SFz43QK835-yB-bYuCxTcWNHwQ8DKPkZdKWBT-G-YKPJhEcvD5WxMwsJxjbklNqJTqitWbnJ_rlBi7EsdlbqqufPSaRhExbEZ0022Jg0_c'],
            ['Utility Cargo Pants', 'pants', 1599000, '34', 9.1, 'Archive utility pants with clean seams and hardware.', 'https://lh3.googleusercontent.com/aida-public/AB6AXuCnL8KydEjtWeik_a7uKREh45DGMK-4DjQWUre-8I-PW76OnqSVJ5NADVB5XGxbM-Mh2pkOOrm-WC8f2IOsXv5mwxjWobqjYGXlnYqYXZfHEJR8ff53C-0AwySaKpLSMJxB7QzDoplxEIjE2SUIcw0YRkblx39aHpoPKm8MDBDwOtIS6IuwXrcVnXPNulPDIBOfwtZivCJuIfoM1pIydIizqxkYq8R1IBhqUB-9QOdx'],
            ['Neon Oversized Tee', 't-shirts', 999000, 'XL', 9.5, 'Oversized streetwear tee, excellent vintage condition.', 'https://lh3.googleusercontent.com/aida-public/AB6AXuBZXSAlqCZZOU12KfqsmGbZxJjfIdikKejfI1ODCKHRFc5KY77P48GSKe3KjMD57hwz4BNyrO62Fmh088gYD06GwaGyAJEPulZ8oixzPtSDgmhN1cRntvC94dsPFMa5Ffe56-dNnTlJrXooEQCyDECLV2MJ_VTUlV-LsH0ej6RS_YmfsfIyZhM-MR0tOdLLEkw0JCzxlhyGUhycagMQk58LQ_g2wOjUAo7unPyjsBpc'],
        ];

        foreach ($products as $index => [$title, $category, $price, $size, $conditionScore, $conditionNotes, $imageUrl]) {
            Product::query()->updateOrCreate(
                ['sku' => 'NEON-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'title' => $title,
                    'slug' => Str::slug($title),
                    'description' => $conditionNotes.' Measurements are listed as a guide; please review every photo before purchase.',
                    'category' => $category,
                    'price' => $price,
                    'compare_at_price' => $price + 200000,
                    'size' => $size,
                    'condition_score' => $conditionScore,
                    'condition_notes' => $conditionNotes,
                    'image_url' => $imageUrl,
                    'era' => '1990s archive',
                    'measurements' => ['pit_to_pit' => '58 cm', 'length' => '72 cm', 'shoulder' => '56 cm'],
                    'stock' => 1,
                    'is_featured' => $index < 2,
                    'is_published' => true,
                ],
            );
        }
    }
}
