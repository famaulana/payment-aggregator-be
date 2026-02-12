<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\HeadOffice;
use App\Models\Merchant;
use App\Models\SystemOwner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class DevelopmentDataSeeder extends Seeder
{
    private $faker;
    private $systemOwner;
    private $provinces;
    private $cities;
    private $districts;
    private $subDistricts;
    private $createdClients = [];
    private $createdHeadOffices = [];

    public function __construct()
    {
        $this->faker = Faker::create('id_ID');
    }

    public function run(): void
    {
        // Get System Owner
        $this->systemOwner = SystemOwner::where('code', 'JDP')->first();
        if (!$this->systemOwner) {
            $this->command->error('System Owner not found. Please run SystemOwnerSeeder first.');
            return;
        }

        // Load location data
        $this->loadLocationData();

        // Create 10 Clients with their hierarchies
        $this->command->info('Creating 10 Clients with their hierarchies...');
        for ($i = 1; $i <= 10; $i++) {
            $this->createClientHierarchy($i);
        }

        $this->command->info('✅ Successfully created 10 clients with their hierarchies!');
        $this->command->info("Total created:");
        $this->command->info("- Clients: " . count($this->createdClients));
        $this->command->info("- Head Offices: " . count($this->createdHeadOffices));
    }

    private function loadLocationData(): void
    {
        // Get random provinces
        $this->provinces = DB::table('provinces')
            ->inRandomOrder()
            ->limit(10)
            ->get();

        if ($this->provinces->isEmpty()) {
            $this->command->warn('No provinces found. Location data will be null.');
            return;
        }

        // Get cities from those provinces
        $provinceIds = $this->provinces->pluck('id');
        $this->cities = DB::table('cities')
            ->whereIn('province_id', $provinceIds)
            ->inRandomOrder()
            ->limit(30)
            ->get();

        // Get districts from those cities
        $cityIds = $this->cities->pluck('id');
        $this->districts = DB::table('districts')
            ->whereIn('city_id', $cityIds)
            ->inRandomOrder()
            ->limit(50)
            ->get();

        // Get sub-districts from those districts
        $districtIds = $this->districts->pluck('id');
        $this->subDistricts = DB::table('sub_districts')
            ->whereIn('district_id', $districtIds)
            ->inRandomOrder()
            ->limit(100)
            ->get();
    }

    private function createClientHierarchy(int $index): void
    {
        try {
            DB::beginTransaction();

            // Generate unique client code
            $clientCode = $this->generateUniqueClientCode();

            // Get random location
            $location = $this->getRandomLocation();

            // Create Client
            $client = Client::create([
                'system_owner_id' => $this->systemOwner->id,
                'client_code' => $clientCode,
                'client_name' => $this->faker->unique()->company,
                'business_type' => $this->faker->randomElement([
                    'Financial Technology',
                    'Retail',
                    'E-Commerce',
                    'Food & Beverage',
                    'Technology',
                    'Automotive',
                    'Education',
                    'Healthcare',
                    'Logistics',
                    'Manufacturing',
                ]),
                'kyb_status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
                'kyb_submitted_at' => now()->subDays(rand(1, 60)),
                'kyb_approved_at' => $this->faker->boolean(70) ? now()->subDays(rand(1, 30)) : null,
                'settlement_time' => sprintf('%02d:00:00', rand(10, 17)),
                'settlement_config' => json_encode([
                    'auto_settlement' => $this->faker->boolean(80),
                    'settlement_cycle' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
                    'min_settlement_amount' => rand(50000, 500000),
                ]),
                'bank_name' => $this->faker->randomElement([
                    'Bank Mandiri',
                    'Bank BCA',
                    'Bank BNI',
                    'Bank BRI',
                    'Bank CIMB Niaga',
                    'Bank Permata',
                    'Bank Danamon',
                ]),
                'bank_account_number' => $this->faker->numerify('##########'),
                'bank_account_holder_name' => $this->faker->company,
                'bank_branch' => $this->faker->city . ' ' . $this->faker->streetName,
                'pic_name' => $this->faker->name,
                'pic_position' => $this->faker->randomElement([
                    'Finance Director',
                    'CEO',
                    'CFO',
                    'General Manager',
                    'Operations Manager',
                ]),
                'pic_phone' => $this->faker->unique()->numerify('08##########'),
                'pic_email' => $this->faker->unique()->safeEmail,
                'company_phone' => $this->faker->unique()->numerify('021########'),
                'company_email' => $this->faker->unique()->companyEmail,
                'available_balance' => $this->faker->randomFloat(2, 1000000, 100000000),
                'pending_balance' => $this->faker->randomFloat(2, 0, 10000000),
                'minus_balance' => 0,
                'province_id' => $location['province_id'],
                'city_id' => $location['city_id'],
                'address' => $this->faker->address,
                'postal_code' => $this->faker->numerify('#####'),
                'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            ]);

            $this->createdClients[] = $client;
            $this->command->info("✓ Created Client: {$client->client_name} ({$client->client_code})");

            // Create Client User
            $this->createClientUser($client);

            // Create 2-3 Head Offices for this Client
            $numberOfHO = rand(2, 3);
            for ($ho = 1; $ho <= $numberOfHO; $ho++) {
                $this->createHeadOfficeHierarchy($client, $ho);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Failed to create client hierarchy: " . $e->getMessage());
        }
    }

    private function createHeadOfficeHierarchy(Client $client, int $index): void
    {
        $location = $this->getRandomLocation();
        $hoCode = $this->generateUniqueHOCode($client);

        $headOffice = HeadOffice::create([
            'client_id' => $client->id,
            'code' => $hoCode,
            'name' => $this->faker->unique()->company . ' - Head Office ' . $this->faker->city,
            'province_id' => $location['province_id'],
            'city_id' => $location['city_id'],
            'district_id' => $location['district_id'],
            'sub_district_id' => $location['sub_district_id'],
            'address' => $this->faker->address,
            'postal_code' => $this->faker->numerify('#####'),
            'phone' => $this->faker->unique()->numerify('021########'),
            'email' => $this->faker->unique()->companyEmail,
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ]);

        $this->createdHeadOffices[] = $headOffice;
        $this->command->info("  ✓ Created Head Office: {$headOffice->name} ({$headOffice->code})");

        // Create Head Office User
        $this->createHeadOfficeUser($headOffice);

        // Create 2-3 Merchants for this Head Office
        $numberOfMerchants = rand(2, 3);
        for ($m = 1; $m <= $numberOfMerchants; $m++) {
            $this->createMerchant($client, $headOffice, $m);
        }
    }

    private function createMerchant(Client $client, HeadOffice $headOffice, int $index): void
    {
        $location = $this->getRandomLocation();
        $merchantCode = $this->generateUniqueMerchantCode($client);

        $merchant = Merchant::create([
            'client_id' => $client->id,
            'head_office_id' => $headOffice->id,
            'merchant_code' => $merchantCode,
            'merchant_name' => $this->faker->unique()->company . ' - ' . $this->faker->randomElement([
                'Outlet',
                'Cabang',
                'Toko',
                'Store',
                'Branch',
            ]) . ' ' . $this->faker->city,
            'province_id' => $location['province_id'],
            'city_id' => $location['city_id'],
            'district_id' => $location['district_id'],
            'sub_district_id' => $location['sub_district_id'],
            'address' => $this->faker->address,
            'postal_code' => $this->faker->numerify('#####'),
            'phone' => $this->faker->unique()->numerify('021########'),
            'email' => $this->faker->unique()->companyEmail,
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ]);

        $this->command->info("    ✓ Created Merchant: {$merchant->merchant_name} ({$merchant->merchant_code})");

        // Create Merchant User
        $this->createMerchantUser($merchant);
    }

    private function createClientUser(Client $client): void
    {
        $email = $this->generateUniqueEmail('client');
        $username = $this->generateUniqueUsername('client');

        $user = User::create([
            'username' => $username,
            'email' => $email,
            'full_name' => $this->faker->name . ' (Client Admin)',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
            'entity_type' => Client::class,
            'entity_id' => $client->id,
        ]);

        $user->assignSingleRole('client');
        $this->command->info("  → Created Client User: {$user->email}");
    }

    private function createHeadOfficeUser(HeadOffice $headOffice): void
    {
        $email = $this->generateUniqueEmail('ho');
        $username = $this->generateUniqueUsername('ho');

        $user = User::create([
            'username' => $username,
            'email' => $email,
            'full_name' => $this->faker->name . ' (HO Admin)',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
            'entity_type' => HeadOffice::class,
            'entity_id' => $headOffice->id,
        ]);

        $user->assignSingleRole('head_office');
        $this->command->info("    → Created Head Office User: {$user->email}");
    }

    private function createMerchantUser(Merchant $merchant): void
    {
        $email = $this->generateUniqueEmail('merchant');
        $username = $this->generateUniqueUsername('merchant');

        $user = User::create([
            'username' => $username,
            'email' => $email,
            'full_name' => $this->faker->name . ' (Merchant Admin)',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
            'entity_type' => Merchant::class,
            'entity_id' => $merchant->id,
        ]);

        $user->assignSingleRole('merchant');
        $this->command->info("      → Created Merchant User: {$user->email}");
    }

    private function generateUniqueClientCode(): string
    {
        do {
            $code = 'CLT' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Client::where('client_code', $code)->exists());

        return $code;
    }

    private function generateUniqueHOCode(Client $client): string
    {
        $counter = HeadOffice::where('client_id', $client->id)->count() + 1;
        do {
            $code = 'HO-' . strtoupper(substr($client->client_code, -3)) . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        } while (HeadOffice::where('code', $code)->exists());

        return $code;
    }

    private function generateUniqueMerchantCode(Client $client): string
    {
        $counter = Merchant::where('client_id', $client->id)->count() + 1;
        do {
            $code = 'MER-' . strtoupper(substr($client->client_code, -3)) . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
            $counter++;
        } while (Merchant::where('merchant_code', $code)->exists());

        return $code;
    }

    private function generateUniqueEmail(string $prefix): string
    {
        do {
            $email = $prefix . '_' . $this->faker->unique()->userName . '@' . $this->faker->domainName;
        } while (User::where('email', $email)->exists());

        return strtolower($email);
    }

    private function generateUniqueUsername(string $prefix): string
    {
        do {
            $username = $prefix . '_' . $this->faker->unique()->userName . rand(100, 999);
        } while (User::where('username', $username)->exists());

        return strtolower($username);
    }

    private function getRandomLocation(): array
    {
        $location = [
            'province_id' => null,
            'city_id' => null,
            'district_id' => null,
            'sub_district_id' => null,
        ];

        if ($this->provinces->isNotEmpty()) {
            $province = $this->provinces->random();
            $location['province_id'] = $province->id;

            // Get city from this province
            $citiesInProvince = $this->cities->where('province_id', $province->id);
            if ($citiesInProvince->isNotEmpty()) {
                $city = $citiesInProvince->random();
                $location['city_id'] = $city->id;

                // Get district from this city
                $districtsInCity = $this->districts->where('city_id', $city->id);
                if ($districtsInCity->isNotEmpty()) {
                    $district = $districtsInCity->random();
                    $location['district_id'] = $district->id;

                    // Get sub-district from this district
                    $subDistrictsInDistrict = $this->subDistricts->where('district_id', $district->id);
                    if ($subDistrictsInDistrict->isNotEmpty()) {
                        $subDistrict = $subDistrictsInDistrict->random();
                        $location['sub_district_id'] = $subDistrict->id;
                    }
                }
            }
        }

        return $location;
    }
}
