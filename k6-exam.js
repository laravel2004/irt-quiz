import http from 'k6/http';
import { check, sleep } from 'k6';
import { parseHTML } from 'k6/html';
import exec from 'k6/execution';

export const options = {
    vus: 100,          // Jumlah virtual user (siswa) yang akan testing bersamaan
    iterations: 100,   // Jumlah iterasi total (masing-masing VU akan jalan 1x)
};

const BASE_URL = 'https://exam.tampilku.id';

// ==========================================
// KONFIGURASI DATA (Sesuaikan dengan DB Anda)
// ==========================================
const EMAIL = 'peserta1@gmail.com';
const PASSWORD = 'password';
const EXAM_CODE = 'S0RM4WOA'; // Ganti dengan 'code' dari tabel exam_sessions
const CATEGORY_ID = '78';     // Ganti dengan ID dari tabel exam_session_categories

export default function () {
    let currentEmail = EMAIL;

    // 1. Buka Halaman Login untuk mendapatkan CSRF Token
    let loginPageRes = http.get(`${BASE_URL}/`);
    check(loginPageRes, { 'halaman login terbuka': (r) => r.status === 200 });

    let doc = parseHTML(loginPageRes.body);
    let csrfToken = doc.find('input[name="_token"]').first().attr('value');

    // 2. Lakukan Login
    let loginRes = http.post(`${BASE_URL}/login`, {
        _token: csrfToken,
        email: currentEmail,
        password: PASSWORD,
    });
    // k6 otomatis mengikuti redirect, jadi status akhirnya harusnya 200 (Dashboard)
    check(loginRes, { 'berhasil login': (r) => r.status === 200 });

    // 3. Masuk ke halaman Persetujuan Ujian (Terms)
    let termsRes = http.get(`${BASE_URL}/exam/${EXAM_CODE}/terms`);
    check(termsRes, { 'halaman terms terbuka': (r) => r.status === 200 });

    doc = parseHTML(termsRes.body);
    let termsToken = doc.find('input[name="_token"]').first().attr('value') || csrfToken;

    // 4. Setuju dengan persetujuan (Agree)
    let agreeRes = http.post(`${BASE_URL}/exam/${EXAM_CODE}/agree`, {
        _token: termsToken,
        agree_terms: '1', // Checkbox disetujui
    });
    check(agreeRes, { 'berhasil setuju terms': (r) => r.status === 200 });

    // 5. Mulai Kategori Mata Pelajaran
    let startCatRes = http.post(`${BASE_URL}/exam/${EXAM_CODE}/category/${CATEGORY_ID}/start`, {
        _token: termsToken,
    });
    check(startCatRes, { 'berhasil mulai kategori': (r) => r.status === 200 });

    // Simulasi siswa membaca dan mengerjakan soal
    sleep(3);

    // 6. Submit Jawaban (Simulasi)
    // Di sini kita mengirim jawaban untuk ID soal tertentu. 
    // Jika tidak tahu ID spesifik, kita bisa saja mengirim dummy payload, backend akan skip soal yang tidak valid.
    let submitPayload = {
        _token: termsToken,
        'answers[1]': 'A',  // Contoh: Soal ID 1 jawab 'A'
        'answers[2]': 'B',  // Contoh: Soal ID 2 jawab 'B'
        'finish_category': '1' // Flag untuk menyelesaikan mapel ini
    };

    let submitRes = http.post(`${BASE_URL}/exam/${EXAM_CODE}/category/${CATEGORY_ID}/submit`, submitPayload, {
        headers: {
            'Accept': 'application/json',
        }
    });
    check(submitRes, { 'berhasil submit jawaban': (r) => r.status === 200 });

    // 7. Selesaikan Sesi Ujian Keseluruhan
    let finishRes = http.post(`${BASE_URL}/exam/${EXAM_CODE}/finish`, {
        _token: termsToken,
    }, {
        headers: {
            'Accept': 'application/json',
        }
    });
    check(finishRes, { 'berhasil menyelesaikan ujian': (r) => r.status === 200 });

    sleep(1);
}
