<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');
// $routes->get('/User','Test\UserC::index');
// $routes->get('/User/Make_otp','No_login\UserC::Make_otp');
// $routes->get('/User/Cek_email','No_login\UserC::Cek_email');
// $routes->post('/User/Kirim_otp','No_login\UserC::kirim_otp');
// $routes->get('/User/Kirim_email','No_login\UserC::kirim_email');
// $routes->post('/User','Test\UserC::Create');

$routes->group("No_login",function($rute){
    $rute->post("Sign_up","No_login\UserC::Sign_up");
    $rute->post("Login","No_login\UserC::Login");
    $rute->get("Cek_email","No_login\UserC::Cek_email");
    $rute->get("Cek_ktp","No_login\UserC::Cek_ktp");
    $rute->post('Kirim_otp','No_login\UserC::Kirim_otp');
    $rute->get("/","No_login\UserC::index");
});
$routes->group("User",function($rute){
    $rute->get("Logout/(:alphanum)","No_login\UserC::Logout/$1");
    $rute->get("Cek_login/(:alphanum)","No_login\UserC::Cek_login/$1");
    $rute->group("Rekening",function($rute_2){
        $rute_2->post("Tambah","User\RekeningC::Tambah");
        $rute_2->get("Baca_ktp/(:alphanum)","User\RekeningC::Baca_ktp/$1");
        $rute_2->post("Ubah/(:alphanum)","User\RekeningC::Ubah/$1");
        $rute_2->get("Hapus/(:alphanum)","User\RekeningC::Hapus/$1");
    });
    $rute->group("Permohonan",function($rute_2){
        $rute_2->post("Kirim_revisi_permohonan","User\PermohonanC::Kirim_revisi_permohonan");  
        $rute_2->post("Kirim_permohonan","User\PermohonanC::Kirim_permohonan");
        $rute_2->get("Permohonan_dari_saya/(:alphanum)","User\PermohonanC::Permohonan_dari_saya/$1");
        $rute_2->get("Permohonan_kepada_saya/(:alphanum)","User\PermohonanC::Permohonan_kepada_saya/$1");
        $rute_2->get("Detail_permohonan/(:alphanum)","User\PermohonanC::Detail_permohonan/$1");
        $rute_2->post("ACC_lender/(:alphanum)","User\PermohonanC::ACC_lender/$1");
        $rute_2->post("ACC_borrower/(:alphanum)","User\PermohonanC::ACC_borrower/$1");
    });
    $rute->group("Transaksi",function($rute_2){
        $rute_2->post("Konfirmasi_pinjaman","User\TransaksiC::Konfirmasi_pinjaman");
        $rute_2->get("Jumlah_hutang_berjalan/(:alphanum)","User\TransaksiC::Jumlah_hutang_berjalan/$1");
    });
    $rute->group("Cicilan",function($rute_2){
        $rute_2->post("Kirim_cicilan","User\CicilanC::Kirim_cicilan");
        $rute_2->get("Konfirmasi_cicilan/(:alphanum)","User\CicilanC::Konfirmasi_cicilan/$1");      
    });
    $rute->group("Penawaran",function($rute_2){
        $rute_2->post("Kirim_tawaran","User\PenawaranC::Kirim_tawaran");
        $rute_2->get("Tawaran_dari_saya/(:alphanum)","User\PenawaranC::Tawaran_dari_saya/$1");
        $rute_2->get("Tawaran_diterima/(:alphanum)","User\PenawaranC::Tawaran_diterima/$1");
        $rute_2->get("Tawaran_kepada_saya/(:alphanum)","User\PenawaranC::Tawaran_kepada_saya/$1");
        $rute_2->get("Detail_tawaran/(:alphanum)","User\PenawaranC::Detail_tawaran/$1");
    });
    $rute->group("Data_user",function($rute_2){
        $rute_2->get("Read_email","User\Data_userC::Read_email");
        $rute_2->get("Read_ktp","User\Data_userC::Read_ktp");
    });
    $rute->post("Login","No_login\UserC::Login");
    $rute->post('Kirim_otp','No_login\UserC::Kirim_otp');
    $rute->get("/","No_login\UserC::index");
});
/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
