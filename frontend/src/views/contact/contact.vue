<template>
    <div class="generalBody page-not-home">
        <HeaderComponent :scrolled="y" />
        <!-- ======= Contact Section ======= -->
        <section id="contact" class="contact">
            <div class="container">

                <div class="section-header">
                    <h2>Let Us Hear From You</h2>
                    <p>Ea vitae aspernatur deserunt voluptatem impedit deserunt magnam occaecati dssumenda quas ut ad
                        dolores
                        adipisci aliquam.</p>
                </div>

            </div>

            <!-- <div class="map">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d12097.433213460943!2d-74.0062269!3d40.7101282!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0xb89d1fe6bc499443!2sDowntown+Conference+Center!5e0!3m2!1smk!2sbg!4v1539943755621"
                    frameborder="0" allowfullscreen></iframe>
            </div> -->

            <div class="container">

                <div class="row gy-5 gx-lg-5">

                    <div class="col-lg-4">

                        <div class="info">
                            <h3>Get in touch</h3>
                            <p>Et id eius voluptates atque nihil voluptatem enim in tempore minima sit ad mollitia
                                commodi minus.</p>

                            <div class="info-item d-flex">
                                <i class="bi bi-geo-alt flex-shrink-0"></i>
                                <div>
                                    <h4>Location:</h4>
                                    <p>A108 Adam Street, New York, NY 535022</p>
                                </div>
                            </div><!-- End Info Item -->

                            <div class="info-item d-flex">
                                <i class="bi bi-envelope flex-shrink-0"></i>
                                <div>
                                    <h4>Email:</h4>
                                    <p>info@example.com</p>
                                </div>
                            </div><!-- End Info Item -->

                            <div class="info-item d-flex">
                                <i class="bi bi-phone flex-shrink-0"></i>
                                <div>
                                    <h4>Call:</h4>
                                    <p>+1 5589 55488 55</p>
                                </div>
                            </div><!-- End Info Item -->

                        </div>

                    </div>

                    <div class="col-lg-8">
                        <form action="forms/contact.php" method="post" role="form" class="php-email-form">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <input type="text" name="name" class="form-control" id="name"
                                        placeholder="Your Name" required>
                                </div>
                                <div class="col-md-6 form-group mt-3 mt-md-0">
                                    <input type="email" class="form-control" name="email" id="email"
                                        placeholder="Your Email" required>
                                </div>
                            </div>
                            <div class="form-group mt-3">
                                <input type="text" class="form-control" name="subject" id="subject"
                                    placeholder="Subject" required>
                            </div>
                            <div class="form-group mt-3">
                                <textarea class="form-control" name="message" placeholder="Message" required></textarea>
                            </div>
                            <div class="my-3">
                                <div class="loading">Loading</div>
                                <div class="error-message"></div>
                                <div class="sent-message">Your message has been sent. Thank you!</div>
                            </div>
                            <div class="text-center"><button type="submit">Send Message</button></div>
                        </form>
                    </div><!-- End Contact Form -->

                </div>

            </div>
        </section><!-- End Contact Section -->
        <FooterComponent />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useWindowScroll } from '@vueuse/core'
const { x, y } = useWindowScroll()
onMounted(() => {
    window.scrollTo(0, 0);
})
</script>

<style scoped>
.page-not-home {
    margin-top: 50px;
}


section {
    padding: 60px 0;
    overflow: hidden;
}

.section-header {
    text-align: center;
    padding-bottom: 40px;
}

.section-header h2 {
    font-size: 48px;
    font-weight: 300;
    margin-bottom: 20px;
    color: var(--bs-secondary);
}

.section-header p {
    margin: 0 auto;
    color: var(--bs-secondary-light);
}

@media (min-width: 1280px) {
    .section-header p {
        max-width: 80%;
    }
}

@media (max-width: 991px) {
    .section-header h2 {
        font-size: 30px;

    }
}

.contact .map {
    margin-bottom: 40px;
}

.contact .map iframe {
    border: 0;
    width: 100%;
    height: 400px;
}

.contact .info {
    padding: 40px;
    box-shadow: 0px 2px 15px rgba(var(--bs-black-rgb), 0.1);
    overflow: hidden;
}

.contact .info h3 {
    font-weight: 600;
    font-size: 24px;
}

.contact .info p {
    color: var(--bs-secondary-light);
    margin-bottom: 30px;
    font-size: 15px;
}

.contact .info-item+.info-item {
    padding-top: 20px;
    margin-top: 20px;
    border-top: 1px solid rgba(var(--bs-secondary-rgb), 0.15);
}

.contact .info-item i {
    font-size: 24px;
    color: var(--color-primary);
    transition: all 0.3s ease-in-out;
    margin-right: 20px;
}

.contact .info-item h4 {
    padding: 0;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--bs-secondary);
}

.contact .info-item p {
    padding: 0;
    margin-bottom: 0;
    font-size: 14px;
    color: var(--bs-secondary-light);
}

.contact .php-email-form {
    width: 100%;
    background: var(--bs-white);
}

.contact .php-email-form .form-group {
    padding-bottom: 8px;
}

.contact .php-email-form .error-message {
    display: none;
    color: var(--bs-white);
    background: var(--bs-red);
    text-align: left;
    padding: 15px;
    font-weight: 600;
}

.contact .php-email-form .error-message br+br {
    margin-top: 25px;
}

.contact .php-email-form .sent-message {
    display: none;
    color: var(--bs-white);
    background: var(--bs-green);
    text-align: center;
    padding: 15px;
    font-weight: 600;
}

.contact .php-email-form .loading {
    display: none;
    background: var(--bs-white);
    text-align: center;
    padding: 15px;
}

.contact .php-email-form .loading:before {
    content: "";
    display: inline-block;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    margin: 0 10px -6px 0;
    border: 3px solid var(--bs-green);
    border-top-color: var(--bs-white);
    -webkit-animation: animate-loading 1s linear infinite;
    animation: animate-loading 1s linear infinite;
}

.contact .php-email-form input[type=text],
.contact .php-email-form input[type=email],
.contact .php-email-form textarea {
    border-radius: 0px;
    box-shadow: none;
    font-size: 14px;
}

.contact .php-email-form input[type=text]:focus,
.contact .php-email-form input[type=email]:focus,
.contact .php-email-form textarea:focus {
    border-color: none;
}

.contact .php-email-form input[type=text],
.contact .php-email-form input[type=email] {
    height: 48px;
    padding: 10px 15px;
}

.contact .php-email-form textarea {
    padding: 10px 12px;
    height: 290px;
}

.contact .php-email-form button[type=submit] {
    background: #00BD8C;
    border: 0;
    padding: 13px 50px;
    color: var(--bs-white);
    transition: 0.4s;
    border-radius: 0;
}

.contact .php-email-form button[type=submit]:hover {
    background: rgb(5, 216, 160);
}

@-webkit-keyframes animate-loading {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

@keyframes animate-loading {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>