import { defineStore } from "pinia";

export const useColorStore = defineStore({
    id: "colorStore",
    state: () => ({
        cc1: '#00BD8C',
        cc2: '#BD2c00',
        ccThk: '#007052',
        ccBg: '#00bd8b11',
        ccBtnH: '#05d8a0'

    })
});
