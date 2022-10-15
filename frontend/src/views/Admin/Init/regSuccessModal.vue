<template>
    <div>
        <div class="modal fade show" data-backdrop="static" data-keyboard="false" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0">
                    <div class="modal-body">
                        <div class="check">
                            <i class="bi bi-check-circle text-success"></i>
                        </div>
                        <div class="info">
                            Registration successful!
                        </div>
                        <div class="l-btn">
                            <button @click="emit('clicked')" class="btn btn-success">Continue to Login</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
const emit = defineEmits(["clicked"]);
</script>

<style scoped>
.modal {
    display: block;
    background-color: #11111144;
}

.check {
    display: flex;
    justify-content: center;
    font-size: 100px;
    margin-block: 20px;
}

.info {
    display: flex;
    justify-content: center;
    font-size: 20px;
    margin-block: 20px;
}

.l-btn {
    display: flex;
    justify-content: center;
    margin-block: 20px;
}

.btn {
    width: 200px;
}
</style>