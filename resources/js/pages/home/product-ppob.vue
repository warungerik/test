<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch } from "vue";
import { route } from "ziggy-js";

const loading = ref(false)
const loadingOrder = ref(false)

const elementConfirm = ref(true)
const elementInformation = ref(false)

const pickDenom = ref({})
const pickPayment = ref({})
const name = ref(null)
const denom = ref(null)
const whatsapp = ref(null)
const price = ref(null)
const voucher = ref(null)
const paymentMetode = ref(null)

const paymentFees = ref({});
const errors = ref({});

const formData = ref({})

const page = usePage();
const props = defineProps({
    app: String,
    product: Object,
    categories: Array,
    sistem_target: Array,
    config: Object,
    feedback: Object,
    rating: [String, Number],
    count: [String, Number],
    c: [String, Number],
    ratings: Object,
    paymentCategory: Object,
    payment: Object,
    dataDenom: Object,
    flashsale: Array,
    title: String
});

onMounted(() => {
    if (props.sistem_target?.target) {
        Object.keys(props.sistem_target?.target).forEach(key => {
            formData.value[key] = ""
        })
    }
    const accordionBtn = document.querySelectorAll(".accordionHead");

    accordionBtn.forEach((accordion) => {
        accordion.onclick = function () {
            this.classList.toggle("is-open");

            let content = this.nextElementSibling;

            if (content.style.maxHeight) {
                content.style.maxHeight = null;
            } else {
                content.style.maxHeight = content.scrollHeight + "px";
            }

            const otherAccordions = document.querySelectorAll(".accordionHead");
            otherAccordions.forEach((otherAccordion) => {
                if (otherAccordion !== this && otherAccordion.classList.contains("is-open")) {
                    otherAccordion.classList.remove("is-open");
                    let otherContent = otherAccordion.nextElementSibling;
                    otherContent.style.maxHeight = null;
                }
            });
        };
    });

    const accordionBtnPay = document.querySelectorAll(".accordionHeadPay");

    accordionBtnPay.forEach((accordion) => {
        accordion.onclick = function () {
            this.classList.toggle("is-open");

            let content = this.nextElementSibling;

            if (content.style.maxHeight) {
                content.style.maxHeight = null;
            } else {
                content.style.maxHeight = content.scrollHeight + "px";
            }

            const otherAccordions = document.querySelectorAll(".accordionHeadPay");
            otherAccordions.forEach((otherAccordion) => {
                if (
                    otherAccordion !== this &&
                    otherAccordion.classList.contains("is-open")
                ) {
                    otherAccordion.classList.remove("is-open");
                    let otherContent = otherAccordion.nextElementSibling;
                    otherContent.style.maxHeight = null;
                }
            });
        };
    });
});

const discount = ref(0)

const getFlashsalePrice = (d) => {
    const f = props.flashsale.find(f => f.denom_id === d.id)
    if (!f) return null

    if (f.type_discount === "fixed") {
        return d.price - f.discount
    }
    if (f.type_discount === "percent") {
        return d.price - (d.price * f.discount / 100)
    }
    return null
}

const basePrice = computed(() => {
    if (!pickDenom.value || !pickDenom.value.id) return 0

    const flashsalePrice = getFlashsalePrice(pickDenom.value)
    return flashsalePrice !== null ? flashsalePrice : pickDenom.value.price
})

const finalPrice = computed(() => {
    let calculatedPrice = basePrice.value || 0
    if (pickPayment.value && paymentFees.value[pickPayment.value.id]) {
        calculatedPrice = paymentFees.value[pickPayment.value.id]
    }
    return Math.max(0, calculatedPrice - discount.value)
})

const priceBeforeVoucher = computed(() => {
    let calculatedPrice = basePrice.value || 0

    if (pickPayment.value && paymentFees.value[pickPayment.value.id]) {
        calculatedPrice = paymentFees.value[pickPayment.value.id]
    }

    return calculatedPrice
})

async function selectDenom(denom) {
    try {
        const response = await axios.post(route("api.select-denom"), { id: denom.id });
        if (response.data.status) {
            response.data.data.forEach(p => {
                paymentFees.value[p.payment_id] = p.fee;
            });
            pickDenom.value = denom;
            elementConfirm.value = false;
            elementInformation.value = true;
        } else {
            toastify["error"]?.("Whoops!", response.data.message);
        }
    } catch (e) {
        toastify["error"]?.("Whoops!", e.message);
    }
}

