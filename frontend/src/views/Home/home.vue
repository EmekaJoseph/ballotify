<template>
    <div class="generalBody">
        <HeaderComponent :scrolled="y" />

        <section id="hero-animated" class="hero-animated d-flex align-items-center">
            <div class="container d-flex flex-column justify-content-center align-items-center text-center position-relative"
                data-aos="zoom-out">
                <img src="@/assets/images/hero-img2.svg" class="img-fluid animated">
                <h2 class="fw-bold">Welcome to <span style="color: #BD2c00">Ballotify</span></h2>
                <p>
                    <VueWriter :array="textArr" :typeSpeed="70" />
                </p>

                <div class="d-flex">
                    <button class="btn btn-primary btn-get-started">New Organisation</button>
                    <button class="btn btn-primary btn-get-started">Voter Account</button>
                </div>
            </div>
        </section>
        <main class="container">
            <section id="featured-services" class="featured-services">
                <div class="container">

                    <div class="row gy-4">

                        <div class="col-xl-3 col-md-6 d-flex" data-aos="zoom-out">
                            <div class="service-item position-relative">
                                <div class="icon"><i class="bi bi-activity icon"></i></div>
                                <h4><a href="/" class="stretched-link">Lorem Ipsum</a></h4>
                                <p>Voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi</p>
                            </div>
                        </div><!-- End Service Item -->

                        <div class="col-xl-3 col-md-6 d-flex" data-aos="zoom-out" data-aos-delay="200">
                            <div class="service-item position-relative">
                                <div class="icon"><i class="bi bi-bounding-box-circles icon"></i></div>
                                <h4><a href="/" class="stretched-link">Sed ut perspici</a></h4>
                                <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore</p>
                            </div>
                        </div><!-- End Service Item -->

                        <div class="col-xl-3 col-md-6 d-flex" data-aos="zoom-out" data-aos-delay="400">
                            <div class="service-item position-relative">
                                <div class="icon"><i class="bi bi-calendar4-week icon"></i></div>
                                <h4><a href="/" class="stretched-link">Magni Dolores</a></h4>
                                <p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia</p>
                            </div>
                        </div><!-- End Service Item -->

                        <div class="col-xl-3 col-md-6 d-flex" data-aos="zoom-out" data-aos-delay="600">
                            <div class="service-item position-relative">
                                <div class="icon"><i class="bi bi-broadcast icon"></i></div>
                                <h4><a href="/" class="stretched-link">Nemo Enim</a></h4>
                                <p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis</p>
                            </div>
                        </div><!-- End Service Item -->

                    </div>

                </div>
            </section><!-- End Featured Services Section -->
        </main>
        <FooterComponent />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useWindowScroll } from '@vueuse/core'
const { x, y } = useWindowScroll()
const textArr = ref<string[]>(['This App is still in Dev mode', 'With this App, You can create voting event', 'You can manage users also in a organisation'])
onMounted(() => {
    window.scrollTo(0, 0);
})

// const scrollPosition = ref<any>(null)
// function updateScrollPosition() {
//     scrollPosition.value = window.scrollY
// }
// onMounted(() => {
//     window.addEventListener('scroll', updateScrollPosition);
// })
</script>
<style scoped>
.hero-animated {
    width: 100%;
    min-height: 50vh;
    background: url("@/assets/images/hero-bg.png") center center;
    background-size: cover;
    position: relative;
    padding: 120px 0 60px;
}

.hero-animated h2 {
    margin: 0 0 10px 0;
    font-size: 48px;
    font-weight: 300;
    color: #485664;
}

.hero-animated h2 span {
    color: #00BD8C;
}

.hero-animated p {
    color: rgba(72, 86, 100, 0.8);
    margin: 0 0 30px 0;
    font-size: 20px;
    font-weight: 400;
}

.hero-animated .animated {
    margin-bottom: 60px;
    animation: up-down 2s ease-in-out infinite alternate-reverse both;
}

@media (min-width: 992px) {
    .hero-animated .animated {
        max-width: 45%;
    }
}

@media (max-width: 991px) {
    .hero-animated .animated {
        max-width: 60%;
    }
}

@media (max-width: 575px) {
    .hero-animated .animated {
        max-width: 80%;
    }
}

.hero-animated .btn-get-started {
    font-size: 16px;
    font-weight: 400;
    border-radius: 4px;
    transition: 0.5s;
    background: #00BD8C;
    width: 200px;
    border: none;
    margin-inline: 6px;
    color: #fff;
}

.hero-animated .btn-get-started:hover {
    background: #067c5d;
}



@media (max-width: 640px) {
    .hero-animated h2 {
        font-size: 32px;
    }

    .hero-animated p {
        font-size: 18px;
        margin-bottom: 30px;
    }

    .hero-animated .btn-get-started,
    .hero-animated .btn-watch-video {
        font-size: 14px;
    }

    .hero-animated .btn-get-started {
        width: 150px;
    }
}

@-webkit-keyframes up-down {
    0% {
        transform: translateY(10px);
    }

    100% {
        transform: translateY(-10px);
    }
}

@keyframes up-down {
    0% {
        transform: translateY(10px);
    }

    100% {
        transform: translateY(-10px);
    }
}


/*--------------------------------------------------------------
# Featured Services Section
--------------------------------------------------------------*/
.featured-services .service-item {
    padding: 30px;
    transition: all ease-in-out 0.4s;
    background: #fff;
    height: 100%;
}

.featured-services .service-item .icon {
    margin-bottom: 10px;
}

.featured-services .service-item .icon i {
    color: #00BD8C;
    font-size: 36px;
    transition: 0.3s;
}

.featured-services .service-item h4 {
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 24px;
}

.featured-services .service-item h4 a {
    color: #485664;
    transition: ease-in-out 0.3s;
}

.featured-services .service-item p {
    line-height: 24px;
    font-size: 14px;
    margin-bottom: 0;
}

.featured-services .service-item:hover {
    transform: translateY(-10px);
    box-shadow: 0px 0 60px 0 rgba(72, 86, 100, 0.1);
}

.featured-services .service-item:hover h4 a {
    color: #00BD8C;
}
</style>
