<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref, toDisplayString } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps({
    app: String
})
const type = ref("")

const totalMatch = ref(0)
const totalWinrate = ref(0)
const targetWinrate = ref(0)
const kalkulasiWinrate = ref("")

const pointMagicWheel = ref(0)
const hasilDiamondMwheel = ref("")

const pointBintang = ref(0)
const hasilZodiac = ref("")

function rumus(TotalMatch, TotalWr, MauWr) {
    let tWin = TotalMatch * (TotalWr / 100);
    let tLose = TotalMatch - tWin;
    let sisaWr = 100 - MauWr;
    let wrResult = 100 / sisaWr;
    let seratusPersen = tLose * wrResult;
    let final = seratusPersen - TotalMatch;
    return Math.round(final);
}

const hitungwinrate = async () => {
    try {
        if (targetWinrate.value == "100") {
            toastify['error']?.('Whoops!', 'Kamu tidak dapat mencapai 100% win rate!')
            kalkulasiWinrate.value = `Jangan Ngaco ya! wkwk`;
            return;
        }
        const result = rumus(totalMatch.value, totalWinrate.value, targetWinrate.value)

        kalkulasiWinrate.value = `Kamu memerlukan sekitar <b>${result}</b> win tanpa lose untuk mendapatkan win rate <b>${targetWinrate.value}%</b>`
        toastify["success"]?.("Gotcha!", "Berhasil menghitung winrate") // 🔥 pakai success, bukan error
    } catch (error) {
        toastify["error"]?.("Whoops!", "Terjadi kesalahan! silahkan ulangi beberapa saat lagi.")
    }
}
const hitungmwheel = async () => {
    let points = parseInt(pointMagicWheel.value);
    let requiredDiamonds;
    if (points < 196) {
        let remainingPoints = 200 - points;
        let spinsNeeded = Math.ceil(remainingPoints / 5);
        requiredDiamonds = spinsNeeded * 270;
        toastify["success"]?.("Gotcha!", "Berhasil menghitung jumlah Diamond.");
    } else {
        if (points > 199) {
            toastify["error"]?.("Whoops!", "Point tidak boleh melebihi 199.");
        }
        let remainingPoints = 200 - points;
        requiredDiamonds = remainingPoints * 60;
    }
    hasilDiamondMwheel.value = requiredDiamonds
}

const hitungZodiac = async () => {
    let points = parseInt(pointBintang.value);
    let requiredDiamonds;
    if (points < 90) {
        requiredDiamonds = Math.ceil((2000 - points * 20) * 850 / 1000);
        toastify["success"]?.("Gotcha!", "Berhasil menghitung jumlah Diamond.");
    } else {
        if (points > 99) {
            toastify["error"]?.("Whoops!", "Point Zodiac tidak boleh melebihi 99.");
        }
        requiredDiamonds = Math.ceil(2000 - points * 20);
    }
    hasilZodiac.value = requiredDiamonds;
}

</script>
<template>

    <Head
        :title="`Kalkulator ${type == 'winrate' ? 'winrate' : (type == 'mwheel' ? 'Magic Wheel' : (type == '' ? null : 'Zodiac'))}`" />
    <div :style="{ minHeight: '100vh', display: 'flex', justifyContent: 'center', alignItems: 'center' }">
        <div class="container">
            <Link class="btn-back inline-flex items-center justify-center" :href="route('home.index')"
                style="outline: none;">
            <i class="bi bi-x"></i>
            </Link>
            <div class="row justify-center">
                <div class="col-lg-4 col-md-6 col-12" v-if="type == ''">
                    <div class="login-card">
                        <span class="head">Kalkulator</span>
                        <button type="button" @click="type = 'winrate'" class="btnYellowPrimary md w-100">Win
                            Rate</button>
                        <button type="button" @click="type = 'mwheel'" class="btnYellowPrimary md w-100">Magic
                            Wheel</button>
                        <button type="button" @click="type = 'zodiac'" class="btnYellowPrimary md w-100">Zodiac</button>
                    </div>
                </div>
                <div class="col-md-10" v-else-if="type == 'winrate'">
                    <div class="title-head text-left mb-3 mt-3">Hitung Win Rate</div>
                    <div class="cards shadow mb-4">
                        <div class="nowrap-left floating-label-content">
                            <form @submit.prevent="hitungwinrate">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="text-white form-label">Total Match</label>
                                        <input type="number" class="form-control games-input floating-input"
                                            id="TotalMatch" v-model="totalMatch" placeholder="Masukkan Total Match">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-white form-label">Total Win Rate</label>
                                        <input type="number" v-model="totalWinrate"
                                            class="form-control games-input floating-input" id="TotalWr"
                                            placeholder="Total Win Rate Kamu (%)">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="text-white form-label">Target Win Rate</label>
                                        <input type="number" v-model="targetWinrate"
                                            class="form-control games-input floating-input" id="MauWr"
                                            placeholder="Total Target Win Rate (%)">
                                        <button id="hasil" type="submit"
                                            class="btnYellowPrimary mt-3 w-100">Hitung</button>
                                    </div>
                                    <label class="text-white form-label" v-html="kalkulasiWinrate"></label>
                                    <a href="javascript:void(0)" @click="type = ''">Kembali </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-10" v-else-if="type == 'mwheel'">
                    <div class="title-head text-left mb-3 mt-3">Hitung Magic Wheel</div>
                    <div class="cards shadow mb-4">
                        <div class="nowrap-left floating-label-content">
                            <form @submit.prevent="hitungmwheel">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label text-white">Point :</label>
                                        <input type="number" class="form-control games-input floating-input" id="point"
                                            min="0" placeholder="Point Magic Wheel" v-model="pointMagicWheel"
                                            data-gtm-form-interact-field-id="0">
                                        <button type="submit" class="btnYellowPrimary mt-3 w-100">Hitung</button>
                                    </div>
                                    <span class="text-white">Jumlah diamond yang dibutuhkan: <span id="jumlah">{{
                                        hasilDiamondMwheel }}</span></span>
                                    <a href="javascript:void(0)" @click="type = ''">Kembali </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-10" v-else>
                    <div class="title-head text-left mb-3 mt-3">Hitung Zodiac</div>
                    <div class="cards shadow mb-4">
                        <div class="nowrap-left floating-label-content">
                            <div class="row">
                                <form @submit.prevent="hitungZodiac">
                                    <div class="col-md-12 mb-3">
                                        <label class="text-white form-label">Point Bintang Kamu</label>
                                        <input type="number" class="form-control games-input floating-input" id="point"
                                            min="0" placeholder="Point Bintang kamu" v-model="pointBintang" />
                                        <span class="text-white">Jumlah diamonds yang dibutuhkan :
                                            <span>{{ hasilZodiac }}</span></span>
                                        <button type="submit" class="btnYellowPrimary mt-3 w-100">Hitung</button>
                                        <a href="javascript:void(0)" @click="type = ''">Kembali </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>