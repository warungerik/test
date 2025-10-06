<script setup>
import { Head, router } from "@inertiajs/vue3";
import utility from "../../admin/utility/page-title.vue";
import { ref } from "vue";
import axios from "axios";
import { route } from "ziggy-js";
import Swal from "sweetalert2";

const props = defineProps({
    config: Object,
});

const dataDigiflazz = props.config.konfigurasi_ppob;

const form = ref({
    username: dataDigiflazz.username || "",
    api_key: dataDigiflazz.api_key || "",
    secret: dataDigiflazz.secret || "",
    status: dataDigiflazz.status ? "1" : "0",
    auto_add: dataDigiflazz.auto_add ? "1" : "0",
});
const formProfit = ref(JSON.parse(JSON.stringify(props.config.konfigurasi_profit)));

const submit = async () => {
    try {
        const res = await axios.post(route('api.change-ppob'), form.value)
        if (res.data.status) {
            Swal.fire('Gotcha!!', res.data.message, 'success')
        } else {
            Swal.fire('Whoops!!', res.data.message, 'error')

        }
    } catch (err) {
        Swal.fire('Gagal', 'Terjadi kesalahan server', 'error')
    }
};

function loadingState() {
    Swal.fire({
        title: 'Loading',
        html: 'Mohon tunggu sebentar',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });
}

const cekBalance = async () => {
    loadingState();
    try {
        const res = await axios.post(route('api.cek-balance'));
        if (res.data.status) {
            dataDigiflazz.balance = res.data.balance
            Swal.fire('Gotcha!!', res.data.message, 'success')
        } else {
            Swal.fire('Whoops!!', res.data.message, 'error')
        }
    } catch (err) {
        Swal.fire('Whoops!!', 'Terjadi kesalahan system', 'error')

    }
}
const submitProfit = async () => {
    try {
        const res = await axios.post(route('api.change-profit'), {
            data: formProfit.value
        });
        if (res.data.status) {
            Swal.fire('Gotcha!!', res.data.message, 'success');
        } else {
            Swal.fire('Whoops!!', res.data.message, 'error');
        }
    } catch (err) {
        Swal.fire('Gagal', 'Terjadi kesalahan server', 'error');
    }
};

function number_format(number, decimals = 0, dec_point = ",", thousands_sep = ".") {
    number = (number + "").replace(/[^0-9+\-Ee.]/g, "");
    let n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = thousands_sep,
        dec = dec_point,
        s = "",
        toFixedFix = function (n, prec) {
            let k = Math.pow(10, prec);
            return "" + Math.round(n * k) / k;
        };
    s = (prec ? toFixedFix(n, prec) : "" + Math.round(n)).split(".");
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || "").length < prec) {
        s[1] = s[1] || "";
        s[1] += new Array(prec - s[1].length + 1).join("0");
    }
    return s.join(dec);
}
</script>

<template>

    <Head :title="'Konfigurasi Digiflazz'" />
    <utility title="Konfigurasi Digiflazz" />

    <ul class="nav nav-tabs mb-2" id="tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general"
                type="button" role="tab" aria-controls="general" aria-selected="true">
                General
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="custom-profit-tab" data-bs-toggle="tab" data-bs-target="#custom-profit"
                type="button" role="tab" aria-controls="custom-profit" aria-selected="false">
                Custom Profit
            </button>
        </li>
    </ul>
    <div class="card">
        <div class="card-body">

            <div class="tab-content" id="tabsContent">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <form @submit.prevent="submit">
                        <div class="typeprovider digiflazz rounded-3 mb-3">
                            <div class="mb-4 row align-items-center">
                                <label class="col-lg-3 col-form-label fw-semibold">
                                    Username Digiflazz
                                </label>
                                <div class="col-lg-9">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" class="form-control" v-model="form.username"
                                            placeholder="Masukkan Username" />
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4 row align-items-center">
                                <label class="col-lg-3 col-form-label fw-semibold">
                                    Api key Digiflazz
                                </label>
                                <div class="col-lg-9">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-key"></i>
                                        </span>
                                        <input type="text" class="form-control" v-model="form.api_key"
                                            placeholder="Masukkan Api key" />
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4 row align-items-center">
                                <label class="col-lg-3 col-form-label fw-semibold">Sisa Saldo</label>
                                <div class="col-lg-9">
                                    <div class="input-group">
                                        <span class="input-group-text bg-primary">Rp</span>
                                        <input type="text" class="form-control" readonly
                                            :value="dataDigiflazz.balance" />
                                        <button class="btn btn-primary" type="button" @click="cekBalance('digiflazz')">
                                            <i class="fas fa-sync-alt me-1"></i> Cek Saldo
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4 row align-items-center">
                                <label class="col-lg-3 col-form-label fw-semibold">
                                    Secret Digiflazz
                                </label>
                                <div class="col-lg-9">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-info-circle"></i>
                                        </span>
                                        <input type="text" class="form-control" v-model="form.secret"
                                            placeholder="Masukkan Secret" />
                                    </div>
                                </div>
                            </div>

                            <!-- Status Provider -->
                            <div class="mb-4 row align-items-center">
                                <label class="col-lg-3 col-form-label fw-semibold">Status Provider</label>
                                <div class="col-lg-9">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-toggle-on"></i>
                                        </span>
                                        <select class="form-control form-select py-2" v-model="form.status">
                                            <option value="1">Aktif</option>
                                            <option value="0">Nonaktif</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Auto Add -->
                            <div class="mb-4 row align-items-center">
                                <label class="col-lg-3 col-form-label fw-semibold">Auto Add Layanan</label>
                                <div class="col-lg-9">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-toggle-on"></i>
                                        </span>
                                        <select class="form-control form-select py-2" v-model="form.auto_add">
                                            <option value="1">Aktif</option>
                                            <option value="0">Nonaktif</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol submit -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="custom-profit" role="tabpanel" aria-labelledby="custom-profit-tab">

                    <div v-for="(values, keys) in formProfit" :key="keys" class="row">
                        <input type="hidden" :value="values.min">
                        <input type="hidden" :value="values.max">

                        <div class="form-group col-3 col-lg-3 mb-2">
                            <select class="form-control" v-model="values.type">
                                <option value="">Pilih...</option>
                                <option value="percent">%(Persen)</option>
                                <option value="fixed">+(Tambah)</option>
                                <option value="minus">-(Minus)</option>
                            </select>
                        </div>

                        <div class="form-group col-5 col-lg-6">
                            <input type="number" class="form-control" v-model="values.profit">
                        </div>

                        <div class="form-group col-4 col-lg-3">
                            <input type="text" class="form-control"
                                :value="keys == 4 ? `>- Rp ${number_format(values.min, 0, ',', '.')}` : 'Rp ' + number_format(values.min, 0, ',', '.') + ' - Rp ' + number_format(values.max, 0, ',', '.')">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-primary" @click="submitProfit">Simpan</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
