<template>
    <div>
        <div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasGeneral"
            aria-labelledby="offcanvasGeneralLabel">
            <ul>
                <li data-bs-dismiss="offcanvas">
                    <router-link to="/contact"> Reach Us</router-link>
                </li>
                <!-- <li data-bs-dismiss="offcanvas">
                    <router-link @click.prevent to=""> Help</router-link>
                </li> -->
                <li data-bs-dismiss="offcanvas">
                    <router-link to="/"> Home</router-link>
                </li>

            </ul>

        </div>
    </div>
</template>



<style scoped>
.offcanvas {
    height: 60px;
    display: flex;
    align-items: center;
}

.active {
    color: #00BD8C;
    font-weight: bold;
}

ul {
    list-style-type: none;
    margin: 0;
    padding: 0;
    overflow: hidden;
}

li {
    float: right;
}

li a {
    display: block;
    color: #2c3e50;
    text-align: center;
    padding: 16px;
    text-decoration: none;
}
</style>