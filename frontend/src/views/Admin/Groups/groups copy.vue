<template>
    <div>
        <div class="card">
            <div class="card-header">Groups:<span class="badge rounded-pill bg-light text-dark">0</span>
                <span class="float-end">
                    <button class="customBtn btn-sm p-1" style="width:100px">add new</button>
                </span>
            </div>
            <div class="card-body p-lg-4">
                <div class="row g-2">
                    <div class="col-12 col-lg-5">
                        <div class="card">
                            <div class="row g-3 g-lg-4">
                                <div class=" col-6 col-lg-12">
                                    <span class="groupCard text-custom"><i class="bi bi-folder"></i> Group 1</span>
                                </div>
                                <div class=" col-6 col-lg-12">
                                    <span class="groupCard text-custom"><i class="bi bi-folder"></i> Group 2</span>
                                </div>
                                <div class="col-6 col-lg-12">
                                    <span class="groupCard text-custom"><i class="bi bi-folder"></i> Group 3</span>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-7">
                        <div class="card bg-light">
                            <div class="card-body">
                                list
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">

</script>

<style scoped>
.groupCard {
    padding: 10px;
    cursor: pointer;
    /* font-weight: bold; */
    font-size: 18px;

    /* border: 1px solid #eee; */
}

@media (max-width: 991px) {
    .groupCard {
        font-size: small;

        /* border: 1px solid #eee; */
    }
}
</style>