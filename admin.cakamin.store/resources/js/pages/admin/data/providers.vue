<script setup>
import { Head } from '@inertiajs/vue3'
import utility from '../../admin/utility/page-title.vue'
import "datatables.net-bs5";
import "datatables.net-bs5/css/dataTables.bootstrap5.min.css"
import { ref, onMounted } from 'vue'
import axios from 'axios'
import $ from 'jquery'
import Swal from 'sweetalert2';

window.$ = $;
window.jQuery = $;

const props = defineProps({
    provider: Array
})
const modalForm = ref({
    id: null,
    name: '',
    api_key: '',
    type_api: 2,
    url: { register: '', game_id: '', paket: '', reset: '' },
    reset_license: 'enabled',
    status: 'active'
})
const providers = ref([...props.provider])
const isCreate = ref(true)
const bsModal = ref(false)
let table = null;
const showApi = ref(false)
onMounted(() => {

    table = $('#table-providers').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        dom: '<"row mb-3"<"col-sm-6"l><"col-sm-6"f>>t<"pagination-wrapper"p>',
        ajax: {
            url: route('ssr.table-provider'),
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
            { data: 'name' },
            { data: 'api_key' },
            { data: 'type_api' },
            { data: 'status' },
            { data: 'aksi', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [0, 1, 5], className: 'text-center' }
        ],
        escape: false,
    });
})

const openCreate = () => {
    isCreate.value = true
    modalForm.value = { id: null, name: '', api_key: '', type_api: 0, url: { register: '', game_id: '', paket: '', reset: '' }, reset_license: 'enabled', status: 'active' }
    bsModal.value = true;
}

window.openEdit = (id) => {
    isCreate.value = false
    modalForm.value = providers.value.find(p => p.id == id)
    bsModal.value = true;
}
const toggleApi = () => {
    showApi.value = !showApi.value
}


const submitProvider = async () => {
    try {
        const res = await axios.post(route('api.providers.store', { ...modalForm.value, create: isCreate.value }))
        if (res.data.status) {
            toastify["success"]?.("Gotcha!", res.data.message);
            if (table) table.ajax.reload(null, false)
            const newProvider = res.data.data
            if (isCreate.value == '0') {
                const idx = providers.value.findIndex(p => p.id === newProvider.id)
                if (idx !== -1) {
                    providers.value[idx] = newProvider
                }
            } else {
                providers.value.unshift(newProvider)
            }
        } else {
            toastify["error"]?.("Oops!", res.data.message);
        }
        bsModal.value = false
    } catch (err) {
        toastify["error"]?.("Oops!", err.message);
    }
    bsModal.value = false
}

window.removeProvider = (row) => {
    Swal.fire({
        title: 'Yakin ingin menghapus provider ini?',
        text: "Jika provider ini sudah digunakan, maka provider ini akan dibatalkan",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!',
    })
        .then(result => {
            if (result.isConfirmed) {
                axios.post(route('api.providers.store'), { create: 2, id: row }).then(res => {
                    if (res.data.status) {
                        toastify["success"]?.("Gotcha!", res.data.message);
                        if (table) table.ajax.reload(null, false)
                    } else {
                        toastify["error"]?.("Oops!", res.data.message);
                    }
                })
            }
        })
}
</script>

<template>

    <Head :title="'Providers'" />
    <utility :title="'Providers'"></utility>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="card-title">Daftar Providers</span>
            <button class="btn btn-primary" @click="openCreate">Tambah Provider</button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" id="table-providers">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px">ID</th>
                            <th>Nama</th>
                            <th>API Key</th>
                            <th style="width:120px">Tipe API</th>
                            <th style="width:120px">Status</th>
                            <th style="width:120px" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" :class="{ show: bsModal }" style="display: block;" v-if="bsModal"
        @click.self="bsModal = false" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form class="needs-validation" @submit.prevent="submitProvider">
                    <div class="modal-header">
                        <h6 class="modal-title">{{ isCreate ? 'Tambah' : 'Edit' }} Provider</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="name">Nama</label>
                            <input id="name" type="text" v-model="modalForm.name" class="form-control" required />
                            <div class="invalid-feedback">Nama wajib diisi.</div>
                        </div>
                        <div v-if="modalForm.type_api == 1">
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label" for="url_register">Endpoint Register</label>
                                    <input id="url_register" type="text" v-model="modalForm.url.register"
                                        class="form-control" />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="url_reset">Endpoint Reset Key</label>
                                    <input id="url_reset" type="text" v-model="modalForm.url.reset"
                                        class="form-control" />
                                </div>
                            </div>
                        </div>
                        <div v-else-if="modalForm.type_api == 2">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label" for="url_register">Endpoint Register</label>
                                    <input id="url_register" type="text" v-model="modalForm.url.register"
                                        class="form-control" />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="url_game_id">Endpoint Get Game ID</label>
                                    <input id="url_game_id" type="text" v-model="modalForm.url.game_id"
                                        class="form-control" />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="url_paket">Endpoint Get Paket ID By Game
                                        ID</label>
                                    <input id="url_paket" type="text" v-model="modalForm.url.paket"
                                        class="form-control" />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="url_reset">Endpoint Reset Key</label>
                                    <input id="url_reset" type="text" v-model="modalForm.url.reset"
                                        class="form-control" />
                                </div>
                            </div>

                        </div>
                        <div v-else-if="modalForm.type_api == 3">
                            <div class="col-md-12">
                                <label class="form-label" for="url_register">Endpoint Register</label>
                                <input id="url_register" type="text" v-model="modalForm.url.register"
                                    class="form-control" />
                            </div>
                        </div>
                        <div class="form-text mb-2 text-danger" v-if="modalForm.type_api != 0">
                            Lihat di dokumentasi API nya
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_key">API Key</label>
                            <div class="input-group">
                                <input id="api_key" :type="showApi ? 'text' : 'password'" v-model="modalForm.api_key"
                                    class="form-control" required />
                                <button type="button" class="btn btn-outline-secondary" @click="toggleApi"
                                    :title="showApi ? 'Hide' : 'Show'">
                                    <i :class="['fas', showApi ? 'fa-eye' : 'fa-eye-slash']"></i>
                                </button>
                            </div>
                            <div class="form-text">Klik ikon untuk menyalin API key.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="type_api">Tipe API</label>
                                <select id="type_api" v-model.number="modalForm.type_api" class="form-select" required>
                                    <option :value="0">0 ( Manual stocks )</option>
                                    <option :value="1">1 ( Created Akiracode )</option>
                                    <option :value="2">2 ( Create DFCode )</option>
                                    <option :value="3">3 ( API CYRAX / CUSTOM )</option>
                                </select>
                                <div class="invalid-feedback">Pilih tipe API.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="reset">Enable Reset?</label>
                                <select id="reset" v-model="modalForm.reset_license" class="form-select" required>
                                    <option value="enabled">Enabled</option>\r
                                    <option value="disabled">Disabled</option>\r
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="status">Status</label>
                                <select id="status" v-model="modalForm.status" class="form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <div class="invalid-feedback">Pilih status.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" @click="bsModal = false">Batal</button>
                        <button class="btn btn-primary" type="submit">{{ isCreate ? 'Tambah' : 'Simpan'
                        }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
