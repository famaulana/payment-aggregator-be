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
        $systemOwner = User::updateOrCreate(
            ['email' => 'system-owner@pg-lit.test'],
            [
                'username' => 'system_owner',
                'full_name' => 'System Owner Admin',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
                'client_id' => null,
                'head_office_id' => null,
                'merchant_id' => null,
            ]
        );
        $systemOwner->assignSingleRole('system_owner');

        $client1 = Client::where('client_code', 'DPI001')->first();
        if ($client1) {
            $clientAdmin1 = User::updateOrCreate(
                ['email' => 'client@pg-lit.test'],
                [
                    'username' => 'client_dpi',
                    'full_name' => 'Admin PT Digital Payment Indonesia',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'client_id' => $client1->id,
                    'head_office_id' => null,
                    'merchant_id' => null,
                ]
            );
            $clientAdmin1->assignSingleRole('client');
        }

        $client2 = Client::where('client_code', 'RET002')->first();
        if ($client2) {
            $clientAdmin2 = User::updateOrCreate(
                ['email' => 'client2@pg-lit.test'],
                [
                    'username' => 'client_retail',
                    'full_name' => 'Admin PT Retail Nusantara',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'client_id' => $client2->id,
                    'head_office_id' => null,
                    'merchant_id' => null,
                ]
            );
            $clientAdmin2->assignSingleRole('client');
        }

        $headOffice1 = HeadOffice::where('code', 'HO-JKT')->first();
        if ($headOffice1) {
            $hoAdmin1 = User::updateOrCreate(
                ['email' => 'ho@pg-lit.test'],
                [
                    'username' => 'ho_jakarta',
                    'full_name' => 'Admin Head Office Jakarta',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'client_id' => $headOffice1->client_id,
                    'head_office_id' => $headOffice1->id,
                    'merchant_id' => null,
                ]
            );
            $hoAdmin1->assignSingleRole('head_office');
        }

        $headOffice2 = HeadOffice::where('code', 'HO-SBY')->first();
        if ($headOffice2) {
            $hoAdmin2 = User::updateOrCreate(
                ['email' => 'ho-surabaya@pg-lit.test'],
                [
                    'username' => 'ho_surabaya',
                    'full_name' => 'Admin Head Office Surabaya',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'client_id' => $headOffice2->client_id,
                    'head_office_id' => $headOffice2->id,
                    'merchant_id' => null,
                ]
            );
            $hoAdmin2->assignSingleRole('head_office');
        }

        $merchant1 = Merchant::where('merchant_code', 'MER-JKT-001')->first();
        if ($merchant1) {
            $merchantAdmin1 = User::updateOrCreate(
                ['email' => 'merchant@pg-lit.test'],
                [
                    'username' => 'merchant_001',
                    'full_name' => 'Admin Toko Maju Jaya',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'client_id' => $merchant1->client_id,
                    'head_office_id' => $merchant1->head_office_id,
                    'merchant_id' => $merchant1->id,
                ]
            );
            $merchantAdmin1->assignSingleRole('merchant');
        }

        $merchant2 = Merchant::where('merchant_code', 'MER-JKT-002')->first();
        if ($merchant2) {
            $merchantAdmin2 = User::updateOrCreate(
                ['email' => 'merchant2@pg-lit.test'],
                [
                    'username' => 'merchant_002',
                    'full_name' => 'Admin Warung Sejahtera',
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'client_id' => $merchant2->client_id,
                    'head_office_id' => $merchant2->head_office_id,
                    'merchant_id' => $merchant2->id,
                ]
            );
            $merchantAdmin2->assignSingleRole('merchant');
        }
    }
}
