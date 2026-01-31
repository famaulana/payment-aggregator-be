<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SystemOwner;
use App\Models\Client;
use App\Models\HeadOffice;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $systemOwner = SystemOwner::where('code', 'JDP')->first();
        $client = Client::where('client_code', 'JDP001')->first();

        if (!$systemOwner || !$client) {
            return;
        }

        // Super Admin - Entity: SystemOwner (HIGHEST LEVEL)
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@jdp.co.id'],
            [
                'username' => 'superadmin',
                'full_name' => 'Super Admin',
                'password' => Hash::make('SuperAdmin123!'),
                'status' => 'active',
                'email_verified_at' => now(),
                'entity_type' => SystemOwner::class,
                'entity_id' => $systemOwner->id,
            ]
        );
        $superAdmin->assignSingleRole('system_owner');

        // System Owner Staff - Entity: SystemOwner
        $systemOwnerAdmin = User::updateOrCreate(
            ['email' => 'system-owner@jdp.co.id'],
            [
                'username' => 'system_owner',
                'full_name' => 'System Owner Admin',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
                'entity_type' => SystemOwner::class,
                'entity_id' => $systemOwner->id,
                'created_by' => $superAdmin->id ?? null,
            ]
        );
        $systemOwnerAdmin->assignSingleRole('system_owner_admin');

        $systemOwnerFinance = User::updateOrCreate(
            ['email' => 'system-owner-finance@jdp.co.id'],
            [
                'username' => 'system_owner_finance',
                'full_name' => 'System Owner Finance',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
                'entity_type' => SystemOwner::class,
                'entity_id' => $systemOwner->id,
                'created_by' => $superAdmin->id ?? null,
            ]
        );
        $systemOwnerFinance->assignSingleRole('system_owner_finance');

        $systemOwnerSupport = User::updateOrCreate(
            ['email' => 'system-owner-support@jdp.co.id'],
            [
                'username' => 'system_owner_support',
                'full_name' => 'System Owner Support',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
                'entity_type' => SystemOwner::class,
                'entity_id' => $systemOwner->id,
                'created_by' => $superAdmin->id ?? null,
            ]
        );
        $systemOwnerSupport->assignSingleRole('system_owner_support');

        // Client Admin - Entity: Client
        $clientAdmin = User::updateOrCreate(
            ['email' => 'client@jdp.co.id'],
            [
                'username' => 'client_jdp',
                'full_name' => 'Admin PT Jago Digital Payment',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
                'entity_type' => Client::class,
                'entity_id' => $client->id,
                'created_by' => $systemOwnerAdmin->id ?? null,
            ]
        );
        $clientAdmin->assignSingleRole('client');

        // Head Office Admins - Entity: HeadOffice
        $hoJkt = HeadOffice::where('client_id', $client->id)->where('code', 'HO-JKT')->first();
        if ($hoJkt) {
            $hoAdminJkt = User::updateOrCreate(
                ['email' => 'ho-jakarta@jdp.co.id'],
                [
                    'username' => 'ho_jakarta',
                    'full_name' => 'Admin Head Office Jakarta',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'entity_type' => HeadOffice::class,
                    'entity_id' => $hoJkt->id,
                    'created_by' => $systemOwnerAdmin->id ?? null,
                ]
            );
            $hoAdminJkt->assignSingleRole('head_office');
        }

        $hoSby = HeadOffice::where('client_id', $client->id)->where('code', 'HO-SBY')->first();
        if ($hoSby) {
            $hoAdminSby = User::updateOrCreate(
                ['email' => 'ho-surabaya@jdp.co.id'],
                [
                    'username' => 'ho_surabaya',
                    'full_name' => 'Admin Head Office Surabaya',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'entity_type' => HeadOffice::class,
                    'entity_id' => $hoSby->id,
                    'created_by' => $systemOwnerAdmin->id ?? null,
                ]
            );
            $hoAdminSby->assignSingleRole('head_office');
        }

        $hoBdg = HeadOffice::where('client_id', $client->id)->where('code', 'HO-BDG')->first();
        if ($hoBdg) {
            $hoAdminBdg = User::updateOrCreate(
                ['email' => 'ho-bandung@jdp.co.id'],
                [
                    'username' => 'ho_bandung',
                    'full_name' => 'Admin Head Office Bandung',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'entity_type' => HeadOffice::class,
                    'entity_id' => $hoBdg->id,
                    'created_by' => $systemOwnerAdmin->id ?? null,
                ]
            );
            $hoAdminBdg->assignSingleRole('head_office');
        }

        // Merchant Admins - Entity: Merchant
        $merchant1 = Merchant::where('client_id', $client->id)->where('merchant_code', 'MER-JKT-001')->first();
        if ($merchant1) {
            $merchantAdmin1 = User::updateOrCreate(
                ['email' => 'merchant-001@jdp.co.id'],
                [
                    'username' => 'merchant_001',
                    'full_name' => 'Admin Toko Maju Jaya',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'entity_type' => Merchant::class,
                    'entity_id' => $merchant1->id,
                    'created_by' => $systemOwnerAdmin->id ?? null,
                ]
            );
            $merchantAdmin1->assignSingleRole('merchant');
        }

        $merchant2 = Merchant::where('client_id', $client->id)->where('merchant_code', 'MER-JKT-002')->first();
        if ($merchant2) {
            $merchantAdmin2 = User::updateOrCreate(
                ['email' => 'merchant-002@jdp.co.id'],
                [
                    'username' => 'merchant_002',
                    'full_name' => 'Admin Warung Sejahtera',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'entity_type' => Merchant::class,
                    'entity_id' => $merchant2->id,
                    'created_by' => $systemOwnerAdmin->id ?? null,
                ]
            );
            $merchantAdmin2->assignSingleRole('merchant');
        }

        $merchant3 = Merchant::where('client_id', $client->id)->where('merchant_code', 'MER-SBY-001')->first();
        if ($merchant3) {
            $merchantAdmin3 = User::updateOrCreate(
                ['email' => 'merchant-003@jdp.co.id'],
                [
                    'username' => 'merchant_003',
                    'full_name' => 'Admin Toko Berkah Surabaya',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'entity_type' => Merchant::class,
                    'entity_id' => $merchant3->id,
                    'created_by' => $systemOwnerAdmin->id ?? null,
                ]
            );
            $merchantAdmin3->assignSingleRole('merchant');
        }

        $merchant4 = Merchant::where('client_id', $client->id)->where('merchant_code', 'MER-BDG-001')->first();
        if ($merchant4) {
            $merchantAdmin4 = User::updateOrCreate(
                ['email' => 'merchant-004@jdp.co.id'],
                [
                    'username' => 'merchant_004',
                    'full_name' => 'Admin Toko Jaya Bandung',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'entity_type' => Merchant::class,
                    'entity_id' => $merchant4->id,
                    'created_by' => $systemOwnerAdmin->id ?? null,
                ]
            );
            $merchantAdmin4->assignSingleRole('merchant');
        }
    }
}
