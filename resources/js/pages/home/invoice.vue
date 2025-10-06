<script setup>
import { Head, usePage } from "@inertiajs/vue3"
import axios from "axios"
import { reactive, ref, watch } from "vue"
import useClipboard from "vue-clipboard3"
const { toClipboard } = useClipboard()
const props = defineProps({
    app: String,
    config: Array,
    history: Array,
    historyFeedback: Array,
    title: String
})
const days = ref(false)
const rateStar = ref(1)
const message = ref(null)
const feedback = ref(false)
const page = usePage()


async function copyText(text) {
    try {
        await toClipboard(text)
        toastify["success"]?.("Gotcha!", "Teks berhasil disalin!")
    } catch (e) {
        toastify["error"]?.("Whoops!", "Gagal menyalin teks")
    }
}
if (props.historyFeedback?.information.invoice_id == props.history?.invoice_id) {
    feedback.value = true
}
watch(message, (val) => {
    if (val && val.length > 200) {
        message.classList.add("is-invalid")
        message.value = val.slice(0, 200);
    }
});
function number_format(number, decimals = 0, dec_point = ',', thousands_sep = '.') {
    let n = !isFinite(+number) ? 0 : +number;
    let prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);

    let s = n.toFixed(prec).split('.');

    // format ribuan
    s[0] = s[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousands_sep);

    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }

    return s.join(dec_point);
}


