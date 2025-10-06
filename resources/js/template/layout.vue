<script setup>
import { usePage, Link, Head } from '@inertiajs/vue3';
import { onBeforeMount, onMounted, ref, watch } from 'vue'
import { route } from 'ziggy-js';
import Swiper from "swiper";
import axios from 'axios';
import debounce from "lodash/debounce"


const page = usePage();
const darkTheme = ref(true)

const keyword = ref("")
const dataSearch = ref([])

const lastHistory = ref({})

const auth = page.props.auth;
const swiperFeedback = ref([]);
const props = defineProps({
    app: String,
    website: Array
})
const doSearch = async (val) => {
    if (!val) {
        dataSearch.value = []
        return
    }
    try {
        const res = await axios.post(route('api.search'), { search: val })
        if (res.data.data.length === 0) {
            dataSearch.value = []
        } else {
            dataSearch.value = res.data.status ? res.data.data : []
        }
    } catch (e) {
        console.error(e)
    }
}
const debouncedSearch = debounce(doSearch, 500)
watch(keyword, (newVal) => {
    debouncedSearch(newVal)
})
watch(darkTheme, (newVal) => {
    if (newVal) {
        document.querySelector('html').setAttribute('data-theme', 'dark')
    } else {
        document.querySelector('html').removeAttribute('data-theme')
    }
})
const toggleTheme = () => {
    darkTheme.value = !darkTheme.value
}
const offcanvasButton = ref(null)

const toggleMenu = () => {
    if (offcanvasButton.value) {
        offcanvasButton.value.click()
    }
}
const removeData = () => {
    dataSearch.value = []
    keyword.value = ''
}
onBeforeMount(async () => {
    try {
        const res = await axios.post(route('api.data-footer'))
        if (res.data.status) {
            swiperFeedback.value = res.data.feedback.map(f => ({
                ...f,
                reply: "",
                showReply: false
            }))
        }
    } catch (e) {
        console.error("Error load feedback:", e)
    }
})

