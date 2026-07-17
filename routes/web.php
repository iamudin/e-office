<?php

/*
|--------------------------------------------------------------------------
| E-Arsip Plugin Routes
|--------------------------------------------------------------------------
| Registered via add_plugin_public_route() for dual-domain support
| (main domain: /e-office/... , custom domain: /...)
*/

// Load helpers
require_once __DIR__ . '/../Helpers/functions.php';

// ─── Namespace shortcuts ───
$authCtrl = 'App\Http\Controllers\Plugins\Earsip\Auth\LoginController';
$dashCtrl = 'App\Http\Controllers\Plugins\Earsip\Admin\DashboardController';
$suratCtrl = 'App\Http\Controllers\Plugins\Earsip\Admin\SuratMasukController';
$pejabatCtrl = 'App\Http\Controllers\Plugins\Earsip\Admin\PejabatController';

// ─── Auth Routes ───
add_plugin_public_route(['name' => 'home', 'method' => 'GET', 'path' => '/', 'controller' => $authCtrl, 'function' => 'showLogin']);
add_plugin_public_route(['name' => 'login', 'method' => 'GET', 'path' => '/login', 'controller' => $authCtrl, 'function' => 'showLogin']);
add_plugin_public_route(['name' => 'login.submit', 'method' => 'POST', 'path' => '/login', 'controller' => $authCtrl, 'function' => 'login']);
add_plugin_public_route(['name' => 'logout', 'method' => 'POST', 'path' => '/logout', 'controller' => $authCtrl, 'function' => 'logout']);

// ─── Dashboard ───
add_plugin_public_route(['name' => 'dashboard', 'method' => 'GET', 'path' => '/dashboard', 'controller' => $dashCtrl, 'function' => 'index']);

// ─── Surat Masuk ───
add_plugin_public_route(['name' => 'earsip.surat-masuk.index', 'method' => 'GET', 'path' => '/surat-masuk', 'controller' => $suratCtrl, 'function' => 'index']);
add_plugin_public_route(['name' => 'earsip.surat-masuk.index.selesai', 'method' => 'GET', 'path' => '/surat-masuk/selesai', 'controller' => $suratCtrl, 'function' => 'index']);
add_plugin_public_route(['name' => 'earsip.surat-masuk.create', 'method' => 'GET', 'path' => '/surat-masuk/create', 'controller' => $suratCtrl, 'function' => 'create']);
add_plugin_public_route(['name' => 'earsip.surat-masuk.store', 'method' => 'POST', 'path' => '/surat-masuk', 'controller' => $suratCtrl, 'function' => 'store']);
add_plugin_public_route(['name' => 'earsip.surat-masuk.show', 'method' => 'GET', 'path' => '/surat-masuk/{surat}/show', 'controller' => $suratCtrl, 'function' => 'show']);
add_plugin_public_route(['name' => 'earsip.surat-masuk.edit', 'method' => 'GET', 'path' => '/surat-masuk/{surat_masuk}/edit', 'controller' => $suratCtrl, 'function' => 'edit']);
add_plugin_public_route(['name' => 'earsip.surat-masuk.update', 'method' => 'PUT', 'path' => '/surat-masuk/{surat_masuk}', 'controller' => $suratCtrl, 'function' => 'update']);
add_plugin_public_route(['name' => 'earsip.surat-masuk.destroy', 'method' => 'GET', 'path' => '/surat-masuk/{arsip}/delete', 'controller' => $suratCtrl, 'function' => 'destroy']);
add_plugin_public_route(['name' => 'earsip.surat-masuk.datatable', 'method' => 'POST', 'path' => '/surat-masuk/datatable', 'controller' => $suratCtrl, 'function' => 'datatable']);
add_plugin_public_route(['name' => 'earsip.surat-masuk.riwayat', 'method' => 'POST', 'path' => '/surat-masuk/riwayat', 'controller' => $suratCtrl, 'function' => 'riwayat']);
add_plugin_public_route(['name' => 'earsip.surat-masuk.disposisi', 'method' => 'PUT', 'path' => '/surat-masuk/{arsip}/disposisi', 'controller' => $suratCtrl, 'function' => 'disposisi']);
add_plugin_public_route(['name' => 'earsip.merge.pdf', 'method' => 'POST', 'path' => '/merge-pdf', 'controller' => $suratCtrl, 'function' => 'merge_pdf']);

$briefingCtrl = 'App\Http\Controllers\Plugins\Earsip\Admin\BriefingController';

