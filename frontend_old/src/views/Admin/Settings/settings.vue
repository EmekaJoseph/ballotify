<template>
    <div class="row justify-content-center g-4">
        <orgSettings />
        <profileSettings />
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">Password:</div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-sm-12">
                            <label>Old Password:</label>
                            <input type="text" class="form-control w-100">
                        </div>

                        <div class="col-sm-12">
                            <label>New Password:</label>
                            <input type="text" class="form-control w-100">
                        </div>
                        <div class="col-sm-12">
                            <label>Repeat Password:</label>
                            <input type="text" class="form-control w-100">
                        </div>
                        <div>
                            <span class="float-end">
                                <button class="btn btn-secondary btn-sm" style="width: 100px">Change</button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">Other Users:</div>
                <div class="card-body">
                    <div class="">

                    </div>
                </div>
            </div>
        </div>


    </div>
</template>
<script setup>
import orgSettings from './orgSettingsComponent'
import profileSettings from './profileSettingsComponent'
</script>

