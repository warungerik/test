<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    game: Array,
    denom: Array,
    product: Array
})
const loop = 0
const kategori = ref("")
const brand = ref("")

const filteredProduct = ref([])
const filteredDenom = ref([])

watch(kategori, (val) => {
    if (!val) {
        filteredProduct.value = props.product
    } else {
        filteredProduct.value = props.product.filter(p => p.game_id == val)
    }
})
watch(brand, (val) => {
    if (!val) {
        filteredDenom.value = props.denom
    } else {
        filteredDenom.value = props.denom.filter(p => p.product_id == val)
    }
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

    <Head :title="`Daftar Harga`" />
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="title-head text-left mb-3 mt-3">
                Daftar Harga
            </div>
            <div class="cards shadow mb-2">
                <div class="nowrap-left floating-label-content mb-2">
                    <select class="form-select form-control floating-select" id="kategori" v-model="kategori">
                        <option selected value="">—Kategori Game—</option>
                        <option v-for="g in game" :value="g.id" v-html="g.name"></option>
                    </select>
                </div>

                <div class="nowrap-left floating-label-content mb-2">
                    <select class="form-select form-control floating-select" id="brand" name="brand" v-model="brand">
                        <option value="">—Game VIP—</option>
                        <option v-for="b in filteredProduct" :value="b.id" v-html="b.name"></option>
                    </select>
                </div>

                <div class="mt-3 cards-title">
                    <hr>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered cards-title mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" rowspan="2" style="vertical-align:middle">#</th>
                                <th class="text-center" colspan="2" style="vertical-align:middle">INFORMATION
                                </th>
                                <th class="text-center" rowspan="2" style="vertical-align:middle">Status</th>
                            </tr>
                            <tr>
                                <th class="text-center">Durasi</th>
                                <th class="text-center">Harga</th>
                            </tr>
                        </thead>
                        <tbody id="pricelist">
                            <tr v-if="filteredDenom.length === 0 && kategori == ''">
                                <td colspan="5" class="text-center">Silakan Pilih Kategori.</td>
                            </tr>
                            <tr v-else-if="filteredDenom.length > 0 && kategori !== ''"
                                v-for="(filter, index) in filteredDenom" :key="filter.id">
                                <td class="text-center">{{ index + 1 }}.</td>
                                <td class="text-center">
                                    {{ filter.name }}
                                </td>
                                <td class="text-center">
                                    Rp {{ number_format(filter.price, 0, ',', '.') }}
                                </td>
                                <td class="text-center"
                                    :class="filter.status == 'active' ? 'text-success' : 'text-danger'">
                                    <i class="bi"
                                        :class="filter.status == 'active' ? 'bi-check-circle' : 'bi-x-circle'"></i>
                                </td>
                            </tr>

                            <tr v-else>
                                <td colspan="5" class="text-center">Data tidak ditemukan</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                </div>
            </div>
        </div>
    </div>
    <div class="h-40"></div>
    <div class="h-40"></div>
    <div class="h-40"></div>
</template>