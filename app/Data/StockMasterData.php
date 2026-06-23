<?php

namespace App\Data;

/**
 * Single source of truth for the stocks master seed list.
 *
 * HOW TO ADD A NEW STOCK
 * ──────────────────────
 * 1. Add a row to the array below (copy any existing row as a template).
 * 2. Call  POST /api/stocks/sync  — it upserts by ISIN, so only new rows are inserted.
 *    Nothing already in the DB is deleted.
 *
 * ISIN NOTE
 * ─────────
 * ISINs here are best-effort for development. In production, load the official
 * NSE/BSE CSV (available at nseindia.com / bseindia.com) and run the sync endpoint.
 * The ISIN is the unique key — company_name / symbols are updated on each sync.
 *
 * COLUMNS
 * ───────
 * isin          — unique identifier across NSE + BSE (required)
 * company_name  — full legal name
 * nse_symbol    — NSE ticker (null if not listed there)
 * bse_symbol    — BSE ticker (usually same as NSE; null if not listed there)
 * bse_code      — BSE numeric scrip code (null if not listed on BSE)
 * sector        — broad sector: IT / Banking / FMCG / Energy / Finance / Consumer …
 * industry      — specific industry within sector
 */
class StockMasterData
{
    public static function all(): array
    {
        return [

            // ── IT / Software ────────────────────────────────────────────────
            ['isin' => 'INE009A01021', 'company_name' => 'Infosys Ltd',                          'nse_symbol' => 'INFY',         'bse_symbol' => 'INFY',         'bse_code' => '500209', 'sector' => 'IT',               'industry' => 'Software'],
            ['isin' => 'INE467B01029', 'company_name' => 'Tata Consultancy Services Ltd',        'nse_symbol' => 'TCS',          'bse_symbol' => 'TCS',          'bse_code' => '532540', 'sector' => 'IT',               'industry' => 'Software'],
            ['isin' => 'INE002A01018', 'company_name' => 'Wipro Ltd',                            'nse_symbol' => 'WIPRO',        'bse_symbol' => 'WIPRO',        'bse_code' => '507685', 'sector' => 'IT',               'industry' => 'Software'],
            ['isin' => 'INE116L01010', 'company_name' => 'Kwality Ltd',                             'nse_symbol' => 'KWALITY',      'bse_symbol' => 'KWALITY',      'bse_code' => '531882', 'sector' => 'FMCG',            'industry' => 'Dairy & Ice Cream'],

            // ── Banking ──────────────────────────────────────────────────────
            ['isin' => 'INE040A01034', 'company_name' => 'HDFC Bank Ltd',                        'nse_symbol' => 'HDFCBANK',     'bse_symbol' => 'HDFCBANK',     'bse_code' => '500180', 'sector' => 'Banking',          'industry' => 'Private Banks'],
            ['isin' => 'INE062A01020', 'company_name' => 'State Bank of India',                  'nse_symbol' => 'SBIN',         'bse_symbol' => 'SBIN',         'bse_code' => '500112', 'sector' => 'Banking',          'industry' => 'Public Sector Banks'],
            ['isin' => 'INE090A01021', 'company_name' => 'ICICI Bank Ltd',                       'nse_symbol' => 'ICICIBANK',    'bse_symbol' => 'ICICIBANK',    'bse_code' => '532174', 'sector' => 'Banking',          'industry' => 'Private Banks'],
            ['isin' => 'INE160A01022', 'company_name' => 'Punjab National Bank',                 'nse_symbol' => 'PNB',          'bse_symbol' => 'PNB',          'bse_code' => '532461', 'sector' => 'Banking',          'industry' => 'Public Sector Banks'],
            ['isin' => 'INE528G01035', 'company_name' => 'Yes Bank Ltd',                         'nse_symbol' => 'YESBANK',      'bse_symbol' => 'YESBANK',      'bse_code' => '532648', 'sector' => 'Banking',          'industry' => 'Private Banks'],

            // ── Finance / Capital Markets ─────────────────────────────────────
            ['isin' => 'INE001A01036', 'company_name' => 'Bajaj Finance Ltd',                    'nse_symbol' => 'BAJFINANCE',   'bse_symbol' => 'BAJFINANCE',   'bse_code' => '500034', 'sector' => 'Finance',          'industry' => 'Diversified NBFCs'],
            ['isin' => 'INE736A01011', 'company_name' => 'Central Depository Services (India) Ltd', 'nse_symbol' => 'CDSL',     'bse_symbol' => 'CDSL',         'bse_code' => '543341', 'sector' => 'Finance',          'industry' => 'Capital Markets'],
            ['isin' => 'INE202N01016', 'company_name' => 'National Securities Depository Ltd',   'nse_symbol' => 'NSDL',         'bse_symbol' => 'NSDL',         'bse_code' => '544316', 'sector' => 'Finance',          'industry' => 'Capital Markets'],

            // ── Insurance ────────────────────────────────────────────────────
            ['isin' => 'INE0J1Y01017', 'company_name' => 'Life Insurance Corporation of India',  'nse_symbol' => 'LICI',         'bse_symbol' => 'LICI',         'bse_code' => '543526', 'sector' => 'Insurance',        'industry' => 'Life Insurance'],
            ['isin' => 'INE58Y01019',  'company_name' => 'Star Health and Allied Insurance Co Ltd', 'nse_symbol' => 'STARHEALTH', 'bse_symbol' => 'STARHEALTH',  'bse_code' => '543412', 'sector' => 'Insurance',        'industry' => 'Health Insurance'],

            // ── FMCG ─────────────────────────────────────────────────────────
            ['isin' => 'INE030A01027', 'company_name' => 'ITC Ltd',                              'nse_symbol' => 'ITC',          'bse_symbol' => 'ITC',          'bse_code' => '500875', 'sector' => 'FMCG',             'industry' => 'Cigarettes & FMCG'],
            ['isin' => 'INE030A01011', 'company_name' => 'Hindustan Unilever Ltd',               'nse_symbol' => 'HINDUNILVR',   'bse_symbol' => 'HINDUNILVR',   'bse_code' => '500696', 'sector' => 'FMCG',             'industry' => 'Household & Personal Products'],

            // ── Energy / Oil & Gas ───────────────────────────────────────────
            ['isin' => 'INE585B01010', 'company_name' => 'Reliance Industries Ltd',              'nse_symbol' => 'RELIANCE',     'bse_symbol' => 'RELIANCE',     'bse_code' => '500325', 'sector' => 'Energy',           'industry' => 'Integrated Oil & Gas'],
            ['isin' => 'INE242A01010', 'company_name' => 'Indian Oil Corporation Ltd',           'nse_symbol' => 'IOC',          'bse_symbol' => 'IOC',          'bse_code' => '530965', 'sector' => 'Energy',           'industry' => 'Oil Refining & Marketing'],
            ['isin' => 'INE172A01027', 'company_name' => 'Castrol India Ltd',                    'nse_symbol' => 'CASTROLIND',   'bse_symbol' => 'CASTROLIND',   'bse_code' => '500870', 'sector' => 'Energy',           'industry' => 'Lubricants'],

            // ── Consumer / Quick Commerce ────────────────────────────────────
            ['isin' => 'INE758T01015', 'company_name' => 'Eternal Ltd',                          'nse_symbol' => 'ETERNAL',      'bse_symbol' => 'ETERNAL',      'bse_code' => '543320', 'sector' => 'Consumer',         'industry' => 'Quick Commerce'],
            // ↑ Formerly Zomato Ltd — renamed Eternal Ltd in 2025; update ISIN / symbol if NSE changes it
            ['isin' => 'INE211B01039', 'company_name' => 'Asian Paints Ltd',                     'nse_symbol' => 'ASIANPAINT',   'bse_symbol' => 'ASIANPAINT',   'bse_code' => '500820', 'sector' => 'Consumer',         'industry' => 'Paints'],

            // ── Auto ─────────────────────────────────────────────────────────
            ['isin' => 'INE208A01029', 'company_name' => 'Ashok Leyland Ltd',                    'nse_symbol' => 'ASHOKLEY',     'bse_symbol' => 'ASHOKLEY',     'bse_code' => '500477', 'sector' => 'Auto',             'industry' => 'Commercial Vehicles'],

            // ── EV / New Energy ──────────────────────────────────────────────
            ['isin' => 'INE0M5201028', 'company_name' => 'Ola Electric Mobility Ltd',            'nse_symbol' => 'OLAELEC',      'bse_symbol' => 'OLAELEC',      'bse_code' => '544225', 'sector' => 'EV',               'industry' => 'Electric Vehicles'],

            // ── Cement ───────────────────────────────────────────────────────
            ['isin' => 'INE079A01024', 'company_name' => 'Ambuja Cements Ltd (Adani)',           'nse_symbol' => 'AMBUJACEM',    'bse_symbol' => 'AMBUJACEM',    'bse_code' => '500425', 'sector' => 'Cement',           'industry' => 'Cement'],
            // ↑ "adani cements" — Ambuja is the main Adani-group cement entity on NSE

            // ── Metals & Mining ──────────────────────────────────────────────
            ['isin' => 'INE205A01025', 'company_name' => 'Vedanta Ltd',                          'nse_symbol' => 'VEDL',         'bse_symbol' => 'VEDL',         'bse_code' => '500295', 'sector' => 'Metals & Mining',  'industry' => 'Diversified Metals'],
            ['isin' => 'INE584A01023', 'company_name' => 'NMDC Ltd',                             'nse_symbol' => 'NMDC',         'bse_symbol' => 'NMDC',         'bse_code' => '526371', 'sector' => 'Metals & Mining',  'industry' => 'Iron Ore Mining'],
            // ↑ "neoon indie edit id" — interpreted as NMDC Ltd; update company_name / isin if incorrect

            // ── Power / Steel ─────────────────────────────────────────────────
            ['isin' => 'INE177H01002', 'company_name' => 'Godawari Power and Ispat Ltd',         'nse_symbol' => 'GPIL',         'bse_symbol' => 'GPIL',         'bse_code' => '532734', 'sector' => 'Power & Steel',    'industry' => 'Integrated Steel'],

            // ── Textiles ─────────────────────────────────────────────────────
            ['isin' => 'INE064C01017', 'company_name' => 'Trident Ltd',                          'nse_symbol' => 'TRIDENT',      'bse_symbol' => 'TRIDENT',      'bse_code' => '521064', 'sector' => 'Textiles',         'industry' => 'Home Textiles'],

            // ── Gaming / Hospitality ──────────────────────────────────────────
            ['isin' => 'INE124I01026', 'company_name' => 'Delta Corp Ltd',                       'nse_symbol' => 'DELTACORP',    'bse_symbol' => 'DELTACORP',    'bse_code' => '532848', 'sector' => 'Gaming',           'industry' => 'Casinos & Gaming'],

            // ── REIT ─────────────────────────────────────────────────────────
            ['isin' => 'INE041700012', 'company_name' => 'Embassy Office Parks REIT',            'nse_symbol' => 'EMBASSY',      'bse_symbol' => 'EMBASSY',      'bse_code' => '542602', 'sector' => 'REIT',             'industry' => 'Office REIT'],

        ];
    }
}
