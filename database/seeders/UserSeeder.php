<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Shop;
use App\Models\ShopTag;
use App\Models\ShopTranslation;
use App\Models\User;
use DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Throwable;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $users = [
            [
                'id' => 102,
                'uuid' => Str::uuid(),
                'firstname' => 'User',
                'lastname' => 'User',
                'email' => 'user@gmail.com',
                'phone' => '998911902595',
                'birthday' => '1993-12-30',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('user123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 103,
                'uuid' => Str::uuid(),
                'firstname' => 'Owner',
                'lastname' => 'Owner',
                'email' => 'owner@githubit.com',
                'phone' => '998911902696',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('githubit'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 104,
                'uuid' => Str::uuid(),
                'firstname' => 'Manager',
                'lastname' => 'Manager',
                'email' => 'manager@githubit.com',
                'phone' => '998911902616',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('manager'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 105,
                'uuid' => Str::uuid(),
                'firstname' => 'Moderator',
                'lastname' => 'Moderator',
                'email' => 'moderator@githubit.com',
                'phone' => '998911902116',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('moderator'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 106,
                'uuid' => Str::uuid(),
                'firstname' => 'Delivery',
                'lastname' => 'Delivery',
                'email' => 'delivery@githubit.com',
                'phone' => '998911912116',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('delivery'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 108,
                'uuid' => Str::uuid(),
                'firstname' => 'Waiter',
                'lastname' => 'Waiter',
                'email' => 'waiter@githubit.com',
                'phone' => '9989119121245',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('waiter'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 109,
                'uuid' => Str::uuid(),
                'firstname' => 'Cook',
                'lastname' => 'Cook',
                'email' => 'cook@githubit.com',
                'phone' => '9989119121241',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('cook'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 107,
                'uuid' => Str::uuid(),
                'firstname' => 'Branch',
                'lastname' => 'Branch',
                'email' => 'branch1@githubit.com',
                'phone' => '998911902691',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('branch1'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 110,
                'uuid' => Str::uuid(),
                'firstname' => 'Branch',
                'lastname' => 'Branch',
                'email' => 'branch2@githubit.com',
                'phone' => '998911902692',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('branch2'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 111,
                'uuid' => Str::uuid(),
                'firstname' => 'Branch',
                'lastname' => 'Branch',
                'email' => 'branch3@githubit.com',
                'phone' => '998911902693',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('branch3'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            User::updateOrInsert(['id' => data_get($user, 'id')], $user);
        }

        User::find(102)->syncRoles('user');
        User::find(103)->syncRoles(['admin', 'seller']);
        User::find(107)->syncRoles('seller');
        User::find(110)->syncRoles('seller');
        User::find(111)->syncRoles('seller');
        User::find(104)->syncRoles('manager');
        User::find(105)->syncRoles('moderator');
        User::find(106)->syncRoles('deliveryman');
        User::find(108)->syncRoles('waiter');
        User::find(109)->syncRoles('cook');

        $shop = Shop::updateOrCreate([
            'user_id'           => 103,
        ], [
            'uuid'              => Str::uuid(),
            'location'          => [
                'latitude'          => -69.3453324,
                'longitude'         => 69.3453324,
            ],
            'phone'             => '+1234567',
            'show_type'         => 1,
            'open'              => 1,
            'background_img'    => 'url.webp',
            'logo_img'          => 'url.webp',
            'status'            => 'approved',
            'status_note'       => 'approved',
            'delivery_time'     => [
                'from'              => '10',
                'to'                => '90',
                'type'              => 'minute',
            ],
            'type'              => 2,
        ]);

        ShopTranslation::updateOrCreate([
            'shop_id'       => $shop->id,
        ], [
            'description'   => 'shop desc',
            'title'         => 'shop title',
            'locale'        => data_get(Language::languagesList()->first(), 'locale', 'en'),
            'address'       => 'address',
        ]);

        $shop->tags()->sync(ShopTag::pluck('id')->toArray());

        $childShop = Shop::updateOrCreate([
            'user_id'           => 107,
        ], [
            'uuid'              => Str::uuid(),
            'parent_id'         => $shop->id,
            'location'          => [
                'latitude'          => -69.3453324,
                'longitude'         => 69.3453324,
            ],
            'phone'             => '+1234566',
            'show_type'         => 1,
            'open'              => 1,
            'background_img'    => 'url.webp',
            'logo_img'          => 'url.webp',
            'status'            => 'approved',
            'status_note'       => 'approved',
            'delivery_time'     => [
                'from'              => '10',
                'to'                => '90',
                'type'              => 'minute',
            ],
            'type'              => 2,
        ]);

        ShopTranslation::updateOrCreate([
            'shop_id'       => $childShop->id,
        ], [
            'description'   => 'branch desc',
            'title'         => 'branch title',
            'locale'        => data_get(Language::languagesList()->first(), 'locale', 'en'),
            'address'       => 'address',
        ]);

        $childShop1 = Shop::updateOrCreate([
            'user_id'           => 110,
        ], [
            'uuid'              => Str::uuid(),
            'parent_id'         => $shop->id,
            'location'          => [
                'latitude'          => -69.3453324,
                'longitude'         => 69.3453324,
            ],
            'phone'             => '+12345617',
            'show_type'         => 1,
            'open'              => 1,
            'background_img'    => 'url.webp',
            'logo_img'          => 'url.webp',
            'status'            => 'approved',
            'status_note'       => 'approved',
            'delivery_time'     => [
                'from'              => '10',
                'to'                => '90',
                'type'              => 'minute',
            ],
            'type'              => 2,
        ]);

        ShopTranslation::updateOrCreate([
            'shop_id'       => $childShop1->id,

        ], [
            'description'   => 'branch 1 desc',
            'title'         => 'branch 1 title',
            'locale'        => data_get(Language::languagesList()->first(), 'locale', 'en'),
            'address'       => 'address 1',
        ]);

        $childShop2 = Shop::updateOrCreate([
            'user_id'           => 111,
        ], [
            'uuid'              => Str::uuid(),
            'parent_id'         => $shop->id,
            'location'          => [
                'latitude'          => -69.3453324,
                'longitude'         => 69.3453324,
            ],
            'phone'             => '+1234564',
            'show_type'         => 1,
            'open'              => 1,
            'background_img'    => 'url.webp',
            'logo_img'          => 'url.webp',
            'status'            => 'approved',
            'status_note'       => 'approved',
            'delivery_time'     => [
                'from'              => '10',
                'to'                => '90',
                'type'              => 'minute',
            ],
            'type'              => 2,
        ]);

        ShopTranslation::updateOrCreate([
            'shop_id'       => $childShop2->id,
        ],[
            'description'   => 'branch 2 desc',
            'title'         => 'branch 2 title',
            'locale'        => data_get(Language::languagesList()->first(), 'locale', 'en'),
            'address'       => 'address 2',
        ]);

		//Как я понял это ограничение на количество родительских магазинов
		/*         
		try {
            $query = "
				CREATE TRIGGER saqw1_trigger
				BEFORE INSERT ON shops
				FOR EACH ROW
				BEGIN
					DECLARE shop_count INT;
					SET shop_count = (SELECT COUNT(*) FROM shops);
					IF shop_count >= 5 THEN
						SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '.';
					END IF;
				END;
			";
            DB::unprepared($query);
        } catch (Throwable) {} 
		*/

        $childShop->tags()->sync(ShopTag::pluck('id')->toArray());
        $childShop1->tags()->sync(ShopTag::pluck('id')->toArray());
        $childShop2->tags()->sync(ShopTag::pluck('id')->toArray());

    }

}
