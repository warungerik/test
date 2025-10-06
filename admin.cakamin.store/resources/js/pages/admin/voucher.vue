<script setup>
import { Head } from '@inertiajs/vue3';
import utility from '../admin/utility/page-title.vue'
import "datatables.net-bs5";
import "datatables.net-bs5/css/dataTables.bootstrap5.min.css"
import $ from "jquery";
import { onMounted, ref } from 'vue';
import { route } from 'ziggy-js';
import Swal from 'sweetalert2';

window.$ = $;
window.jQuery = $;

const props = defineProps({
    product: Array,
})
const form = ref({
    code: "",
    product_id: 0,
    type: "fixed",
    amount: "",
    maximal_fee: "",
    minimal_amount: "",
    limit: "",
    use_limit: 0,
    expired_at: "",
    status: "active",
});
const type_action = ref("")
const edit_id = ref("")
let table = null;

onMounted(() => {
    table = $('#table-voucher').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        dom: '<"row mb-3"<"col-sm-6"l><"col-sm-6"f>>t<"pagination-wrapper"p>',
        ajax: {
            url: route('ssr.table-voucher'),
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: function (d) {
                d.length = $('select[name="show_row"]').val() || d.length;
                d.start_date = $('input[name="start_date"]').val();
                d.end_date = $('input[name="end_date"]').val();
                d.filter_status = $('select[name="filter_status"]').val();
                d.sort_field = $('select[name="sort_field"]').val();
                d.sort_type = $('select[name="sort_type"]').val();
                d.search_field = $('select[name="search_field"]').val();
                d.search_value = $('input[name="search_value"]').val();
            }
        },
        language: {
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        columns: [
            { data: 'id' },
            { data: 'code' },
            { data: 'product' },
            { data: 'amount' },
            { data: 'info_limit' },
            { data: 'aksi', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [0, 1, 5], className: 'text-center' }
        ],
        escape: false,
    });
})
const tambahVoucher = () => {
    type_action.value = 'tambah';
    form.value = {
        code: "",
        product_id: 0,
        type: "fixed",
        amount: "",
        maximal_fee: "",
        minimal_amount: "",
        limit: "",
        use_limit: 0,
        expired_at: "",
        status: "active",
    };
}
window.editVoucher = async (id) => {
    try {
        const res = await axios.post(route('api.voucher.detail'), { id: id });
        if (res.data.status) {
            type_action.value = 'edit';
            form.value = res.data.data;
            edit_id.value = id;
        } else {
            toastify["error"]?.("Whoops!", res.data.message);
        }
    } catch (e) {
        toastify["error"]?.("Whoops!", "Terjadi kesalahan pada server");
    }
}

window.deleteVoucher = async (id) => {
    await Swal.fire({
        title: 'Yakin ingin menghapus voucher ini?',
        text: "Jika voucher ini sudah digunakan, maka voucher ini akan dibatalkan",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!',
    }).then(result => {
        if (result.isConfirmed) {
            axios.post(route('api.voucher.store'), { id: id, type_action: 'delete' }).then(res => {
                if (res.data.status) {
                    toastify["success"]?.("Gotcha!", res.data.message);
                    if (table) table.ajax.reload(null, false);
                } else {
                    toastify["error"]?.("Whoops!", res.data.message)
                }
            })
        }
    })
}
const submitVoucher = async () => {
    try {
        const res = await axios.post(route("api.voucher.store"), {
            ...form.value,
            type_action: type_action.value,
            edit_id: edit_id.value
        });


        if (res.data.status) {
            toastify["success"]?.("Gotcha!", res.data.message);
            if (table) table.ajax.reload(null, false);
            form.value = {
                code: "",
                product_id: 0,
                type: "fixed",
                amount: "",
                maximal_fee: "",
                minimal_amount: "",
                limit: "",
                use_limit: 0,
                expired_at: "",
                status: "active",
            };
        } else {
            toastify["error"]?.("Whoops!", res.data.message);
        }
    } catch (e) {
        toastify["error"]?.("Whoops!", "Terjadi kesalahan pada server");
    }
}

</script>
<style scope>
.pagination-wrapper {
    display: flex;
    justify-content: flex-end;
    padding: 0.75rem 1rem;
    margin-top: 0.5rem;
}

.pagination-wrapper .pagination {
    margin-bottom: 0;
}

.dt-length {
    padding: 10px 15px;
}

.dt-search {
    padding: 10px 15px;
}
</style>
<template>

    <Head :title="'Kelola Voucher'" />
    <utility :title="'Voucher'"></utility>
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <div class="card-title">Kelola Voucher</div>
                </div>
                <div class="col-auto">
                    <button class="btn bg-primary text-white" @click="tambahVoucher" data-bs-toggle="modal"
                        data-bs-target="#modal"><i class="fas fa-plus me-1"></i> Tambah Voucher</button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table" id="table-voucher">
                    <thead class="table-light">
                        <tr class=" text-uppercase">
                            <th class="border-top-0">ID</th>
                            <th class="border-top-0">Code</th>
                            <th class="border-top-0">Provider</th>
                            <th class="border-top-0">Amount</th>
                            <th class="border-top-0">L <i class="iconoir-warning-hexagon text-warning align-middle"
                                    data-bs-toggle="tooltip" data-bs-placement="top" :style="{ cursor: 'pointer' }"
                                    title="Limit"></i> / U <i class="iconoir-warning-hexagon text-warning align-middle"
                                    data-bs-toggle="tooltip" data-bs-placement="top" :style="{ cursor: 'pointer' }"
                                    title="USE LIMIT"></i></th>
                            <th class="border-top-0">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal" tabindex="-1" aria-labelledby="data" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="data"
                        v-html="type_action == 'tambah' ? 'Tambah Voucher' : 'Edit Voucher'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="submitVoucher">
                        <div class="mb-3">
                            <label class="form-label">Code</label>
                            <input v-model="form.code" type="text" class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <select v-model="form.product_id" class="form-select">
                                <option value="0">-- All Products --</option>
                                <option v-for="p in product" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select v-model="form.type" class="form-select">
                                <option value="fixed">Fixed</option>
                                <option value="percent">Percent</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount</label>
                                <input v-model="form.amount" type="number" step="0.01" class="form-control" />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Maximal Fee</label>
                                <input v-model="form.maximal_fee" type="number" class="form-control" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Minimal Amount</label>
                                <input v-model="form.minimal_amount" type="number" class="form-control" />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Limit</label>
                                <input v-model="form.limit" type="number" class="form-control" />
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Use Limit</label>
                            <input v-model="form.use_limit" type="number" class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Expired At</label>
                            <input v-model="form.expired_at" type="datetime-local" class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select v-model="form.status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" data-bs-dismiss="modal">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>