const applyVoucher = async () => {
    loading.value = true;
    try {
        if (denom.value == null) {
            toastify["error"]?.("Whoops!", "Pilih nominal terlebih dahulu.");
            return;
        }
        if (!pickPayment.value.id) {
            toastify["error"]?.("Whoops!", "Pilih metode pembayaran terlebih dahulu.");
            return;
        }
        const response = await axios.post(route('api.apply-voucher'), {
            denom_id: denom.value,
            voucher: voucher.value,
            payment_id: pickPayment.value.id,
            id: props.product.id
        })
        if (response.data.status) {
            discount.value = response.data.discount
            toastify["success"]?.("Gotcha!", response.data.message);
        } else {
            toastify["error"]?.("Whoops!", response.data.message);
        }
    } catch (err) {
        toastify["error"]?.("Whoops!", "Terjadi kesalahan, coba lagi nanti.");
    } finally {
        loading.value = false;
    }
}

const confirmOrder = () => {
    if (!pickPayment.value || !pickPayment.value.id) {
        toastify["error"]?.("Whoops!", "Pilih metode pembayaran dahulu!");
        return;
    }
    if (props.sistem_target?.target) {
        const isValid = Object.keys(formData.value).every(key => {
            return formData.value[key] !== null && formData.value[key] !== '';
        })

        if (!isValid) {
            toastify["error"]?.("Whoops!", "Semua field target wajib diisi!");
            return false;
        }
    }

    const modalEl = document.getElementById("modal-confirm");
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
};

const order = async () => {
    loadingOrder.value = true;
    errors.value = {};
    try {

        if (props.sistem_target?.target) {
            const isValid = Object.keys(formData.value).every(key => {
                return formData.value[key] !== null && formData.value[key] !== '';
            })

            if (!isValid) {
                toastify["error"]?.("Whoops!", "Semua field target wajib diisi!");
                return false;
            }
        }
        if (!whatsapp.value) {
            errors.value.whatsapp = 'Nomor WhatsApp tidak boleh kosong';
        }
        if (!denom.value) {
            errors.value.price = 'Nominal tidak boleh kosong';
        }
        if (!pickPayment.value || !pickPayment.value.id) {
            errors.value.payment = 'Metode pembayaran tidak boleh kosong';
        }

        if (Object.keys(errors.value).length > 0) {
            toastify["error"]?.("Whoops!", "Isi semua kolom yang ada!");
            return;
        }
        const response = await axios.post(route("api.create-order"), {
            name: name.value,
            whatsapp: whatsapp.value,
            denom_id: denom.value,
            payment_id: pickPayment.value.id,
            voucher: voucher.value,
            target: formData.value,
            type: 'ppob'
        });

        if (response.data.status) {
            toastify["success"]?.("Gotcha!", response.data.message);
            setTimeout(() => {
                window.location.href = route("home.invoice", response.data.data);
            }, 2000);
        } else {
            toastify["error"]?.("Whoops!", response.data.message);
        }
    } catch (err) {
        toastify["error"]?.("Whoops!", "Terjadi kesalahan, coba lagi nanti.");
    } finally {
        loadingOrder.value = false;
    }
};

function maskInvoice(id) {
    if (!id) return "";
    if (id.length <= 6) return id;

    const start = id.slice(0, 6);
    const end = id.slice(-2);
    return start + "****" + end;
}

function formatDate(dateString) {
    const bulan = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember",
    ];

    const date = new Date(dateString);
    const hari = date.getDate();
    const bulanText = bulan[date.getMonth()];
    const tahun = date.getFullYear();

    const jam = String(date.getHours()).padStart(2, "0");
    const menit = String(date.getMinutes()).padStart(2, "0");

    return `${hari} ${bulanText} ${tahun}, ${jam}:${menit}`;
}

