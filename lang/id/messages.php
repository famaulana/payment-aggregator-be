<?php

return [
    // Umum
    'welcome' => 'Selamat datang di Payment Platform',
    'success' => 'Operasi berhasil diselesaikan',
    'resource_created' => 'Sumber daya berhasil dibuat',
    'request_accepted' => 'Permintaan diterima',
    'no_content' => 'Tidak ada konten',
    'resource_updated' => 'Sumber daya berhasil diperbarui',
    'resource_deleted' => 'Sumber daya berhasil dihapus',
    'error' => 'Terjadi kesalahan',
    'not_found' => 'Sumber daya tidak ditemukan',
    'resource_not_found' => 'Sumber daya tidak ditemukan',
    'endpoint_not_found' => 'Endpoint tidak ditemukan',
    'unauthorized' => 'Akses tidak diperbolehkan',
    'forbidden' => 'Akses dilarang',
    'validation_failed' => 'Validasi gagal',
    'too_many_requests' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
    'method_not_allowed' => 'Metode tidak diizinkan',

    // Validasi
    'validation_error' => 'Validasi gagal',
    'invalid_input' => 'Data input tidak valid',
    'required_field' => 'Field ini wajib diisi',
    'invalid_format' => 'Format tidak valid',
    'duplicate_entry' => 'Entri duplikat ditemukan',
    'auth_failed' => 'Autentikasi gagal',
    'token_expired' => 'Token telah kadaluarsa',
    'token_invalid' => 'Token tidak valid',
    'session_expired' => 'Sesi telah kadaluarsa',

    // API Key Errors
    'invalid_api_key' => 'API key tidak valid',
    'invalid_api_secret' => 'API secret tidak valid',
    'api_key_revoked' => 'API key telah dicabut',
    'api_key_expired' => 'API key telah kadaluarsa',
    'api_key_required' => 'API key wajib diisi',
    'ip_not_allowed' => 'Alamat IP tidak diizinkan',
    'invalid_signature' => 'Signature tidak valid',
    'request_expired' => 'Permintaan telah kadaluarsa',
    'invalid_timestamp' => 'Timestamp tidak valid',

    // Autentikasi
    'login_success' => 'Login berhasil',
    'login_failed' => 'Kredensial tidak valid',
    'login_error' => 'Terjadi kesalahan saat login',
    'logout_success' => 'Logout berhasil',
    'logout_error' => 'Terjadi kesalahan saat logout',
    'logout_all_success' => 'Berhasil logout dari semua perangkat',
    'logout_all_error' => 'Gagal logout dari semua perangkat',
    'refresh_success' => 'Token berhasil diperbarui',
    'refresh_failed' => 'Gagal memperbarui token',
    'refresh_error' => 'Terjadi kesalahan saat memperbarui token',
    'profile_retrieved' => 'Profil berhasil diambil',
    'tokens_retrieved' => 'Token berhasil diambil',
    'tokens_error' => 'Gagal mengambil token',
    'api_login_success' => 'API login berhasil',
    'api_login_failed' => 'API login gagal',
    'api_login_error' => 'Terjadi kesalahan saat API login',
    'api_logout_success' => 'API logout berhasil',
    'api_logout_error' => 'Terjadi kesalahan saat API logout',
    'api_profile_retrieved' => 'Profil API berhasil diambil',

    // Pengguna
    'user_created' => 'Pengguna berhasil dibuat',
    'user_updated' => 'Pengguna berhasil diperbarui',
    'user_deleted' => 'Pengguna berhasil dihapus',
    'user_not_found' => 'Pengguna tidak ditemukan',

    // Klien
    'client_created' => 'Klien berhasil dibuat',
    'client_updated' => 'Klien berhasil diperbarui',
    'client_deleted' => 'Klien berhasil dihapus',
    'client_not_found' => 'Klien tidak ditemukan',
    'client_kyb_pending' => 'KYB Klien menunggu persetujuan',
    'client_kyb_approved' => 'KYB Klien disetujui',
    'client_kyb_rejected' => 'KYB Klien ditolak',

    // Transaksi
    'transaction_created' => 'Transaksi berhasil dibuat',
    'transaction_paid' => 'Transaksi berhasil dibayar',
    'transaction_failed' => 'Transaksi gagal',
    'transaction_expired' => 'Transaksi telah kadaluarsa',
    'transaction_refunded' => 'Transaksi berhasil direfund',
    'transaction_not_found' => 'Transaksi tidak ditemukan',
    'merchant_not_found' => 'Merchant tidak ditemukan',
    'duplicate_transaction' => 'Transaksi duplikat',
    'invalid_amount' => 'Jumlah tidak valid',
    'invalid_currency' => 'Mata uang tidak valid',
    'client_suspended' => 'Akun klien disuspend',
    'merchant_suspended' => 'Akun merchant disuspend',
    'reconciliation_failed' => 'Rekonsiliasi gagal',

    // Pembayaran
    'payment_pending' => 'Pembayaran tertunda',
    'payment_success' => 'Pembayaran berhasil',
    'payment_failed' => 'Pembayaran gagal',
    'payment_expired' => 'Pembayaran telah kadaluarsa',
    'invalid_payment_method' => 'Metode pembayaran tidak valid',

    // Settlement
    'settlement_created' => 'Batch settlement berhasil dibuat',
    'settlement_completed' => 'Settlement selesai',
    'settlement_failed' => 'Settlement gagal',
    'insufficient_balance' => 'Saldo tidak mencukupi',

    // API Key Management
    'api_keys_retrieved' => 'API keys berhasil diambil',
    'api_key_retrieved' => 'API key berhasil diambil',
    'api_key_created' => 'API key berhasil dibuat',
    'api_key_updated' => 'API key berhasil diperbarui',
    'api_key_revoked' => 'API key berhasil dicabut',
    'api_key_status_toggled' => 'Status API key berhasil diubah',
    'api_secret_regenerated' => 'API secret berhasil dibuat ulang',
    'client_api_keys_retrieved' => 'API keys klien berhasil diambil',
    'api_key_not_found' => 'API key tidak ditemukan',
    'api_key_create_error' => 'Gagal membuat API key',
    'api_key_update_error' => 'Gagal memperbarui API key',
    'api_key_revoke_error' => 'Gagal mencabut API key',
    'api_secret_regenerate_error' => 'Gagal membuat ulang API secret',
    'api_key_toggle_error' => 'Gagal mengubah status API key',
    'api_keys_retrieve_error' => 'Gagal mengambil API keys',

    // Audit Logs
    'audit_logs_retrieved' => 'Audit logs berhasil diambil',
    'audit_log_retrieved' => 'Audit log berhasil diambil',
    'audit_log_not_found' => 'Audit log tidak ditemukan',
    'audit_logs_error' => 'Gagal mengambil audit logs',

    // Server Errors
    'internal_server_error' => 'Kesalahan server internal',
    'database_error' => 'Kesalahan database',
    'external_service_error' => 'Kesalahan layanan eksternal',
    'payment_gateway_error' => 'Kesalahan payment gateway',
    'network_error' => 'Kesalahan jaringan',
    'service_unavailable' => 'Layanan tidak tersedia',

    // Validasi Forms
    'client_id_required' => 'Field client ID wajib diisi',
    'key_name_required' => 'Field key name wajib diisi',
    'environment_required' => 'Field environment wajib diisi',
    'environment_invalid' => 'Environment harus dev, staging, atau production',
    'ip_whitelist_invalid' => 'IP whitelist berisi alamat IP tidak valid',
    'key_name_string' => 'Key name harus berupa string',

    // Umum
    'yes' => 'Ya',
    'no' => 'Tidak',
    'active' => 'Aktif',
    'inactive' => 'Tidak Aktif',
    'pending' => 'Tertunda',
    'approved' => 'Disetujui',
    'rejected' => 'Ditolak',
    'suspended' => 'Disuspend',
];
