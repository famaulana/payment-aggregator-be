<?php

return [

    /*
    |--------------------------------------------------------------------------
    | General Success
    |--------------------------------------------------------------------------
    */
    'success' => 'Permintaan berhasil diproses',
    'resource_created' => 'Resource berhasil dibuat',
    'resource_updated' => 'Resource berhasil diperbarui',
    'resource_deleted' => 'Resource berhasil dihapus',
    'request_accepted' => 'Permintaan diterima',
    'no_content' => 'Tidak ada konten',

    /*
    |--------------------------------------------------------------------------
    | Validation & Input
    |--------------------------------------------------------------------------
    */
    'validation_error' => 'Terjadi kesalahan validasi',
    'validation_failed' => 'Validasi gagal',
    'invalid_input' => 'Input tidak valid',
    'required_field' => 'Field wajib belum diisi',
    'invalid_format' => 'Format data tidak valid',
    'duplicate_entry' => 'Data duplikat terdeteksi',

    /*
    |--------------------------------------------------------------------------
    | Authentication & Authorization
    |--------------------------------------------------------------------------
    */
    'unauthorized' => 'Akses tidak diizinkan',
    'auth_failed' => 'Autentikasi gagal',
    'forbidden' => 'Akses ditolak',
    'session_expired' => 'Sesi telah berakhir',

    'token_expired' => 'Token telah kedaluwarsa',
    'token_invalid' => 'Token tidak valid',

    /*
    |--------------------------------------------------------------------------
    | API Key & Signature
    |--------------------------------------------------------------------------
    */
    'api_key_required' => 'API key wajib disertakan',
    'invalid_api_key' => 'API key tidak valid',
    'invalid_api_secret' => 'API secret tidak valid',
    'api_key_revoked' => 'API key telah dicabut',
    'api_key_expired' => 'API key telah kedaluwarsa',
    'ip_not_allowed' => 'Alamat IP tidak diizinkan',
    'invalid_signature' => 'Signature permintaan tidak valid',
    'request_expired' => 'Permintaan telah kedaluwarsa',
    'invalid_timestamp' => 'Timestamp tidak valid',

    /*
    |--------------------------------------------------------------------------
    | Resource Not Found
    |--------------------------------------------------------------------------
    */
    'not_found' => 'Data tidak ditemukan',
    'resource_not_found' => 'Resource yang diminta tidak ditemukan',
    'endpoint_not_found' => 'Endpoint tidak ditemukan',

    'user_not_found' => 'Pengguna tidak ditemukan',
    'client_not_found' => 'Klien tidak ditemukan',
    'transaction_not_found' => 'Transaksi tidak ditemukan',
    'merchant_not_found' => 'Merchant tidak ditemukan',
    'api_key_not_found' => 'API key tidak ditemukan',
    'audit_log_not_found' => 'Audit log tidak ditemukan',

    /*
    |--------------------------------------------------------------------------
    | API Key Management (Dashboard)
    |--------------------------------------------------------------------------
    */
    'api_keys_retrieved' => 'API key berhasil diambil',
    'api_keys_retrieve_error' => 'Gagal mengambil API key',

    'api_key_created' => 'API key berhasil dibuat',
    'api_key_create_error' => 'Gagal membuat API key',

    'api_key_retrieved' => 'API key berhasil diambil',
    'api_key_updated' => 'API key berhasil diperbarui',
    'api_key_update_error' => 'Gagal memperbarui API key',

    'api_key_revoked' => 'API key berhasil dicabut',
    'api_key_revoke_error' => 'Gagal mencabut API key',

    'api_secret_regenerated' => 'API secret berhasil diperbarui',
    'api_secret_regenerate_error' => 'Gagal memperbarui API secret',

    'api_key_status_toggled' => 'Status API key berhasil diperbarui',
    'api_key_toggle_error' => 'Gagal memperbarui status API key',

    'client_api_keys_retrieved' => 'API key klien berhasil diambil',

    /*
    |--------------------------------------------------------------------------
    | Audit Logs
    |--------------------------------------------------------------------------
    */
    'audit_logs_retrieved' => 'Audit log berhasil diambil',
    'audit_logs_error' => 'Gagal mengambil audit log',
    'audit_log_retrieved' => 'Audit log berhasil diambil',

    /*
    |--------------------------------------------------------------------------
    | Payment & Transaction
    |--------------------------------------------------------------------------
    */
    'insufficient_balance' => 'Saldo tidak mencukupi',
    'invalid_payment_method' => 'Metode pembayaran tidak valid',
    'payment_failed' => 'Pembayaran gagal',
    'payment_expired' => 'Pembayaran telah kedaluwarsa',
    'payment_pending' => 'Pembayaran sedang diproses',

    'transaction_failed' => 'Transaksi gagal',
    'transaction_expired' => 'Transaksi telah kedaluwarsa',
    'duplicate_transaction' => 'Transaksi duplikat terdeteksi',

    'invalid_amount' => 'Jumlah tidak valid',
    'invalid_currency' => 'Mata uang tidak valid',

    /*
    |--------------------------------------------------------------------------
    | Client & Merchant Status
    |--------------------------------------------------------------------------
    */
    'client_kyb_pending' => 'Verifikasi KYB klien sedang diproses',
    'client_kyb_rejected' => 'Verifikasi KYB klien ditolak',
    'client_suspended' => 'Akun klien ditangguhkan',
    'merchant_suspended' => 'Akun merchant ditangguhkan',

    /*
    |--------------------------------------------------------------------------
    | Settlement & Reconciliation
    |--------------------------------------------------------------------------
    */
    'settlement_failed' => 'Settlement gagal',
    'reconciliation_failed' => 'Rekonsiliasi gagal',

    /*
    |--------------------------------------------------------------------------
    | System Errors
    |--------------------------------------------------------------------------
    */
    'internal_server_error' => 'Terjadi kesalahan pada server',
    'database_error' => 'Terjadi kesalahan database',
    'external_service_error' => 'Terjadi kesalahan layanan eksternal',
    'payment_gateway_error' => 'Terjadi kesalahan payment gateway',
    'network_error' => 'Terjadi kesalahan jaringan',
    'too_many_requests' => 'Terlalu banyak permintaan',
    'service_unavailable' => 'Layanan tidak tersedia',

    /*
    |--------------------------------------------------------------------------
    | Additional Messages
    |--------------------------------------------------------------------------
    */
    'unauthorized_action' => 'Aksi tidak diizinkan',
    'method_not_allowed' => 'Metode tidak diizinkan',
];