onMounted(async () => {
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
    new Swiper(".swiperTesti", {
        slidesPerGroup: 1,
        loop: true,
        spaceBetween: 20,
        grabCursor: true,
        autoplay: { delay: 2500 },
        breakpoints: {
            800: { slidesPerView: 2.2, centeredSlides: false },
            300: { slidesPerView: 1.5, centeredSlides: false },
        },
    });

    let StoreDigitals = [];
    try {
        const res = await axios.post(route("api.get-history"));
        if (res.data.status) {
            StoreDigitals = res.data.data;
        } else {
            toastify["error"]?.("Whoops!", res.data.message);
        }
    } catch (e) {
        console.error("Error getHistory:", e);
    }

    if (!StoreDigitals.length) return; // kalau kosong, stop

    let currentIndex = 0;
    let widgetElement = null;
    let progressBar = null;
    let contentElement = null;

    function showCurrentContent() {
        let StoreDigital = StoreDigitals[currentIndex];

        let icons = StoreDigital.product_db ? `${props.app}/assets/images/product/${StoreDigital.product_db?.thumbnail}` : props.app + '/assets/images/logo/' + props.website.logo;

        let StoreDiv = document.createElement("div");
        StoreDiv.setAttribute("id", StoreDigital.invoice_id);
        StoreDiv.setAttribute("class", "tsp-widget alert-widget alert-widget-4");
        StoreDiv.setAttribute(
            "style",
            "inset: auto auto 10px 30px; display: block; z-index: 15"
        );

        StoreDiv.innerHTML = `
            <div class="pull-left icon" style="border-radius:10px;">
                <img class="live_preview_image" style="border-radius:10px; height:115%" src="${icons}" alt="${StoreDigital.invoice_id}">
            </div>

            <div class="desc desc_live_preview" style="background-color:#FFFFFF;border:1px solid rgba(162, 169, 171, 0.19);border-radius:10px;">
                <span class="tsp-has-close-button" style="cursor:pointer; color: #ff8400; position: absolute;text-shadow: 0px 0px 0px #000; right:10px">✖</span>
                <h4 class="desc-heading">
                    <small>
                        <span id="id-${StoreDigital.invoice_id}" class="desc-heading-name">
                            <span class="text-danger">ID Order</span>
                            <a target="_blank" href="${props.app}">
                                <span style="color:#000000"> ${StoreDigital.invoice_id.slice(0, -7) + "***"}</span>
                            </a>
                        </span>
                    </small>
                </h4>

                <div id="content${StoreDigital.invoice_id}">
                    <h4 class="desc-heading_foot">
                        <a style="color:#282a42;" target="_blank" href="${props.app}" title="${StoreDigital.product.name_provider}">
                            ${StoreDigital.product.game} - ${StoreDigital.product.denom}
                        </a>
                    </h4>
                    <progress class="mt-3" id="progress${StoreDigital.invoice_id}" value="0" max="100" style="width: 100%;"></progress>
                </div>
            </div>`;

        document.getElementById("StoreContainer").appendChild(StoreDiv);

        contentElement = document.querySelector(`#content${StoreDigital.invoice_id}`);
        progressBar = contentElement.querySelector(
            `#progress${StoreDigital.invoice_id}`
        );
        widgetElement = document.getElementById(StoreDigital.invoice_id);

        let closeBtn = widgetElement.querySelector(".tsp-has-close-button");
        closeBtn.addEventListener("click", hideWidget);

        function hideWidget() {
            if (widgetElement) {
                widgetElement.classList.remove("fade-in");
                widgetElement.classList.add("fade-out");
                setTimeout(function () {
                    widgetElement.remove();
                    currentIndex++;
                    if (currentIndex >= StoreDigitals.length) {
                        currentIndex = 0;
                    }
                    showCurrentContent();
                }, 500);
            }
        }

        function runProgressBar(duration) {
            let startTime = new Date().getTime();
            let endTime = startTime + duration;

            function updateProgressBar() {
                let currentTime = new Date().getTime();
                let progress = Math.min((currentTime - startTime) / duration, 1);
                progressBar.value = progress * 100;
                if (currentTime < endTime) {
                    requestAnimationFrame(updateProgressBar);
                } else {
                    hideWidget();
                }
            }
            updateProgressBar();
        }

        runProgressBar(5000);
    }
    if (page.url == '/') {
        showCurrentContent();
    }
});


