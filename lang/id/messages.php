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
    | User Management (Dashboard)
    |--------------------------------------------------------------------------
    */
    'users_retrieved' => 'Pengguna berhasil diambil',
    'user_retrieved' => 'Pengguna berhasil diambil',
    'user_created' => 'Pengguna berhasil dibuat',
    'user_and_entity_created' => 'Pengguna dan entitas berhasil dibuat',
    'user_updated' => 'Pengguna berhasil diperbarui',
    'user_status_updated' => 'Status pengguna berhasil diperbarui',
    'user_password_reset' => 'Password pengguna berhasil direset',

    'cannot_create_system_owner' => 'Tidak dapat membuat pengguna system owner',
    'cannot_assign_system_owner_role' => 'Tidak dapat memberikan role system owner',
    'system_owner_can_only_create_client_users' => 'System owner hanya dapat membuat pengguna klien',
    'client_can_only_create_head_quarter_or_merchant_users' => 'Klien hanya dapat membuat pengguna head quarter atau merchant',
    'head_quarter_can_only_create_merchant_users' => 'Head quarter hanya dapat membuat pengguna merchant',
    'entity_type_must_be_client' => 'Tipe entitas harus klien',
    'entity_type_must_be_merchant' => 'Tipe entitas harus merchant',
    'head_quarter_must_belong_to_your_client' => 'Head quarter harus milik klien Anda',
    'merchant_must_belong_to_your_client' => 'Merchant harus milik klien Anda',
    'merchant_must_belong_to_your_head_quarter' => 'Merchant harus milik head quarter Anda',
    'invalid_entity_type' => 'Tipe entitas tidak valid',
    'failed_to_create_entity' => 'Gagal membuat entitas',

    /*
    |--------------------------------------------------------------------------
    | Client Management (Dashboard)
    |--------------------------------------------------------------------------
    */
    'clients_retrieved' => 'Klien berhasil diambil',
    'client_retrieved' => 'Klien berhasil diambil',
    'client_created' => 'Klien berhasil dibuat',
    'client_updated' => 'Klien berhasil diperbarui',
    'client_status_updated' => 'Status klien berhasil diperbarui',
    'client_create_error' => 'Gagal membuat klien',
    'client_update_error' => 'Gagal memperbarui klien',

    /*
    |--------------------------------------------------------------------------
    | Head Quarter Management (Dashboard)
    |--------------------------------------------------------------------------
    */
    'head_quarters_retrieved' => 'Head quarter berhasil diambil',
    'head_quarter_retrieved' => 'Head quarter berhasil diambil',
    'head_quarter_created' => 'Head quarter berhasil dibuat',
    'head_quarter_updated' => 'Head quarter berhasil diperbarui',
    'head_quarter_status_updated' => 'Status head quarter berhasil diperbarui',
    'head_quarter_not_found' => 'Head quarter tidak ditemukan',
    'head_quarter_create_error' => 'Gagal membuat head quarter',
    'head_quarter_update_error' => 'Gagal memperbarui head quarter',

    /*
    |--------------------------------------------------------------------------
    | Merchant Management (Dashboard)
    |--------------------------------------------------------------------------
    */
    'merchants_retrieved' => 'Merchant berhasil diambil',
    'merchant_retrieved' => 'Merchant berhasil diambil',
    'merchant_created' => 'Merchant berhasil dibuat',
    'merchant_updated' => 'Merchant berhasil diperbarui',
    'merchant_status_updated' => 'Status merchant berhasil diperbarui',
    'merchant_create_error' => 'Gagal membuat merchant',
    'merchant_update_error' => 'Gagal memperbarui merchant',

    /*
    |--------------------------------------------------------------------------
    | Location (Dashboard)
    |--------------------------------------------------------------------------
    */
    'provinces_retrieved' => 'Provinsi berhasil diambil',
    'cities_retrieved' => 'Kota berhasil diambil',
    'districts_retrieved' => 'Kecamatan berhasil diambil',
    'sub_districts_retrieved' => 'Kelurahan berhasil diambil',

    /*
    |--------------------------------------------------------------------------
    | Additional Messages
    |--------------------------------------------------------------------------
    */
    'unauthorized_action' => 'Aksi tidak diizinkan',
    'method_not_allowed' => 'Metode tidak diizinkan',
];
