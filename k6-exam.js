import http from 'k6/http';
import { check, sleep } from 'k6';
import { parseHTML } from 'k6/html';
import exec from 'k6/execution';

const BASE_URL = (__ENV.BASE_URL || 'http://localhost').replace(/\/$/, '');
const EXAM_CODE = __ENV.EXAM_CODE;
const MAX_VUS = Number(__ENV.MAX_VUS || 50);
const RAMP_DURATION = __ENV.RAMP_DURATION || '30s';
const ACCOUNTS = JSON.parse(__ENV.TEST_ACCOUNTS || '[]');
const rampTargets = [10, 25, 50, 100].filter(target => target <= MAX_VUS);

if (!rampTargets.includes(MAX_VUS)) rampTargets.push(MAX_VUS);
if (!EXAM_CODE) throw new Error('EXAM_CODE wajib diisi.');
if (ACCOUNTS.length < MAX_VUS) throw new Error(`TEST_ACCOUNTS harus berisi minimal ${MAX_VUS} akun peserta unik.`);

export const options = {
    scenarios: {
        simultaneous_submit: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                ...rampTargets.map(target => ({ duration: RAMP_DURATION, target })),
                { duration: RAMP_DURATION, target: 0 },
            ],
            gracefulRampDown: '10s',
        },
    },
    thresholds: {
        checks: ['rate>0.99'],
        http_req_failed: ['rate<0.01'],
        'http_req_duration{endpoint:submit}': ['p(95)<2000', 'p(99)<5000'],
    },
};

export default function () {
    const account = ACCOUNTS[exec.vu.idInTest - 1];

    const loginPage = http.get(`${BASE_URL}/`, { tags: { endpoint: 'login_page' } });
    const loginToken = parseHTML(loginPage.body).find('input[name="_token"]').first().attr('value');
    if (!check(loginPage, {
        'halaman login terbuka': response => response.status === 200,
        'token login tersedia': () => Boolean(loginToken),
    })) return;

    const login = http.post(`${BASE_URL}/login`, {
        _token: loginToken,
        email: account.email,
        password: account.password,
    }, { tags: { endpoint: 'login' } });
    if (!check(login, { 'berhasil login': response => response.status === 200 })) return;

    const terms = http.get(`${BASE_URL}/exam/${EXAM_CODE}/terms`, { tags: { endpoint: 'terms' } });
    const csrfToken = parseHTML(terms.body).find('input[name="_token"]').first().attr('value');
    if (!check(terms, {
        'halaman terms terbuka': response => response.status === 200,
        'token ujian tersedia': () => Boolean(csrfToken),
    })) return;

    const agree = http.post(`${BASE_URL}/exam/${EXAM_CODE}/agree`, {
        _token: csrfToken,
        agree_terms: '1',
    }, { tags: { endpoint: 'agree' } });
    if (!check(agree, { 'berhasil menyetujui terms': response => response.status === 200 })) return;

    const start = http.post(`${BASE_URL}/exam/${EXAM_CODE}/category/${account.category_id}/start`, {
        _token: csrfToken,
    }, { tags: { endpoint: 'start_category' } });
    if (!check(start, { 'berhasil memulai kategori': response => response.status === 200 })) return;

    const payloadAnswers = Object.fromEntries(
        Object.entries(account.answers || {}).map(([questionId, answer]) => [
            questionId,
            { answer, is_doubtful: false },
        ])
    );
    const submitAt = Number(__ENV.SUBMIT_AT || 0);
    const waitSeconds = submitAt > 0 ? submitAt - (Date.now() / 1000) : Number(__ENV.THINK_TIME || 3);
    if (waitSeconds > 0) sleep(waitSeconds);

    const submit = http.post(
        `${BASE_URL}/exam/${EXAM_CODE}/category/${account.category_id}/submit`,
        JSON.stringify({ answers: payloadAnswers, finish_category: true }),
        {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            tags: { endpoint: 'submit' },
        }
    );

    check(submit, {
        'submit HTTP 200': response => response.status === 200,
        'submit dikonfirmasi server': response => {
            try {
                return response.json('status') === 'success';
            } catch (error) {
                return false;
            }
        },
    });

    // Satu akun hanya boleh dipakai oleh satu VU dan satu submit dalam test ini.
    sleep(Number(__ENV.HOLD_SECONDS || 180));
}