function timeAgo(dateString) {
    const now = new Date()
    const past = new Date(dateString)
    const diff = Math.floor((now - past) / 1000)

    if (diff < 60) return `${diff} seconds ago`
    if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`
    if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`
    if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`

    return past.toLocaleDateString("en-US", {
        day: "2-digit",
        month: "long",
        year: "numeric",
    })
}
</script>
<template>

    <Head :title="`Cari Data : ${keyword}`" />
    <button ref="offcanvasButton" class="btn btn-primary d-none" @click="toggleOffcanvas()" type="button"
        data-bs-toggle="offcanvas" data-bs-target="#offcanvas">
    </button>

    <div class="offcanvas offcanvas-start" style="z-index: 100 !important; visibility: visible;" data-bs-scroll="true"
        tabindex="-1" id="offcanvas" aria-labelledby="offcanvasLabel" aria-hidden="true">
        <div class="offcanvas-header">
            <div class="d-flex align-items-center gap-2">
                <span class="navigation">Navigation</span>
            </div>
            <label class="theme-switch">
                <input class="toggle-checkbox" id="checkbox" type="checkbox">
                <div class="switch-icon" @click="toggleTheme">
                    <i class="bi bi-brightness-high yellowprim"></i>
                </div>
            </label>
        </div>
        <div class="offcanvas-body d-flex flex-column justify-content-between">
            <div class="link-list">
                <Link :href="route('home.index')" class="nav-item-link"
                    :class="{ active: route().current('home.index') }">
                <div class="icon-link">
                    <i class="bi bi-house-door"></i>
                </div>
                <div class="title">Beranda</div>
                </Link>
                <Link :href="route('home.daftar-harga')" class="nav-item-link "
                    :class="{ active: route().current('home.daftar-harga') }">
                <div class="icon-link">
                    <i class="bi bi-tags"></i>
                </div>
                <div class="title">Daftar Harga</div>
                </Link>
                <Link :href="route('home.lacak-transaksi')" class="nav-item-link "
                    :class="{ active: route().current('home.lacak-transaksi') }">
                <div class="icon-link">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
                <div class="title">Lacak Transaksi</div>
                </Link>
                <a :href="route('home.kalkulator')" class="nav-item-link "
                    :class="{ active: route().current('home.kalkulator') }">
                    <div class="icon-link">
                        <i class="bi bi-calculator"></i>
                    </div>
                    <div class="title">Kalkulator</div>
                </a>
                <a :href="route('home.reset-member')" class="nav-item-link "
                    :class="{ active: route().current('home.reset-member') }">
                    <div class="icon-link">
                        <i class="bi bi-repeat"></i>
                    </div>
                    <div class="title">Reset License</div>
                </a>
                <a :href="route('home.feedback')" class="nav-item-link "
                    :class="{ active: route().current('home..feedback') }">
                    <div class="icon-link">
                        <i class="bi bi-star"></i>
                    </div>
                    <div class="title">Feedback</div>
                </a>
            </div>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container position-relative justify-content-md-center">
            <div class="d-flex align-items-center gap-2" id="navLogo">
                <img :src="`${app}/assets/images/logo/${website.logo}`"
                    @click="window.location.href = route('home.index')" class="logo " alt="">
                <Link :href="route('home.index')" class="logoName ">{{ website.name_store }}</Link>
            </div>
            <div class="collapse navbar-collapse order-2 " id="navbarNavDropdown">
                <div class="py-3  d-flex d-md-none " id="navSearch">
                    <div class="navSearch ">
                        <input type="text" id="searchInput" v-model="keyword" placeholder="Cari Game" />
                        <i class="bi bi-search"></i>
                    </div>
                </div>
            </div>
            <div class="navRight w-100 justify-content-end d-none d-md-flex">
                <div class="navSearch">
                    <input type="text" id="searchInput" v-model="keyword" placeholder="Cari Game" />
                    <i class="bi bi-search"></i>
                </div>

                <div class="containerMenu">

                    <div class="dropdown">
                        <button class="dropdownMenu shadow">
                            <i class="bi bi-grid-fill"></i>
                        </button>
                        <div class="dropdown-content">
                            <Link :href="route('home.index')">
                            <div class="containers">
                                <i class="bi bi-house-door"></i>
                                <div class="name">Beranda</div>
                            </div>
                            <i class="bi bi-arrow-right-short"></i>
                            </Link>
                            <Link :href="route('home.daftar-harga')">
                            <div class="containers">
                                <i class="bi bi-tags"></i>
                                <div class="name">Daftar Harga</div>
                            </div>
                            <i class="bi bi-arrow-right-short"></i>
                            </Link>
                            <Link :href="route('home.lacak-transaksi')">
                            <div class="containers">
                                <i class="bi bi-receipt-cutoff"></i>
                                <div class="name">Lacak Pesanan</div>
                            </div>
                            <i class="bi bi-arrow-right-short"></i>
                            </Link>
                            <Link :href="route('home.kalkulator')">
                            <div class="containers">
                                <i class="bi bi-calculator"></i>
                                <div class="name">Kalkulator</div>
                            </div>
                            <i class="bi bi-arrow-right-short"></i>
                            </Link>
                            <Link :href="route('home.reset-member')">
                            <div class="containers">
                                <i class="bi bi-repeat"></i>
                                <div class="name">Reset Member</div>
                            </div>
                            <i class="bi bi-arrow-right-short"></i>
                            </Link>
                            <Link :href="route('home.feedback')">
                            <div class="containers">
                                <i class="bi bi-star-fill"></i>
                                <div class="name">Feedback</div>
                            </div>
                            <i class="bi bi-arrow-right-short"></i>
                            </Link>
                        </div>
                    </div>
                </div>

                <label class="theme-switch shadow">
                    <input class="toggle-checkbox" id="checkbox" type="checkbox">
                    <div class="switch-icon" @click="toggleTheme">
                        <i class="bi bi-brightness-high yellowprim"></i>
                    </div>
                </label>

            </div>
            <button type="button" aria-label="Display the menu order-1" @click="toggleMenu"
                class="search d-md-none d-block" id="menuCheckbox">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </nav>
    <div class="container">
        <slot v-if="dataSearch.length === 0" />

        <div class="containerProduct" v-else>
            <div class="title-head mt-5">Pencarian <span class="txt-primary">"{{ keyword }}"</span></div>
            <div class="row g-2 g-lg-3">
                <div class="col-lg-2 col-md-3 col-4 mb-lg-3 mb-md-2 col-sm-4" v-for="data in dataSearch">
                    <div class="containers shadow">
                        <Link @click="removeData" :href="route('home.product', { slug: data.slug })">
                        <img :src="`${app}/assets/images/product/${data.thumbnail}`" class="product-img" alt="">
                        <div class="mask"></div>
                        <div class="desc">
                            <div class="game">{{ data.name }}</div>
                            <div class="vendor">{{ data.game.name }}</div>
                        </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
        <div class="h-40"></div>
    </div>
    <div class="containerTestimoni">
        <div class="container">
            <div class="row">
                <!-- New Footer -->
                <div class="col-lg-5">


                    <div class="trusted-text">{{ website.footer.section1.title }}</div>
                    <div class="desc-text">{{ website.footer.section1.text }} </div>
                    <div class="subdesc-text">{{ website.footer.section1.text2 }}</div>
                    <div class="containerUsers mb-lg-3 mb-md-2">
                        <div class="containers">
                            <div class="count">4+</div>
                            <div class="title">Pengguna</div>
                        </div>


                        <div class="containers">
                            <div class="count">9+</div>
                            <div class="title">Kategori</div>
                        </div>

                        <div class="containers">
                            <div class="count">18+
                            </div>
                            <div class="title">Produk</div>
                        </div>

                        <div class="containers">
                            <div class="count">
                                5.610+
                            </div>
                            <div class="title">Transaksi</div>
                        </div>
                    </div>

                    <Link :href="route('home.index')" class="btnYellowPrimary d-inline-flex px-3 mt-4">ORDER
                    SEKARANG<i class="bi bi-arrow-right-short ms-2 mt-1"></i></Link>
                </div>
                <div class="col-lg-7">
                    <div class="swiper swiperTesti">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide testimoni" v-for="f in swiperFeedback">
                                <div class="review-card">
                                    <div class="review-header-row">
                                        <div class="review-rating">
                                            <i class="fas" v-for="n in 5" :key="n"
                                                :class="n <= f.rating ? 'fa-star text-info' : 'fa-star text-secondary'">
                                            </i>
                                        </div>
                                        <div class="review-date">
                                            <span>{{ timeAgo(f.created_at) }}</span>
                                        </div>
                                    </div>
                                    <p class="review-content">{{ f.message }}</p>
                                    <div class="verified-badge">
                                        <div class="badge-text">
                                            <i class="fas fa-check-circle me-2"></i> Verified Purchase
                                        </div>
                                        <div class="badge-text text-info" style="cursor: pointer"
                                            v-if="auth.user?.role == 'admin'" @click="toggleReply(f)">
                                            {{ f.showReply ? "Close" : "Reply" }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide testimoni" v-for="f in swiperFeedback">
                                <div class="review-card">
                                    <div class="review-header-row">
                                        <div class="review-rating">
                                            <i class="fas" v-for="n in 5" :key="n"
                                                :class="n <= f.rating ? 'fa-star text-info' : 'fa-star text-secondary'">
                                            </i>
                                        </div>
                                        <div class="review-date">
                                            <span>{{ timeAgo(f.created_at) }}</span>
                                        </div>
                                    </div>
                                    <p class="review-content">{{ f.message }}</p>
                                    <div class="verified-badge">
                                        <div class="badge-text">
                                            <i class="fas fa-check-circle me-2"></i> Verified Purchase
                                        </div>
                                        <div class="badge-text text-info" style="cursor: pointer"
                                            v-if="auth.user?.role == 'admin'" @click="toggleReply(f)">
                                            {{ f.showReply ? "Close" : "Reply" }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide testimoni" v-for="f in swiperFeedback">
                                <div class="review-card">
                                    <div class="review-header-row">
                                        <div class="review-rating">
                                            <i class="fas" v-for="n in 5" :key="n"
                                                :class="n <= f.rating ? 'fa-star text-info' : 'fa-star text-secondary'">
                                            </i>
                                        </div>
                                        <div class="review-date">
                                            <span>{{ timeAgo(f.created_at) }}</span>
                                        </div>
                                    </div>
                                    <p class="review-content">{{ f.message }}</p>
                                    <div class="verified-badge">
                                        <div class="badge-text">
                                            <i class="fas fa-check-circle me-2"></i> Verified Purchase
                                        </div>
                                        <div class="badge-text text-info" style="cursor: pointer"
                                            v-if="auth.user?.role == 'admin'" @click="toggleReply(f)">
                                            {{ f.showReply ? "Close" : "Reply" }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
                    </div>
                </div>
                <!-- END TESTIMONI -->
            </div>
        </div>
    </div>
    <div class="containerNews" id="faqs">
        <div class="container">
            <div class="head mb-3">
                <div class="title1">{{ website.footer.section2.title }}</div>
                <div class="title2">{{ website.footer.section2.title2 }}</div>
                <div class="title3">{{ website.footer.section2.title3 }}</div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion mb-2" v-for="(faq, index) in website.footer.section2.accordion">
                        <div class="accordionHeadPay" style="background: var(--dark-grey);">
                            <div class="title">{{ faq.title }}</div>
                        </div>
                        <div class="accordionBodyPay shadow">
                            <div class="accordionContent">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="title3" :style="{ fontWeight: 500 }" v-html="faq.fill"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer">
        <div class="container">

            <div class="desc mx-auto" v-html="website.footer.section3.desc"></div>
            <div class="sosmed">
                <a v-for="c in website.footer.section3.contact" :href="c.url" target="_blank"
                    class="containers text-white">
                    <i :class="c.icon"></i>
                </a>
            </div>
            <img :src="website.footer.section3.image_cover" v-if="website.footer.section3.image_cover" alt=""
                width="700" height="475" style="width:100%;height:auto" class="object-cover object-bottom">
            <div class="bottomFoot">
                <div class="copyright">
                    <div class="text">
                        Copyright ©{{ new Date().getFullYear() }}
                        <Link :href="route('home.index')" class="text-white"> {{ website.name_store }}</Link> - All
                        Right Reserved
                    </div>
                </div>

                <div class="linkFoot">
                    <div class="containers">
                        <Link :href="route('home.index')">Home</Link>
                        <Link :href="route('home.lacak-transaksi')">Lacak Pesanan</Link>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div id="StoreContainer"></div>
    <a id="backToTopBtn" onclick="scrollToTop()"
        class="floating-btu d-flex justify-content-center gap-1 flex-column contact d-none" tabindex="0">
        <i class="bi bi-arrow-up"></i>
    </a>
</template>