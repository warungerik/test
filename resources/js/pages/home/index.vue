<script setup>
import { computed, onMounted } from "vue";
import Swiper from "swiper";
import { Head, Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps({
    app: String,
    config: Array,
    website: Array,
    flashsale: Array,
    popular: Array,
    categories: Array,
    product: Array,
    title: String
})
onMounted(() => {
    new Swiper(".swiperBanner", {
        slidesPerView: 1.5,
        centeredSlides: true,
        loop: true,
        autoplay: { delay: 4000, disableOnInteraction: false },
        spaceBetween: 5,
        breakpoints: {
            0: { slidesPerView: 1, centeredSlides: true, spaceBetween: 0 },
            950: { slidesPerView: 1.5, centeredSlides: true, spaceBetween: 5 },
        },
    });
    new Swiper(".swiperFlashSale", {
        direction: "vertical",
        slidesPerView: 4,
        loop: true,
        slideToClickedSlide: true,
        spaceBetween: 20,
        slidesPerGroup: 1,
        autoplay: {
            delay: 2000,
        },
        breakpoints: {
            1000: {
                slidesPerView: 4,
                direction: "horizontal",
            },
            800: {
                slidesPerView: 3,
                direction: "horizontal",
            },
            400: {
                slidesPerView: 1.5,
                direction: "horizontal",
            },
            200: {
                slidesPerView: 1.5,
                direction: "horizontal",
            },
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
    });
    let countDownDate = new Date(props.config.data.konfigurasi_flashsale.expired_at).getTime();
    const flashsale = document.querySelector(".containerFlashSale");

    if (flashsale) {
        let x = setInterval(function () {
            let now = new Date().getTime();
            let distance = countDownDate - now;

            if (distance < 0) {
                clearInterval(x);
                document.getElementById("demo").innerHTML = "EXPIRED";
                return;
            }

            let days = Math.floor(distance / (1000 * 60 * 60 * 24)).toString().padStart(2, '0');
            let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)).toString().padStart(2, '0');
            let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0');
            let seconds = Math.floor((distance % (1000 * 60)) / 1000).toString().padStart(2, '0');

            if (document.querySelector(".day")) document.querySelector(".day").innerHTML = days;
            if (document.querySelector(".hours")) document.querySelector(".hours").innerHTML = hours;
            if (document.querySelector(".minute")) document.querySelector(".minute").innerHTML = minutes;
            if (document.querySelector(".second")) document.querySelector(".second").innerHTML = seconds;
        }, 1000);
    }
});
const isFlashSaleActive = computed(() => {
    const fs = props.config?.data?.konfigurasi_flashsale
    if (!fs) return false
    return fs.status === "active" && new Date(fs.expired_at).getTime() > Date.now()
})
const banners = computed(() => {
    let arr = props.config.data.konfigurasi_banner || []
    if (arr.length === 0) {
        return "[]"
    }

    if (arr.length < 4) {
        let hasil = []
        while (hasil.length < 4) {
            hasil = hasil.concat(arr)
        }
        return hasil.slice(0, 4)
    }

    return arr
})

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
    <div class="containerBanner">
        <div class="swiper swiperBanner">
            <div class="swiper-wrapper">
                <div class="swiper-slide banner" v-for="(url, i) in banners" :key="i">
                    <div class="banner-img">
                        <img :src="`assets/images/banner/${url}`" loading="lazy" alt="banner" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="containerFlashSale" v-if="isFlashSaleActive">
        <div class="headerFS">
            <div class="containers">
                <div class="title-head">Flash Sale</div>
                <img :src="`${app}/assets/images/icon/flash.svg`" alt="" />
                <div class="time">
                    <div class="day">00</div>
                    <div class="dots">:</div>
                    <div class="hours">00</div>
                    <div class="dots">:</div>
                    <div class="minute">00</div>
                    <div class="dots">:</div>
                    <div class="second">00</div>
                </div>
            </div>
            <div class="FSnav">
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </div>
        <div class="swiper swiperFlashSale">
            <div class="swiper-wrapper">
                <Link :href="route('home.product', { slug: f.product.slug })" class="swiper-slide flashSale"
                    v-for="f in flashsale">
                <img :src="`${app}/assets/images/product/${f.product.thumbnail}`" alt="" />
                <div class="mask"></div>
                <div class="desc">
                    <div class="price">
                        <div class="titleFs">{{ f.product.name }} - {{ f.game.name }}</div>
                        <div class="realPrice">
                            <span class="rp">{{ f.denom.name }} - Rp {{ number_format(f.type_discount == 'fixed' ?
                                f.denom.price - f.discount : (f.denom.price - (f.denom.price * f.discount / 100)),
                                0, ',', '.')
                            }}</span>,-
                        </div>

                        <div class="disc">Rp
                            {{ number_format(f.denom.price, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                </Link>
            </div>
        </div>

    </div>
    <div class="containerPopuler mb-3">
        <div class="title-head"><i class="fa-solid fa-fire fa-beat text-danger mr-3"></i>Populer</div>
        <div class="row g-2 g-lg-3">
            <div v-for="(p, i) in popular" :key="i" class="col-6 col-md-4 col-lg-3">
                <Link :href="route('home.product', { slug: p.slug })" class="card-populer shadow">
                <div class="card-populer-content"
                    :style="{ backgroundImage: `url(${app}/assets/images/product/${p.thumbnail})` }">
                    <div class="card-populer-img">
                        <img :src="`${app}/assets/images/product/${p.thumbnail}`" alt="SENJU GOLD SERIES"
                            loading="lazy">
                    </div>
                    <div class="card-populer-text">
                        <span>{{ p.game.name }}</span>
                        <h1>{{ p.name }}</h1>
                    </div>
                    <div class="mask"></div>
                </div>
                </Link>
            </div>
        </div>
    </div>

    <div class="containerProduct">
        <ul class="nav navTabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation" v-for="(category, i) in categories" :key="i">
                <button class="btnNavTabs" :class="{ active: i === 0 }"
                    :id="`${category.name.replace(/\s+/g, '-').toLowerCase()}-tab`" data-bs-toggle="tab"
                    :data-bs-target="`#${category.name.replace(/\s+/g, '-').toLowerCase()}`" type="button" role="tab">
                    <div class="icon"><i :class="category.icon"></i></div>
                    <div class="text">{{ category.name }}</div>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <div v-for="(c, i) in categories" :key="i" :class="`tab-pane fade ${i === 0 ? 'show active' : ''}`"
                :id="`${c.name.replace(/\s+/g, '-').toLowerCase()}`" role="tabpanel">
                <div class="row g-2 g-lg-3">
                    <div class="col-lg-2 col-md-3 col-4 mb-lg-3 mb-md-2 col-sm-4"
                        v-for="p in (Array.isArray(product) ? product.filter(pr => pr.category_id == c.id) : [])"
                        :key="p.id">
                        <div class="containers shadow">
                            <Link :href="route('home.product', { slug: p?.slug })">
                            <img :src="`${app}/assets/images/product/${p?.thumbnail}`" :alt="p?.name"
                                class="product-img" loading="lazy" />
                            <div class="mask"></div>
                            <div class="desc px-3">
                                <div class="game">{{ p?.name }}</div>
                                <div class="vendor">{{ p.ppob == '1' ? p.publisher : p?.game?.name }}</div>
                            </div>
                            </Link>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</template>