function number_format(number, decimals = 0, dec_point = ',', thousands_sep = '.') {
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

    <Head :title="props.title" />
    <div class="desc mb-3">
        <span class="txt-primary">
            <Link :href="route('home.index')">HOME</Link>
        </span>
        <span class="mx-2">/</span>{{ product.game.name.toUpperCase() }}
    </div>
    <div class="relative h-56 w-full bg-muted lg:h-[340px]">
        <img alt="{{ product.game.name }}" fetchpriority="high" decoding="async" data-nimg="fill"
            class="object-cover object-center" sizes="80vw" :src="`${app}/assets/images/banner/${product.banner}`"
            style=" position: absolute;height: 100%;width: 100%;inset: 0px; color: transparent; " />
    </div>
    <div class="bg-title-product flex min-h-32 w-full items-center lg:min-h-[160px]">
        <div class="container flex items-center gap-2">
            <div>
                <div class="flex items-start gap-4">
                    <div class="product-thumbnail-container relative -top-28">
                        <img alt="" loading="lazy" width="150" height="220" decoding="async" data-nimg="1"
                            class="z-20 -mb-14 aspect-square w-32 rounded-2xl object-cover shadow-2xl custom-image"
                            sizes="100vw" :src="`${app}/assets/images/product/${product.thumbnail}`"
                            style="color: transparent" />
                    </div>
                </div>
            </div>

            <div class="py-4 sm:py-0">
                <h1 class="text-white font-bold uppercase leading-7 tracking-wider sm:text-lg">
                    {{ product.name }}
                </h1>
                <p class="text-white font-medium sm:text-base/6">{{ product.game.name }}</p>
                <div class="mt-4 flex flex-custom gap-2 text-xs sm:gap-8 sm:text-sm/6">
                    <div class="flex items-center gap-2">
                        <img :src="`${app}/assets/images/gif/lightning.gif`" width="30" height="30" alt="" />
                        <span class="text-white">Proses Cepat</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <img :src="`${app}/assets/images/gif/contact-support.gif`" width="30" height="30" alt="" />
                        <span class="text-white">Layanan Chat 24/7</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <img :src="`${app}/assets/images/gif/secure.gif`" width="30" height="30" alt="" />
                        <span class="text-white">Pembayaran Aman</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="containerNominal">
        <div class="row">
            <div class="col-lg-4 mb-3">
                <div class="sticky gap-8">
                    <div class="cards py-4 mb-4 text-white">
                        <b class="me-1">{{ product.name }}</b>
                        <i class="fa fa-fw fa-check-circle text-success"></i>

                        <div v-html="product.description"></div>
                    </div>
                    <div class="cards my-4 custom-card">
                        <div class="title-card text-left mb-3">Ulasan</div>
                        <div class="mb-6 flex flex-col items-center justify-center text-white">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-star text-yellow" :style="{ fontSize: '2rem' }"></i>
                                <div>
                                    <span class="text-5xl font-semibold text-card-foreground">{{
                                        rating
                                    }}</span>
                                    <sub class="text-lg font-semibold text-card-foreground">/ 5.0</sub>
                                </div>
                            </div>

                            <p class="pt-2 text-center text-sm text-card-foreground">
                                Pelanggan merasa puas dengan produk ini. <br />
                                Dari <span class="font-semibold">{{ c }}</span> ulasan.
                            </p>
                        </div>

                        <dl class="space-y-3 text-card-foreground">
                            <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center text-sm">
                                <dt class="flex flex-1 items-center pl-1">
                                    <p class="text-text-color w-3 font-medium">
                                        {{ star }}<span class="sr-only"> star reviews</span>
                                    </p>
                                    <div aria-hidden="true" class="ml-1 flex flex-1 items-center">
                                        <i class="fas fa-star text-yellow-400 h-5 w-5"></i>
                                        <div class="relative ml-3 flex-1">
                                            <div class="h-3 rounded-4 border border-gray-200 bg-gray-100"></div>
                                            <div class="absolute inset-y-0 rounded-4 border border-yellow-400 bg-yellow-400"
                                                :style="{ width: (ratings[star] / count) * 100 + '%' }"></div>
                                        </div>
                                    </div>
                                </dt>
                                <dd class="text-text-color ml-3 w-10 text-right text-sm tabular-nums">
                                    {{ ratings[star] }}<span class="sr-only"> reviews</span>
                                </dd>
                            </div>
                        </dl>
                        <div class="mt-6">
                            <p class="text-sm/6 text-card-foreground">
                                Apakah kamu menyukai produk ini? Beri tahu kami dan calon pembeli lainnya
                                tentang pengalamanmu.
                            </p>
                        </div>
                        <div
                            class="flex flex-col gap-y-4 divide-y divide-card-foreground/25 border-t border-card-foreground/25">
                            <div class="text-card-foreground" :class="{ 'pt-4': i === 0, 'pt-2': i > 0 }"
                                v-for="(f, i) in feedback" :key="i">
                                <div class="flex items-center">
                                    <div class="w-full">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <h4 class="mt-0.5 text-xs font-bold text-foreground">
                                                    {{ maskInvoice(f.information['invoice_id']) }}
                                                </h4>
                                            </div>

                                            <div class="flex items-center">
                                                <i v-for="n in 5" :key="n" class="fas" :class="{
                                                    'fa-star text-yellow': n <= Math.floor(f.rating),
                                                    'fa-star-half-alt text-yellow':
                                                        n === Math.floor(f.rating) + 1 &&
                                                        f.rating % 1 >= 0.3 &&
                                                        f.rating % 1 < 0.8,
                                                    'fa-star text-secondary': n > Math.ceil(f.rating),
                                                }"></i>
                                            </div>
                                        </div>
                                        <p class="sr-only">5 dari 5 bintang</p>
                                    </div>
                                </div>

                                <div class="flex w-full justify-between pt-1 text-xxs">
                                    <span>{{ f.information['product']['name_provider'] }}</span><span>
                                        {{ formatDate(f.created_at) }}
                                    </span>
                                </div>
                                <div class="mt-1 space-y-6 text-xs italic text-foreground">
                                    “{{ f.message }}”
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end pt-4">
                            <Link class="text-info text-sm" type="button" :href="route('home.feedback')"
                                style="outline: none;"><span class="me-2">Lihat
                                semua
                                ulasan</span>
                            <i class="fas fa-arrow-right mr-2"></i>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="cards mb-4 d-flex flex-column gap-3" id="section-method">
                    <div class="title-card text-left mb-2">
                        1. {{ props.sistem_target?.judul }}
                        <span class="sigma_question" :style="{ fontSize: '1rem' }" data-bs-target="#petunjukModal"
                            data-bs-toggle="modal">?</span>
                    </div>

                    <div class="row">
                        <template v-for="(type, key, index) in props.sistem_target?.target" :key="key">
                            <div class="mt-2" :class="[
                                Object.keys(props.sistem_target?.target).length % 2 !== 0 &&
                                    index + 1 === Object.keys(props.sistem_target?.target).length
                                    ? 'col-lg col-md'
                                    : 'col-lg-6 col-md-6',
                                index + 1 > 2 ? 'mt-3' : ''
                            ]">
                                <div class="floating-label-content" v-if="type !== 'option'">
                                    <label class="label-content mb-1" for="target">{{ key }}</label>
                                    <input :type="type" class="form-control games-input floating-input"
                                        :name="`target[${key}]`" :id="`target[${key}]`" :placeholder="key" required
                                        v-model="formData[key]" />
                                </div>
                                <div class="floating-label-content" v-else>
                                    <label class="label-content mb-1" for="target">{{ key }}</label>
                                    <select class="form-control games-input floating-input" :name="`target[${key}]`"
                                        v-model="formData[key]">
                                        <!-- Default placeholder -->
                                        <option disabled value="">
                                            Pilih {{ key }}
                                        </option>

                                        <!-- Loop opsi -->
                                        <option v-for="(opt, optKey) in props.sistem_target?.option" :key="optKey"
                                            :value="opt.validasi">
                                            {{ opt.title }}
                                        </option>
                                    </select>


                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="col-12 text-white" :style="{ 'font-size': '0.875rem' }"
                        v-html="props.sistem_target?.konten?.replace(/\r\n/g, '<br>')"></div>
                </div>

                <div class="cards py-3 mb-4">
                    <div class="title-card text-left mb-3">2. Pilih Paket</div>
                    <ul class="nav navTabs custom_content mb-2" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation" v-for="(c, key) in categories" :key="c.id">
                            <button class="btnNavTabs" :class="{ active: key === 0 }" :id="'tab-' + c.id + '-tab'"
                                data-bs-toggle="tab" :data-bs-target="'#tab-' + c.id" type="button" role="tab"
                                :aria-controls="'tab-' + c.id" :aria-selected="key === 0">
                                <div class="text"
                                    style="font-weight: 300; font-style: normal; color: var(--black-text);">
                                    <i :class="c.icon + ' me-1'"></i> {{ c.name }}
                                </div>
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class=" tab-content" id="myTabContent">
                        <div v-for="(c, key) in categories" :key="c.id" class="tab-pane fade"
                            :class="{ 'show active': key === 0 }" :id="'tab-' + c.id" role="tabpanel"
                            :aria-labelledby="'tab-' + c.id + '-tab'">
                            <div class="row">
                                <div class="col-md-4 col-6" v-for="d in dataDenom.filter(d => d.category_id === c.id)"
                                    :key="d.id">
                                    <input type="radio" name="product" :id="`denom-${d.id}`" v-model="denom"
                                        :value="d.id" class="nom-radio" @click="selectDenom(d)" />
                                    <label :for="`denom-${d.id}`" class="containerChoice">
                                        <div class="containerIcon" hidden>
                                            <i class="bi bi-check-lg"></i>
                                        </div>
                                        <div class="text">
                                            <div class="desc">{{ d.name }}</div>
                                            <div class="count">
                                                <div v-if="getFlashsalePrice(d) !== null">
                                                    <div style="font-size: 12px">
                                                        Rp {{ number_format(getFlashsalePrice(d), 0,
                                                            ',', '.') }}
                                                        <s class="text-danger"
                                                            :style="{ marginLeft: '2px', fontSize: '10px' }">
                                                            Rp {{ number_format(d.price, 0, ',', '.') }}
                                                        </s>
                                                    </div>
                                                </div>
                                                <div v-else>
                                                    <div style="font-size: 13px">
                                                        Rp {{ number_format(d.price, 0, ',', '.') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion">
                    <div class="accordionHead">
                        <div class="containers">
                            <div class="title">3. Punya Kode Promo?</div>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                    <div class="accordionBody">
                        <div class="accordionContent">
                            <div class="floating-label-content">
                                <div class="floating-label-content">
                                    <label class="label-content mb-1" for="target">Kode Voucher</label>
                                    <input type="text" v-model="voucher" class="form-control games-input floating-input"
                                        name="voucher" id="voucher" placeholder="Masukkan kode" required="" />
                                </div>
                                <button class="btnYellowPrimary my-3 w-100" :disabled="loading" type="button"
                                    @click="applyVoucher()">
                                    <span v-if="loading">Applying...</span>
                                    <span v-else>Apply</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cards my-4">
                    <div class="title-card text-left mb-3">4. Metode Pembayaran</div>
                    <div class="accordion mb-2" v-for="pc in paymentCategory">
                        <div class="accordionHeadPay" style="background: var(--dark-grey)">
                            <div class="title">{{ pc.name }}</div>
                            <div class="containers">
                                <img :src="pc" alt="" />
                            </div>
                            <i class="bi bi-caret-right-fill"></i>
                        </div>

                        <div class="accordionBodyPay">
                            <div class="accordionContent">
                                <div class="row">
                                    <div class="col-sm-12" v-for="p in payment.filter(p => p.category_id == pc.id)">
                                        <input type="radio" name="method" class="pay-radio" :id="`method-${p.id}`"
                                            :value="p" v-model="pickPayment" />

                                        <label :for="`method-${p.id}`" class="choicePay">
                                            <div class="containers">
                                                <div class="icon">
                                                    <i class="bi bi-check-lg"></i>
                                                </div>
                                                <div class="text">
                                                    <div class="name">{{ p.name }}</div>
                                                    <div class="price" :id="`method-${p.id}-price`">
                                                        {{ paymentFees[p.id] ? 'Rp ' +
                                                            number_format(paymentFees[p.id], 0, ',', '.') : '-' }}
                                                    </div>
                                                </div>
                                            </div>
                                            <img :src="p.image && p.image.includes('http') ? p.image : `${app}/assets/images/payment/${p.image}`"
                                                width="60" />
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cards mb-4 d-flex flex-column gap-3" id="section-method">
                    <div class="title-card text-left">5. Detail Kontak</div>
                    <div class="floating-label-content">
                        <label class="label-content mb-1" for="target">No. Whatsapp</label>
                        <input type="text" inputmode="numeric" class="form-control floating-input" name="wa" id="wa"
                            placeholder="Masukkan nomor whatsapp" v-model="whatsapp" />
                        <div class="note">*Data transaksi akan dikirim via whatsapp.</div>
                    </div>
                </div>
                <div v-if="elementConfirm">
                    <div class="rounded-lg border border-dashed bg-secondary p-4 text-sm text-secondary-foreground ">
                        <div class="text-center">Belum ada item produk yang dipilih.</div>
                    </div>
                    <button type="button" id="btn-confirm" class="btnYellowPrimary w-100 mt-2 disabled">
                        Pilih Produk
                    </button>
                </div>
                <div v-if="elementInformation"
                    class="shad sticky bottom-0 rounded-t-lg pb-4 flex flex-col gap-2 bg-background">
                    <div class="rounded-lg border border-dashed  bg-secondary p-44 text-sm text-secondary-foreground">
                        <div class="flex items-center gap-4">
                            <div class="aspect-square h-16">
                                <img alt="The Ants" loading="lazy" width="300" height="300" decoding="async"
                                    data-nimg="1" class="aspect-square h-16 rounded-lg object-cover" sizes="100vw"
                                    :src="`${app}/assets/images/product/${product.thumbnail}`"
                                    style="color: transparent; width:100%;">
                            </div>
                            <div>
                                <div class="text-xs" id="title-package">{{ pickDenom.name }}</div>
                                <div class="flex items-center gap-2 pt-0.5 font-semibold" id="paymentSelect">
                                    <span class="text-warning" id="price-package">
                                        Rp {{ number_format(finalPrice, 0, ',', '.') }}
                                    </span>
                                    <div v-if="pickPayment.name">
                                        <span>- {{ pickPayment.name }}</span>
                                    </div>
                                </div>
                                <div class="text-xxs italic text-muted-foreground">**Waktu proses instan</div>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="btn-submit" @click="confirmOrder" class="btnYellowPrimary w-100 mt-2"
                        :class="loadingOrder ? 'disabled' : ''">
                        <span v-if="loadingOrder">Loading...</span>
                        <span v-else>Konfirmasi Pesanan</span>
                    </button>
                </div>


                <div class="cards my-4 custom-card2">
                    <div class="title-card text-left mb-3">Ulasan</div>
                    <div class="mb-6 flex flex-col items-center justify-center text-white">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-star text-yellow" :style="{ fontSize: '2rem' }"></i>
                            <div>
                                <span class="text-5xl font-semibold text-card-foreground">{{
                                    rating
                                }}</span>
                                <sub class="text-lg font-semibold text-card-foreground">/ 5.0</sub>
                            </div>
                        </div>

                        <p class="pt-2 text-center text-sm text-card-foreground">
                            Pelanggan merasa puas dengan produk ini. <br />
                            Dari <span class="font-semibold">{{ c }}</span> ulasan.
                        </p>
                    </div>

                    <dl class="space-y-3 text-card-foreground">
                        <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center text-sm">
                            <dt class="flex flex-1 items-center pl-1">
                                <p class="text-text-color w-3 font-medium">
                                    {{ star }}<span class="sr-only"> star reviews</span>
                                </p>
                                <div aria-hidden="true" class="ml-1 flex flex-1 items-center">
                                    <i class="fas fa-star text-yellow-400 h-5 w-5"></i>
                                    <div class="relative ml-3 flex-1">
                                        <div class="h-3 rounded-4 border border-gray-200 bg-gray-100"></div>
                                        <div class="absolute inset-y-0 rounded-4 border border-yellow-400 bg-yellow-400"
                                            :style="{ width: (ratings[star] / count) * 100 + '%' }"></div>
                                    </div>
                                </div>
                            </dt>
                            <dd class="text-text-color ml-3 w-10 text-right text-sm tabular-nums">
                                {{ ratings[star] }}<span class="sr-only"> reviews</span>
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-6">
                        <p class="text-sm/6 text-card-foreground">
                            Apakah kamu menyukai produk ini? Beri tahu kami dan calon pembeli lainnya
                            tentang pengalamanmu.
                        </p>
                    </div>
                    <div
                        class="flex flex-col gap-y-4 divide-y divide-card-foreground/25 border-t border-card-foreground/25">
                        <div class="text-card-foreground" :class="{ 'pt-4': i === 0, 'pt-2': i > 0 }"
                            v-for="(f, i) in feedback" :key="i">
                            <div class="flex items-center">
                                <div class="w-full">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="mt-0.5 text-xs font-bold text-foreground">
                                                {{ maskInvoice(f.information['invoice_id']) }}
                                            </h4>
                                        </div>

                                        <div class="flex items-center">
                                            <i v-for="n in 5" :key="n" class="fas" :class="{
                                                'fa-star text-yellow': n <= Math.floor(f.rating),
                                                'fa-star-half-alt text-yellow':
                                                    n === Math.floor(f.rating) + 1 &&
                                                    f.rating % 1 >= 0.3 &&
                                                    f.rating % 1 < 0.8,
                                                'fa-star text-secondary': n > Math.ceil(f.rating),
                                            }"></i>
                                        </div>
                                    </div>
                                    <p class="sr-only">5 dari 5 bintang</p>
                                </div>
                            </div>

                            <div class="flex w-full justify-between pt-1 text-xxs">
                                <span>{{ f.information['product']['name_provider'] }}</span><span>
                                    {{ formatDate(f.created_at) }}
                                </span>
                            </div>
                            <div class="mt-1 space-y-6 text-xs italic text-foreground">
                                “{{ f.message }}”
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4">
                        <Link class="text-info text-sm" type="button" :href="route('home.feedback')"
                            style="outline: none;">
                        <span class="me-2">Lihat
                            semua
                            ulasan</span>
                        <i class="fas fa-arrow-right mr-2"></i>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal-confirm">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content pt-5 pb-4" style="border-radius:20px">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="ticketDetail page">
                                <div class="head text-center">Detail Pembelian</div>
                                <div class="containers">
                                    <div class="title">Nama</div>
                                    <div class="desc">{{ name }}</div>
                                </div>
                                <div class="containers">
                                    <div class="title">Game</div>
                                    <div class="desc">{{ product.game.name }}</div>
                                </div>
                                <div class="containers">
                                    <div class="title">Provider</div>
                                    <div class="desc">{{ product.name }}</div>
                                </div>
                                <div class="containers">
                                    <div class="title">Denom</div>
                                    <div class="desc">{{ pickDenom.name }}</div>
                                </div>
                                <div class="containers">
                                    <div class="title">Metode Pembayaran</div>
                                    <div class="desc leading-none" id="metode_bayar">{{ pickPayment.name }}</div>
                                </div>
                                <div class="containers">
                                    <div class="title">Subtotal</div>
                                    <div class="desc">Rp {{ number_format(priceBeforeVoucher + discount, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="containers" v-if="discount > 0">
                                    <div class="title">Discount</div>
                                    <div class="desc">-Rp {{ number_format(discount, 0, ',', '.') }}</div>
                                </div>
                                <div class="containers">
                                    <div class="title">Total Harga</div>
                                    <div class="desc txt-primary" id="total_bayar">
                                        Rp {{ number_format(finalPrice, 0, ',', '.') }}
                                    </div>
                                </div>
                                <p class="text-center text-white" style="margin: 50px 0;">
                                    Pastikan data yang di input sudah benar
                                </p>
                                <button type="button" @click="order" :disabled="lloadingOrderoading"
                                    id="btn-order-process" class="btnYellowPrimary w-100 mt-3">
                                    <span v-if="loadingOrder">Loading...</span>
                                    <span v-else>Bayar sekarang</span>
                                </button>
                                <button type="button" data-bs-dismiss="modal" class="btnGrey w-100 mt-2">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade none" id="petunjukModal" tabindex="-1" aria-labelledby="petunjukModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content" style="background: transparent !important;">
                <div class="modal-body"
                    :style="{ 'background-color': 'var(--light-blue)', 'opacity': '1', 'color': '#ffff', 'display': 'grid', 'gap': '0.7rem' }">
                    <img style="max-width: 100%" src="http://newtopup.test/assets/images/1738076720.webp">
                    <p>Untuk Menemukan ID Kamu, Klik Avatar Pada Pojok Kiri Atas Layar Akan Muncul ID & Server Kamu.
                        Contoh:
                        1234567890</p>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.sigma_question {
    background-color: var(--primary-color);
    color: #fff;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    line-height: 24px;
    display: inline-flex;
    justify-content: center;
    cursor: pointer;
    margin-left: 5px;
}
</style>