// ─── Briefing / Tugas ───
add_plugin_public_route(['name' => 'earsip.briefing.index', 'method' => 'GET', 'path' => '/briefing', 'controller' => $briefingCtrl, 'function' => 'index']);
add_plugin_public_route(['name' => 'earsip.briefing.create', 'method' => 'GET', 'path' => '/briefing/create', 'controller' => $briefingCtrl, 'function' => 'create']);
add_plugin_public_route(['name' => 'earsip.briefing.store', 'method' => 'POST', 'path' => '/briefing', 'controller' => $briefingCtrl, 'function' => 'store']);
add_plugin_public_route(['name' => 'earsip.briefing.show', 'method' => 'GET', 'path' => '/briefing/{id}', 'controller' => $briefingCtrl, 'function' => 'show']);
add_plugin_public_route(['name' => 'earsip.briefing.teruskan', 'method' => 'POST', 'path' => '/briefing/{id}/teruskan', 'controller' => $briefingCtrl, 'function' => 'teruskan']);
add_plugin_public_route(['name' => 'earsip.briefing.balas', 'method' => 'POST', 'path' => '/briefing/{id}/balas', 'controller' => $briefingCtrl, 'function' => 'balas']);
add_plugin_public_route(['name' => 'earsip.briefing.feedback', 'method' => 'POST', 'path' => '/briefing/disposisi/{id}/feedback', 'controller' => $briefingCtrl, 'function' => 'feedback']);
add_plugin_public_route(['name' => 'earsip.briefing.validasi', 'method' => 'POST', 'path' => '/briefing/disposisi/{id}/validasi', 'controller' => $briefingCtrl, 'function' => 'validasi']);
add_plugin_public_route(['name' => 'earsip.briefing.close', 'method' => 'POST', 'path' => '/briefing/{id}/close', 'controller' => $briefingCtrl, 'function' => 'close']);

// ─── Pejabat (Admin only) ───
add_route('admin', ['title' => 'Struktur Jabatan', 'name' => 'earsip.struktur-briefing.index', 'icon' => 'fa-sitemap', 'path' => 'e-office/struktur-briefing', 'method' => 'get', 'function' => 'struktur', 'controller' => $pejabatCtrl, 'show_in_sidebar' => true]);
add_route('admin', ['title' => 'Update Struktur', 'name' => 'earsip.struktur-briefing.update', 'icon' => '', 'path' => 'e-office/struktur-briefing', 'method' => 'post', 'function' => 'updateStruktur', 'controller' => $pejabatCtrl, 'show_in_sidebar' => false]);
add_route('admin', ['title' => 'Update Struktur Ajax', 'name' => 'earsip.struktur-briefing.update-ajax', 'icon' => '', 'path' => 'e-office/struktur-briefing/ajax', 'method' => 'post', 'function' => 'updateStrukturAjax', 'controller' => $pejabatCtrl, 'show_in_sidebar' => false]);

add_route('admin', ['title' => 'Get Tupoksi', 'name' => 'earsip.pejabat.tupoksi.get', 'icon' => '', 'path' => 'e-office/pejabat/{pejabatId}/tupoksi', 'method' => 'get', 'function' => 'getTupoksi', 'controller' => $pejabatCtrl, 'show_in_sidebar' => false]);
add_route('admin', ['title' => 'Store Tupoksi', 'name' => 'earsip.pejabat.tupoksi.store', 'icon' => '', 'path' => 'e-office/pejabat/{pejabatId}/tupoksi', 'method' => 'post', 'function' => 'storeTupoksi', 'controller' => $pejabatCtrl, 'show_in_sidebar' => false]);
add_route('admin', ['title' => 'Delete Tupoksi', 'name' => 'earsip.pejabat.tupoksi.delete', 'icon' => '', 'path' => 'e-office/pejabat/tupoksi/{id}', 'method' => 'delete', 'function' => 'deleteTupoksi', 'controller' => $pejabatCtrl, 'show_in_sidebar' => false]);

add_route('admin', ['title' => 'Kelola Pejabat', 'name' => 'earsip.pejabat.index', 'icon' => 'fa-users', 'path' => 'e-office/pejabat', 'method' => 'get', 'function' => 'index', 'controller' => $pejabatCtrl, 'show_in_sidebar' => true]);
add_route('admin', ['title' => 'Tambah Pejabat', 'name' => 'earsip.pejabat.create', 'icon' => '', 'path' => 'e-office/pejabat/create', 'method' => 'get', 'function' => 'create', 'controller' => $pejabatCtrl, 'show_in_sidebar' => false]);
add_route('admin', ['title' => 'Simpan Pejabat', 'name' => 'earsip.pejabat.store', 'icon' => '', 'path' => 'e-office/pejabat', 'method' => 'post', 'function' => 'store', 'controller' => $pejabatCtrl, 'show_in_sidebar' => false]);
add_route('admin', ['title' => 'Edit Pejabat', 'name' => 'earsip.pejabat.edit', 'icon' => '', 'path' => 'e-office/pejabat/{pejabat}/edit', 'method' => 'get', 'function' => 'edit', 'controller' => $pejabatCtrl, 'show_in_sidebar' => false]);
add_route('admin', ['title' => 'Update Pejabat', 'name' => 'earsip.pejabat.update', 'icon' => '', 'path' => 'e-office/pejabat/{pejabat}', 'method' => 'put', 'function' => 'update', 'controller' => $pejabatCtrl, 'show_in_sidebar' => false]);
add_route('admin', ['title' => 'Datatable Pejabat', 'name' => 'earsip.pejabat.datatable', 'icon' => '', 'path' => 'e-office/pejabat/datatable', 'method' => 'post', 'function' => 'datatable', 'controller' => $pejabatCtrl, 'show_in_sidebar' => false]);

// ─── Pengaturan (Admin only) ───
add_route('admin', ['title' => 'Pengaturan WA', 'name' => 'earsip.pengaturan.index', 'icon' => 'fa-cogs', 'path' => 'e-office/pengaturan', 'method' => ['get', 'put'], 'function' => 'pengaturan', 'controller' => $dashCtrl, 'show_in_sidebar' => true]);
