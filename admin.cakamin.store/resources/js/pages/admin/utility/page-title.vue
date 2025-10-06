<script setup>
const props = defineProps({
    title: String,
})
</script>
<template>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box d-md-flex justify-content-md-between align-items-center">
                <h4 class="page-title">{{ title }}</h4>
                <div class="">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Panel</a>
                        </li><!--end nav-item-->
                        <li class="breadcrumb-item active">{{ title }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</template>