function formatDate(dateString) {
    const bulan = [
        "Januari",
        "Februari",
        "Maret",
        "April",
        "Mei",
        "Juni",
        "Juli",
        "Agustus",
        "September",
        "Oktober",
        "November",
        "Desember",
    ];

    const date = new Date(dateString);
    const hari = date.getDate();
    const bulanText = bulan[date.getMonth()];
    const tahun = date.getFullYear();

    const jam = String(date.getHours()).padStart(2, "0");
    const menit = String(date.getMinutes()).padStart(2, "0");

    return `${hari} ${bulanText} ${tahun}, ${jam}:${menit}`;
}
function splitNominal(nominal) {
    let formatted = number_format(nominal, 0, ',', '.');

    // pisah berdasarkan titik terakhir
    let parts = formatted.split(".");
    let depan = parts.slice(0, -1).join(".");
    let belakang = parts[parts.length - 1];

    return { depan, belakang };
}
function hover_star(star) {
    rateStar.value = star
    for (let i = 1; i <= 5; i++) {
        const el = document.getElementById(`star-${i}`);
        if (i <= star) {
            el.classList.remove("bi-star");
            el.classList.add("bi-star-fill", "text-warning");
        } else {
            el.classList.remove("bi-star-fill", "text-warning");
            el.classList.add("bi-star");
        }
    }
}
const submitFeedback = async () => {
    try {
        if (message.length > 200) {
            message.value = message.value.slice(0, 200);
        }
        const res = await axios.post(route('api.submit-feedback', { invoice_id: props.history.invoice_id, message: message.value, star: rateStar.value }))

        if (res.data.status) {
            toastify["success"]?.("Yeay!", res.data.message);
            feedback.value = true
            setTimeout(() => {
                window.location.href = route('home')
            }, 2000);
        } else {
            toastify["error"]?.("Whoops!", res.data.message);
        }
    } catch (error) {
        toastify["error"]?.("Whoops!", 'Terjadi kesalahan, mohon coba lagi nanti.');
    }
};
if (props.history.status == 'pending') {
    let countDownDate = new Date(props.history.expired_at).getTime();
    let x = setInterval(function () {
        let now = new Date().getTime();
        let distance = countDownDate - now;

        if (distance < 0) {
            clearInterval(x);
            document.getElementById("time").innerHTML = "EXPIRED";
            return;
        }

        let dayTime = Math.floor(distance / (1000 * 60 * 60 * 24)).toString().padStart(2, '0');
        let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)).toString().padStart(2, '0');
        let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0');
        let seconds = Math.floor((distance % (1000 * 60)) / 1000).toString().padStart(2, '0');

        if (document.querySelector(".day")) document.querySelector(".day").innerHTML = dayTime;
        if (document.querySelector(".hours")) document.querySelector(".hours").innerHTML = hours;
        if (document.querySelector(".minute")) document.querySelector(".minute").innerHTML = minutes;
        if (document.querySelector(".second")) document.querySelector(".second").innerHTML = seconds;
        days.value = dayTime > 0 ? dayTime.toString().padStart(2, '0') : false
    }, 1000);
}
</script>
<template>

    <Head :title="page.props.title" />
    <div class="container container-track overflow-hidden mb-4 mt-3">
        <div class="row justify-content-center position-relative">
            <div class="col-lg-8 mb-5">
                <div class="cards py-3">
                    <ol class="stepper">
                        <li class="focus" :class="history.status">
                            <i class="bi bi-wallet2"></i>
                            <div class="loader active"></div>
                        </li>
                        <li :class="history.status != 'pending' ? history.status : null">
                            <i class="bi bi-controller"></i>
                            <div class="loader" :class="history.status != 'pending' ? 'active' : null"> </div>
                        </li>
                        <li :class="history.status == 'success' ? history.status : null">
                            <i class=" bi bi-check-circle"></i>
                            <div class="loader" :class="history.status == 'success' ? 'active' : null"></div>
                        </li>
                    </ol>
                    <div class="containerTimeCount flex justify-content-center flex-column align-items-center">
                        <div class="mb-3" v-if="history.status == 'pending'">
                            <h1>Menunggu Pembayaran</h1>
                            <p v-html="config.data.konfigurasi_order['pending']"></p>
                        </div>
                        <div class="mb-3" v-if="history.status == 'failed'">
                            <h1 :style="{ color: 'red' }">Transaksi Gagal</h1>
                            <p v-html="config.data.konfigurasi_order['failed']"></p>
                        </div>
                        <div class="mb-3" v-if="history.status == 'success'">
                            <h1>Transaksi berhasil</h1>
                            <p v-html="config.data.konfigurasi_order['success']"></p>
                        </div>
                        <div class="headerFS" v-if="history.status == 'pending'">
                            <div class="containers">
                                <div class="time" id="time"
                                    v-if="(new Date(history.expired_at).getTime() - new Date().getTime()) > 0">
                                    <template v-if="days">
                                        <div class="day">{{ days }}</div>
                                        <div class="dots">:</div>
                                    </template>
                                    <div class="hours">00</div>
                                    <div class="dots">:</div>
                                    <div class="minute">00</div>
                                    <div class="dots">:</div>
                                    <div class="second">00</div>
                                </div>
                                <span class="text-danger fs-5" v-else>EXPIRED</span>
                            </div>
                        </div>
                    </div>

                    <div class="buyer-notes" v-if="history.seller_notes && history.status == 'success'">
                        <div class="notes-header"><i class="fas fa-user-circle notes-icon"></i>
                            <h3 class="notes-title">Seller Notes</h3>
                        </div>
                        <div class="note-content-box">
                            <span v-html="history.seller_notes.notes"></span>
                            <div class="mt-4">
                                <a :href="d.link" target="_blank" class="btn btn-outline-info btn-sm me-2"
                                    v-for="d in history.seller_notes?.download">
                                    <i class="fas fa-download me-1"></i>
                                    {{ d.title }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center  position-relative">
            <div class="col-lg-4 col-12 receipt-left">
                <div class="ticketBarcode page" v-if="history.status == 'pending'">
                    <!-- <div class="gameNm" style="font-size: 1.3em;">{{ history.product.game }}</div>
                    <div class="diamond">{{ history.product.name_provider }}</div>
                    <div class="price">
                        <span class="text-warning">Rp {{ number_format(history.price, 0, ',', '.') }}</span>
                    </div> -->
                    <div v-if="history.payment.detail.qr_link">
                        <img :src="history.payment.detail.qr_link" :alt="history.product.name_provider" class="barcode"
                            :target="`_blank`">
                        <a :href="history.payment.detail.url" :class="`btnYellowPrimary w-100 mt-2`">Bayar
                            sekarang</a>
                    </div>
                    <div v-else-if="history.payment.provider == 'private'">
                        <br></br>
                        <img :src="`${app}/assets/images/icon/transaksi-pending.svg`" width="300" />
                        <div class="gameNm text-center mt-2 mb-2">Nomor Rekening</div>
                        <div class="diamond text-center">
                            <div class="mb-2">
                                <b class="text-danger">{{ history.payment.number }} </b> <i class="bi bi-clipboard2"
                                    :style="{ cursor: 'pointer' }" @click="copyText(history.payment.number)"></i> <br>
                                Atas Nama : {{ history.payment.name_account }}<br>
                            </div>

                            <div>
                                Total Pembayaran : Rp {{ splitNominal(history.price).depan }}.<span
                                    class="text-success">{{
                                        splitNominal(history.price).belakang }}</span>
                                <i class="bi bi-clipboard2" :style="{ cursor: 'pointer' }"
                                    @click="copyText(history.price.toString())"></i>
                            </div>

                        </div>
                        <div class="desc mt-4">
                            <div class="text text-center">Transfer sesuai jumlah tagihan hingga 3 digit terakhir agar
                                proses
                                verifikasinya lancar</div>
                        </div>

                        <!-- Virtual Account -->

                        <!-- Redirect -->

                        <!-- Panduan Pembayaran -->
                        <div class="pt-4 virtualaccount">
                            <div class="instruction-container">
                                <p class="instruction-content"></p>
                            </div>
                        </div>
                    </div>
                    <div v-else>
                        <img :src="`${app}/assets/images/icon/transaksi-pending.svg`"
                            style="width: 200px; height: 200px;" alt="" class="status">
                        <a :href="history.payment.detail.url" :class="`btnYellowPrimary w-100 mt-2`">Bayar
                            sekarang</a>
                    </div>
                </div>
                <div class="ticketBarcode page" v-else-if="history.status == 'failed' || history.status == 'success'">
                    <br></br>
                    <br></br>
                    <br></br>
                    <img :src="`${app}/assets/images/icon/${history.status == 'failed' ? 'transaksi-failed.png' : 'transaksi-success.svg'}`"
                        width="300" />

                    <button v-if="!feedback && history.status == 'success'" data-bs-toggle="modal"
                        data-bs-target="#rate" class="btnYellowPrimary w-100 mt-3">Kirim
                        Feedback</button>
                    <br>
                    <div class="modal fade" id="rate" v-if="!feedback && history.status == 'success'">
                        <div class="modal-dialog modal-dialog-centered modal-md">
                            <div class="modal-content pt-5 pb-4" style="border-radius:20px">
                                <form @submit.prevent="submitFeedback">
                                    <div class="containerRate">
                                        <div class="emoji fw-bold" style="font-size: 25px">Kirim Ulasan</div>
                                        <div class="emoji">
                                            <input type="hidden" name="star" class="emoji-radio">
                                            <div class="containers" style="font-size: 3rem !important;">
                                                <i @click="hover_star('1');" id="star-1"
                                                    class="bi bi-star text-gray cursor-pointer"></i>
                                            </div>
                                            <div class="containers" style="font-size: 3rem !important;">
                                                <i @click="hover_star('2');" id="star-2"
                                                    class="bi bi-star text-gray cursor-pointer"></i>
                                            </div>
                                            <div class="containers" style="font-size: 3rem !important;">
                                                <i @click="hover_star('3');" id="star-3"
                                                    class="bi bi-star text-gray cursor-pointer"></i>
                                            </div>
                                            <div class="containers" style="font-size: 3rem !important;">
                                                <i @click="hover_star('4');" id="star-4"
                                                    class="bi bi-star text-gray cursor-pointer"></i>
                                            </div>
                                            <div class="containers" style="font-size: 3rem !important;">
                                                <i @click="hover_star('5');" id="star-5"
                                                    class="bi bi-star text-gray cursor-pointer"></i>
                                            </div>
                                        </div>
                                        <div class="floating-label-content mb-lg-3 mb-md-2">
                                            <textarea class="form-control floating-input" v-model="message"
                                                name="content" id="content" placeholder=" " style="min-height:140px"
                                                maxlength="200">
                                    </textarea>

                                            <label class="floating-label" for="content">Tulis Ulasan Kamu</label>
                                            <div class="form-text">Maximum {{ message?.length || 0 }}/200 characters
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-12">
                                            <button type="submit" name="tombol" value="submit" data-bs-dismiss="modal"
                                                class="btnYellowPrimary w-100 py-4">Kirim</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-12 receipt-right">
                <div class="ticketDetail text-white shadow-sm ">
                    <div class="head h5 fw-bold mb-4 border-bottom pb-2">
                        Detail Pembelian
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-semibold">Invoice ID</span>
                        <span class="text-end">
                            {{ history.invoice_id }}
                            <i class="bi bi-clipboard2 ms-2 text-primary" :style="{ cursor: 'pointer' }"
                                @click="copyText(history.invoice_id)"></i>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Game</span>
                        <span class="fw-medium">{{ history.product.game }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Produk</span>
                        <span class="fw-medium text-end">{{ history.product.name_provider }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Denom</span>
                        <span class="fw-medium text-end">{{ history.product.denom }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Whatsapp</span>
                        <span class="fw-medium text-end">{{ history.whatsapp }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Metode Pembayaran</span>
                        <span class="fw-medium text-end">{{ history.payment.name }}</span>
                    </div>

                    <hr class="my-3" />

                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span class="text-end">
                            Rp {{
                                number_format(
                                    Number(history.price) +
                                    Number(history.other_prices.discount) -
                                    Number(history.other_prices.tax),
                                    0,
                                    ',',
                                    '.'
                                )
                            }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Fee Transaksi</span>
                        <span class="text-end">
                            Rp {{ number_format(history.other_prices.tax, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Discount</span>
                        <span class="text-end text-success">
                            -Rp {{ number_format(history.other_prices.discount, 0, ',', '.') }}
                        </span>
                    </div>

                    <hr class="my-3" />

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold">Total Harga</span>
                        <span class="fw-bold text-primary">
                            Rp {{ number_format(history.price, 0, ',', '.') }}
                            <i class="bi bi-clipboard2 ms-2" :style="{ cursor: 'pointer' }"
                                @click="copyText(history.price.toString())"></i>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Status</span>
                        <span>
                            <span v-if="history.status == 'pending'" class="badge bg-warning text-dark">Pending</span>
                            <span v-if="history.status == 'success'" class="badge bg-success">Berhasil</span>
                            <span v-if="history.status == 'failed'" class="badge bg-danger">Gagal</span>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <span>Tgl. Dibuat</span>
                        <span class="text-end">{{ formatDate(history.created_at) }}</span>
                    </div>
                </div>
            </div>


        </div>
    </div>
</template>