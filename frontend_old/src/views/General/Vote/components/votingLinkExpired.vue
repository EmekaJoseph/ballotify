<template>
    <div>
        <section class="menu-card">
            <div class="container">
                <div class="row justify-content-center gy-4">
                    <div class="col-md-6" data-aos="zoom-out" data-aos-delay="200">
                        <div class="card-item position-relative">
                            <div class="icon"><i class="bi bi-exclamation icon text-muted"></i></div>
                            <span class="hh text-muted">Expired Link</span>
                            <p class="text-center">This page link is broken, Please LOGIN with a code to access VOTING
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>

</script>

<style scoped>
.menu-card .card-item {
    transition: all ease-in-out 0.4s;
    background: #fff;
    height: 100%;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    padding: 50px;
}



.menu-card .card-item .icon {
    margin-bottom: 10px;
    display: flex;
    justify-content: center;
}

.menu-card .card-item .icon i {
    color: v-bind(cc1);
    font-size: 4rem;
    transition: 0.3s;
}

.menu-card .card-item .hh {
    font-weight: 600;
    /* margin-bottom: 15px; */
    font-size: 24px;
    display: flex;
    justify-content: center;
    color: v-bind(cc1);
}

.menu-card .card-item p {
    /* line-height: 24px; */
    font-size: 12px;
    margin-bottom: 0;

}


@media (max-width: 991px) {

    .menu-card .card-item {
        padding: 50px 30px;
        margin-inline: 20px;
        border: 1px solid v-bind(cc1);
    }

}
</style>