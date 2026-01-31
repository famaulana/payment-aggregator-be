<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\HeadOffice;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::where('client_code', 'JDP001')->first();

        if (!$client) {
            return;
        }

        // System Owner - Has client_id (JDP as partner)
        $systemOwner = User::updateOrCreate(
            ['email' => 'system-owner@jdp.co.id'],
            [
                'username' => 'system_owner',
                'full_name' => 'System Owner Admin',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
                'client_id' => $client->id,
                'head_office_id' => null,
                'merchant_id' => null,
            ]
        );
        $systemOwner->assignSingleRole('system_owner');

        // Client Admin - Has client_id only
        $clientAdmin = User::updateOrCreate(
            ['email' => 'client@jdp.co.id'],
            [
                'username' => 'client_jdp',
                'full_name' => 'Admin PT Jago Digital Payment',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
                'client_id' => $client->id,
                'head_office_id' => null,
                'merchant_id' => null,
                'created_by' => $systemOwner->id ?? null,
            ]
        );
        $clientAdmin->assignSingleRole('client');

        // Head Office Admins - Has client_id + head_office_id
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
                    'client_id' => $client->id,
                    'head_office_id' => $hoJkt->id,
                    'merchant_id' => null,
                    'created_by' => $systemOwner->id ?? null,
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
                    'client_id' => $client->id,
                    'head_office_id' => $hoSby->id,
                    'merchant_id' => null,
                    'created_by' => $systemOwner->id ?? null,
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
                    'client_id' => $client->id,
                    'head_office_id' => $hoBdg->id,
                    'merchant_id' => null,
                    'created_by' => $systemOwner->id ?? null,
                ]
            );
            $hoAdminBdg->assignSingleRole('head_office');
        }

        // Merchant Admins - Has client_id + head_office_id + merchant_id
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
                    'client_id' => $client->id,
                    'head_office_id' => $merchant1->head_office_id,
                    'merchant_id' => $merchant1->id,
                    'created_by' => $systemOwner->id ?? null,
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
                    'client_id' => $client->id,
                    'head_office_id' => $merchant2->head_office_id,
                    'merchant_id' => $merchant2->id,
                    'created_by' => $systemOwner->id ?? null,
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
                    'client_id' => $client->id,
                    'head_office_id' => $merchant3->head_office_id,
                    'merchant_id' => $merchant3->id,
                    'created_by' => $systemOwner->id ?? null,
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
                    'client_id' => $client->id,
                    'head_office_id' => $merchant4->head_office_id,
                    'merchant_id' => $merchant4->id,
                    'created_by' => $systemOwner->id ?? null,
                ]
            );
            $merchantAdmin4->assignSingleRole('merchant');
        }
    }
}
