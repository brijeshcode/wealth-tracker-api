<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            ['name' => 'zerodha',      'display_name' => 'Zerodha',                  'type' => 'broker', 'supported_asset_types' => ['stock', 'mf', 'bond', 'sgb', 'reit']],
            ['name' => 'groww',        'display_name' => 'Groww',                    'type' => 'app',    'supported_asset_types' => ['stock', 'mf', 'gold', 'sgb', 'reit']],
            ['name' => 'upstox',       'display_name' => 'Upstox',                   'type' => 'broker', 'supported_asset_types' => ['stock', 'mf', 'bond']],
            ['name' => 'angel_one',    'display_name' => 'Angel One',                'type' => 'broker', 'supported_asset_types' => ['stock', 'mf', 'bond']],
            ['name' => 'sbi',          'display_name' => 'State Bank of India',      'type' => 'bank',   'supported_asset_types' => ['fd', 'rd', 'ppf', 'sgb']],
            ['name' => 'pnb',          'display_name' => 'Punjab National Bank',     'type' => 'bank',   'supported_asset_types' => ['fd', 'rd']],
            ['name' => 'hdfc',         'display_name' => 'HDFC Bank',                'type' => 'bank',   'supported_asset_types' => ['fd', 'rd', 'bond']],
            ['name' => 'icici',        'display_name' => 'ICICI Bank',               'type' => 'bank',   'supported_asset_types' => ['fd', 'rd']],
            ['name' => 'axis',         'display_name' => 'Axis Bank',                'type' => 'bank',   'supported_asset_types' => ['fd', 'rd']],
            ['name' => 'bajaj',        'display_name' => 'Bajaj Finance',            'type' => 'nbfc',   'supported_asset_types' => ['fd', 'rd']],
            ['name' => 'kuvera',       'display_name' => 'Kuvera',                   'type' => 'app',    'supported_asset_types' => ['mf']],
            ['name' => 'coin',         'display_name' => 'Zerodha Coin',             'type' => 'app',    'supported_asset_types' => ['mf']],
            ['name' => 'paytm_money',  'display_name' => 'Paytm Money',              'type' => 'app',    'supported_asset_types' => ['mf']],
            ['name' => 'mmtc_pamp',    'display_name' => 'MMTC-PAMP',               'type' => 'app',    'supported_asset_types' => ['gold']],
            ['name' => 'phonepe',      'display_name' => 'PhonePe',                  'type' => 'app',    'supported_asset_types' => ['gold']],
            ['name' => 'post_office',  'display_name' => 'Post Office',              'type' => 'govt',   'supported_asset_types' => ['ppf', 'sgb']],
            ['name' => 'nsdl',         'display_name' => 'NSDL',                     'type' => 'govt',   'supported_asset_types' => ['nps']],
            ['name' => 'hdfc_pension', 'display_name' => 'HDFC Pension Management',  'type' => 'nbfc',   'supported_asset_types' => ['nps']],
        ];

        foreach ($platforms as $platform) {
            DB::table('platforms')->updateOrInsert(
                ['name' => $platform['name']],
                [
                    'display_name'          => $platform['display_name'],
                    'type'                  => $platform['type'],
                    'supported_asset_types' => json_encode($platform['supported_asset_types']),
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]
            );
        }
    }
}
